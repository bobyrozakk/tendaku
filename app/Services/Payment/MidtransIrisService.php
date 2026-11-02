<?php

namespace App\Services\Payment;

use App\Exceptions\IrisConnectionException;
use App\Exceptions\IrisPayoutException;
use App\Models\Settlement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransIrisService
{
    /**
     * Alias nama bank yang umum diisi vendor ke kode bank Payouts/Iris.
     *
     * @var array<string, string>
     */
    private const BANK_CODES = [
        'bca' => 'bca',
        'central asia' => 'bca',
        'bni' => 'bni',
        'negara indonesia' => 'bni',
        'bri' => 'bri',
        'rakyat indonesia' => 'bri',
        'mandiri' => 'mandiri',
        'bsi' => 'bsi',
        'syariah indonesia' => 'bsi',
        'btn' => 'btn',
        'tabungan negara' => 'btn',
        'cimb' => 'cimb',
        'cimb niaga' => 'cimb',
        'niaga' => 'cimb',
        'permata' => 'permata',
        'danamon' => 'danamon',
        'btpn' => 'btpn',
        'jago' => 'jago',
        'seabank' => 'seabank',
        'sea bank' => 'seabank',
        'jatim' => 'jatim',
        'jawa timur' => 'jatim',
        'jateng' => 'jateng',
        'jawa tengah' => 'jateng',
        'dki' => 'dki',
        'dki jakarta' => 'dki',
        'mega' => 'mega',
        'maybank' => 'maybank',
        'ocbc' => 'ocbc',
        'panin' => 'panin',
        'uob' => 'uob',
        'commonwealth' => 'commonwealth',
        'bukopin' => 'bukopin',
        'sinarmas' => 'sinarmas',
        'muamalat' => 'muamalat',
        'jtrust' => 'jtrust',
        'mestika' => 'mestika',
        'artha graha' => 'arthagraha',
        'allo' => 'allo',
        'ibk' => 'ibk',
        'qnb' => 'qnb',
        'victoria' => 'victoria',
        'woori' => 'woori',
        'line' => 'line',
        'hibank' => 'hibank',
        'blu' => 'blu',
        'bca digital' => 'blu',
        'neo commerce' => 'neocommerce',
        'neobank' => 'neobank',
        'raya' => 'raya',
    ];

    public function isConfigured(): bool
    {
        return filled($this->creatorKey()) && filled($this->approverKey());
    }

    /**
     * Buat satu payout untuk settlement memakai kredensial Creator.
     *
     * @return array{reference_no: string, status: string}
     */
    public function createPayout(Settlement $settlement): array
    {
        $this->ensureConfigured();
        $this->ensurePayoutDetails($settlement);

        $idempotencyKey = filled($settlement->iris_idempotency_key)
            ? (string) $settlement->iris_idempotency_key
            : 'TDK-SETTLEMENT-'.$settlement->getKey();
        $accountNumber = $this->accountNumber($settlement);

        $response = $this->send(
            $this->client($this->creatorKey())
                ->withHeader('X-Idempotency-Key', $idempotencyKey),
            'post',
            '/payouts',
            [
                'payouts' => [[
                    'beneficiary_name' => trim((string) $settlement->bank_account_name),
                    'beneficiary_account' => $accountNumber,
                    'beneficiary_bank' => $this->bankCode($settlement->bank_name),
                    'amount' => number_format((float) $settlement->net_payout, 2, '.', ''),
                    'notes' => $this->notes($settlement),
                ]],
            ],
            'Pembuatan payout Midtrans Iris',
        );

        $payload = $this->decode($response, 'Pembuatan payout Midtrans Iris');
        $payout = data_get($payload, 'payouts.0');
        $referenceNumber = is_array($payout) ? ($payout['reference_no'] ?? null) : null;

        if (! is_string($referenceNumber) || $referenceNumber === '') {
            throw new IrisPayoutException('Midtrans Iris tidak mengembalikan nomor referensi payout.');
        }

        return [
            'reference_no' => $referenceNumber,
            'status' => (string) ($payout['status'] ?? 'queued'),
        ];
    }

    /**
     * Setujui payout memakai kredensial Approver.
     */
    public function approvePayout(string $referenceNumber, ?string $otp = null): void
    {
        $this->ensureConfigured();

        $payload = [
            'reference_nos' => [$referenceNumber],
        ];

        if (filled($otp)) {
            $payload['otp'] = (string) $otp;
        }

        $response = $this->send(
            $this->client($this->approverKey()),
            'post',
            '/payouts/approve',
            $payload,
            'Persetujuan payout Midtrans Iris',
        );

        $this->decode($response, 'Persetujuan payout Midtrans Iris');
    }

    /**
     * Ambil status payout terbaru dari Midtrans Iris.
     *
     * @return array<string, mixed>
     */
    public function getPayout(string $referenceNumber): array
    {
        $this->ensureConfigured();

        $response = $this->send(
            $this->client($this->creatorKey()),
            'get',
            '/payouts/'.rawurlencode($referenceNumber),
            [],
            'Pengecekan status payout Midtrans Iris',
        );

        return $this->decode($response, 'Pengecekan status payout Midtrans Iris');
    }

    public function bankCode(?string $bankName): string
    {
        $normalized = Str::of((string) $bankName)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->replaceMatches('/\bbank\b/', ' ')
            ->squish()
            ->toString();

        if ($normalized === '') {
            throw new IrisPayoutException('Nama bank vendor belum diisi.');
        }

        if (isset(self::BANK_CODES[$normalized])) {
            return self::BANK_CODES[$normalized];
        }

        $aliases = self::BANK_CODES;
        uksort($aliases, fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($aliases as $alias => $code) {
            if (preg_match('/\b'.preg_quote($alias, '/').'\b/', $normalized) === 1) {
                return $code;
            }
        }

        return preg_replace('/[^a-z0-9]+/', '', $normalized)
            ?: throw new IrisPayoutException('Kode bank vendor tidak dapat ditentukan.');
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth($apiKey, '')
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('services.midtrans_iris.connect_timeout', 5))
            ->timeout((int) config('services.midtrans_iris.timeout', 15));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(
        PendingRequest $request,
        string $method,
        string $url,
        array $payload,
        string $action,
    ): Response {
        try {
            return match ($method) {
                'get' => $request->get($url),
                'post' => $request->post($url, $payload),
                default => throw new \InvalidArgumentException('Metode HTTP Midtrans Iris tidak didukung.'),
            };
        } catch (ConnectionException $exception) {
            Log::warning('Midtrans Iris connection failed.', [
                'action' => $action,
            ]);

            throw new IrisConnectionException(
                'Koneksi ke Midtrans Iris gagal. Status payout belum pasti; jangan catat transfer manual sebelum dicek. Coba lagi dengan tombol yang sama.',
                previous: $exception,
            );
        }
    }

    private function baseUrl(): string
    {
        $configured = trim((string) config('services.midtrans_iris.base_url'));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return (bool) config('services.midtrans_iris.is_production', false)
            ? 'https://app.midtrans.com/iris/api/v1'
            : 'https://app.sandbox.midtrans.com/iris/api/v1';
    }

    private function creatorKey(): string
    {
        return trim((string) config('services.midtrans_iris.creator_key'));
    }

    private function approverKey(): string
    {
        return trim((string) config('services.midtrans_iris.approver_key'));
    }

    private function ensureConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        throw new IrisPayoutException('Kredensial Midtrans Iris belum lengkap. Isi MIDTRANS_IRIS_CREATOR_KEY dan MIDTRANS_IRIS_APPROVER_KEY.');
    }

    public function ensurePayoutDetails(Settlement $settlement): void
    {
        if ((float) $settlement->net_payout <= 0) {
            throw new IrisPayoutException('Net payout harus lebih dari nol sebelum dicairkan.');
        }

        $missing = [];

        if (! filled($settlement->bank_name)) {
            $missing[] = 'nama bank';
        }

        if (! filled($settlement->bank_account_number)) {
            $missing[] = 'nomor rekening';
        }

        if (! filled($settlement->bank_account_name)) {
            $missing[] = 'nama pemilik rekening';
        }

        if ($missing !== []) {
            throw new IrisPayoutException('Rekening tujuan vendor belum lengkap: '.implode(', ', $missing).'.');
        }

        if (! preg_match('/^[0-9]{6,30}$/', $this->accountNumber($settlement))) {
            throw new IrisPayoutException('Nomor rekening vendor harus terdiri dari 6-30 angka.');
        }
    }

    private function accountNumber(Settlement $settlement): string
    {
        return preg_replace('/\s+/', '', (string) $settlement->bank_account_number) ?? '';
    }

    private function notes(Settlement $settlement): string
    {
        return Str::limit(sprintf(
            'Settlement %d periode %s %s',
            $settlement->getKey(),
            $settlement->period_start->format('Ymd'),
            $settlement->period_end->format('Ymd'),
        ), 100, '');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $action): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            $payload = [];
        }

        if ($response->successful()) {
            return $payload;
        }

        $message = $this->errorMessage($payload);

        Log::warning('Midtrans Iris request failed.', [
            'action' => $action,
            'http_status' => $response->status(),
        ]);

        throw new IrisPayoutException(sprintf('%s gagal (HTTP %d): %s', $action, $response->status(), $message));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function errorMessage(array $payload): string
    {
        $message = data_get($payload, 'error_message')
            ?? data_get($payload, 'error_details.error_message')
            ?? data_get($payload, 'errors.account.0');

        if (! is_string($message) || trim($message) === '') {
            $message = 'Respons Midtrans tidak dapat diproses.';
        }

        $errors = data_get($payload, 'errors');

        if (is_array($errors)) {
            $detail = collect(Arr::flatten($errors))
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->implode(' ');

            if ($detail !== '' && ! str_contains($message, $detail)) {
                $message .= ' '.$detail;
            }
        }

        return Str::limit(trim($message), 300);
    }
}
