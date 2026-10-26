<?php

namespace Tests\Feature\Monetization;

use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\User;
use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-26 10:00:00');

        $this->admin = $this->createRevenueUser('super_admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_weekly_draft_only_contains_eligible_payments_for_the_period(): void
    {
        $vendor = $this->createRevenueVendor('Vendor Settlement');

        [, $qrisPayment] = $this->createPaidRevenuePayment($vendor, 1000000, [
            'booking_code' => 'TND-SET-001',
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
            'gateway_fee_amount' => 7000,
        ]);
        $this->createRevenueCommission($qrisPayment, 50000, [
            'base_amount' => 1000000,
            'recognized_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        [, $cashPayment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'booking_code' => 'TND-SET-002',
            'payment_method' => 'cash',
            'payment_channel' => null,
            'escrow_status' => 'not_applicable',
            'gateway_fee_amount' => 0,
            'paid_at' => Carbon::parse('2026-10-21 10:00:00'),
        ]);
        $this->createRevenueCommission($cashPayment, 25000, [
            'base_amount' => 500000,
            'recognized_at' => Carbon::parse('2026-10-21 10:00:00'),
        ]);

        [, $finePayment] = $this->createPaidRevenuePayment($vendor, 200000, [
            'booking_code' => 'TND-SET-003',
            'payment_type' => 'fine',
            'gateway_fee_amount' => 1400,
            'paid_at' => Carbon::parse('2026-10-22 10:00:00'),
        ]);

        // Tidak eligible: belum lunas, di luar minggu, dan sudah masuk batch lain.
        $this->createPaidRevenuePayment($vendor, 300000, [
            'booking_code' => 'TND-SET-PENDING',
            'status' => 'pending',
            'paid_at' => null,
            'escrow_status' => 'not_applicable',
        ]);
        $this->createPaidRevenuePayment($vendor, 400000, [
            'booking_code' => 'TND-SET-OLD',
            'paid_at' => Carbon::parse('2026-10-12 10:00:00'),
        ]);
        $otherSettlement = Settlement::factory()->create([
            'vendor_id' => $vendor->id,
            'period_start' => '2026-10-12',
            'period_end' => '2026-10-18',
        ]);
        $this->createPaidRevenuePayment($vendor, 650000, [
            'booking_code' => 'TND-SET-ASSIGNED',
            'paid_at' => Carbon::parse('2026-10-23 10:00:00'),
            'settlement_id' => $otherSettlement->id,
        ]);

        $created = app(SettlementService::class)->buildWeeklyDraft(Carbon::parse('2026-10-19'));

        $this->assertCount(1, $created);

        $settlement = $created->first();

        $this->assertSame($vendor->id, $settlement->vendor_id);
        $this->assertSame('2026-10-19', $settlement->period_start->toDateString());
        $this->assertSame('2026-10-25', $settlement->period_end->toDateString());
        $this->assertSame('draft', $settlement->status);
        $this->assertSame(1000000.0, (float) $settlement->gross_qris);
        $this->assertSame(200000.0, (float) $settlement->gross_fine_qris);
        $this->assertSame(75000.0, (float) $settlement->commission_total);
        $this->assertSame(25000.0, (float) $settlement->cash_commission_offset);
        $this->assertSame(7000.0, (float) $settlement->gateway_fee_total);
        $this->assertSame(1400.0, (float) $settlement->fine_fee_total);
        $this->assertSame(3500.0, (float) $settlement->transfer_fee);
        $this->assertSame(1123600.0, (float) $settlement->net_payout);

        $this->assertSame(3, SettlementItem::withoutGlobalScope('vendor')->count());
        $this->assertDatabaseHas('settlement_items', [
            'settlement_id' => $settlement->id,
            'payment_id' => $qrisPayment->id,
            'type' => 'payment',
        ]);
        $this->assertDatabaseHas('settlement_items', [
            'settlement_id' => $settlement->id,
            'payment_id' => $cashPayment->id,
            'type' => 'cash_commission',
        ]);
        $this->assertDatabaseHas('settlement_items', [
            'settlement_id' => $settlement->id,
            'payment_id' => $finePayment->id,
            'type' => 'fine',
        ]);
    }

    public function test_weekly_draft_skips_uncompleted_rentals_unheld_escrow_and_cash_fines(): void
    {
        $vendor = $this->createRevenueVendor('Vendor Eligibility');

        [, $notCompleted] = $this->createPaidRevenuePayment($vendor, 300000, [
            'booking_code' => 'TND-NOT-DONE',
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ], ['status' => 'picked_up']);

        [, $notHeld] = $this->createPaidRevenuePayment($vendor, 400000, [
            'booking_code' => 'TND-NOT-HELD',
            'escrow_status' => 'released',
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        [, $cashFine] = $this->createPaidRevenuePayment($vendor, 100000, [
            'booking_code' => 'TND-CASH-FINE',
            'payment_type' => 'fine',
            'payment_method' => 'cash',
            'payment_channel' => null,
            'escrow_status' => 'not_applicable',
            'gateway_fee_amount' => 0,
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        [, $eligible] = $this->createPaidRevenuePayment($vendor, 500000, [
            'booking_code' => 'TND-ELIGIBLE',
            'paid_at' => Carbon::parse('2026-10-21 10:00:00'),
        ]);
        $this->createRevenueCommission($eligible, 25000, [
            'base_amount' => 500000,
            'recognized_at' => Carbon::parse('2026-10-21 10:00:00'),
        ]);

        $created = app(SettlementService::class)->buildWeeklyDraft(Carbon::parse('2026-10-19'));

        $this->assertCount(1, $created);

        $settlement = $created->first();

        $this->assertSame(500000.0, (float) $settlement->gross_qris);
        $this->assertSame(0.0, (float) $settlement->gross_fine_qris);
        $this->assertSame(25000.0, (float) $settlement->commission_total);
        $this->assertSame(475000.0, (float) $settlement->net_payout);
        $this->assertSame(1, SettlementItem::withoutGlobalScope('vendor')->count());

        foreach ([$notCompleted, $notHeld, $cashFine] as $payment) {
            $this->assertDatabaseMissing('settlement_items', [
                'settlement_id' => $settlement->id,
                'payment_id' => $payment->id,
            ]);
        }
    }

    public function test_building_the_same_week_twice_does_not_create_duplicate_batches(): void
    {
        $vendor = $this->createRevenueVendor();
        [, $payment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($payment, 25000);

        $service = app(SettlementService::class);

        $first = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'));
        $second = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'));

        $this->assertCount(1, $first);
        $this->assertCount(0, $second);
        $this->assertSame(1, Settlement::withoutGlobalScope('vendor')->count());
        $this->assertSame(1, SettlementItem::withoutGlobalScope('vendor')->count());
    }

    public function test_approve_locks_the_batch_but_keeps_escrow_held_until_payment(): void
    {
        $vendor = $this->createRevenueVendor();
        [, $payment] = $this->createPaidRevenuePayment($vendor, 1000000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $commission = $this->createRevenueCommission($payment, 50000);

        $settlement = app(SettlementService::class)
            ->buildWeeklyDraft(Carbon::parse('2026-10-19'))
            ->first();

        $this->assertTrue(app(SettlementService::class)->approve($settlement, $this->admin));

        $settlement->refresh();
        $payment->refresh();
        $commission->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame($this->admin->id, $settlement->approved_by);
        $this->assertNotNull($settlement->approved_at);
        $this->assertSame($settlement->id, $payment->settlement_id);
        $this->assertSame('held', $payment->escrow_status);
        $this->assertSame('settled', $commission->status);
        $this->assertSame($settlement->id, $commission->settlement_id);
    }

    public function test_marking_paid_stores_proof_releases_escrow_and_issues_an_invoice(): void
    {
        $vendor = $this->createRevenueVendor();
        [, $payment] = $this->createPaidRevenuePayment($vendor, 1000000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($payment, 50000);

        $service = app(SettlementService::class);
        $settlement = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'))->first();
        $service->approve($settlement, $this->admin);

        $invoice = $service->markPaid(
            $settlement,
            $this->admin,
            'TRX-2026-001',
            '/storage/settlements/proofs/bukti.jpg',
            'Transfer BCA',
        );

        $this->assertNotNull($invoice);

        $settlement->refresh();
        $payment->refresh();

        $this->assertSame('paid', $settlement->status);
        $this->assertSame('TRX-2026-001', $settlement->reference_number);
        $this->assertSame('/storage/settlements/proofs/bukti.jpg', $settlement->proof_url);
        $this->assertNotNull($settlement->paid_at);
        $this->assertSame('released', $payment->escrow_status);

        $this->assertSame('INV/'.$vendor->id.'/202610/1', $invoice->number);
        $this->assertSame('issued', $invoice->status);
        $this->assertSame((float) $settlement->net_payout, (float) $invoice->total);
        $this->assertSame((float) $settlement->commission_total, (float) $invoice->commission_amount);
        $this->assertNotNull($invoice->issued_at);
    }

    public function test_marking_a_draft_as_paid_is_rejected(): void
    {
        $vendor = $this->createRevenueVendor();
        [, $payment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($payment, 25000);

        $service = app(SettlementService::class);
        $settlement = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'))->first();

        $invoice = $service->markPaid($settlement, $this->admin, 'REF-1', '/proof.jpg');

        $this->assertNull($invoice);
        $this->assertSame('draft', $settlement->refresh()->status);
        $this->assertSame('held', $payment->refresh()->escrow_status);
        $this->assertSame(0, $settlement->invoices()->count());
    }

    public function test_negative_cash_commission_balance_is_carried_to_the_next_batch(): void
    {
        $vendor = $this->createRevenueVendor();

        [, $cashPayment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'booking_code' => 'TND-CASH-W1',
            'payment_method' => 'cash',
            'payment_channel' => null,
            'escrow_status' => 'not_applicable',
            'gateway_fee_amount' => 0,
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($cashPayment, 25000, [
            'base_amount' => 500000,
            'recognized_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        $service = app(SettlementService::class);

        $first = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'))->first();
        $this->assertSame(-25000.0, (float) $first->net_payout);
        $service->approve($first, $this->admin);
        $service->markPaid($first, $this->admin, 'CASH-W1', '/proof-w1.jpg');

        [, $qrisPayment] = $this->createPaidRevenuePayment($vendor, 1000000, [
            'booking_code' => 'TND-QRIS-W2',
            'paid_at' => Carbon::parse('2026-10-27 10:00:00'),
        ]);
        $this->createRevenueCommission($qrisPayment, 50000, [
            'base_amount' => 1000000,
            'recognized_at' => Carbon::parse('2026-10-27 10:00:00'),
        ]);

        $second = $service->buildWeeklyDraft(Carbon::parse('2026-10-26'))->first();

        $this->assertNotNull($second);
        $this->assertSame(-25000.0, (float) $second->adjustment_amount);
        $this->assertSame(925000.0, (float) $second->net_payout);
        $this->assertStringContainsString('Carry-forward', (string) $second->notes);
        $this->assertDatabaseHas('settlement_items', [
            'settlement_id' => $second->id,
            'type' => 'adjustment',
            'net_amount' => -25000,
        ]);
    }

    public function test_command_generates_the_week_draft_and_can_be_re_run_safely(): void
    {
        $vendor = $this->createRevenueVendor();
        [, $payment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($payment, 25000);

        $this->artisan('monetization:generate-settlements', ['--week' => '2026-10-21'])
            ->assertSuccessful();

        $this->assertSame(1, Settlement::withoutGlobalScope('vendor')->count());

        $this->artisan('monetization:generate-settlements', ['--week' => '2026-10-21'])
            ->assertSuccessful();

        $this->assertSame(1, Settlement::withoutGlobalScope('vendor')->count());
        $this->assertSame(1, SettlementItem::withoutGlobalScope('vendor')->count());
    }

    public function test_cancelled_batch_returns_payments_and_commissions_to_the_next_draft(): void
    {
        $vendor = $this->createRevenueVendor();
        [, $payment] = $this->createPaidRevenuePayment($vendor, 500000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $commission = $this->createRevenueCommission($payment, 25000);

        $service = app(SettlementService::class);
        $settlement = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'))->first();
        $service->approve($settlement, $this->admin);

        $this->assertTrue($service->cancel($settlement));

        $this->assertSame('cancelled', $settlement->refresh()->status);
        $this->assertNull($payment->refresh()->settlement_id);
        $this->assertSame('accrued', $commission->refresh()->status);
        $this->assertNull($commission->settlement_id);
    }
}
