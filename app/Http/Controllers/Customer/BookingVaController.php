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
 * Ganti bank Virtual Account untuk booking yang masih menunggu pembayaran.
 *
 * POST /katalog/booking/status/{code}/va   (name: booking.va)
 *
 * Aturan: satu order_id Midtrans = satu bank. Jadi ganti bank = payment baru
 * (order_id baru, format TDK...-N lewat PaymentService), lalu tagihan lama dibatalkan.
 * Nomor VA selalu berasal dari respons Midtrans, tidak pernah dibuat di sini.
 */
class BookingVaController extends Controller
{
    /** Harus sama dengan $vaBanks di status.blade.php plus qris */
    public const BANKS = ['bca', 'bni', 'bri', 'permata', 'cimb', 'qris'];

    public function __construct(private readonly RentalAccessGuard $accessGuard) {}

    public function switch(
        Request $request,
        string $code,
        PaymentService $paymentService,
        MidtransService $midtrans,
    ): JsonResponse {
        $data = $request->validate(['bank' => ['required', Rule::in(self::BANKS)]]);
        $bank = $data['bank'];

        $rental = Rental::query()->where('booking_code', $code)->firstOrFail();
        $this->accessGuard->authorize($request, $rental);

        try {
            [$payment, $old, $details] = DB::transaction(function () use ($rental, $bank, $paymentService, $midtrans) {
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

                abort_if($old === null, 404, 'Tagihan aktif tidak ditemukan.');

                if ($bank === 'qris') {
                    $existing = $old->qris_details;
                    if ($existing && ! empty($existing['qr_code_url'])) {
                        return [$old, null, $existing];
                    }
                } else {
                    // Bank yang sama dengan nomor yang sudah ada: kembalikan saja, tanpa charge baru
                    $existing = $old->va_details;
                    if ($existing && ! empty($existing['va_number']) && strtolower($existing['bank'] ?? '') === $bank) {
                        return [$old, null, $existing];
                    }
                }

                // order_id baru yang pasti unik
                $attempt = $this->nextAttempt($old->order_id);
                while (Payment::query()->where('order_id', $paymentService->generateOrderId(
                    $locked->booking_code, $old->payment_type, $attempt
                ))->exists()) {
                    $attempt++;
                }

                $channel = $bank === 'qris' ? 'qris' : $bank.'_va';
                $new = $paymentService->createOnlinePayment($locked, $old->payment_type, $attempt, $channel);

                if ($bank === 'qris') {
                    $result = $midtrans->chargeQris($new);
                    if (empty($result['qr_code_url']) && empty($result['qr_string'])) {
                        throw new \RuntimeException('Midtrans tidak mengembalikan data QRIS.');
                    }
                } else {
                    $result = $midtrans->chargeBankTransfer($new, $bank);
                    if (empty($result['va_number'])) {
                        throw new \RuntimeException('Midtrans tidak mengembalikan nomor VA.');
                    }
                }

                return [$new, $old, $result];
            });
        } catch (HttpException $e) {
            throw $e; // abort() di atas: teruskan apa adanya (422/404)
        } catch (\Throwable $e) {
            Log::error('Gagal ganti metode/bank pembayaran: '.$e->getMessage(), ['code' => $code, 'bank' => $bank]);

            return response()->json(['message' => 'Gagal membuat tagihan di Midtrans. Coba lagi sebentar.'], 502);
        }

        // Tagihan lama dibatalkan SETELAH yang baru berhasil dibuat
        if ($old !== null) {
            $this->cancelOld($old, $paymentService);
        }

        if ($bank === 'qris') {
            return response()->json([
                'bank' => 'qris',
                'is_qris' => true,
                'qr_code_url' => $details['qr_code_url'] ?? null,
                'qr_string' => $details['qr_string'] ?? null,
                'order_id' => $payment->order_id,
            ]);
        }

        return response()->json([
            'bank' => strtolower($details['bank'] ?? $bank),
            'is_qris' => false,
            'va_number' => $details['va_number'],
            'biller_code' => $details['biller_code'] ?? null,
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

    private function nextAttempt(string $orderId): int
    {
        return preg_match('/-(\d+)$/', $orderId, $m) ? ((int) $m[1]) + 1 : 2;
    }
}
