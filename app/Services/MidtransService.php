<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\InvalidMidtransSignatureException;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Midtrans\Transaction;

/**
 * Integrasi Midtrans: Snap token, charge VA, validasi signature webhook,
 * dan pemrosesan notifikasi.
 *
 * @see docs/TENDAKU_payment_spec.md §7.2, §7.3
 */
class MidtransService
{
    /**
     * Prefix wajib untuk semua order_id Midtrans project ini (§0).
     * Akun sandbox pernah dipakai project lain; prefix memisahkan log dashboard.
     */
    public const ORDER_ID_PREFIX = 'TDK';

    public function __construct()
    {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new LogicException('The Midtrans server key is not configured.');
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    // ── Snap ──────────────────────────────────────────────────────────────────

    /**
     * Buat Snap token untuk payment dan simpan ke payments.snap_token (untuk retry).
     *
     * @throws InvalidArgumentException
     */
    public function createTransaction(Payment $payment): string
    {
        $amount = (int) $payment->amount;
        $orderId = $payment->order_id;

        if (! $payment->exists || ! $payment->getKey()) {
            throw new InvalidArgumentException('A saved payment is required to create a Midtrans transaction.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('The payment amount must be a positive whole number.');
        }

        if (empty($orderId)) {
            throw new InvalidArgumentException('Payment belum memiliki order_id. Panggil PaymentService::createOnlinePayment terlebih dahulu.');
        }

        if (! str_starts_with($orderId, self::ORDER_ID_PREFIX)) {
            throw new InvalidArgumentException('Invalid order ID format. Expected prefix: '.self::ORDER_ID_PREFIX);
        }

        $rental = $this->rentalFor($payment);
        $statusUrl = route('booking.status', $rental->booking_code);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => $this->buildItemDetails($payment, $rental),
            'customer_details' => $this->buildCustomerDetails($rental),
            'enabled_payments' => config('payment.enabled_channels'),
            'callbacks' => [
                'finish' => $statusUrl,
                'unfinish' => $statusUrl,
                'error' => $statusUrl,
            ],
        ];

        // Samakan masa berlaku dengan rentals.expires_at
        if ($expiry = $this->snapExpiry($rental)) {
            $params['expiry'] = $expiry;
        }

        $snapToken = Snap::getSnapToken($params);

        $payment->update(['snap_token' => $snapToken]);

        return $snapToken;
    }

    // ── Core API: Virtual Account ─────────────────────────────────────────────

    /**
     * Generate nomor VA via Core API
     * @param  string  $bank
     * @return array{bank: string, va_number: string, biller_code: ?string, qr_code_url: ?string}|null
     */
    public function chargeBankTransfer(Payment $payment, string $bank = 'bca'): ?array
    {
        $bank = strtolower(trim($bank ?: 'bca'));

        // Sudah punya VA untuk bank yang sama → pakai lagi
        if ($payment->raw_notification && ! empty($payment->va_details)) {
            if (strtolower($payment->va_details['bank'] ?? '') === $bank) {
                return $payment->va_details;
            }

            $baseOrderId = explode('-', $payment->order_id)[0];
            $payment->order_id = $baseOrderId.'-'.substr(str_shuffle('123456789'), 0, 3);
            $payment->save();
        }

        $rental = $this->rentalFor($payment);

        $params = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => $this->buildCustomerDetails($rental),
            'item_details' => $this->buildItemDetails($payment, $rental),
        ];

        if ($bank === 'mandiri') {
            throw new \InvalidArgumentException('Metode pembayaran Mandiri tidak tersedia.');
        }

        $params['payment_type'] = 'bank_transfer';
        $params['bank_transfer'] = ['bank' => $bank];

        // Samakan masa berlaku VA dengan batas bayar
        if ($payment->expires_at && $payment->expires_at->isFuture()) {
            $params['custom_expiry'] = [
                'expiry_duration' => max(1, (int) floor(now()->diffInMinutes($payment->expires_at))),
                'unit' => 'minute',
            ];
        }

        try {
            $raw = json_decode(json_encode(CoreApi::charge($params)), true);
        } catch (\Throwable $e) {
            Log::error('Midtrans CoreApi Charge error: '.$e->getMessage(), [
                'order_id' => $payment->order_id,
                'bank' => $bank,
            ]);

            throw $e;
        }

        $payment->update([
            'midtrans_transaction_id' => $raw['transaction_id'] ?? $payment->midtrans_transaction_id,
            'midtrans_status' => $raw['transaction_status'] ?? 'pending',
            'method' => 'va_'.$bank,
            'raw_notification' => $raw,
        ]);

        return $payment->fresh()->va_details;
    }

