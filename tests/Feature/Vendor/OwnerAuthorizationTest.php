<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $owner;

    protected User $admin;

    protected User $customer;

    protected User $superAdmin;

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

        $this->owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendor->id,
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'vendor_id' => null,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'vendor_id' => null,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('vendor.owner.dashboard'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('vendor.products.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('vendor.categories.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('vendor.units'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_owner_pages(): void
    {
        $this->actingAs($this->customer);

        $this->get(route('vendor.owner.dashboard'))->assertForbidden();
        $this->get(route('vendor.products.index'))->assertForbidden();
        $this->get(route('vendor.categories.index'))->assertForbidden();
        $this->get(route('vendor.units'))->assertForbidden();
    }

    public function test_vendor_admin_cannot_access_owner_pages(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('vendor.owner.dashboard'))->assertForbidden();
        $this->get(route('vendor.products.index'))->assertForbidden();
        $this->get(route('vendor.categories.index'))->assertForbidden();
        $this->get(route('vendor.units'))->assertForbidden();
    }

    public function test_super_admin_cannot_access_owner_crud(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('vendor.owner.dashboard'))->assertForbidden();
        $this->get(route('vendor.products.index'))->assertForbidden();
        $this->get(route('vendor.categories.index'))->assertForbidden();
        $this->get(route('vendor.units'))->assertForbidden();
    }

    public function test_vendor_owner_can_access_all_owner_pages(): void
    {
        $this->actingAs($this->owner);

        $this->get(route('vendor.owner.dashboard'))->assertOk();
        $this->get(route('vendor.products.index'))->assertOk();
        $this->get(route('vendor.categories.index'))->assertOk();
        $this->get(route('vendor.units'))->assertOk();
    }

    public function test_vendor_owner_without_vendor_id_is_forbidden(): void
    {
        $orphanOwner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => null,
        ]);

        $this->actingAs($orphanOwner);

        $this->get(route('vendor.owner.dashboard'))->assertForbidden();
    }
}
