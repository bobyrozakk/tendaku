<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MidtransPaymentController extends Controller
{
    /**
     * Generate Midtrans SNAP token untuk pembayaran pokok (full/dp) rental.
     */
    public function store(Request $request, Rental $rental, MidtransService $midtrans): JsonResponse
    {
        $this->authorizeCustomerOwnership($request, $rental);

        $rental->expireIfPastDue();

        if ($rental->status === 'expired') {
            return response()->json([
                'message' => 'Batas waktu pembayaran telah habis. Booking telah kedaluwarsa dan tidak dapat dilanjutkan.',
            ], 422);
        }

        abort_unless(
            $rental->status === 'pending',
            422,
            'Rental ini tidak lagi menunggu pembayaran.',
        );

        $payment = $rental->payments()
            ->where('status', 'pending')
            ->whereIn('payment_type', ['full', 'dp', 'settlement'])
            ->where(function ($q) {
                $q->where('channel', 'midtrans')
                    ->orWhere('payment_method', 'midtrans_snap');
            })
            ->latest('id')
            ->firstOrFail();

        $snapToken = $payment->snap_token ?: $midtrans->createTransaction($payment);

        return response()->json([
            'data' => [
                'payment_id' => $payment->getKey(),
                'order_id' => $payment->order_id ?? $payment->midtrans_order_id,
                'snap_token' => $snapToken,
            ],
        ]);
    }

    /**
     * Generate Midtrans SNAP token untuk pembayaran denda (keterlambatan + kerusakan).
     *
     * Endpoint ini dipanggil setelah proses pengembalian barang selesai dan
     * terdapat denda yang belum dibayar pada rental tersebut.
     */
    public function storeFine(
        Request $request,
        Rental $rental,
        PaymentService $paymentService,
        MidtransService $midtrans,
    ): JsonResponse
    {
        $this->authorizeCustomerOwnership($request, $rental);

        // Rental harus sudah dalam status returned atau completed agar ada denda
        abort_unless(
            in_array($rental->status, ['returned', 'completed'], true),
            422,
            'Rental belum dalam status pengembalian. Denda hanya dapat dibayar setelah barang dikembalikan.',
        );

        $fineTotal = (int) round((float) $rental->total_late_fee + (float) $rental->total_damage_fee);

        abort_if(
            $fineTotal <= 0,
            422,
            'Tidak terdapat denda yang perlu dibayar pada rental ini.',
        );

        // Cegah double payment dalam DB transaction + lockForUpdate
        return DB::transaction(function () use ($rental, $fineTotal, $paymentService, $midtrans) {
            // Lock rental row agar tidak ada request lain yang lolos bersamaan
            Rental::query()->where('id', $rental->id)->lockForUpdate()->first();

            $existingFinePayment = $rental->payments()
                ->where('payment_type', 'fine')
                ->whereIn('status', ['pending', 'paid'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existingFinePayment?->status === 'paid') {
                return response()->json([
                    'message' => 'Denda pada rental ini sudah lunas.',
                    'data' => ['payment_id' => $existingFinePayment->getKey()],
                ]);
            }

            // Reuse payment pending yang sudah ada, atau buat tagihan denda baru via PaymentService
            $payment = $existingFinePayment ?? $paymentService->createOnlineFinePayment($rental, $fineTotal);

            $snapToken = $payment->snap_token ?: $midtrans->createTransaction($payment);

            return response()->json([
                'data' => [
                    'payment_id' => $payment->getKey(),
                    'order_id' => $payment->order_id,
                    'snap_token' => $snapToken,
                    'fine_total' => (float) $fineTotal,
                    'late_fee' => (float) $rental->total_late_fee,
                    'damage_fee' => (float) $rental->total_damage_fee,
                ],
            ]);
        });
    }

    /**
     * Hanya customer pemilik rental yang boleh membayar; selain itu 404 agar rental tidak terekspos.
     */
    private function authorizeCustomerOwnership(Request $request, Rental $rental): void
    {
        abort_unless(
            $request->user()?->role === 'customer' && $rental->user_id === Auth::id(),
            404,
        );
    }
}
