<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use App\Services\RentalAccessGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Buat tagihan baru jika tagihan lama expired/failed.
 *
 * POST /payments/{order_id}/retry
 * Akses: pemilik rental (login) atau guest yang punya akses (signed URL / session).
 *
 * @see docs/contract.md §4.8
 */
class PaymentRetryController extends Controller
{
    private const RETRYABLE_STATUSES = ['expired', 'failed'];

    public function __construct(private readonly RentalAccessGuard $accessGuard) {}

    public function store(
        Request $request,
        string $orderId,
        PaymentService $paymentService,
        MidtransService $midtransService,
    ): JsonResponse {
        $oldPayment = Payment::query()
            ->with('rental')
            ->where('order_id', $orderId)
            ->firstOrFail();

        $rental = $oldPayment->rental;

        abort_if($rental === null, 404);

        $this->accessGuard->authorize($request, $rental);

        abort_unless(
            in_array($oldPayment->status, self::RETRYABLE_STATUSES, true),
            422,
            'Tagihan ini tidak memerlukan retry. Hanya tagihan yang expired atau failed yang bisa di-retry.',
        );

        abort_unless(
            $rental->status === 'pending',
            422,
            'Rental ini tidak lagi menunggu pembayaran. Retry tidak dapat dilakukan.',
        );

        $newPayment = $paymentService->createOnlinePayment(
            $rental,
            $oldPayment->payment_type,
            $this->nextAttemptNumber($oldPayment->order_id),
        );

        $snapToken = $midtransService->createTransaction($newPayment);

        return response()->json([
            'message' => 'Tagihan baru berhasil dibuat.',
            'snap_token' => $snapToken,
            'order_id' => $newPayment->order_id,
            'payment_id' => $newPayment->getKey(),
            'amount' => $newPayment->amount,
            'payment_type' => $newPayment->payment_type,
            'booking_code' => $rental->booking_code,
            'redirect_url' => route('booking.status', $rental->booking_code),
        ]);
    }

    /**
     * Ambil angka percobaan di akhir order_id lalu tambah 1.
     * TDK-2026-ABCDE-FULL-1 → 2 ; TDK-2026-ABCDE-DP-3 → 4
     */
    private function nextAttemptNumber(string $orderId): int
    {
        return preg_match('/-(\d+)$/', $orderId, $matches)
            ? ((int) $matches[1]) + 1
            : 2;
    }
}
