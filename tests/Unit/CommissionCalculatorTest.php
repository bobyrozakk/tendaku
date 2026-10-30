<?php

namespace Tests\Unit;

use App\Services\Finance\CommissionCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    public function test_commission_is_five_percent_for_a_500_000_payment(): void
    {
        $this->assertSame(25000, $this->calculator()->commissionFor(500000));
    }

    public function test_commission_is_the_minimum_at_the_50_000_boundary(): void
    {
        $this->assertSame(2500, $this->calculator()->commissionFor(50000));
    }

    public function test_commission_is_the_minimum_for_a_10_000_payment(): void
    {
        $this->assertSame(2500, $this->calculator()->commissionFor(10000));
    }

    public function test_commission_is_five_percent_for_a_100_000_payment(): void
    {
        $this->assertSame(5000, $this->calculator()->commissionFor(100000));
    }

    public function test_commission_rounds_99_999_to_5_000(): void
    {
        $this->assertSame(5000, $this->calculator()->commissionFor(99999));
    }

    public function test_a_vendor_rate_override_replaces_the_default_rate(): void
    {
        $this->assertSame(15000, $this->calculator()->commissionFor(500000, 0.03));
    }

    public function test_a_zero_rate_results_in_zero_commission(): void
    {
        $this->assertSame(0, $this->calculator()->commissionFor(500000, 0.0));
    }

    public function test_a_zero_amount_results_in_zero_commission(): void
    {
        $this->assertSame(0, $this->calculator()->commissionFor(0));
    }

    public function test_a_negative_amount_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->commissionFor(-1);
    }

    public function test_a_rate_outside_the_zero_to_one_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->commissionFor(500000, 1.01);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonCommissionablePaymentTypes(): array
    {
        return [
            'denda' => ['fine'],
            'deposit' => ['deposit'],
            'pengembalian deposit' => ['deposit_refund'],
            'refund' => ['refund'],
        ];
    }

    #[DataProvider('nonCommissionablePaymentTypes')]
    public function test_non_commissionable_payment_types_yield_zero_commission(string $paymentType): void
    {
        $this->assertSame(
            0,
            $this->calculator()->commissionForPaymentType($paymentType, 500000),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function commissionablePaymentTypes(): array
    {
        return [
            'dp' => ['dp'],
            'pelunasan' => ['settlement'],
        ];
    }

    #[DataProvider('commissionablePaymentTypes')]
    public function test_dp_and_settlement_payments_are_commissionable(string $paymentType): void
    {
        $this->assertSame(
            25000,
            $this->calculator()->commissionForPaymentType($paymentType, 500000),
        );
    }

    public function test_mdr_is_zero_at_the_100_000_threshold(): void
    {
        $this->assertSame(0, $this->calculator()->mdrFor(100000));
    }

    public function test_mdr_is_700_for_100_001(): void
    {
        $this->assertSame(700, $this->calculator()->mdrFor(100001));
    }

    public function test_mdr_is_7_000_for_1_000_000(): void
    {
        $this->assertSame(7000, $this->calculator()->mdrFor(1000000));
    }

    public function test_platform_net_subtracts_mdr_and_transfer_fee(): void
    {
        $this->assertSame(48100, $this->calculator()->platformNet(60000, 8400, 3500));
    }

    private function calculator(): CommissionCalculator
    {
        return new CommissionCalculator(
            defaultRate: 0.05,
            minimumCommission: 2500,
            qrisMdrThreshold: 100000,
            qrisMdrRateBelowThreshold: 0.0,
            qrisMdrRateAboveThreshold: 0.007,
        );
    }
}
