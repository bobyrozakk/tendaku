<?php

namespace App\Services;

use App\Exceptions\InvalidMidtransSignatureException;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

/**
 * Integrasi Midtrans: Snap token, validasi signature webhook, dan pemrosesan notifikasi.
 *
 * @see docs/TENDAKU_payment_spec.md §7.2, §7.3
 */
class MidtransService
{
    /**
     * Prefix wajib untuk semua order_id Midtrans project ini (§0).
     * Akun sandbox pernah dipakai project lain; prefix memisahkan log dashboard.
     */
    public const ORDER_ID_PREFIX = 'TDK-';

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

    /**
     * Buat Snap token untuk payment yang diberikan.
     *
     * Wajib: order_id harus ada di payment (dari PaymentService::createOnlinePayment).
     * Snap token dikembalikan dan disimpan ke kolom snap_token untuk keperluan retry (§7.2).
     *
     * @throws InvalidArgumentException
     */
    public function createTransaction(Payment $payment): string
    {
        if (! $payment->exists || ! $payment->getKey()) {
            throw new InvalidArgumentException('A saved payment is required to create a Midtrans transaction.');
        }

        $amount = (int) $payment->amount;
        if ($amount <= 0) {
            throw new InvalidArgumentException('The payment amount must be a positive whole number.');
        }

        $orderId = $payment->order_id;
        if (empty($orderId)) {
            throw new InvalidArgumentException('Payment belum memiliki order_id. Panggil PaymentService::createOnlinePayment terlebih dahulu.');
        }

        // Pastikan order_id ber-prefix TDK- (§0)
        if (! str_starts_with($orderId, self::ORDER_ID_PREFIX)) {
            throw new InvalidArgumentException("order_id harus ber-prefix 'TDK-'. Ditemukan: {$orderId}");
        }

        // Muat relasi jika belum tersedia
        $payment->loadMissing(['rental.details.masterItem', 'rental.customer']);

        $rental = $payment->rental;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => $this->buildItemDetails($payment, $rental),
            'customer_details' => $this->buildCustomerDetails($rental),
            'enabled_payments' => config('payment.enabled_channels', [
                'qris',
                'bank_transfer',
                'bca_va',
                'bni_va',
                'bri_va',
                'echannel',
                'permata_va',
                'cimb_va',
                'other_va',
            ]),
        ];

        // Sesuaikan expiry dengan rentals.expires_at (§7.2)
        if ($rental?->expires_at) {
            $expiryDuration = now()->diffInMinutes($rental->expires_at, false);
            if ($expiryDuration > 0) {
                $params['expiry'] = [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'minutes',
                    'duration' => (int) $expiryDuration,
                ];
            }
        }

        $snapToken = Snap::getSnapToken($params);

        // Simpan snap_token untuk retry tanpa memanggil Midtrans lagi
        $payment->update(['snap_token' => $snapToken]);

