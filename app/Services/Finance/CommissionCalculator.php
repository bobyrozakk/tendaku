<?php

namespace App\Services\Finance;

use InvalidArgumentException;

class CommissionCalculator
{
    /**
     * Tipe pembayaran yang memicu komisi platform. Komisi dihitung dari total
     * sewa dan dicatat sekali per rental, pada pembayaran pertama (dp atau
     * full). Pelunasan (remaining) dan denda tidak dikenai komisi lagi.
     *
     * @var list<string>
     */
    public const COMMISSIONABLE_PAYMENT_TYPES = ['dp', 'full'];

    public function __construct(
        private readonly float $defaultRate,
        private readonly int $minimumCommission,
        private readonly int $qrisMdrThreshold,
        private readonly float $qrisMdrRateBelowThreshold,
        private readonly float $qrisMdrRateAboveThreshold,
    ) {
        $this->assertRate($defaultRate, 'default commission rate');
        $this->assertNonNegative($minimumCommission, 'minimum commission');
        $this->assertNonNegative($qrisMdrThreshold, 'QRIS MDR threshold');
        $this->assertRate($qrisMdrRateBelowThreshold, 'QRIS MDR rate below threshold');
        $this->assertRate($qrisMdrRateAboveThreshold, 'QRIS MDR rate above threshold');
    }

    public static function fromConfig(): self
    {
        return new self(
            defaultRate: (float) config('booking.admin_fee_rate'),
            minimumCommission: (int) config('booking.admin_fee_minimum'),
            qrisMdrThreshold: (int) config('monetization.qris.mdr_threshold'),
            qrisMdrRateBelowThreshold: (float) config('monetization.qris.mdr_rate_below_threshold'),
            qrisMdrRateAboveThreshold: (float) config('monetization.qris.mdr_rate_above_threshold'),
        );
    }

    /**
     * Hitung komisi berdasarkan tipe pembayaran. Denda, deposit, dan refund
     * tidak dikenai komisi.
     */
    public function commissionForPaymentType(
        string $paymentType,
        float $amount,
        ?float $vendorRate = null,
    ): int {
        if (! in_array($paymentType, self::COMMISSIONABLE_PAYMENT_TYPES, true)) {
            return 0;
        }

        return $this->commissionFor($amount, $vendorRate);
    }

    /**
     * Hitung komisi per pembayaran: max(round(P x r), minimum).
     */
    public function commissionFor(float $amount, ?float $vendorRate = null): int
    {
        $this->assertAmount($amount, 'payment amount');

        $rate = $this->rateFor($vendorRate);

        if ($amount <= 0 || $rate <= 0) {
            return 0;
        }

        $commission = (int) round($amount * $rate);

        return max($this->minimumCommission, $commission);
    }

    /**
     * Hitung MDR QRIS. Nominal pada atau di bawah ambang tidak dikenai MDR.
     */
    public function mdrFor(float $amount): int
    {
        $this->assertAmount($amount, 'QRIS amount');

        if ($amount <= 0) {
            return 0;
        }

        $rate = $amount <= $this->qrisMdrThreshold
            ? $this->qrisMdrRateBelowThreshold
            : $this->qrisMdrRateAboveThreshold;

        return (int) round($amount * $rate);
    }

    /**
     * Laba platform dari komisi setelah MDR dan biaya transfer.
     */
    public function platformNet(int $commission, int $mdr, int $transferFee = 0): int
    {
        return $commission - $mdr - $transferFee;
    }

    /**
     * Tarif efektif vendor: override vendor bila ada, jika tidak config global.
     */
    public function rateFor(?float $vendorRate = null): float
    {
        $rate = $vendorRate ?? $this->defaultRate;

        $this->assertRate($rate, 'commission rate');

        return $rate;
    }

    private function assertAmount(float $amount, string $label): void
    {
        if (! is_finite($amount) || $amount < 0) {
            throw new InvalidArgumentException("The {$label} must be a non-negative finite number.");
        }
    }

    private function assertRate(float $rate, string $label): void
    {
        if (! is_finite($rate) || $rate < 0 || $rate > 1) {
            throw new InvalidArgumentException("The {$label} must be between 0 and 1.");
        }
    }

    private function assertNonNegative(int $value, string $label): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException("The {$label} must not be negative.");
        }
    }
}
