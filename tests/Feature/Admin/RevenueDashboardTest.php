<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RevenueDashboard;
use App\Models\User;
use App\Models\Vendor;
use App\Services\RevenueReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class RevenueDashboardTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    private User $superAdmin;

    private User $vendorOwner;

    private User $vendorAdmin;

    private User $customer;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = $this->createRevenueVendor('Vendor Utama');

        $this->superAdmin = $this->createRevenueUser('super_admin');
        $this->vendorOwner = $this->createRevenueUser('vendor_owner', $this->vendor);
        $this->vendorAdmin = $this->createRevenueUser('vendor_admin', $this->vendor);
        $this->customer = $this->createRevenueUser('customer');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_superadmin_login(): void
    {
        $this->get(route('admin.pendapatan'))
            ->assertRedirect(route('superadmin.login'));
    }

    public function test_superadmin_can_access_the_revenue_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.pendapatan'))
            ->assertOk()
            ->assertSee('Laporan Pendapatan Platform')
            ->assertSee('Tren Komisi Platform')
            ->assertSee('Ringkasan per Vendor')
            ->assertSee('Export CSV');
    }

    public function test_non_superadmin_roles_are_forbidden_from_the_revenue_dashboard(): void
    {
        foreach ([$this->vendorOwner, $this->vendorAdmin, $this->customer] as $user) {
            $this->actingAs($user)
                ->get(route('admin.pendapatan'))
                ->assertForbidden();
        }
    }

    public function test_example_numbers_match_the_prd_commission_and_platform_net(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        [, $dpPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            500000,
            [
                'payment_type' => 'dp',
                'gateway_fee_amount' => 3500,
                'booking_code' => 'TND-REV-001',
                'paid_at' => Carbon::now(),
            ],
        );
        [, $settlementPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            700000,
            [
                'payment_type' => 'settlement',
                'gateway_fee_amount' => 4900,
                'booking_code' => 'TND-REV-002',
                'paid_at' => Carbon::now(),
            ],
        );

        $this->createRevenueCommission($dpPayment, 25000, ['base_amount' => 500000]);
        $this->createRevenueCommission($settlementPayment, 35000, ['base_amount' => 700000]);

        $service = app(RevenueReportService::class);
        $filters = $service->normalizeFilters(['period' => 'this_month']);
        $summary = $service->getSummary($filters);
        $kpis = $service->getKpis($filters);

        $this->assertSame(1200000.0, $summary['current']['gross_revenue']);
        $this->assertSame(60000.0, $summary['current']['commission']);
        $this->assertSame(8400.0, $summary['current']['gateway_fee_total']);
        $this->assertSame(3500.0, $summary['current']['transfer_fee_total']);
        $this->assertSame(48100.0, $summary['current']['platform_net']);
        $this->assertSame(60000.0, $kpis['commission_this_month']);

        Livewire::actingAs($this->superAdmin)
            ->test(RevenueDashboard::class)
            ->assertSee('Rp 60.000')
            ->assertSee('Rp 48.100')
            ->assertSee('TND-REV-001')
            ->assertSee('TND-REV-002')
            ->assertSee($this->vendor->name);
    }

    public function test_vendor_filter_recalculates_kpis_chart_and_tables(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        [, $vendorOnePayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            [
                'gateway_fee_amount' => 8400,
                'booking_code' => 'TND-REV-001',
            ],
        );
        $this->createRevenueCommission($vendorOnePayment, 60000, ['base_amount' => 1200000]);

        $otherVendor = $this->createRevenueVendor('Vendor Kedua');
        [, $vendorTwoPayment] = $this->createPaidRevenuePayment(
            $otherVendor,
            300000,
            [
                'gateway_fee_amount' => 2100,
                'booking_code' => 'TND-REV-002',
            ],
        );
        $this->createRevenueCommission($vendorTwoPayment, 15000, ['base_amount' => 300000]);

        Livewire::actingAs($this->superAdmin)
            ->test(RevenueDashboard::class)
            ->assertSee('Rp 75.000')
            ->assertSee('TND-REV-001')
            ->assertSee('TND-REV-002')
            ->set('selectedVendorId', $otherVendor->id)
            ->assertSet('selectedVendorId', $otherVendor->id)
            ->assertSee('Rp 15.000')
            ->assertSee('TND-REV-002')
            ->assertDontSee('TND-REV-001')
            ->assertDontSee('Rp 75.000')
            ->set('selectedVendorId', '')
            ->assertSet('selectedVendorId', null)
            ->assertSee('Rp 75.000');
    }

    public function test_method_escrow_and_payment_type_filters_are_applied(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        [, $qrisPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            [
                'gateway_fee_amount' => 8400,
                'booking_code' => 'TND-QRIS-001',
            ],
        );
        $this->createRevenueCommission($qrisPayment, 60000, ['base_amount' => 1200000]);

        [, $cashPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            500000,
            [
                'payment_type' => 'dp',
                'payment_method' => 'cash',
                'payment_channel' => null,
                'gateway_fee_amount' => 0,
                'escrow_status' => 'not_applicable',
                'booking_code' => 'TND-CASH-001',
            ],
        );
        $this->createRevenueCommission($cashPayment, 25000, ['base_amount' => 500000]);

        [, $finePayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            200000,
            [
                'payment_type' => 'fine',
                'gateway_fee_amount' => 1400,
                'booking_code' => 'TND-FINE-001',
            ],
        );
        unset($finePayment);

        Livewire::actingAs($this->superAdmin)
            ->test(RevenueDashboard::class)
            ->set('paymentMethod', 'cash')
            ->assertSee('Rp 25.000')
            ->assertSee('TND-CASH-001')
            ->assertDontSee('TND-QRIS-001')
            ->set('paymentMethod', '')
            ->set('escrowStatus', 'held')
            ->assertSee('TND-QRIS-001')
            ->assertSee('TND-FINE-001')
            ->assertDontSee('TND-CASH-001')
            ->set('escrowStatus', '')
            ->set('paymentType', 'fine')
            ->assertSee('TND-FINE-001')
            ->assertDontSee('TND-QRIS-001')
            ->assertDontSee('TND-CASH-001');
    }

    public function test_chart_series_has_zero_filled_buckets_and_uses_the_filtered_commission(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        [, $payment] = $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            ['booking_code' => 'TND-REV-001'],
        );
        $this->createRevenueCommission($payment, 60000, ['base_amount' => 1200000]);

        $service = app(RevenueReportService::class);
        $filters = $service->normalizeFilters(['period' => 'this_month']);
        $series = $service->getCommissionSeries('daily', $filters);

        $this->assertSame('daily', $series['granularity']);
        $this->assertCount(30, $series['points']);
        $this->assertSame(60000.0, $series['total']);
        $this->assertSame(60000.0, $series['points'][29]['value']);
        $this->assertSame(0.0, $series['points'][0]['value']);
    }

    public function test_vendor_summaries_are_calculated_without_n_plus_one_queries(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        [, $payment] = $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            ['gateway_fee_amount' => 8400, 'booking_code' => 'TND-REV-001'],
        );
        $this->createRevenueCommission($payment, 60000, ['base_amount' => 1200000]);

        $service = app(RevenueReportService::class);
        $filters = $service->normalizeFilters(['period' => 'this_month']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rows = $service->getVendorSummaries($filters);

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
        $this->assertCount(1, $rows);

        $row = $rows->first();

        $this->assertSame($this->vendor->id, $row['vendor_id']);
        $this->assertSame(1200000.0, $row['gmv']);
        $this->assertSame(60000.0, $row['commission_total']);
        $this->assertSame(1200000.0, $row['escrow_held']);
        $this->assertSame(1140000.0, $row['payout_estimate']);
        $this->assertSame(0.0, $row['cash_receivable']);
    }

    public function test_summary_compares_the_selected_period_with_the_previous_period(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        [, $currentPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            ['gateway_fee_amount' => 8400, 'booking_code' => 'TND-REV-NOW'],
        );
        $this->createRevenueCommission($currentPayment, 60000, ['base_amount' => 1200000]);

        [, $previousPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            600000,
            [
                'gateway_fee_amount' => 4200,
                'booking_code' => 'TND-REV-PREV',
                'paid_at' => Carbon::parse('2026-09-10 10:00:00'),
            ],
        );
        $this->createRevenueCommission($previousPayment, 30000, [
            'base_amount' => 600000,
            'recognized_at' => Carbon::parse('2026-09-10 10:00:00'),
        ]);

        $service = app(RevenueReportService::class);
        $filters = $service->normalizeFilters(['period' => 'this_month']);
        $summary = $service->getSummary($filters);

        $this->assertSame(60000.0, $summary['current']['commission']);
        $this->assertSame(30000.0, $summary['previous']['commission']);
        $this->assertSame(100.0, $summary['delta']['commission']);
    }
}
