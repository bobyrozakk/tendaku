<?php

namespace App\Services;

use App\Models\Rental;

class MidtransService
{
    /**
     * Generate Snap Token untuk transaksi pembayaran Midtrans.
     */
    public function createSnapToken(Rental $rental): string
    {
        // Integrasi SDK / API Midtrans Snap
        return '';
    }

    /**
     * Dipanggil oleh MidtransWebhookController untuk memproses status pembayaran.
     */
    public function handleNotification(array $payload): void
    {
        // Update status payment & status rental
    }
}
