<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\Booking\RentalAccessGuard;
use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Midtrans\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ganti metode pembayaran booking yang masih menunggu pembayaran.
 *
 * POST /katalog/booking/status/{code}/va   (name: booking.va)
 *
 * Satu order_id Midtrans hanya dapat memakai satu metode pembayaran.
 */
class BookingVaController extends Controller
{
    /** Harus sama dengan $vaBanks di status.blade.php */
    public const METHODS = ['bca', 'bni', 'bri', 'permata', 'cimb', 'qris'];

    public function __construct(private readonly RentalAccessGuard $accessGuard) {}

    public function switch(
        Request $request,
        string $code,
        PaymentService $paymentService,
        MidtransService $midtrans,
    ): JsonResponse {
        $data = $request->validate(['bank' => ['required', Rule::in(self::METHODS)]]);
        $bank = $data['bank'];
        $isQris = $bank === 'qris';

        $rental = Rental::query()->where('booking_code', $code)->firstOrFail();
        $this->accessGuard->authorize($request, $rental);

        try {
            [$payment, $old, $paymentInfo] = DB::transaction(function () use ($rental, $bank, $isQris, $paymentService, $midtrans) {
                // Kunci rental supaya dua klik cepat tidak membuat dua tagihan
                $locked = Rental::query()->whereKey($rental->id)->lockForUpdate()->firstOrFail();

                abort_unless(
                    $locked->status === 'pending' && ! ($locked->expires_at && $locked->expires_at->isPast()),
                    422,
                    'Booking ini sudah tidak bisa dibayar.'
                );

                $old = $locked->payments()
                    ->where('channel', 'midtrans')
                    ->whereIn('payment_type', ['full', 'dp'])
                    ->where('status', 'pending')
                    ->latest('id')
                    ->first();

                if ($old === null) {
                    abort_if(
                        $locked->payments()->exists(),
                        404,
                        'Tagihan aktif tidak ditemukan.',
                    );

                    $paymentType = (float) $locked->dp_amount > 0 ? 'dp' : 'full';
                    $attempt = 1;
                } else {
                    $paymentType = $old->payment_type;
                    $attempt = $paymentService->orderSequence($locked->booking_code, $old->order_id);
                }

                if ($old !== null && $isQris && $old->payment_channel === 'qris') {
                    $existing = $old->qris_details;
                    if ($existing) {
                        return [$old, null, $existing];
                    }

                    return [$old, null, $midtrans->chargeQris($old)];
                }

                if ($old !== null && ! $isQris && $old->payment_channel === $bank.'_va') {
                    $existing = $old->va_details;
                    if ($existing && ! empty($existing['va_number']) && strtolower($existing['bank'] ?? '') === $bank) {
                        return [$old, null, $existing];
                    }
                }

                // order_id baru yang pasti unik
                $method = $isQris ? 'qris' : $bank.'_va';
                while (Payment::query()->where('order_id', $paymentService->generateOrderId(
                    $locked->booking_code, $method, $attempt
                ))->exists()) {
                    $attempt++;
                }

                $new = $paymentService->createOnlinePayment(
                    $locked,
                    $paymentType,
                    $attempt,
                    $method
                );

                if ($isQris) {
                    $paymentInfo = $midtrans->chargeQris($new);
                    if (empty($paymentInfo['qr_code_url'])) {
                        throw new \RuntimeException('Midtrans tidak mengembalikan URL QRIS.');
                    }
                } else {
                    $paymentInfo = $midtrans->chargeBankTransfer($new, $bank);
                    if (empty($paymentInfo['va_number'])) {
                        throw new \RuntimeException('Midtrans tidak mengembalikan nomor VA.');
                    }
                }

                return [$new, $old, $paymentInfo];
            });
        } catch (HttpException $e) {
            throw $e; // abort() di atas: teruskan apa adanya (422/404)
        } catch (\Throwable $e) {
            Log::error('Gagal mengganti metode pembayaran: '.$e->getMessage(), ['code' => $code, 'bank' => $bank]);

            return response()->json(['message' => 'Gagal membuat tagihan di Midtrans. Coba lagi sebentar.'], 502);
        }

        // Tagihan lama dibatalkan SETELAH yang baru berhasil dibuat
        if ($old !== null) {
            $this->cancelOld($old, $paymentService);
        }

        if ($isQris) {
            return response()->json([
                'bank' => 'qris',
                'is_qris' => true,
                'qris_info' => $paymentInfo,
                'order_id' => $payment->order_id,
            ]);
        }

        return response()->json([
            'bank' => strtolower($paymentInfo['bank'] ?? $bank),
            'is_qris' => false,
            'va_number' => $paymentInfo['va_number'],
            'biller_code' => $paymentInfo['biller_code'] ?? null,
            'order_id' => $payment->order_id,
        ]);
    }

    /** Batalkan tagihan lama di Midtrans lalu tandai 'cancelled' di database. */
    private function cancelOld(Payment $old, PaymentService $paymentService): void
    {
        try {
            Transaction::cancel($old->order_id);
        } catch (\Throwable $e) {
            // 404 = tagihan lama belum pernah punya VA di Midtrans, aman ditandai batal.
            // Selain itu VA lama mungkin masih bisa dibayar: biarkan 'pending' supaya
            // kalau dibayar, webhook tetap bisa mencatatnya (status final tidak bisa dibuka lagi).
            if (! str_contains($e->getMessage(), '404')) {
                Log::warning('Gagal membatalkan tagihan VA lama di Midtrans.', [
                    'order_id' => $old->order_id,
                    'message' => $e->getMessage(),
                ]);

                return;
            }
        }

        $paymentService->advanceStatus($old->fresh(), 'cancelled');
    }
}
