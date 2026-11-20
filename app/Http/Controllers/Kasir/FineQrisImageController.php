<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\MidtransService;
use Illuminate\Http\Response;

class FineQrisImageController extends Controller
{
    public function show(string $payment, MidtransService $midtrans): Response
    {
        $payment = Payment::withoutGlobalScope('vendor')
            ->whereKey($payment)
            ->firstOrFail();

        abort_unless(
            $payment->payment_type === 'fine'
                && $payment->channel === 'midtrans'
                && $payment->payment_channel === 'qris'
                && $payment->status === 'pending'
                && $payment->qris_details !== null,
            404,
        );

        return response($midtrans->getQrisImage($payment), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
