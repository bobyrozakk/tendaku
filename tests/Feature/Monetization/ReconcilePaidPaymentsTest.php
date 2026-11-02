<?php

namespace Tests\Feature\Monetization;

use App\Models\Payment;
use App\Models\PlatformCommission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class ReconcilePaidPaymentsTest extends TestCase
{
    use CreatesRevenueFixtures;
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_backfill_tops_up_commission_for_a_paid_remaining_payment(): void
    {
        Carbon::setTestNow('2026-10-30 10:00:00');

        $vendor = $this->createRevenueVendor('Vendor Rekonsiliasi');

        [$rental, $dpPayment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'payment_type' => 'dp',
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        $this->createRevenueCommission($dpPayment, 25000);

        $remainingPayment = Payment::query()->create([
            'rental_id' => $rental->id,
            'vendor_id' => $vendor->id,
            'payment_type' => 'remaining',
            'payment_method' => 'cash',
            'payment_channel' => 'cash',
            'amount' => 500000,
            'status' => 'paid',
            'escrow_status' => 'not_applicable',
            'paid_at' => Carbon::parse('2026-10-25 10:00:00'),
        ]);

        $this->artisan('payments:reconcile')
            ->assertSuccessful();

        $topUp = PlatformCommission::withoutGlobalScope('vendor')
            ->where('payment_id', $remainingPayment->id)
            ->first();

        $this->assertNotNull($topUp);
        $this->assertSame(25000, (int) $topUp->amount);

        $total = PlatformCommission::withoutGlobalScope('vendor')
            ->where('rental_id', $rental->id)
            ->sum('amount');

        $this->assertSame(50000.0, (float) $total);
    }

    public function test_backfill_is_idempotent_when_run_twice(): void
    {
        Carbon::setTestNow('2026-10-30 10:00:00');

        $vendor = $this->createRevenueVendor('Vendor Rekonsiliasi Idempoten');
        [, $payment] = $this->createPaidRevenuePayment($vendor, 1000000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(
            1,
            PlatformCommission::withoutGlobalScope('vendor')
                ->where('payment_id', $payment->id)
                ->count(),
        );
    }
}
