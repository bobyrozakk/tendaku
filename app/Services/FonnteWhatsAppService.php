<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FonnteWhatsAppService implements WhatsAppService
{
    /**
     * Kirim pesan WhatsApp melalui Fonnte API (https://api.fonnte.com/send).
     *
     * Header autentikasi Fonnte memakai token mentah tanpa prefiks "Bearer".
     * Kegagalan selalu dikembalikan sebagai `false`, tidak pernah melempar exception.
     */
    public function send(string $phone, string $message): bool
    {
        $target = PhoneNumberNormalizer::normalize($phone);

        if ($target === null) {
            Log::warning('Fonnte: nomor telepon tidak valid, pengiriman dibatalkan.', [
                'phone' => $phone,
            ]);

            return false;
        }

        $token = config('services.fonnte.token');

        if (! is_string($token) || trim($token) === '') {
            Log::error('Fonnte: FONNTE_TOKEN belum dikonfigurasi, pesan tidak dikirim.', [
                'target' => $target,
            ]);

            return false;
        }

        $baseUrl = rtrim((string) config('services.fonnte.base_url', 'https://api.fonnte.com'), '/');

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->timeout((int) config('services.fonnte.timeout', 10))
                ->retry(
                    (int) config('services.fonnte.retry_times', 2),
                    (int) config('services.fonnte.retry_sleep_ms', 300),
                    throw: false,
                )
                ->post($baseUrl.'/send', [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => PhoneNumberNormalizer::COUNTRY_CODE,
                ]);
        } catch (Throwable $exception) {
            Log::error('Fonnte: permintaan pengiriman gagal.', [
                'target' => $target,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('Fonnte: server merespons HTTP error.', [
                'target' => $target,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['status'] ?? false) !== true) {
            Log::error('Fonnte: pengiriman ditolak oleh server.', [
                'target' => $target,
                'reason' => is_array($payload) ? ($payload['reason'] ?? 'unknown') : 'invalid-payload',
            ]);

            return false;
        }

        return true;
    }
}
