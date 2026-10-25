<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Menangani logika pembayaran: kalkulasi nominal, pembuatan payment record,
 * pembuatan order_id, dan transisi status (hanya maju, tidak mundur).
 *
 * @see docs/TENDAKU_payment_spec.md §7.1, §4.3, §5.1, §7.2
 */
class PaymentService
{
    /**
     * Persen DP default (D4 — belum final, diatur dari config agar mudah diubah).
     * Gunakan config('payment.dp_percentage', 50) di produksi.
     */
    public function defaultDpPercentage(): int
    {
        return (int) config('payment.dp_percentage', 50);
    }

    /**
     * Hitung nominal DP berdasarkan grand_total rental.
     * Dibulatkan ke atas agar selalu bilangan bulat (rupiah).
     */
    public function calculateDpAmount(int $grandTotal): int
    {
        $percentage = $this->defaultDpPercentage();

        return (int) ceil($grandTotal * $percentage / 100);
    }

    /**
     * Kalkulasi biaya admin (platform_fee) per pembayaran online.
     *
     * Rumus: rate% dari amount, minimum Rp1.000, maks tidak melebihi amount (§4.10, D1).
     * Fee hanya dikenakan pada pembayaran online PERTAMA per rental (D3).
     */
    public function calculatePlatformFee(int $amount): int
    {
        $feeRate = (float) config('payment.platform_fee_rate', 0.02);
        $feeMin = (int) config('payment.platform_fee_min', 1000);

        $fee = (int) ceil($amount * $feeRate);
        $fee = max($fee, $feeMin);

        return min($fee, $amount);
    }

    /**
     * Apakah rental sudah pernah punya pembayaran online yang berhasil (paid)?
     * Digunakan untuk fee sekali per rental (D3).
     */
    private function rentalHasFirstOnlinePayment(Rental $rental): bool
    {
        return $rental->payments()
            ->where('status', 'paid')
            ->where('channel', 'midtrans')
            ->exists();
    }

    /**   Buat order_id Midtrans yang unik dengan prefix TDK-.
     *     'full'      => '1', fULL
     *     'dp'        => '2', dP
     *     'remaining' => '3', pelunasan
     *     'fine'      => '4', denda
     */
    public function generateOrderId(string $bookingCode, string $paymentType, int $attempt = 1): string
    {
        $cleanCode = str_replace(['TDK-', 'TDK'], '', strtoupper($bookingCode));

        $datePart = substr($cleanCode, 0, 8);
        $seqPart = substr($cleanCode, 8);

        $typeNumber = match ($paymentType) {
            'full' => '1',
            'dp' => '2',
            'remaining' => '3',
            'fine' => '4',
            default => '9',
        };

        $orderId = "TDK{$datePart}{$typeNumber}{$seqPart}-{$attempt}";

        return $orderId;
    }

    /**
     * Buat payment record baru untuk pembayaran via Midtrans (full atau DP).
     *
     * Nominal dihitung di sini (server-side), tidak dari input browser (§0).
     * Status awal selalu 'pending' (§5.1).
     *
     * @param  'full'|'dp'  $paymentType
     */
    public function createOnlinePayment(
        Rental $rental,
        string $paymentType,
        int $attempt = 1,
        ?string $method = null
    ): Payment {
        if (! in_array($paymentType, ['full', 'dp'], true)) {
            throw new InvalidArgumentException("payment_type harus 'full' atau 'dp' untuk pembayaran online.");
        }

        $grandTotal = (int) $rental->grand_total;

        if ($paymentType === 'dp') {
            $amount = $this->calculateDpAmount($grandTotal);
            // Simpan DP amount ke rental
            $rental->update(['dp_amount' => $amount]);
        } else {
            $amount = $grandTotal;
        }

        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran harus lebih dari 0.');
        }

        $orderId = $this->generateOrderId($rental->booking_code, $paymentType, $attempt);

