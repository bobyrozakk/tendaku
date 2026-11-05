<?php

namespace Tests\Feature\Vendor;

use App\Livewire\Vendor\CashierManager;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CashierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected User $ownerA;

    protected User $ownerB;

    protected User $cashierA;

    protected User $cashierB;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorA = Vendor::create([
            'name' => 'Toko Outdoor A',
            'slug' => 'toko-outdoor-a',
            'phone' => '081234567890',
            'address' => 'Jl. Toko A No. 1',
            'status' => 'active',
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Toko Outdoor B',
            'slug' => 'toko-outdoor-b',
            'phone' => '081234567891',
            'address' => 'Jl. Toko B No. 2',
            'status' => 'active',
        ]);

        $this->ownerA = User::factory()->create([
            'name' => 'Owner Toko A',
            'email' => 'ownera@example.com',
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendorA->id,
            'is_active' => true,
        ]);

        $this->ownerB = User::factory()->create([
            'name' => 'Owner Toko B',
            'email' => 'ownerb@example.com',
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendorB->id,
            'is_active' => true,
        ]);

        $this->cashierA = User::factory()->create([
            'name' => 'Kasir Toko A',
            'email' => 'kasira@example.com',
            'phone' => '081234567892',
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendorA->id,
            'is_active' => true,
        ]);

        $this->cashierB = User::factory()->create([
            'name' => 'Kasir Toko B',
            'email' => 'kasirb@example.com',
            'phone' => '081234567893',
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendorB->id,
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Customer Biasa',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'vendor_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_cashier_management(): void
    {
        $response = $this->get(route('vendor.cashiers.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_cashier_management(): void
    {
        $this->actingAs($this->customer);

        $response = $this->get(route('vendor.cashiers.index'));
        $response->assertForbidden();
    }

    public function test_cashier_cannot_access_cashier_management(): void
    {
        $this->actingAs($this->cashierA);

        $response = $this->get(route('vendor.cashiers.index'));
        $response->assertForbidden();
    }

    public function test_vendor_owner_can_access_cashier_management(): void
    {
        $this->actingAs($this->ownerA);

        $response = $this->get(route('vendor.cashiers.index'));
        $response->assertOk();
        $response->assertSeeLivewire(CashierManager::class);
    }

    public function test_vendor_owner_only_sees_cashiers_from_their_own_store(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(CashierManager::class)
            ->assertSee('Kasir Toko A')
            ->assertDontSee('Kasir Toko B');
    }

    public function test_vendor_owner_can_create_cashier_and_owner_session_is_preserved(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(CashierManager::class)
            ->call('openCreateModal')
            ->set('name', 'Staf Kasir Baru')
            ->set('email', 'stafbaru@gmail.com')
            ->set('phone', '081299887766')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('createCashier')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        // Pastikan akun kasir baru tersimpan dengan benar di DB
        $this->assertDatabaseHas('users', [
            'name' => 'Staf Kasir Baru',
            'email' => 'stafbaru@gmail.com',
            'phone' => '081299887766',
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendorA->id,
            'is_active' => true,
        ]);

        // Verifikasi sesi login Owner tetap terjaga (tidak tergantikan oleh kasir baru)
        $this->assertSame($this->ownerA->id, auth()->id());
    }

    public function test_create_cashier_validation_errors(): void
    {
        $this->actingAs($this->ownerA);

        // 1. Duplicate email & invalid phone format
        Livewire::test(CashierManager::class)
            ->call('openCreateModal')
            ->set('name', '')
            ->set('email', 'kasira@example.com') // sudah terdaftar
            ->set('phone', '12345') // tidak diawali 08
            ->set('password', 'secret')
            ->set('password_confirmation', 'different')
            ->call('createCashier')
            ->assertHasErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_vendor_owner_can_update_cashier_profile(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(CashierManager::class)
            ->call('openEditModal', $this->cashierA->id)
            ->assertSet('name', 'Kasir Toko A')
            ->set('name', 'Kasir Toko A Diperbarui')
            ->set('phone', '081299880011')
            ->call('updateCashier')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('users', [
            'id' => $this->cashierA->id,
            'name' => 'Kasir Toko A Diperbarui',
            'phone' => '081299880011',
        ]);
    }

    public function test_vendor_owner_cannot_manage_cashier_from_another_vendor(): void
    {
        $this->actingAs($this->ownerA);

        // Mencoba edit kasir B milik vendor B
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(CashierManager::class)
            ->call('openEditModal', $this->cashierB->id);
    }

    public function test_vendor_owner_can_toggle_cashier_status(): void
    {
        $this->actingAs($this->ownerA);

        $this->assertTrue((bool) $this->cashierA->is_active);

        // Toggle to inactive
        Livewire::test(CashierManager::class)
            ->call('toggleStatus', $this->cashierA->id);

        $this->assertFalse((bool) $this->cashierA->fresh()->is_active);

        // Toggle back to active
        Livewire::test(CashierManager::class)
            ->call('toggleStatus', $this->cashierA->id);

        $this->assertTrue((bool) $this->cashierA->fresh()->is_active);
    }

    public function test_inactive_cashier_cannot_login(): void
    {
        $this->cashierA->update([
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        // Melalui form login Volt
        Livewire::test('pages.auth.login')
            ->set('form.email', $this->cashierA->email)
            ->set('form.password', 'password123')
            ->call('login')
            ->assertHasErrors(['form.email']);

        $this->assertGuest();
    }

    public function test_active_cashier_can_login_and_redirects_to_kasir_dashboard(): void
    {
        $this->cashierA->update([
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        Livewire::test('pages.auth.login')
            ->set('form.email', $this->cashierA->email)
            ->set('form.password', 'password123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('kasir.dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->cashierA);
    }

    public function test_inactive_cashier_is_blocked_from_kasir_routes(): void
    {
        $this->cashierA->update(['is_active' => false]);
        $this->actingAs($this->cashierA);

        $response = $this->get(route('kasir.dashboard'));
        $response->assertRedirect(route('login'));
        $this->assertGuest(); // session di-logout
    }

    public function test_vendor_owner_can_soft_delete_cashier(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(CashierManager::class)
            ->call('confirmDelete', $this->cashierA->id)
            ->assertSet('showDeleteModal', true)
            ->call('deleteCashier')
            ->assertSet('showDeleteModal', false);

        // User terhapus secara soft delete
        $this->assertSoftDeleted('users', ['id' => $this->cashierA->id]);

        // Tidak lagi muncul di daftar aktif
        Livewire::test(CashierManager::class)
            ->assertDontSee($this->cashierA->name);
    }

    public function test_search_and_filter_cashiers(): void
    {
        $this->actingAs($this->ownerA);

        $inactiveCashier = User::factory()->create([
            'name' => 'Kasir Nonaktif A',
            'email' => 'nonaktif@example.com',
            'phone' => '081299990000',
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendorA->id,
            'is_active' => false,
        ]);

        // Filter active only
        Livewire::test(CashierManager::class)
            ->set('statusFilter', 'active')
            ->assertSee('Kasir Toko A')
            ->assertDontSee('Kasir Nonaktif A');

        // Filter inactive only
        Livewire::test(CashierManager::class)
            ->set('statusFilter', 'inactive')
            ->assertDontSee('Kasir Toko A')
            ->assertSee('Kasir Nonaktif A');

        // Search term
        Livewire::test(CashierManager::class)
            ->set('search', 'Nonaktif')
            ->assertDontSee('Kasir Toko A')
            ->assertSee('Kasir Nonaktif A');
    }
}
