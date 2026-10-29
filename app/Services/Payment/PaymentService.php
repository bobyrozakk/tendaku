<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PaymentService
{
    //  persen DP dari grand_total.
    public function defaultDpPercentage(): int
    {
        return (int) config('payment.dp_percentage', 50);
    }

    //  Nominal DP, dibulatkan ke atas agar selalu bilangan bulat rupiah.
    public function calculateDpAmount(int $grandTotal): int
    {
        return (int) ceil($grandTotal * $this->defaultDpPercentage() / 100);
    }

    //  Biaya admin (platform_fee) per pembayaran online.
    public function calculatePlatformFee(int $amount): int
    {
        $feeRate = (float) config('payment.platform_fee_rate', 0.02);
        $feeMin = (int) config('payment.platform_fee_min', 1000);

        $fee = max((int) ceil($amount * $feeRate), $feeMin);

        return min($fee, $amount);
    }

    //  order_id Midtrans unik berprefix TDK.
    //  Digit tipe: full=1, dp=2, remaining=3, fine=4, lainnya=9.
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

        return "TDK{$datePart}{$typeNumber}{$seqPart}-{$attempt}";
    }

    // ── Pembayaran online (Midtrans) ──────────────────────────────────────────

    /**
     * Payment pending untuk bayar penuh atau DP.
     * Nominal dihitung di server, tidak dari input browser.
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
        $amount = $paymentType === 'dp' ? $this->calculateDpAmount($grandTotal) : $grandTotal;

        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran harus lebih dari 0.');
        }

        if ($paymentType === 'dp') {
            $rental->update(['dp_amount' => $amount]);
        }

        return $this->createMidtransPayment($rental, $paymentType, $amount, $attempt, [
            'payment_channel' => $method,
            'method' => $method,
            'expires_at' => $rental->expires_at,
        ]);
    }

    //  Payment pending untuk pelunasan sisa via Midtrans.
    public function createRemainingPayment(Rental $rental, int $attempt = 1): Payment
    {
        return $this->createMidtransPayment(
            $rental, 'remaining', $this->remainingAmountOrFail($rental), $attempt
        );
    }

    //  Payment pending untuk denda via Midtrans.
    public function createOnlineFinePayment(Rental $rental, int $fineAmount, int $attempt = 1): Payment
    {
        $this->assertFineAmount($fineAmount);

        return $this->createMidtransPayment($rental, 'fine', $fineAmount, $attempt);
    }

    // ── Pembayaran tunai (kasir)

    public function createCashRemainingPayment(Rental $rental, int $receivedBy): Payment
    {
        return $this->createCashPayment(
            $rental, 'remaining', $this->remainingAmountOrFail($rental), $receivedBy
        );
    }

    /**
     * Denda tunai. Langsung ke vendor, tidak ikut payout.
     */
    public function createCashFinePayment(Rental $rental, int $fineAmount, int $receivedBy): Payment
    {
        $this->assertFineAmount($fineAmount);

        return $this->createCashPayment($rental, 'fine', $fineAmount, $receivedBy);
    }

    /**
     * Sisa tagihan = grand_total - total dp/full yang sudah paid.
     */
    public function calculateRemainingAmount(Rental $rental): int
    {
        $paidSoFar = (int) $rental->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->whereIn('payment_type', ['dp', 'full'])
            ->sum('amount');

        return max(0, (int) $rental->grand_total - $paidSoFar);
    }

    /**
     * Transisi status payment dan simpan (lihat PaymentStatus).
     *
     * @return bool true jika status berubah, false jika diabaikan (idempotent)
     */
    public function advanceStatus(Payment $payment, string $newStatus): bool
    {
        if (! $payment->transitionTo(PaymentStatus::from($newStatus))) {
            return false;
        }

        $payment->save();

        return true;
    }

    // ── Payout ────────────────────────────────────────────────────────────────

    /**
     * Ringkasan dana layak cair per vendor.
     *
     * @return Collection<int, object{vendor_id: int, dana_vendor: int, biaya_admin: int, net_payout: int}>
     */
    public function payoutEligibleSummary(): Collection
    {
        return DB::table('payments')
            ->join('rentals', 'payments.rental_id', '=', 'rentals.id')
            ->where('payments.status', PaymentStatus::Paid->value)
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
     * Tandai payments sebagai bagian dari payout.
     * Harus dipanggil di dalam DB::transaction() oleh kode pencairan.
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

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $extra  atribut tambahan (mis. method, expires_at)
     */
    private function createMidtransPayment(
        Rental $rental,
        string $paymentType,
        int $amount,
        int $attempt,
        array $extra = []
    ): Payment {
        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => $paymentType,
            'channel' => 'midtrans',
            'amount' => $amount,
            'order_id' => $this->generateOrderId($rental->booking_code, $paymentType, $attempt),
            'platform_fee' => $this->rentalHasFirstOnlinePayment($rental) ? 0 : $this->calculatePlatformFee($amount),
            'status' => PaymentStatus::Pending->value,
        ] + $extra);
    }

    //  Tunai langsung lunas setelah kasir konfirmasi; tidak kena platform fee
    private function createCashPayment(Rental $rental, string $paymentType, int $amount, int $receivedBy): Payment
    {
        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => $paymentType,
            'channel' => 'cash',
            'method' => 'cash',
            'amount' => $amount,
            'platform_fee' => 0,
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
            'received_by' => $receivedBy,
        ]);
    }

    //  Fee hanya untuk pembayaran online pertama yang berhasil per rental
    private function rentalHasFirstOnlinePayment(Rental $rental): bool
    {
        return $rental->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->where('channel', 'midtrans')
            ->exists();
    }

    private function remainingAmountOrFail(Rental $rental): int
    {
        $remaining = $this->calculateRemainingAmount($rental);

        if ($remaining <= 0) {
            throw new RuntimeException('Tidak ada sisa yang perlu dilunasi.');
        }

        return $remaining;
    }

    private function assertFineAmount(int $fineAmount): void
    {
        if ($fineAmount <= 0) {
            throw new InvalidArgumentException('Nominal denda harus lebih dari 0.');
        }
    }
}
