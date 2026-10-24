<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoint retry pembayaran — generate tagihan baru jika yang lama expired/failed.
 *
 * POST /payments/{order_id}/retry
 * Akses: pemilik rental (customer login). Guest menggunakan signed URL (D24).
 *
 * @see docs/contract.md §4.8
 */
class PaymentRetryController extends Controller
{
    public function store(
        Request $request,
        string $orderId,
        PaymentService $paymentService,
        MidtransService $midtransService
    ): JsonResponse {
        // Cari payment lama berdasarkan order_id
        $oldPayment = Payment::query()
            ->where('order_id', $orderId)
            ->firstOrFail();

        $rental = $oldPayment->rental;

        // Otorisasi: pemilik rental (§4.8)
        abort_unless(
            $rental !== null && $rental->user_id === Auth::id(),
            404,
        );

        // Hanya bisa retry jika payment lama sudah expired atau failed
        abort_unless(
            in_array($oldPayment->status, ['expired', 'failed'], true),
            422,
            'Tagihan ini tidak memerlukan retry. Hanya tagihan yang expired atau failed yang bisa di-retry.',
        );

        // Hitung percobaan berikutnya berdasarkan order_id lama
        // Format: TDK-{code}-{type}-{n} → ambil n, increment
        $attempt = $this->nextAttemptNumber($oldPayment->order_id);

        // Buat payment baru dengan order_id baru (attempt +1)
        $newPayment = $paymentService->createOnlinePayment(
            $rental,
            $oldPayment->payment_type,
            $attempt
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
     * Ekstrak nomor percobaan dari order_id lama dan kembalikan +1.
     * Contoh: TDK-2026-ABCDE-FULL-1 → 2; TDK-2026-ABCDE-DP-3 → 4
     */
    private function nextAttemptNumber(string $orderId): int
    {
        if (preg_match('/-(\d+)$/', $orderId, $matches)) {
            return ((int) $matches[1]) + 1;
        }

        return 2;
    }
}
