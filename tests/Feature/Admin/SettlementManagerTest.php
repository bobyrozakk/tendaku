<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SettlementManager;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class SettlementManagerTest extends TestCase
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

        Carbon::setTestNow('2026-10-26 10:00:00');

        $this->vendor = $this->createRevenueVendor('Vendor Settlement');

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
        $settlement = Settlement::factory()->create(['vendor_id' => $this->vendor->id]);

        $this->get(route('admin.settlements'))
            ->assertRedirect(route('superadmin.login'));

        $this->get(route('admin.settlements.invoice', $settlement))
            ->assertRedirect(route('superadmin.login'));
    }

    public function test_non_superadmin_roles_are_forbidden(): void
    {
        $settlement = Settlement::factory()->create(['vendor_id' => $this->vendor->id]);

        foreach ([$this->vendorOwner, $this->vendorAdmin, $this->customer] as $user) {
            $this->actingAs($user)
                ->get(route('admin.settlements'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('admin.settlements.invoice', $settlement))
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_view_the_settlement_page(): void
    {
        $settlement = $this->createDraftSettlement();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.settlements'))
            ->assertOk()
            ->assertSee('Settlement &amp; Pencairan Vendor', false)
            ->assertSee($settlement->vendor->name)
            ->assertSee('Draft Menunggu Review');
    }

    public function test_superadmin_can_approve_a_draft_through_livewire(): void
    {
        $settlement = $this->createDraftSettlement();

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->assertSee($this->vendor->name)
            ->call('openSettlement', $settlement->id)
            ->assertSet('selectedSettlementId', $settlement->id)
            ->call('approve', $settlement->id)
            ->assertSet('flashError', null)
            ->assertSee('disetujui');

        $this->assertSame('approved', $settlement->refresh()->status);
    }

    public function test_approving_an_already_approved_batch_shows_an_error(): void
    {
        $settlement = $this->createDraftSettlement();
        $service = app(SettlementService::class);
        $service->approve($settlement, $this->superAdmin);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('approve', $settlement->id)
            ->assertSet('flashSuccess', null)
            ->assertSee('tidak dapat disetujui');
    }

    public function test_marking_a_batch_paid_stores_the_proof_and_issues_an_invoice(): void
    {
        Storage::fake('public');

        $settlement = $this->createDraftSettlement();
        $service = app(SettlementService::class);
        $service->approve($settlement, $this->superAdmin);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openPayModal', $settlement->id)
            ->assertSet('showPayModal', true)
            ->set('referenceNumber', 'BCA-20261026-001')
            ->set('paymentNotes', 'Transfer batch minggu lalu')
            ->set('proof', UploadedFile::fake()->create('bukti-transfer.pdf', 120, 'application/pdf'))
            ->call('markPaid')
            ->assertHasNoErrors()
            ->assertSet('showPayModal', false)
            ->assertSee('telah diterbitkan');

        $settlement->refresh();

        $this->assertSame('paid', $settlement->status);
        $this->assertSame('BCA-20261026-001', $settlement->reference_number);
        $this->assertNotNull($settlement->paid_at);
        $this->assertNotNull($settlement->proof_url);
        $this->assertStringContainsString('/storage/settlements/proofs/', $settlement->proof_url);

        Storage::disk('public')->assertExists(Str::after($settlement->proof_url, '/storage/'));

        $this->assertDatabaseHas('vendor_invoices', [
            'settlement_id' => $settlement->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'issued',
            'total' => $settlement->net_payout,
        ]);
    }

    public function test_payment_form_requires_a_reference_number_and_proof(): void
    {
        $settlement = $this->createDraftSettlement();
        app(SettlementService::class)->approve($settlement, $this->superAdmin);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openPayModal', $settlement->id)
            ->call('markPaid')
            ->assertHasErrors([
                'referenceNumber' => 'required',
                'proof' => 'required',
            ]);

        $this->assertSame('approved', $settlement->refresh()->status);
    }

    public function test_superadmin_can_print_the_invoice_after_payment(): void
    {
        $invoice = $this->createPaidSettlementWithInvoice();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.settlements.invoice', $invoice->settlement))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee($this->vendor->name)
            ->assertSee('Invoice Settlement')
            ->assertSee('Total Net Payout');
    }

    public function test_invoice_cannot_be_printed_before_the_batch_is_paid(): void
    {
        $settlement = $this->createDraftSettlement();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.settlements.invoice', $settlement))
            ->assertNotFound();
    }

    private function createDraftSettlement(): Settlement
    {
        [, $payment] = $this->createPaidRevenuePayment($this->vendor, 1000000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($payment, 50000, [
            'base_amount' => 1000000,
            'recognized_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        return app(SettlementService::class)
            ->buildWeeklyDraft(Carbon::parse('2026-10-19'))
            ->first();
    }

    private function createPaidSettlementWithInvoice()
    {
        $settlement = $this->createDraftSettlement();
        $service = app(SettlementService::class);
        $service->approve($settlement, $this->superAdmin);

        return $service->markPaid(
            $settlement,
            $this->superAdmin,
            'BCA-20261026-001',
            '/storage/settlements/proofs/bukti.pdf',
        );
    }
}
