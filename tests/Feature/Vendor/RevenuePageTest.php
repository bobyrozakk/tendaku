<?php

namespace Tests\Feature\Vendor;

use App\Models\Settlement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class RevenuePageTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    private Vendor $vendor;

    private User $owner;

    private User $vendorAdmin;

    private User $customer;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-26 10:00:00');

        $this->vendor = $this->createRevenueVendor('Vendor Pendapatan');
        $this->owner = $this->createRevenueUser('vendor_owner', $this->vendor);
        $this->vendorAdmin = $this->createRevenueUser('vendor_admin', $this->vendor);
        $this->customer = $this->createRevenueUser('customer');
        $this->superAdmin = $this->createRevenueUser('super_admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_vendor_owner_can_view_their_invoices_and_payout_details(): void
    {
        $invoice = $this->createIssuedInvoice();

        $this->actingAs($this->owner)
            ->get(route('vendor.pendapatan'))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee('Menunggu Transfer')
            ->assertSee('Rp 1.000.000')
            ->assertSee('Rp 950.000')
            ->assertSee($this->vendor->name);
    }

    public function test_vendor_owner_can_print_an_issued_invoice_before_transfer(): void
    {
        $invoice = $this->createIssuedInvoice();

        $this->actingAs($this->owner)
            ->get(route('vendor.pendapatan.invoice', $invoice->settlement))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee('Menunggu Transfer')
            ->assertSee('Total Net Payout');
    }

    public function test_vendor_owner_cannot_view_another_vendors_invoice(): void
    {
        $otherVendor = $this->createRevenueVendor('Vendor Lain');
        $otherSettlement = Settlement::factory()->create(['vendor_id' => $otherVendor->id]);
        $otherInvoice = VendorInvoice::factory()->create([
            'vendor_id' => $otherVendor->id,
            'settlement_id' => $otherSettlement->id,
            'number' => 'INV-OTHER-001',
        ]);

        $this->actingAs($this->owner)
            ->get(route('vendor.pendapatan'))
            ->assertOk()
            ->assertDontSee($otherInvoice->number);

        $this->actingAs($this->owner)
            ->get(route('vendor.pendapatan.invoice', $otherSettlement))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('vendor.pendapatan'))
            ->assertRedirect(route('login'));
    }

    public function test_other_roles_cannot_access_vendor_revenue_pages(): void
    {
        $invoice = $this->createIssuedInvoice();

        $this->actingAs($this->vendorAdmin)
            ->get(route('vendor.pendapatan'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('vendor.pendapatan'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('vendor.pendapatan'))
            ->assertForbidden();

        $this->actingAs($this->vendorAdmin)
            ->get(route('vendor.pendapatan.invoice', $invoice->settlement))
            ->assertForbidden();
    }

    private function createIssuedInvoice(): VendorInvoice
    {
        [, $payment] = $this->createPaidRevenuePayment($this->vendor, 1000000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);
        $this->createRevenueCommission($payment, 50000, [
            'base_amount' => 1000000,
            'recognized_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        $service = app(SettlementService::class);
        $settlement = $service->buildWeeklyDraft(Carbon::parse('2026-10-19'))->first();

        $service->approve($settlement, $this->superAdmin);

        return $settlement->invoices()->firstOrFail();
    }
}
