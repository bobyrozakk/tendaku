<?php

namespace Tests\Feature;

use App\Livewire\Kasir\Pickup;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KasirPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $kasir;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        $this->kasir = User::factory()->create([
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'vendor_id' => null,
            'email_verified_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('kasir.dashboard'))->assertRedirect(route('login'));
        $this->get(route('kasir.units'))->assertRedirect(route('login'));
        $this->get(route('kasir.pickup'))->assertRedirect(route('login'));
        $this->get(route('kasir.return'))->assertRedirect(route('login'));
    }

    public function test_kasir_can_access_all_four_kasir_pages(): void
    {
        $this->actingAs($this->kasir);

        $this->get(route('kasir.dashboard'))->assertOk()->assertSeeLivewire('kasir.pos');
        $this->get(route('kasir.units'))->assertOk()->assertSeeLivewire('kasir.item-unit-list');
        $this->get(route('kasir.pickup'))->assertOk()->assertSeeLivewire('kasir.pickup');
        $this->get(route('kasir.return'))->assertOk()->assertSeeLivewire('kasir.return-flow');
    }

    public function test_old_vendor_routes_redirect_to_kasir(): void
    {
        $this->actingAs($this->kasir);

        $this->get(route('vendor.dashboard'))->assertRedirect(route('kasir.dashboard'));
        $this->get(route('vendor.pickup'))->assertRedirect(route('kasir.pickup'));
        $this->get(route('vendor.return'))->assertRedirect(route('kasir.return'));
    }

    public function test_kasir_pickup_blocks_handover_when_dp_unsettled_and_allows_cash_payment(): void
    {
        $this->actingAs($this->kasir);

        $rental = Rental::query()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TDK202611040001',
            'pickup_date' => now()->toDateString(),
            'return_date' => now()->addDay()->toDateString(),
            'rental_days' => 1,
            'subtotal' => 200000,
            'grand_total' => 200000,
            'status' => 'confirmed',
        ]);

        Payment::query()->create([
            'rental_id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'dp',
            'channel' => 'midtrans',
            'payment_method' => 'qris',
            'amount' => 100000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $test = Livewire::test(Pickup::class)
            ->call('selectPickup', (string) $rental->id)
            ->assertSee('DP (Sisa Rp 100.000)')
            ->assertSee('Tagihan Belum Lunas')
            ->set('tenantPhotoUploaded', true)
            ->set('tenantKtpUploaded', true)
            ->set('checkIdentity', true)
            ->set('checkUnits', true)
            ->set('checkHandover', true);

        // Belum lunas -> confirmPickup harus ditolak
        $test->call('confirmPickup');
        $this->assertSame('confirmed', $rental->fresh()->status);

        // Kasir menerima pelunasan sisa tunai
        $test->call('acceptCashPayment');

        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'payment_type' => 'remaining',
            'payment_method' => 'cash',
            'amount' => 100000,
            'status' => 'paid',
            'received_by' => $this->kasir->id,
        ]);

        // Setelah lunas, verifikasi checklist lagi lalu confirmPickup
        $test->call('selectPickup', (string) $rental->id)
            ->set('tenantPhotoUploaded', true)
            ->set('tenantKtpUploaded', true)
            ->set('checkIdentity', true)
            ->set('checkUnits', true)
            ->set('checkHandover', true)
            ->call('confirmPickup');

        $rental->refresh();
        $this->assertSame('picked_up', $rental->status);
        $this->assertSame($this->kasir->id, $rental->picked_up_by);
    }
}