    // ── Status dari Midtrans: polling & webhook ───────────────────────────────

    /**
     * Sinkronisasi status langsung dari API Midtrans (§0, §7.1).
     * Dipakai polling di halaman status. Gagal hubungi Midtrans → payment dikembalikan apa adanya.
     * @throws InvalidArgumentException
     */
    public function syncStatus(Payment $payment): Payment
    {
        if (empty($payment->order_id)) {
            throw new InvalidArgumentException('Payment belum memiliki order_id.');
        }

        try {
            $statusData = json_decode(json_encode(Transaction::status($payment->order_id)), true);
        } catch (\Throwable $e) {
            Log::info('Gagal sinkronisasi status transaksi dari Midtrans: '.$e->getMessage(), [
                'order_id' => $payment->order_id,
            ]);

            return $payment;
        }

        if (! is_array($statusData) || empty($statusData['transaction_status'])) {
            return $payment;
        }

        return DB::transaction(fn (): Payment => $this->applyGatewayUpdate(
            Payment::query()->lockForUpdate()->findOrFail($payment->id),
            $statusData,
        ));
    }

    /**
     * Validasi dan proses notifikasi webhook Midtrans.
     *
     * @param  array<string, string>  $notification
     *
     * @throws InvalidMidtransSignatureException
     * @throws InvalidArgumentException
     * @throws ModelNotFoundException
     */
    public function handleNotification(array $notification): Payment
    {
        $this->verifySignature($notification);

        return DB::transaction(function () use ($notification): Payment {
            $payment = Payment::query()
                ->where('order_id', $notification['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertAmountMatches($payment, $notification);

            return $this->applyGatewayUpdate($payment, $notification);
        });
    }

    /**
     * Verifikasi signature webhook (§7.3 langkah 2):
     * SHA512(order_id + status_code + gross_amount + ServerKey), dibandingkan constant-time (§11).
     *
     * @param  array<string, string>  $notification
     *
     * @throws InvalidMidtransSignatureException
     */
    public function verifySignature(array $notification): void
    {
        $expectedSignature = hash('sha512', implode('', [
            $notification['order_id'] ?? '',
            $notification['status_code'] ?? '',
            $notification['gross_amount'] ?? '',
            config('services.midtrans.server_key'),
        ]));

        if (! hash_equals($expectedSignature, (string) ($notification['signature_key'] ?? ''))) {
            throw new InvalidMidtransSignatureException;
        }
    }

    // ── Internal: status ──────────────────────────────────────────────────────

    /**
     * Terapkan data status dari Midtrans (webhook atau polling) ke payment yang sudah di-lock.
     * Transisi idempotent; rental dikonfirmasi hanya saat payment baru saja menjadi paid.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyGatewayUpdate(Payment $payment, array $data): Payment
    {
        $transactionStatus = (string) $data['transaction_status'];
        $newStatus = $this->mapMidtransStatus($transactionStatus, $data['fraud_status'] ?? null);

        $statusChanged = $newStatus !== null && $payment->transitionTo($newStatus);

        $payment->midtrans_transaction_id = $data['transaction_id'] ?? $payment->midtrans_transaction_id;
        $payment->midtrans_status = $transactionStatus;

        if (blank($payment->method) && isset($data['payment_type'])) {
            $payment->method = (string) $data['payment_type'];
        }

        $payment->raw_notification = array_merge($payment->raw_notification ?? [], $data);
        $payment->save();

        if ($statusChanged && $newStatus === PaymentStatus::Paid) {
            $this->confirmRentalIfFirstPayment($payment);
        }

        return $payment;
    }

    /**
     * transaction_status Midtrans → status internal (§5.2).
     * null = tidak ada perubahan (pending, atau capture+challenge).
     */
    private function mapMidtransStatus(string $transactionStatus, ?string $fraudStatus): ?PaymentStatus
    {
        return match ($transactionStatus) {
            'settlement' => PaymentStatus::Paid,
            'capture' => match ($fraudStatus) {
                'accept' => PaymentStatus::Paid,
                'deny' => PaymentStatus::Failed,
                default => null, // challenge → tetap pending
            },
            'expire' => PaymentStatus::Expired,
            'cancel' => PaymentStatus::Cancelled,
            'deny', 'failure' => PaymentStatus::Failed,
            'refund', 'partial_refund' => PaymentStatus::Refunded,
            default => null,
        };
    }

    /**
     * Rental → 'confirmed' saat pembayaran paid pertama (§5.4, §7.3).
     * Rental di-lock agar webhook ganda tidak race.
     */
    private function confirmRentalIfFirstPayment(Payment $payment): void
    {
        /** @var Rental|null $rental */
        $rental = Rental::query()->lockForUpdate()->find($payment->rental_id);

        if ($rental?->status !== 'pending') {
            return;
        }

        $paidCount = Payment::query()
            ->where('rental_id', $rental->id)
            ->where('status', PaymentStatus::Paid->value)
            ->count();

        if ($paidCount === 1) {
            $rental->update(['status' => 'confirmed']);
        }
    }

    /**
     * Nominal notifikasi harus sama dengan nominal payment (§7.3 langkah 5).
     *
     * @param  array<string, string>  $notification
     *
     * @throws InvalidArgumentException
     */
    private function assertAmountMatches(Payment $payment, array $notification): void
    {
        $paymentAmount = self::normalizeAmount((string) $payment->amount);
        $notificationAmount = self::normalizeAmount((string) $notification['gross_amount']);

        if ($paymentAmount !== null && $paymentAmount === $notificationAmount) {
            return;
        }

        Log::warning('Midtrans webhook: nominal tidak cocok.', [
            'order_id' => $notification['order_id'],
            'payment_amount' => $payment->amount,
            'notification_amount' => $notification['gross_amount'],
        ]);

        throw new InvalidArgumentException('The notification amount does not match the payment amount.');
    }

    /**
     * Normalisasi nominal untuk perbandingan (mencegah mismatch float).
     */
    private static function normalizeAmount(string $amount): ?string
    {
        if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 2, '0');
    }

    // ── Internal: payload Midtrans ────────────────────────────────────────────

    private function rentalFor(Payment $payment): Rental
    {
        $payment->loadMissing(['rental.details.masterItem', 'rental.customer']);

        return $payment->rental;
    }

    /**
     * Blok 'expiry' Snap dari sisa waktu rental; null jika tidak ada/sudah lewat.
     *
     * @return array{start_time: string, unit: string, duration: int}|null
     */
    private function snapExpiry(Rental $rental): ?array
    {
        if (! $rental->expires_at) {
            return null;
        }

        $minutes = (int) now()->diffInMinutes($rental->expires_at, false);

        if ($minutes <= 0) {
            return null;
        }

        return [
            'start_time' => now()->format('Y-m-d H:i:s O'),
            'unit' => 'minutes',
            'duration' => $minutes,
        ];
    }

    /**
     * item_details Snap (§7.2). Total HARUS sama dengan gross_amount (validasi Midtrans).
     */
    private function buildItemDetails(Payment $payment, Rental $rental): array
    {
        $amount = (int) $payment->amount;
        $code = $rental->booking_code;

        if ($payment->payment_type === 'dp') {
            $dpPct = (int) config('payment.dp_percentage', 50);

            return [[
                'id' => "DP-{$code}",
                'price' => $amount,
                'quantity' => 1,
                'name' => "DP {$dpPct}% Sewa {$code}",
            ]];
        }

        if ($payment->payment_type === 'full' && $rental->details->isNotEmpty()) {
            return $this->itemizedDetails($rental, $amount);
        }

        return [[
            'id' => "PAY-{$payment->getKey()}",
            'price' => $amount,
            'quantity' => 1,
            'name' => match ($payment->payment_type) {
                'remaining' => "Pelunasan Sewa {$code}",
                'fine' => "Denda Sewa {$code}",
                default => "Pembayaran Sewa {$code}",
            },
        ]];
    }

    /**
     * Rincian per item + baris penyesuaian agar total = gross_amount.
     */
    private function itemizedDetails(Rental $rental, int $amount): array
    {
        $items = $rental->details->map(fn ($detail) => [
            'id' => "ITEM-{$detail->master_item_id}",
            'price' => (int) $detail->daily_rate_snapshot,
            'quantity' => $rental->rental_days,
            'name' => $detail->masterItem?->name ?? "Item #{$detail->master_item_id}",
        ])->all();

        $itemTotal = array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items));
        $diff = $amount - $itemTotal;

        if ($diff !== 0) {
            $items[] = [
                'id' => 'ADJ',
                'price' => $diff,
                'quantity' => 1,
                'name' => 'Penyesuaian Harga',
            ];
        }

        return $items;
    }

    /**
     * customer_details Snap/Core API (§7.2).
     */
    private function buildCustomerDetails(Rental $rental): array
    {
        if ($rental->isGuest()) {
            return [
                'first_name' => $rental->guest_name ?? 'Tamu',
                'phone' => $rental->guest_phone ?? '',
            ];
        }

        $customer = $rental->customer;

        return [
            'first_name' => $customer?->name ?? 'Pelanggan',
            'email' => $customer?->email ?? '',
            'phone' => $customer?->phone ?? '',
        ];
    }
}
