<?php

namespace Tests\Feature\Monetization;

use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Models\Rental;
use App\Models\Vendor;
use App\Services\CommissionCalculator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

class PlatformCommissionTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    public function test_schema_has_the_monetization_columns_and_commission_table(): void
    {
        $this->assertTrue(Schema::hasColumns('payments', [
            'payment_channel',
            'gateway_fee_amount',
            'escrow_status',
            'settlement_id',
        ]));
        $this->assertTrue(Schema::hasColumn('vendors', 'commission_rate'));
        $this->assertTrue(Schema::hasTable('platform_commissions'));
    }

    public function test_platform_commission_factory_creates_a_complete_record(): void
    {
        $commission = PlatformCommission::factory()->create();

        $this->assertSame($commission->payment->rental_id, $commission->rental_id);
        $this->assertSame($commission->payment->vendor_id, $commission->vendor_id);
        $this->assertSame('100000.00', $commission->base_amount);
        $this->assertSame('5000.00', $commission->amount);
    }

    public function test_platform_commission_records_the_payment_snapshot_and_relations(): void
    {
        [$customer, $rental, $payment] = $this->createRentalWithPendingPayment();

        $commission = $this->recordCommission($payment);

        $this->assertSame('10000.00', $commission->base_amount);
        $this->assertSame('0.0500', $commission->rate);
        $this->assertSame('2500.00', $commission->amount);
        $this->assertTrue($commission->payment->is($payment));
        $this->assertTrue($commission->rental->is($rental));
        $this->assertTrue($commission->vendor->is($payment->vendor));
        $this->assertTrue($payment->commission->is($commission));
        $this->assertTrue($rental->commissions->contains($commission));
        $this->assertTrue($payment->vendor->commissions->contains($commission));
    }

    public function test_platform_commissions_are_scoped_to_the_active_vendor(): void
    {
        [$customer, $rental, $payment] = $this->createRentalWithPendingPayment();
        $commission = $this->recordCommission($payment);

        $otherVendor = Vendor::query()->create([
            'name' => 'Other Vendor',
            'slug' => 'other-vendor',
            'phone' => '081200000002',
            'address' => 'Other address',
            'status' => 'active',
        ]);

        $otherRental = Rental::query()->create([
            'vendor_id' => $otherVendor->getKey(),
            'user_id' => $customer->getKey(),
            'booking_code' => 'TDK-TEST-002',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'rental_days' => 1,
            'subtotal' => 20000,
            'grand_total' => 20000,
            'status' => 'pending',
        ]);

        $otherPayment = Payment::query()->create([
            'rental_id' => $otherRental->getKey(),
            'vendor_id' => $otherVendor->getKey(),
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => 20000,
            'midtrans_order_id' => 'TENDA-INV-002',
            'status' => 'pending',
        ]);

        $otherCommission = $this->recordCommission($otherPayment);

        $this->assertSame(2, PlatformCommission::query()->count());

        $this->actingAsVendor($payment->vendor);

        $this->assertSame(1, PlatformCommission::query()->count());
        $this->assertTrue(PlatformCommission::query()->first()->is($commission));
        $this->assertFalse(PlatformCommission::query()->whereKey($otherCommission->getKey())->exists());
    }

    public function test_calculator_is_resolved_from_the_monetization_config(): void
    {
        config([
            'monetization.commission.rate' => 0.03,
            'monetization.commission.minimum' => 1000,
        ]);

        $calculator = $this->app->make(CommissionCalculator::class);

        $this->assertSame(30000, $calculator->commissionFor(1000000));
    }

    private function recordCommission(Payment $payment): PlatformCommission
    {
        return PlatformCommission::query()->create([
            'payment_id' => $payment->getKey(),
            'rental_id' => $payment->rental_id,
            'vendor_id' => $payment->vendor_id,
            'base_amount' => $payment->amount,
            'rate' => 0.05,
            'amount' => 2500,
            'status' => 'accrued',
            'recognized_at' => now(),
        ]);
    }
}
