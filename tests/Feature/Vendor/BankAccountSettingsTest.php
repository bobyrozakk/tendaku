<?php

namespace Tests\Feature\Vendor;

use App\Livewire\Vendor\BankAccountSettings;
use App\Models\Settlement;
use App\Models\VendorPaymentSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class BankAccountSettingsTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    public function test_vendor_owner_sees_saved_bank_account_on_the_revenue_page(): void
    {
        $vendor = $this->createRevenueVendor('Vendor Rekening');
        $owner = $this->createRevenueUser('vendor_owner', $vendor);

        VendorPaymentSetting::query()->create([
            'vendor_id' => $vendor->id,
            'bank_name' => 'Bank Jatim',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Toko Pendaki',
        ]);

        $this->actingAs($owner)
            ->get(route('vendor.pendapatan'))
            ->assertOk()
            ->assertSeeLivewire(BankAccountSettings::class)
            ->assertSee('Bank Jatim')
            ->assertSee('1234567890')
            ->assertSee('Toko Pendaki');
    }

    public function test_vendor_owner_can_save_bank_account_and_pending_settlements_are_updated(): void
    {
        $vendor = $this->createRevenueVendor('Vendor Rekening');
        $owner = $this->createRevenueUser('vendor_owner', $vendor);

        $draft = $this->createSettlement($vendor->id, 'draft');
        $approved = $this->createSettlement($vendor->id, 'approved');
        $paid = $this->createSettlement($vendor->id, 'paid');
        $cancelled = $this->createSettlement($vendor->id, 'cancelled');

        $otherVendor = $this->createRevenueVendor('Vendor Lain');
        $otherDraft = $this->createSettlement($otherVendor->id, 'draft');

        Livewire::actingAs($owner)
            ->test(BankAccountSettings::class)
            ->call('openModal')
            ->set('bankName', '  Bank Jatim  ')
            ->set('bankAccountNumber', '123 456 7890')
            ->set('bankAccountName', '  Toko Pendaki  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('savedMessage', 'Rekening tujuan berhasil disimpan.')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('vendor_payment_settings', [
            'vendor_id' => $vendor->id,
            'bank_name' => 'Bank Jatim',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Toko Pendaki',
        ]);

        foreach ([$draft, $approved] as $settlement) {
            $settlement->refresh();

            $this->assertSame('Bank Jatim', $settlement->bank_name);
            $this->assertSame('1234567890', $settlement->bank_account_number);
            $this->assertSame('Toko Pendaki', $settlement->bank_account_name);
        }

        foreach ([$paid, $cancelled, $otherDraft] as $settlement) {
            $settlement->refresh();

            $this->assertSame('BCA', $settlement->bank_name);
            $this->assertSame('1111111111', $settlement->bank_account_number);
            $this->assertSame('Rekening Lama', $settlement->bank_account_name);
        }
    }

    public function test_bank_account_fields_are_required_and_account_number_must_be_numeric(): void
    {
        $vendor = $this->createRevenueVendor('Vendor Rekening');
        $owner = $this->createRevenueUser('vendor_owner', $vendor);

        Livewire::actingAs($owner)
            ->test(BankAccountSettings::class)
            ->call('openModal')
            ->set('bankName', '')
            ->set('bankAccountNumber', '12345')
            ->set('bankAccountName', '')
            ->call('save')
            ->assertHasErrors([
                'bankName' => 'required',
                'bankAccountNumber' => 'regex',
                'bankAccountName' => 'required',
            ])
            ->assertSet('showModal', true);

        $this->assertDatabaseCount('vendor_payment_settings', 0);
    }

    public function test_vendor_admin_cannot_open_bank_account_settings(): void
    {
        $vendor = $this->createRevenueVendor('Vendor Rekening');
        $vendorAdmin = $this->createRevenueUser('vendor_admin', $vendor);

        $this->actingAs($vendorAdmin);

        Livewire::test(BankAccountSettings::class)
            ->assertForbidden();

        $this->assertDatabaseCount('vendor_payment_settings', 0);
    }

    public function test_customer_cannot_open_bank_account_settings(): void
    {
        $customer = $this->createRevenueUser('customer');

        $this->actingAs($customer);

        Livewire::test(BankAccountSettings::class)
            ->assertForbidden();

        $this->assertDatabaseCount('vendor_payment_settings', 0);
    }

    private function createSettlement(int $vendorId, string $status): Settlement
    {
        $periodStart = match ($status) {
            'draft' => '2026-10-05',
            'approved' => '2026-10-12',
            'paid' => '2026-10-19',
            'cancelled' => '2026-10-26',
        };

        return Settlement::factory()->create([
            'vendor_id' => $vendorId,
            'period_start' => $periodStart,
            'period_end' => date('Y-m-d', strtotime($periodStart.' +6 days')),
            'status' => $status,
            'bank_name' => 'BCA',
            'bank_account_number' => '1111111111',
            'bank_account_name' => 'Rekening Lama',
        ]);
    }
}
