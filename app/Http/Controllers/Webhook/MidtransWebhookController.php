<?php

namespace App\Http\Controllers\Webhook;

use App\Exceptions\InvalidMidtransSignatureException;
use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Menerima dan memproses notifikasi webhook dari Midtrans.
 *
 * Rute ini dikecualikan dari CSRF dan auth (Midtrans tidak punya sesi) (§7.3).
 * Validasi keabsahan dilakukan via signature SHA512 (§7.3).
 *
 * @see docs/TENDAKU_payment_spec.md §7.3
 */
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
            'payment_type' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $payment = $midtrans->handleNotification($notification);
        } catch (InvalidMidtransSignatureException) {
            // Signature salah → 403 + log; DB tidak berubah (§7.3 langkah 2)
            Log::warning('Rejected Midtrans notification with an invalid signature.', [
                'order_id' => $notification['order_id'],
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid notification signature.'], 403);
        } catch (ModelNotFoundException) {
            // order_id tidak ditemukan → 404 + log (§7.3 langkah 3)
            Log::warning('Midtrans notification for unknown order_id.', [
                'order_id' => $notification['order_id'],
            ]);

            return response()->json(['message' => 'Payment not found.'], 404);
        } catch (InvalidArgumentException $exception) {
            // Nominal tidak cocok → catat anomali, JANGAN diproses (§7.3 langkah 5)
            Log::warning('Midtrans notification amount mismatch.', [
                'order_id' => $notification['order_id'],
                'notification_amount' => $notification['gross_amount'],
                'message' => $exception->getMessage(),
            ]);

            // Balas 422 agar Midtrans retry; anomali sudah dicatat di log
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        // Balas 200 cepat; pekerjaan lambat (WA, email) masuk queue (§7.3 langkah 9)
        return response()->json([
            'status' => 'success',
            'payment_status' => $payment->status,
        ]);
    }
}
