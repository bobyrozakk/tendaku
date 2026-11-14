<?php

namespace App\Services\Finance;

use App\Enums\PaymentType;
use InvalidArgumentException;

class CommissionCalculator
{
    /**
     * Tipe pembayaran yang membentuk basis komisi rental. Komisi dihitung
     * secara inkremental per rental agar minimum hanya berlaku sekali.
     *
     * @var list<string>
     */
    public const COMMISSIONABLE_PAYMENT_TYPES = ['full', 'dp', 'remaining'];

    public function __construct(
        private readonly float $defaultRate,
        private readonly int $minimumCommission,
    ) {
        $this->assertRate($defaultRate, 'default commission rate');
        $this->assertNonNegative($minimumCommission, 'minimum commission');
    }

    public static function fromConfig(): self
    {
        return new self(
            defaultRate: (float) config('monetization.commission.rate', 0.05),
            minimumCommission: (int) config('monetization.commission.minimum', 2500),
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
     * Hitung kekurangan komisi untuk mencapai tarif penuh atas total rental.
     *
     * Dipakai saat pelunasan: pembayaran pertama memakai minimum, lalu
     * pembayaran berikutnya hanya menambah selisih sampai total komisi
     * setara max(5% x total rental, minimum).
     */
    public function incrementalCommissionFor(
        float $totalRentalPaid,
        float $alreadyAccrued,
        ?float $vendorRate = null,
    ): int {
        $target = $this->commissionFor($totalRentalPaid, $vendorRate);

        return max(0, $target - (int) round($alreadyAccrued));
    }

    /**
     * @return list<string>
     */
    public static function commissionablePaymentTypes(): array
    {
        return PaymentType::rentalValues();
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
