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
    public function store(Request $request, Rental $rental, MidtransService $midtrans): JsonResponse
    {
        abort_unless(
            $request->user()?->role === 'customer' && $rental->user_id === Auth::id(),
            404,
        );

        // TODO(Role 1): Create this pending settlement Payment when saving the booking, using the server-calculated grand total.
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
}
