<?php

namespace App\Http\Controllers\Webhook;

use App\Exceptions\InvalidMidtransSignatureException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\MidtransNotificationRequest;
use App\Services\Payment\MidtransService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Menerima dan memproses notifikasi webhook dari Midtrans.
 *
 * Validasi signature SHA512 dilakukan oleh MidtransService.
 *
 * @see docs/TENDAKU_payment_spec.md §7.3
 */
class MidtransWebhookController extends Controller
{
    public function handle(
        MidtransNotificationRequest $request,
        MidtransService $midtrans,
    ): JsonResponse {
        // Validasi input dilakukan oleh MidtransNotificationRequest.
        $notification = $request->validated();

        try {
            $payment = $midtrans->handleNotification($notification);
        } catch (InvalidMidtransSignatureException) {
            // Signature salah: jangan mengubah database.
            Log::warning(
                'Rejected Midtrans notification with an invalid signature.',
                [
                    'order_id' => $notification['order_id'] ?? null,
                    'ip' => $request->ip(),
                ]
            );

            return response()->json([
                'message' => 'Invalid notification signature.',
            ], 403);
        } catch (ModelNotFoundException) {
            // Order tidak ditemukan.
            Log::warning(
                'Midtrans notification for unknown order_id.',
                [
                    'order_id' => $notification['order_id'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'Payment not found.',
            ], 404);
        } catch (InvalidArgumentException $exception) {
            // Misalnya, nominal notifikasi tidak sesuai.
            Log::warning(
                'Midtrans notification amount mismatch.',
                [
                    'order_id' => $notification['order_id'] ?? null,
                    'notification_amount' =>
                        $notification['gross_amount'] ?? null,
                    'message' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        // Pemrosesan berhasil.
        return response()->json([
            'status' => 'success',
            'payment_status' => $payment->status,
        ]);
    }
}
