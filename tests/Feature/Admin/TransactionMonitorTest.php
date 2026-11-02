<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SettlementManager;
use App\Livewire\Admin\TransactionMonitor;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Services\Finance\MonetizationMonitorService;
use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class TransactionMonitorTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    private User $superAdmin;

    private User $vendorOwner;

    private User $vendorAdmin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-26 10:00:00');

        $this->superAdmin = $this->createRevenueUser('super_admin');
        $this->vendorOwner = $this->createRevenueUser('vendor_owner');
        $this->vendorAdmin = $this->createRevenueUser('vendor_admin');
        $this->customer = $this->createRevenueUser('customer');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_superadmin_login(): void
    {
        $this->get(route('admin.monitoring'))
            ->assertRedirect(route('superadmin.login'));
    }

    public function test_only_superadmin_can_open_the_transaction_monitor(): void
    {
        foreach ([$this->vendorOwner, $this->vendorAdmin, $this->customer] as $user) {
            $this->actingAs($user)
                ->get(route('admin.monitoring'))
                ->assertForbidden();
        }

        $this->actingAs($this->superAdmin)
            ->get(route('admin.monitoring'))
            ->assertOk()
            ->assertSee('Monitoring Transaksi')
            ->assertSee('Alur Transaksi Customer');
    }

    public function test_monitor_shows_every_stage_from_payment_to_payout(): void
    {
        $fixtures = $this->seedLifecycleFixtures();

        $summary = app(MonetizationMonitorService::class)->summary();

        $this->assertSame(1, $summary['counts']['awaiting_payment']);
        $this->assertSame(1, $summary['counts']['paid_in_progress']);
        $this->assertSame(1, $summary['counts']['ready_settlement']);
        $this->assertSame(1, $summary['counts']['in_settlement']);
        $this->assertSame(1, $summary['counts']['awaiting_transfer']);
        $this->assertSame(1, $summary['counts']['paid_out']);
        $this->assertSame(1, $summary['counts']['cancelled']);
        $this->assertSame(1, $summary['awaiting_transfer_batches']);
        $this->assertSame(1, $summary['paid_out_batches']);
        $this->assertSame(23750.0, $summary['commission_accrued']);

        Livewire::actingAs($this->superAdmin)
            ->test(TransactionMonitor::class)
            ->assertSee($fixtures['codes']['pending'])
            ->assertSee($fixtures['codes']['process'])
            ->assertSee($fixtures['codes']['ready'])
            ->assertSee($fixtures['codes']['draft'])
            ->assertSee($fixtures['codes']['approved'])
            ->assertSee($fixtures['codes']['paid'])
            ->assertSee($fixtures['codes']['cancelled'])
            ->assertSee('Accrued')
            ->assertSee('Masuk Batch')
            ->assertSee($fixtures['approved_invoice']->number)
            ->assertSee(route('admin.settlements.invoice', $fixtures['settlements']['approved']))
            ->assertSee(route('admin.settlements', ['settlement' => $fixtures['settlements']['paid']->id]));
    }

    public function test_stage_search_and_vendor_filters_only_show_matching_rentals(): void
    {
        $fixtures = $this->seedLifecycleFixtures();

        Livewire::actingAs($this->superAdmin)
            ->test(TransactionMonitor::class)
            ->set('stage', 'awaiting_transfer')
            ->assertSee($fixtures['codes']['approved'])
            ->assertDontSee($fixtures['codes']['ready'])
            ->assertDontSee($fixtures['codes']['pending'])
            ->set('stage', '')
            ->set('search', $fixtures['codes']['ready'])
            ->assertSee($fixtures['codes']['ready'])
            ->assertDontSee($fixtures['codes']['pending'])
            ->set('search', '')
            ->set('vendorId', $fixtures['vendors']['draft']->id)
            ->assertSee($fixtures['codes']['draft'])
            ->assertDontSee($fixtures['codes']['ready']);
    }

    public function test_monitoring_link_opens_the_selected_settlement_batch(): void
    {
        $fixtures = $this->seedLifecycleFixtures();

        Livewire::withQueryParams([
            'settlement' => $fixtures['settlements']['approved']->id,
        ])
            ->actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->assertSet('selectedSettlementId', $fixtures['settlements']['approved']->id);
    }

    /**
     * @return array{
     *     codes: array<string, string>,
     *     vendors: array<string, Vendor>,
     *     settlements: array<string, Settlement>,
     *     approved_invoice: VendorInvoice
     * }
     */
    private function seedLifecycleFixtures(): array
    {
        $settlementService = app(SettlementService::class);

        $pendingVendor = $this->createRevenueVendor('Vendor Menunggu');
        $this->createPaidRevenuePayment($pendingVendor, 100000, [
            'booking_code' => 'TDK-MON-PENDING',
            'status' => 'pending',
            'paid_at' => null,
            'escrow_status' => 'not_applicable',
        ], [
            'status' => 'pending',
            'completed_at' => null,
        ]);

        $processVendor = $this->createRevenueVendor('Vendor Proses');
        [, $processPayment] = $this->createPaidRevenuePayment($processVendor, 150000, [
            'booking_code' => 'TDK-MON-PROCESS',
            'paid_at' => Carbon::now()->subDay(),
        ], [
            'status' => 'confirmed',
            'completed_at' => null,
        ]);
        $this->createRevenueCommission($processPayment, 5000);

        $draftVendor = $this->createRevenueVendor('Vendor Draft');
        [, $draftPayment] = $this->createPaidRevenuePayment($draftVendor, 200000, [
            'booking_code' => 'TDK-MON-DRAFT',
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($draftPayment, 10000);

        $approvedVendor = $this->createRevenueVendor('Vendor Approve');
        [, $approvedPayment] = $this->createPaidRevenuePayment($approvedVendor, 250000, [
            'booking_code' => 'TDK-MON-APPROVE',
            'paid_at' => Carbon::parse('2026-10-20 11:00:00'),
        ]);
        $this->createRevenueCommission($approvedPayment, 12500);

        $paidVendor = $this->createRevenueVendor('Vendor Paid');
        [, $paidPayment] = $this->createPaidRevenuePayment($paidVendor, 300000, [
            'booking_code' => 'TDK-MON-PAID',
            'paid_at' => Carbon::parse('2026-10-20 12:00:00'),
        ]);
        $this->createRevenueCommission($paidPayment, 15000);

        $cancelledVendor = $this->createRevenueVendor('Vendor Batal');
        $this->createPaidRevenuePayment($cancelledVendor, 120000, [
            'booking_code' => 'TDK-MON-CANCEL',
            'status' => 'cancelled',
            'paid_at' => null,
            'escrow_status' => 'not_applicable',
        ], [
            'status' => 'cancelled',
            'completed_at' => null,
        ]);

        $drafts = $settlementService->buildWeeklyDraft(Carbon::parse('2026-10-19 00:00:00'));

        $this->assertCount(3, $drafts);

        $settlements = Settlement::withoutGlobalScope('vendor')
            ->whereIn('vendor_id', [
                $draftVendor->id,
                $approvedVendor->id,
                $paidVendor->id,
            ])
            ->get()
            ->keyBy('vendor_id');

        $draftSettlement = $settlements->get($draftVendor->id);
        $approvedSettlement = $settlements->get($approvedVendor->id);
        $paidSettlement = $settlements->get($paidVendor->id);

        $this->assertNotNull($draftSettlement);
        $this->assertNotNull($approvedSettlement);
        $this->assertNotNull($paidSettlement);

        $this->assertTrue($settlementService->approve($approvedSettlement, $this->superAdmin));
        $this->assertTrue($settlementService->approve($paidSettlement, $this->superAdmin));

        $approvedInvoice = $approvedSettlement->refresh()->invoices()->firstOrFail();
        $paidInvoice = $settlementService->markPaid(
            $paidSettlement,
            $this->superAdmin,
            'REF-MON-PAID',
            'proofs/mon-paid.jpg',
        );

        $this->assertNotNull($paidInvoice);

        $readyVendor = $this->createRevenueVendor('Vendor Siap');
        [, $readyPayment] = $this->createPaidRevenuePayment($readyVendor, 175000, [
            'booking_code' => 'TDK-MON-READY',
        ]);
        $this->createRevenueCommission($readyPayment, 8750);

        return [
            'codes' => [
                'pending' => 'TDK-MON-PENDING',
                'process' => 'TDK-MON-PROCESS',
                'ready' => 'TDK-MON-READY',
                'draft' => 'TDK-MON-DRAFT',
                'approved' => 'TDK-MON-APPROVE',
                'paid' => 'TDK-MON-PAID',
                'cancelled' => 'TDK-MON-CANCEL',
            ],
            'vendors' => [
                'draft' => $draftVendor,
                'approved' => $approvedVendor,
                'paid' => $paidVendor,
                'ready' => $readyVendor,
            ],
            'settlements' => [
                'draft' => $draftSettlement,
                'approved' => $approvedSettlement,
                'paid' => $paidSettlement,
            ],
            'approved_invoice' => $approvedInvoice,
        ];
    }
}
