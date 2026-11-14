<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\InvalidMidtransSignatureException;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\Finance\CommissionCalculator;
use App\Services\Finance\CommissionService;
use App\Services\Finance\EscrowService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    /**
     * Prefix wajib untuk semua order_id Midtrans project ini.
     */
    public const ORDER_ID_PREFIX = 'TDK';

    public function __construct(
        private readonly CommissionCalculator $calculator,
        private readonly CommissionService $commissions,
        private readonly EscrowService $escrow,
    ) {
        $serverKey = config('services.midtrans.server_key');

        if (! is_string($serverKey) || $serverKey === '') {
            throw new LogicException('The Midtrans server key is not configured.');
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    // ── Snap ──────────────────────────────────────────────────────────────

    public function createTransaction(Payment $payment): string
    {
        $amount = (int) $payment->amount;
        $orderId = $payment->order_id;

        if (! $payment->exists || ! $payment->getKey()) {
            throw new InvalidArgumentException(
                'A saved payment is required to create a Midtrans transaction.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'The payment amount must be a positive whole number.'
            );
        }

        if (empty($orderId)) {
            throw new InvalidArgumentException(
                'Payment belum memiliki order_id. Panggil PaymentService::createOnlinePayment terlebih dahulu.'
            );
        }

        if (! str_starts_with($orderId, self::ORDER_ID_PREFIX)) {
            throw new InvalidArgumentException(
                'Invalid order ID format. Expected prefix: '.self::ORDER_ID_PREFIX
            );
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

        if ($expiry = $this->snapExpiry($rental)) {
            $params['expiry'] = $expiry;
        }

        $snapToken = Snap::getSnapToken($params);

        $payment->update(['snap_token' => $snapToken]);

        return $snapToken;
    }

    // ── Core API: Virtual Account ─────────────────────────────────────────

    /**
     * @return array{bank: string, va_number: string, biller_code: ?string, qr_code_url: ?string}|null
     */
    public function chargeBankTransfer(Payment $payment, string $bank = 'bca'): ?array
    {
        $bank = strtolower(trim($bank ?: config('payment.default_bank', 'bca')));

        if (! array_key_exists($bank, config('payment.va_banks', []))) {
            throw new InvalidArgumentException(
                'Bank tidak tersedia. Pilih salah satu: BCA, BNI, BRI, Permata, atau CIMB Niaga.'
            );
        }

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
            'payment_type' => 'bank_transfer',
            'bank_transfer' => ['bank' => $bank],
        ];

        if ($payment->expires_at && $payment->expires_at->isFuture()) {
            $params['custom_expiry'] = [
                'expiry_duration' => max(
                    1,
                    (int) floor(now()->diffInMinutes($payment->expires_at))
                ),
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
            'midtrans_transaction_id' => $raw['transaction_id']
                ?? $payment->midtrans_transaction_id,
            'midtrans_status' => $raw['transaction_status'] ?? 'pending',
            'method' => 'va_'.$bank,
            'raw_notification' => $raw,
        ]);

        return $payment->fresh()->va_details;
    }

    /**
     * Buat tagihan QRIS Midtrans dan simpan responsnya pada payment.
     *
     * @return array{qr_code_url: string}
     */
    public function chargeQris(Payment $payment): array
    {
        if (! $payment->exists || ! $payment->getKey() || blank($payment->order_id)) {
            throw new InvalidArgumentException('Payment tersimpan dengan order_id wajib untuk membuat QRIS.');
        }

        if ($payment->status !== PaymentStatus::Pending->value) {
            throw new InvalidArgumentException('QRIS hanya dapat dibuat untuk pembayaran yang masih pending.');
        }

        if ($payment->payment_channel !== 'qris') {
            throw new InvalidArgumentException('Metode pembayaran ini tidak memilih QRIS.');
        }

        if ($qrisDetails = $payment->qris_details) {
            return $qrisDetails;
        }

        $rental = $this->rentalFor($payment);
        $params = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => $this->buildCustomerDetails($rental),
            'item_details' => $this->buildItemDetails($payment, $rental),
            'payment_type' => 'qris',
            'qris' => ['acquirer' => 'gopay'],
        ];

        if ($payment->expires_at && $payment->expires_at->isFuture()) {
            $params['custom_expiry'] = [
                'expiry_duration' => max(
                    1,
                    (int) floor(now()->diffInMinutes($payment->expires_at))
                ),
                'unit' => 'minute',
            ];
        }

        try {
            $raw = json_decode(json_encode(CoreApi::charge($params)), true);
        } catch (\Throwable $e) {
            Log::error('Midtrans QRIS charge error: '.$e->getMessage(), [
                'order_id' => $payment->order_id,
            ]);

            throw $e;
        }

        if (! is_array($raw)) {
            throw new LogicException('Midtrans tidak mengembalikan respons QRIS yang valid.');
        }

        $qrisDetails = (new Payment(['raw_notification' => $raw]))->qris_details;

        if (! $qrisDetails) {
            throw new LogicException('Midtrans tidak mengembalikan URL gambar QRIS.');
        }

        $payment->update([
            'midtrans_transaction_id' => $raw['transaction_id']
                ?? $payment->midtrans_transaction_id,
            'midtrans_status' => $raw['transaction_status'] ?? 'pending',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'raw_notification' => $raw,
        ]);

        return $qrisDetails;
    }

    /**
     * Ambil gambar QRIS dari Midtrans menggunakan autentikasi server-side.
     */
    public function getQrisImage(Payment $payment): string
    {
        $qrCodeUrl = $payment->qris_details['qr_code_url'] ?? null;
        $isProduction = (bool) config('services.midtrans.is_production');
        $allowedHosts = $isProduction
            ? ['api.midtrans.com', 'merchants-app.midtrans.com']
            : ['api.sandbox.midtrans.com', 'merchants-app.sbx.midtrans.com'];
        $url = is_string($qrCodeUrl) ? parse_url($qrCodeUrl) : false;

        if (
            ! is_array($url)
            || ($url['scheme'] ?? null) !== 'https'
            || ! in_array($url['host'] ?? null, $allowedHosts, true)
        ) {
            throw new LogicException('URL gambar QRIS Midtrans tidak valid.');
        }

        $response = Http::connectTimeout(3)
            ->timeout(10)
            ->withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->accept('image/png')
            ->get($qrCodeUrl)
            ->throw();

        if (! str_starts_with((string) $response->header('Content-Type'), 'image/png')) {
            throw new LogicException('Midtrans tidak mengembalikan gambar QRIS PNG.');
        }

        return $response->body();
    }

    // ── Status: polling & webhook ─────────────────────────────────────────

    public function syncStatus(Payment $payment): Payment
    {
        if (empty($payment->order_id)) {
            throw new InvalidArgumentException(
                'Payment belum memiliki order_id.'
            );
        }

        try {
            $statusData = json_decode(
                json_encode(Transaction::status($payment->order_id)),
                true
            );
        } catch (\Throwable $e) {
            Log::info(
                'Gagal sinkronisasi status transaksi dari Midtrans: '.$e->getMessage(),
                ['order_id' => $payment->order_id]
            );

            return $payment;
        }

        if (! is_array($statusData) || empty($statusData['transaction_status'])) {
            return $payment;
        }

        return DB::transaction(fn (): Payment => $this->applyGatewayUpdate(
            Payment::query()->lockForUpdate()->findOrFail($payment->id),
            $statusData
        ));
    }

    /**
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

        if (! hash_equals(
            $expectedSignature,
            (string) ($notification['signature_key'] ?? '')
        )) {
            throw new InvalidMidtransSignatureException;
        }
    }

    // ── Internal: status ──────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyGatewayUpdate(Payment $payment, array $data): Payment
    {
        $transactionStatus = (string) ($data['transaction_status'] ?? '');

        if ($transactionStatus === '') {
            throw new InvalidArgumentException(
                'Missing Midtrans transaction_status.'
            );
        }

        $newStatus = $this->mapMidtransStatus(
            $transactionStatus,
            isset($data['fraud_status'])
                ? (string) $data['fraud_status']
                : null
        );

        $statusChanged = $newStatus !== null
            && $payment->transitionTo($newStatus);

        $payment->midtrans_transaction_id = $data['transaction_id']
            ?? $payment->midtrans_transaction_id;

        $payment->midtrans_status = $transactionStatus;

        if (blank($payment->method) && isset($data['payment_type'])) {
            $payment->method = (string) $data['payment_type'];
        }

        if (isset($data['payment_type'])) {
            // Channel mengikuti kanal aktual Midtrans, bukan pilihan UI checkout.
            $payment->payment_channel = $this->resolvePaymentChannel(
                (string) $data['payment_type'],
                $data,
                $payment->payment_channel,
            );
        }

        $payment->raw_notification = array_merge(
            $payment->raw_notification ?? [],
            $data
        );

        if ($newStatus === PaymentStatus::Paid) {
            $settlementTime = $data['settlement_time'] ?? $data['transaction_time'] ?? null;
            if ($settlementTime) {
                $payment->paid_at = Carbon::parse($settlementTime, 'Asia/Jakarta');
            } else {
                $payment->paid_at ??= now();
            }
        }

        $payment->save();

        if (! $statusChanged) {
            return $payment;
        }

        if ($newStatus === PaymentStatus::Paid) {
            $this->confirmRentalIfFirstPayment($payment);
        }

        $this->applyMonetization($payment);

        return $payment;
    }

    /**
     * Terapkan aturan escrow dan komisi setelah status pembayaran berubah.
     */
    public function applyMonetization(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Paid->value) {
            $this->escrow->markHeld($payment);
            $this->commissions->accrueForPayment($payment);

            return;
        }

        if ($payment->status === PaymentStatus::Refunded->value) {
            $this->escrow->markRefunded($payment);
            $this->commissions->voidForPayment($payment);

            return;
        }

        if (in_array($payment->status, [
            PaymentStatus::Failed->value,
            PaymentStatus::Expired->value,
            PaymentStatus::Cancelled->value,
        ], true)) {
            $this->escrow->markVoid($payment);
        }
    }

    /**
     * Petakan payment_type Midtrans ke channel internal yang dipakai laporan.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolvePaymentChannel(string $paymentType, array $data, ?string $fallback): ?string
    {
        if (in_array($paymentType, ['qris', 'other_qris'], true)) {
            return 'qris';
        }

        if ($paymentType !== 'bank_transfer') {
            return $fallback;
        }

        if (! empty($data['permata_va_number'])) {
            return 'va_permata';
        }

        $bank = strtolower((string) data_get($data, 'va_numbers.0.bank', ''));

        return array_key_exists($bank, config('payment.va_banks', []))
            ? 'va_'.$bank
            : $fallback;
    }

    private function mapMidtransStatus(
        string $transactionStatus,
        ?string $fraudStatus
    ): ?PaymentStatus {
        return match ($transactionStatus) {
            'settlement' => PaymentStatus::Paid,
            'capture' => match ($fraudStatus) {
                'accept' => PaymentStatus::Paid,
                'deny' => PaymentStatus::Failed,
                default => null,
            },
            'expire' => PaymentStatus::Expired,
            'cancel' => PaymentStatus::Cancelled,
            'deny', 'failure' => PaymentStatus::Failed,
            'refund', 'partial_refund' => PaymentStatus::Refunded,
            default => null,
        };
    }

    /**
     * Konfirmasi rental saat pembayaran paid pertama.
     *
     * Catatan alur bisnis: Setelah pembayaran lunas, status rental TETAP 'pending'
     * agar kasir toko dapat memvalidasi dan mencocokkan barcode unit fisik alat secara manual di POS.
     * Status rental baru berubah menjadi 'confirmed' (Tervalidasi / Siap Pickup) setelah kasir
     * menyelesaikan validasi barcode dan mengklik "Setujui Pesanan".
     */
    private function confirmRentalIfFirstPayment(Payment $payment): void
    {
        // Status rental tetap 'pending' sampai kasir menyetujui pesanan via POS
    }

    /**
     * Membuat order ID dengan prefix project.
     */
    public static function generateOrderId(int $paymentId): string
    {
        if ($paymentId <= 0) {
            throw new InvalidArgumentException(
                'Payment ID harus berupa angka positif.'
            );
        }

        return self::ORDER_ID_PREFIX.'-'.$paymentId;
    }

    /**
     * @param  array<string, string>  $notification
     */
    private function assertAmountMatches(
        Payment $payment,
        array $notification
    ): void {
        $paymentAmount = self::normalizeAmount((string) $payment->amount);
        $notificationAmount = self::normalizeAmount(
            (string) ($notification['gross_amount'] ?? '')
        );

        if (
            $paymentAmount !== null
            && $paymentAmount === $notificationAmount
        ) {
            return;
        }

        Log::warning('Midtrans webhook: nominal tidak cocok.', [
            'order_id' => $notification['order_id'] ?? null,
            'payment_amount' => $payment->amount,
            'notification_amount' => $notification['gross_amount'] ?? null,
        ]);

        throw new InvalidArgumentException(
            'The notification amount does not match the payment amount.'
        );
    }

    private static function normalizeAmount(string $amount): ?string
    {
        if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 2, '0');
    }

    // ── Internal: payload Midtrans ────────────────────────────────────────

    private function rentalFor(Payment $payment): Rental
    {
        $payment->loadMissing([
            'rental.details.masterItem',
            'rental.customer',
        ]);

        return $payment->rental;
    }

    /**
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

    private function buildItemDetails(Payment $payment, Rental $rental): array
    {
        $amount = (int) $payment->amount;
        $code = $rental->booking_code;

        if ($payment->payment_type === PaymentType::Dp->value) {
            $dpPct = app(PaymentService::class)
                ->resolveDpPercentage($rental->vendor?->dp_percentage);

            return [[
                'id' => "DP-{$code}",
                'price' => $amount,
                'quantity' => 1,
                'name' => "DP {$dpPct}% Sewa {$code}",
            ]];
        }

        if (
            $payment->payment_type === PaymentType::Full->value
            && $rental->details->isNotEmpty()
        ) {
            return $this->itemizedDetails($rental, $amount);
        }

        return [[
            'id' => "PAY-{$payment->getKey()}",
            'price' => $amount,
            'quantity' => 1,
            'name' => match ($payment->payment_type) {
                PaymentType::Remaining->value => "Pelunasan Sewa {$code}",
                PaymentType::Fine->value => "Denda Sewa {$code}",
                default => "Pembayaran Sewa {$code}",
            },
        ]];
    }

    private function itemizedDetails(Rental $rental, int $amount): array
    {
        $days = max(1, (int) $rental->rental_days);

        $items = $rental->details
            ->groupBy('master_item_id')
            ->map(fn ($group) => [
                'id' => "ITEM-{$group->first()->master_item_id}",
                'price' => (int) $group->first()->daily_rate_snapshot,
                'quantity' => $group->count() * $days,
                'name' => mb_substr(
                    (string) ($group->first()->masterItem?->name
                        ?? "Item #{$group->first()->master_item_id}"),
                    0,
                    50
                ),
            ])
            ->values()
            ->all();

        if ((int) $rental->delivery_fee > 0) {
            $items[] = [
                'id' => 'DELIVERY',
                'price' => (int) $rental->delivery_fee,
                'quantity' => 1,
                'name' => 'Ongkos Kirim',
            ];
        }

        if ((int) $rental->discount_amount > 0) {
            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -(int) $rental->discount_amount,
                'quantity' => 1,
                'name' => 'Diskon',
            ];
        }

        $itemTotal = array_sum(array_map(
            fn ($item) => $item['price'] * $item['quantity'],
            $items
        ));

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

    /**
     * Batalkan tagihan yang belum dibayar di Midtrans supaya VA/QRIS-nya
     * tidak bisa dibayar setelah pesanan dibatalkan. Gagal = hanya dicatat.
     */
    public function cancelTransaction(Payment $payment): bool
    {
        if (empty($payment->order_id)) {
            return false;
        }

        try {
            Transaction::cancel($payment->order_id);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Gagal membatalkan tagihan Midtrans saat pesanan dibatalkan.', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
