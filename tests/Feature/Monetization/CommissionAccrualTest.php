<?php

namespace Tests\Feature\Monetization;

use App\Models\PlatformCommission;
use App\Services\Finance\CommissionService;
use App\Services\Finance\EscrowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

class CommissionAccrualTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-server-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    public function test_a_paid_dp_payment_also_accrues_a_commission(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'payment_type' => 'dp',
            'amount' => 500000,
            'payment_channel' => 'qris',
        ]);

        $this->postJson(route('webhook.midtrans'), $this->signedNotification([
            'gross_amount' => '500000.00',
            'payment_type' => 'qris',
        ]))->assertOk();

        $commission = PlatformCommission::withoutGlobalScope('vendor')->sole();

        $this->assertSame('25000.00', $commission->amount);
        $this->assertSame('accrued', $commission->status);
    }

    public function test_duplicate_settlement_notification_does_not_double_the_commission(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'settlement']);

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();
        $paidAt = $payment->refresh()->paid_at;

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();

        $this->assertSame(1, PlatformCommission::withoutGlobalScope('vendor')->count());
        $this->assertSame($paidAt->toDateTimeString(), $payment->refresh()->paid_at->toDateTimeString());
    }

    public function test_vendor_commission_rate_override_is_used_for_the_snapshot(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $rental->vendor->update(['commission_rate' => 0.03]);
        $payment->update(['amount' => 500000, 'payment_channel' => 'qris']);

        $this->postJson(route('webhook.midtrans'), $this->signedNotification([
            'gross_amount' => '500000.00',
            'payment_type' => 'qris',
        ]))->assertOk();

        $commission = PlatformCommission::withoutGlobalScope('vendor')->sole();

        $this->assertSame('0.0300', $commission->rate);
        $this->assertSame('15000.00', $commission->amount);
    }

    public function test_fine_payment_is_not_commissioned_but_its_escrow_is_held(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'payment_type' => 'fine',
            'amount' => 200000,
            'payment_channel' => 'qris',
        ]);

        $this->postJson(route('webhook.midtrans'), $this->signedNotification([
            'gross_amount' => '200000.00',
            'payment_type' => 'qris',
        ]))->assertOk();

        $this->assertSame(0, PlatformCommission::withoutGlobalScope('vendor')->count());
        $this->assertSame('held', $payment->refresh()->escrow_status);
        $this->assertSame('1400.00', $payment->gateway_fee_amount);
    }

    public function test_refund_voids_the_commission_and_refunds_the_escrow(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update(['amount' => 500000, 'payment_channel' => 'qris']);

        $this->postJson(route('webhook.midtrans'), $this->signedNotification([
            'gross_amount' => '500000.00',
            'payment_type' => 'qris',
        ]))->assertOk();

        $this->postJson(route('webhook.midtrans'), $this->signedNotification([
            'gross_amount' => '500000.00',
            'payment_type' => 'qris',
            'transaction_status' => 'refund',
            'transaction_id' => 'sandbox-refund',
        ]))->assertOk();

        $commission = PlatformCommission::withoutGlobalScope('vendor')->sole();
        $payment->refresh();

        $this->assertSame('refunded', $payment->status);
        $this->assertSame('refunded', $payment->escrow_status);
        $this->assertSame('void', $commission->status);
        $this->assertNotNull($commission->voided_at);
    }

    public function test_expired_payment_voids_the_escrow_without_a_commission(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();

        $this->postJson(route('webhook.midtrans'), $this->signedNotification([
            'transaction_status' => 'expire',
        ]))->assertOk();

        $this->assertSame(0, PlatformCommission::withoutGlobalScope('vendor')->count());
        $this->assertSame('expired', $payment->refresh()->status);
        $this->assertSame('void', $payment->escrow_status);
        $this->assertSame('pending', $rental->refresh()->status);
    }

    public function test_cash_payment_is_not_held_in_escrow(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update(['payment_method' => 'cash', 'status' => 'paid', 'paid_at' => now()]);

        $this->assertFalse(app(EscrowService::class)->markHeld($payment));
        $this->assertSame('not_applicable', $payment->refresh()->escrow_status);
    }

    public function test_a_pending_payment_does_not_accrue_a_commission(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();

        $this->assertNull(app(CommissionService::class)->accrueForPayment($payment));
        $this->assertSame(0, PlatformCommission::withoutGlobalScope('vendor')->count());
    }

    public function test_a_voided_commission_is_not_revived_by_a_repeated_webhook(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'settlement']);

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();
        app(CommissionService::class)->voidForPayment($payment->refresh());

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();

        $commission = PlatformCommission::withoutGlobalScope('vendor')->sole();

        $this->assertSame('void', $commission->status);
    }

    public function test_a_settled_commission_is_not_voided_by_a_refund(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $commission = PlatformCommission::factory()->create([
            'payment_id' => $payment->getKey(),
            'status' => 'settled',
        ]);

        $this->assertFalse(app(CommissionService::class)->voidForPayment($payment));
        $this->assertSame('settled', $commission->refresh()->status);
        $this->assertNull($commission->voided_at);
    }
}