        return $snapToken;
    }

    /**
     * Sinkronisasi status pembayaran langsung dari API Midtrans (§0, §7.1).
     *
     * Digunakan untuk mengecek status secara aktif saat polling di halaman status
     * atau saat webhook tertunda/belum sampai di sandbox lokal.
     *
     * @throws InvalidArgumentException
     */
    public function syncStatus(Payment $payment): Payment
    {
        if (empty($payment->order_id)) {
            throw new InvalidArgumentException('Payment belum memiliki order_id.');
        }

        try {
            $response = Transaction::status($payment->order_id);
            $statusData = json_decode(json_encode($response), true);
        } catch (\Throwable $e) {
            Log::info('Gagal sinkronisasi status transaksi dari Midtrans: '.$e->getMessage(), [
                'order_id' => $payment->order_id,
            ]);

            return $payment;
        }

        if (! is_array($statusData) || empty($statusData['transaction_status'])) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $statusData): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()
                ->where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $transactionStatus = (string) $statusData['transaction_status'];
            $internalStatus = $this->mapMidtransStatus(
                $transactionStatus,
                isset($statusData['fraud_status']) ? (string) $statusData['fraud_status'] : null
            );

            $statusChanged = false;
            if ($internalStatus !== null) {
                $statusChanged = $this->applyStatusTransition($locked, $internalStatus, $statusData);
            }

            $locked->midtrans_transaction_id = $statusData['transaction_id'] ?? $locked->midtrans_transaction_id;
            $locked->midtrans_status = $transactionStatus;

            if (empty($locked->method) && isset($statusData['payment_type'])) {
                $locked->method = (string) $statusData['payment_type'];
            }

            $locked->raw_notification = $statusData;
            $locked->save();

            if ($locked->status === 'paid' && $statusChanged) {
                $this->confirmRentalIfFirstPayment($locked);
            }

            return $locked;
        });
    }

    /**
     * Validasi dan proses notifikasi webhook Midtrans (§7.3).
     *
     * Alur:
     * 1. Verifikasi signature (constant-time, beda → exception)
     * 2. Cari payment by order_id (tidak ada → exception)
     * 3. Lock baris dengan FOR UPDATE
     * 4. Validasi gross_amount
     * 5. Terapkan status (hanya maju, idempotent)
     * 6. Simpan raw_notification
     * 7. Jika pertama kali paid → konfirmasi rental
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
            /** @var Payment $payment */
            $payment = Payment::query()
                ->where('order_id', $notification['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Validasi nominal (§7.3 langkah 5)
            $paymentAmount = self::normalizeAmount((string) $payment->amount);
            $notificationAmount = self::normalizeAmount((string) $notification['gross_amount']);

            if ($paymentAmount === null || $notificationAmount === null || $paymentAmount !== $notificationAmount) {
                Log::warning('Midtrans webhook: nominal tidak cocok.', [
                    'order_id' => $notification['order_id'],
                    'payment_amount' => $payment->amount,
                    'notification_amount' => $notification['gross_amount'],
                ]);
                throw new InvalidArgumentException('The notification amount does not match the payment amount.');
            }

            // Simpan raw_notification untuk audit (§7.3 langkah 7)
            $payment->raw_notification = $notification;

            // Petakan status Midtrans ke status internal (§5.2)
            $internalStatus = $this->mapMidtransStatus(
                $notification['transaction_status'],
                $notification['fraud_status'] ?? null
            );

            $statusChanged = false;

            if ($internalStatus !== null) {
                $statusChanged = $this->applyStatusTransition($payment, $internalStatus, $notification);
            }

            $payment->midtrans_transaction_id = $notification['transaction_id'] ?? $payment->midtrans_transaction_id;
            $payment->midtrans_status = $notification['transaction_status'];

            // Isi method dari payment_type Midtrans (§6.1)
            if (empty($payment->method) && isset($notification['payment_type'])) {
                $payment->method = $notification['payment_type'];
            }

            $payment->save();

            // Jika pertama kali paid: rental → confirmed (§7.3 langkah 7, §5.4)
            if ($payment->status === 'paid' && $statusChanged) {
                $this->confirmRentalIfFirstPayment($payment);
            }

            return $payment;
        });
    }

    /**
     * Verifikasi signature webhook (§7.3 langkah 2).
     *
     * SHA512(order_id + status_code + gross_amount + ServerKey)
     * Gunakan hash_equals (constant-time) untuk mencegah timing attack (§11).
     *
     * @param  array<string, string>  $notification
     *
     * @throws InvalidMidtransSignatureException
     */
    public function verifySignature(array $notification): void
    {
        $serverKey = config('services.midtrans.server_key');
        $expectedSignature = hash(
            'sha512',
            $notification['order_id'].$notification['status_code'].$notification['gross_amount'].$serverKey,
        );

        if (! hash_equals($expectedSignature, $notification['signature_key'])) {
            throw new InvalidMidtransSignatureException;
        }
    }

    /**
     * Pemetaan transaction_status Midtrans → status internal (§5.2).
     *
     * Mengembalikan null jika status tidak memerlukan perubahan (mis. pending).
     */
    private function mapMidtransStatus(string $transactionStatus, ?string $fraudStatus): ?string
    {
        return match (true) {
            // settlement = sukses; capture + accept = sukses (§5.2)
            $transactionStatus === 'settlement' => 'paid',
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'paid',

            // capture + challenge → tetap pending (tidak ubah status)
            $transactionStatus === 'capture' && $fraudStatus === 'challenge' => null,

            // capture + deny = failed
            $transactionStatus === 'capture' && $fraudStatus === 'deny' => 'failed',

            // expire = expired (§5.2)
            $transactionStatus === 'expire' => 'expired',

            // cancel = cancelled
            $transactionStatus === 'cancel' => 'cancelled',

            // deny, failure = failed
            in_array($transactionStatus, ['deny', 'failure'], true) => 'failed',

            // refund, partial_refund = refunded (§5.2)
            in_array($transactionStatus, ['refund', 'partial_refund'], true) => 'refunded',

            // pending → tidak ada perubahan internal (§5.1)
            default => null,
        };
    }

    /**
     * Terapkan transisi status (hanya maju, idempotent) (§5.1).
     *
     * Mengembalikan true jika status benar-benar berubah.
     */
    private function applyStatusTransition(Payment $payment, string $newStatus, array $notification): bool
    {
        // Urutan kemajuan status
        $statusOrder = ['pending' => 0, 'paid' => 1, 'expired' => 2, 'cancelled' => 2, 'failed' => 2, 'refunded' => 3];
        $currentRank = $statusOrder[$payment->status] ?? -1;
        $newRank = $statusOrder[$newStatus] ?? -1;

        // Status sudah sama atau lebih maju → idempotent, abaikan (§7.3 langkah 6)
        if ($newRank <= $currentRank && $payment->status !== 'pending') {
            return false;
        }

        // Status sudah final (bukan pending) → jangan turunkan (§5.1)
        if (in_array($payment->status, ['paid', 'expired', 'cancelled', 'failed'], true)
            && $newStatus !== 'refunded') {
            return false;
        }

        $payment->status = $newStatus;

        if ($newStatus === 'paid') {
            $payment->paid_at ??= now();
        }

        return true;
    }

    /**
     * Konfirmasi rental menjadi 'confirmed' saat pembayaran pertama berhasil (§5.4, §7.3).
     *
     * Rental hanya perlu dikonfirmasi sekali (dari status pending_payment / pending).
     */
    private function confirmRentalIfFirstPayment(Payment $payment): void
    {
        /** @var Rental|null $rental */
        $rental = Rental::query()->find($payment->rental_id);
        if ($rental === null) {
            return;
        }

        // Konfirmasi jika belum ada pembayaran sukses sebelumnya (pembayaran pertama)
        $paidCount = Payment::query()
            ->where('rental_id', $rental->id)
            ->where('status', 'paid')
            ->count();

        // Jika ini pembayaran paid pertama (baru saja diubah = 1)
        if ($paidCount === 1 && $rental->status === 'pending') {
            $rental->update(['status' => 'confirmed']);
        }
    }

    /**
     * Bangun item_details untuk Snap (§7.2).
     *
     * Total item_details HARUS sama dengan gross_amount (validasi Midtrans).
     */
    private function buildItemDetails(Payment $payment, ?Rental $rental): array
    {
        $amount = (int) $payment->amount;

        // DP: cukup satu baris ringkas (§7.2)
        if ($payment->payment_type === 'dp') {
            $dpPct = (int) config('payment.dp_percentage', 50);

            return [[
                'id' => "DP-{$rental?->booking_code}",
                'price' => $amount,
                'quantity' => 1,
                'name' => "DP {$dpPct}% Sewa {$rental?->booking_code}",
            ]];
        }

        // Full / remaining / fine: rincian per item (jika relasi tersedia)
        if ($rental?->details && $rental->details->isNotEmpty() && $payment->payment_type === 'full') {
            $items = $rental->details->map(fn ($detail) => [
                'id' => "ITEM-{$detail->master_item_id}",
                'price' => (int) $detail->daily_rate_snapshot,
                'quantity' => $rental->rental_days,
                'name' => $detail->masterItem?->name ?? "Item #{$detail->master_item_id}",
            ])->toArray();

            // Rekonsiliasi total agar selalu sama dengan gross_amount
            $itemTotal = array_sum(array_column($items, 'price'));
            $diff = $amount - ($itemTotal * ($items[0]['quantity'] ?? 1));
            if ($diff !== 0 && ! empty($items)) {
                $items[] = [
                    'id' => 'ADJ',
                    'price' => $diff,
                    'quantity' => 1,
                    'name' => 'Penyesuaian Harga',
                ];
            }

            return $items;
        }

        // Fallback: satu baris
        return [[
            'id' => "PAY-{$payment->getKey()}",
            'price' => $amount,
            'quantity' => 1,
            'name' => match ($payment->payment_type) {
                'remaining' => "Pelunasan Sewa {$rental?->booking_code}",
                'fine' => "Denda Sewa {$rental?->booking_code}",
                default => "Pembayaran Sewa {$rental?->booking_code}",
            },
        ]];
    }

    /**
     * Bangun customer_details untuk Snap (§7.2).
     */
    private function buildCustomerDetails(?Rental $rental): array
    {
        if ($rental === null) {
            return [];
        }

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
     * Normalisasi nominal ke format standar untuk perbandingan (mencegah mismatch float).
     */
    private static function normalizeAmount(string $amount): ?string
    {
        if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 2, '0');
    }
}
