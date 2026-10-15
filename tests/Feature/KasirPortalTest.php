<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
