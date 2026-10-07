<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MidtransPaymentController extends Controller
{
    /**
     * Generate Midtrans SNAP token untuk pembayaran pokok (settlement) rental.
     */
    public function store(Request $request, Rental $rental, MidtransService $midtrans): JsonResponse
    {
        abort_unless(
            $request->user()?->role === 'customer' && $rental->user_id === Auth::id(),
            404,
        );

        abort_unless(
            $rental->status === 'pending',
            422,
            'Rental ini tidak lagi menunggu pembayaran.',
        );

        $payment = $rental->payments()
            ->where('status', 'pending')
            ->where('payment_type', 'settlement')
            ->where('payment_method', 'midtrans_snap')
            ->latest('id')
            ->firstOrFail();

        $snapToken = $midtrans->createTransaction($payment);

        return response()->json([
            'data' => [
                'payment_id' => $payment->getKey(),
                'order_id' => $payment->midtrans_order_id,
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
    public function storeFine(Request $request, Rental $rental, MidtransService $midtrans): JsonResponse
    {
        abort_unless(
            $request->user()?->role === 'customer' && $rental->user_id === Auth::id(),
            404,
        );

        // Rental harus sudah dalam status returned atau completed agar ada denda
        abort_unless(
            in_array($rental->status, ['returned', 'completed'], true),
            422,
            'Rental belum dalam status pengembalian. Denda hanya dapat dibayar setelah barang dikembalikan.',
        );

        $fineTotal = (float) $rental->total_late_fee + (float) $rental->total_damage_fee;

        abort_if(
            $fineTotal <= 0,
            422,
            'Tidak terdapat denda yang perlu dibayar pada rental ini.',
        );

        // Cegah double payment: pastikan belum ada pembayaran denda yang pending atau sudah lunas
        $existingFinePayment = $rental->payments()
            ->where('payment_type', 'fine')
            ->whereIn('status', ['pending', 'paid'])
            ->latest('id')
            ->first();

        if ($existingFinePayment?->status === 'paid') {
            return response()->json([
                'message' => 'Denda pada rental ini sudah lunas.',
                'data' => ['payment_id' => $existingFinePayment->getKey()],
            ]);
        }

        // Reuse payment pending yang sudah ada, atau buat yang baru
        $payment = $existingFinePayment ?? $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'payment_method' => 'midtrans_snap',
            'amount' => $fineTotal,
            'status' => 'pending',
        ]);

        $snapToken = $midtrans->createTransaction($payment);

        return response()->json([
            'data' => [
                'payment_id' => $payment->getKey(),
                'order_id' => $payment->midtrans_order_id,
                'snap_token' => $snapToken,
                'fine_total' => (float) $fineTotal,
                'late_fee' => (float) $rental->total_late_fee,
                'damage_fee' => (float) $rental->total_damage_fee,
            ],
        ]);
    }
}
