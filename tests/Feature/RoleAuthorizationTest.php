<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_roles_trait_helpers_work_correctly(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($customer->isVendorStaff());
        $this->assertFalse($customer->isVendorOwner());
        $this->assertFalse($customer->isVendorAdmin());
        $this->assertFalse($customer->isSuperAdmin());
        $this->assertTrue($customer->hasRole('customer'));
        $this->assertTrue($customer->hasRole(['customer', 'vendor_owner']));
        $this->assertFalse($customer->hasRole(['vendor_owner', 'super_admin']));

        $vendorOwner = User::factory()->create(['role' => User::ROLE_VENDOR_OWNER]);
        $this->assertTrue($vendorOwner->isVendorOwner());
        $this->assertTrue($vendorOwner->isVendorStaff());
        $this->assertFalse($vendorOwner->isCustomer());
        $this->assertFalse($vendorOwner->isVendorAdmin());

        $vendorAdmin = User::factory()->create(['role' => User::ROLE_VENDOR_ADMIN]);
        $this->assertTrue($vendorAdmin->isVendorAdmin());
        $this->assertTrue($vendorAdmin->isVendorStaff());
        $this->assertFalse($vendorAdmin->isVendorOwner());
        $this->assertFalse($vendorAdmin->isCustomer());

        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isVendorStaff());
    }

    public function test_unauthenticated_user_cannot_access_vendor_routes(): void
    {
        $response = $this->get('/vendor/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_customer_cannot_access_vendor_routes(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get('/vendor/dashboard');

        // middleware mengarahkan customer ke home
        $response->assertRedirect(route('home'));
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get('/dashboard');

        // Customer diarahkan ke home
        $response->assertRedirect(route('home'));
    }

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->get('/dashboard');

        $response->assertOk();
    }

    public function test_vendor_owner_can_access_dashboard(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_VENDOR_OWNER,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertRedirect(route('vendor.owner.dashboard'));
    }

    public function test_vendor_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_VENDOR_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('vendor.dashboard'));
    }

    public function test_vendor_owner_can_access_vendor_routes(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_VENDOR_OWNER,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get('/vendor/dashboard');

        $response->assertRedirect(route('vendor.owner.dashboard'));
    }

    public function test_vendor_admin_can_access_vendor_routes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_VENDOR_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/vendor/dashboard');

        $response->assertOk();
    }
}