        // Fee hanya dari pembayaran online pertama per rental (D3 - contract.md §4.10)
        $platformFee = $this->rentalHasFirstOnlinePayment($rental)
            ? 0
            : $this->calculatePlatformFee($amount);

        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => $paymentType,
            'channel' => 'midtrans',
            'method' => $method,
            'amount' => $amount,
            'order_id' => $orderId,
            'platform_fee' => $platformFee,
            'status' => 'pending',
            'expires_at' => $rental->expires_at,
        ]);
    }

    /**
     * Hitung sisa tagihan yang belum dibayar untuk rental (grand_total - total paid).
     */
    public function calculateRemainingAmount(Rental $rental): int
    {
        $grandTotal = (int) $rental->grand_total;
        $paidSoFar = (int) $rental->payments()
            ->where('status', 'paid')
            ->whereIn('payment_type', ['dp', 'full'])
            ->sum('amount');

        return max(0, $grandTotal - $paidSoFar);
    }

    /**
     * Buat payment record untuk pelunasan sisa (remaining) di kasir via QRIS.
     * Digunakan oleh FinePaymentController / kasir endpoint.
     */
    public function createRemainingPayment(Rental $rental, int $attempt = 1): Payment
    {
        $remaining = $this->calculateRemainingAmount($rental);

        if ($remaining <= 0) {
            throw new RuntimeException('Tidak ada sisa yang perlu dilunasi.');
        }

        $orderId = $this->generateOrderId($rental->booking_code, 'remaining', $attempt);
        $platformFee = $this->rentalHasFirstOnlinePayment($rental)
            ? 0
            : $this->calculatePlatformFee($remaining);

        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'remaining',
            'channel' => 'midtrans',
            'amount' => $remaining,
            'order_id' => $orderId,
            'platform_fee' => $platformFee,
            'status' => 'pending',
        ]);
    }

    /**
     * Buat payment record untuk pelunasan sisa secara tunai (kasir).
     *
     * Tunai → langsung ke vendor, tidak masuk payout platform (§4.2).
     */
    public function createCashRemainingPayment(Rental $rental, int $receivedBy): Payment
    {
        $remaining = $this->calculateRemainingAmount($rental);

        if ($remaining <= 0) {
            throw new RuntimeException('Tidak ada sisa yang perlu dilunasi.');
        }

        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'remaining',
            'channel' => 'cash',
            'method' => 'cash',
            'amount' => $remaining,
            'platform_fee' => 0,           // tunai tidak masuk platform (§4.2)
            'status' => 'paid',      // tunai langsung lunas setelah kasir konfirmasi
            'paid_at' => now(),
            'received_by' => $receivedBy,
        ]);
    }

    /**
     * Buat payment record denda tunai (kasir).
     * Fine tunai → langsung ke vendor, tidak ikut payout (§4.2).
     */
    public function createCashFinePayment(Rental $rental, int $fineAmount, int $receivedBy): Payment
    {
        if ($fineAmount <= 0) {
            throw new InvalidArgumentException('Nominal denda harus lebih dari 0.');
        }

        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'cash',
            'method' => 'cash',
            'amount' => $fineAmount,
            'platform_fee' => 0,
            'status' => 'paid',
            'paid_at' => now(),
            'received_by' => $receivedBy,
        ]);
    }

    /**
     * Buat payment record denda via QRIS (kasir).
     * Fine QRIS → masuk platform, ikut payout (§4.2).
     *
     * @param  'fine'  $type
     */
    public function createOnlineFinePayment(Rental $rental, int $fineAmount, int $attempt = 1): Payment
    {
        if ($fineAmount <= 0) {
            throw new InvalidArgumentException('Nominal denda harus lebih dari 0.');
        }

        $orderId = $this->generateOrderId($rental->booking_code, 'fine', $attempt);
        $platformFee = $this->rentalHasFirstOnlinePayment($rental)
            ? 0
            : $this->calculatePlatformFee($fineAmount);

        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'amount' => $fineAmount,
            'order_id' => $orderId,
            'platform_fee' => $platformFee,
            'status' => 'pending',
        ]);
    }

    /**
     * Transisi status payment (hanya maju, tidak bisa mundur) (§5.1).
     *
     * Urutan maju: pending → paid → refunded
     *              pending → expired / cancelled / failed (final)
     *
     * Mengembalikan true jika status berubah, false jika sudah lebih maju (idempotent).
     */
    public function advanceStatus(Payment $payment, string $newStatus): bool
    {
        $order = ['pending' => 0, 'paid' => 1, 'expired' => 2, 'cancelled' => 2, 'failed' => 2, 'refunded' => 3];

        $currentRank = $order[$payment->status] ?? 0;
        $newRank = $order[$newStatus] ?? 0;

        // Tidak boleh mundur; jika sama, idempotent (tidak ubah, kembalikan false)
        if ($newRank <= $currentRank && $payment->status !== 'pending') {
            return false;
        }

        // Bila status sudah final (bukan pending), tolak perubahan
        if (in_array($payment->status, ['paid', 'expired', 'cancelled', 'failed', 'refunded'], true)
            && $newStatus !== 'refunded') {
            return false;
        }

        $payment->status = $newStatus;

        if ($newStatus === 'paid') {
            $payment->paid_at ??= now();
        }

        $payment->save();

        return true;
    }

    /**
     * Sumber data untuk payoutEligible (kontrak ke Boby, §9).
     *
     * Kriteria:
     * - status = paid
     * - channel = midtrans
     * - vendor_payout_id IS NULL (belum dicairkan)
     * - rental.status = picked_up (layak cair)
     *
     * Dipakai Boby untuk menghitung dana yang siap dicairkan.
     */
    public function payoutEligibleQuery(): Builder
    {
        return Payment::query()
            ->where('payments.status', 'paid')
            ->where('payments.channel', 'midtrans')
            ->whereNull('payments.vendor_payout_id')
            ->whereHas('rental', fn ($q) => $q->where('status', 'picked_up'));
    }

    /**
     * Hitung ringkasan dana layak cair per vendor.
     *
     * Mengembalikan array ['vendor_id', 'dana_vendor', 'biaya_admin', 'net_payout'].
     *
     * @return Collection<int, object{vendor_id: int, dana_vendor: int, biaya_admin: int, net_payout: int}>
     */
    public function payoutEligibleSummary(): Collection
    {
        return DB::table('payments')
            ->join('rentals', 'payments.rental_id', '=', 'rentals.id')
            ->where('payments.status', 'paid')
            ->where('payments.channel', 'midtrans')
            ->whereNull('payments.vendor_payout_id')
            ->where('rentals.status', 'picked_up')
            ->groupBy('payments.vendor_id')
            ->select([
                'payments.vendor_id',
                DB::raw('SUM(payments.amount) as dana_vendor'),
                DB::raw('SUM(payments.platform_fee) as biaya_admin'),
                DB::raw('SUM(payments.amount) - SUM(payments.platform_fee) as net_payout'),
            ])
            ->get();
    }

    /**
     * Tandai payments sebagai bagian dari payout (dalam transaksi DB, §9).
     *
     * Harus dipanggil dalam DB::transaction() oleh kode pencairan Boby.
     *
     * @param  array<int>  $paymentIds
     */
    public function markPaymentsAsPaidOut(array $paymentIds, int $vendorPayoutId): void
    {
        if (empty($paymentIds)) {
            throw new InvalidArgumentException('Tidak ada payment yang dapat ditandai sebagai payout.');
        }

        Payment::query()
            ->whereIn('id', $paymentIds)
            ->whereNull('vendor_payout_id')
            ->update(['vendor_payout_id' => $vendorPayoutId]);
    }
}
