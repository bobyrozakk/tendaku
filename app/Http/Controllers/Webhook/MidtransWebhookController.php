<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    /**
     * Handler callback / notification webhook dari Midtrans (Role 2).
     */
    public function handle(Request $request): JsonResponse
    {
        // Panggil MidtransService untuk memproses notifikasi pembayaran
        return response()->json(['status' => 'success', 'message' => 'Notification processed']);
    }
}
