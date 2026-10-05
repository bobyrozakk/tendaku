<?php

namespace App\Http\Controllers\Webhook;

use App\Exceptions\InvalidMidtransSignatureException;
use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request, MidtransService $midtrans): JsonResponse
    {
        $notification = $request->validate([
            'order_id' => ['required', 'string', 'max:100'],
            'status_code' => ['required', 'string', 'max:10'],
            'gross_amount' => ['required', 'numeric', 'gt:0'],
            'signature_key' => ['required', 'string', 'size:128'],
            'transaction_status' => ['required', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'fraud_status' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $payment = $midtrans->handleNotification($notification);
        } catch (InvalidMidtransSignatureException) {
            Log::warning('Rejected Midtrans notification with an invalid signature.', [
                'order_id' => $notification['order_id'],
            ]);

            return response()->json(['message' => 'Invalid notification signature.'], 403);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Rejected Midtrans notification with an invalid amount.', [
                'order_id' => $notification['order_id'],
            ]);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'payment_status' => $payment->status,
        ]);
    }
}
