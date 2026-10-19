<?php

namespace Tests\Feature\Admin;

use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\GmvService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $vendorOwner;

    protected User $vendorAdmin;

    protected User $customer;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected GmvService $gmvService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gmvService = new GmvService;

        // 1. Buat Vendor
        $this->vendor1 = Vendor::create([
            'name' => 'Tendaku Vendor Utama',
            'slug' => 'tendaku-vendor-utama',
            'phone' => '081234567891',
            'address' => 'Jl. Utama No. 1',
            'city' => 'Malang',
            'status' => 'active',
        ]);

        $this->vendor2 = Vendor::create([
            'name' => 'Mountaineer Gear',
            'slug' => 'mountaineer-gear',
            'phone' => '081234567892',
            'address' => 'Jl. Gunung No. 2',
            'city' => 'Batu',
            'status' => 'active',
        ]);

        // 2. Buat User untuk tiap peran
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@tendaku.com',
            'role' => 'super_admin',
            'vendor_id' => null,
            'email_verified_at' => now(),
        ]);

        $this->vendorOwner = User::factory()->create([
            'name' => 'Vendor Owner',
            'email' => 'owner@tendaku.com',
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor1->id,
            'email_verified_at' => now(),
        ]);

        $this->vendorAdmin = User::factory()->create([
            'name' => 'Vendor Admin',
            'email' => 'admin@tendaku.com',
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendor1->id,
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Pelanggan Biasa',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'vendor_id' => null,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test 1: User belum login diarahkan ke login/supadmin.
     */
    public function test_unauthenticated_user_is_redirected_to_superadmin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('superadmin.login'));
    }

    /**
     * Test 2: Role selain super_admin ditolak dengan HTTP 403 Forbidden saat akses /admin/*.
     */
    public function test_non_superadmin_roles_are_forbidden_with_403(): void
    {
        // 1. Vendor Owner ditolak 403
        $this->actingAs($this->vendorOwner)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->vendorOwner)
            ->get(route('admin.vendors.index'))
            ->assertStatus(403);

        // 2. Vendor Admin / Kasir ditolak 403
        $this->actingAs($this->vendorAdmin)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);

        // 3. Customer ditolak 403
        $this->actingAs($this->customer)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    /**
     * Test 3: Super Admin dapat mengakses halaman Dashboard GMV dan Daftar Vendor.
     */
    public function test_super_admin_can_access_dashboard_and_vendor_pages(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Gross Merchandise Value')
            ->assertSee('Super Admin Panel');

        $this->get(route('admin.vendors.index'))
            ->assertOk()
            ->assertSee('Manajemen')
            ->assertSee($this->vendor1->name);
    }

    /**
     * Test 4: Super Admin dapat mengakses /dashboard umum.
     */
    public function test_super_admin_can_access_general_dashboard(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
    }

    /**
     * Test 5: Super Admin dapat menyetujui (Approve) vendor yang berstatus pending.
     */
    public function test_super_admin_can_approve_pending_vendor(): void
    {
        $this->actingAs($this->superAdmin);

        $pendingVendor = Vendor::create([
            'name' => 'Vendor Ranu Camping',
            'slug' => 'vendor-ranu-camping',
            'phone' => '087788990011',
            'address' => 'Jl. Ranu No. 5',
            'status' => 'pending',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $response = $this->patch(route('admin.vendors.approve', $pendingVendor));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendingVendor->refresh();
        $this->assertEquals('active', $pendingVendor->status);
        $this->assertNotNull($pendingVendor->approved_at);
        $this->assertEquals($this->superAdmin->id, $pendingVendor->approved_by);
    }

    /**
     * Test 6: Super Admin dapat menolak / menangguhkan (Suspend) vendor.
     */
    public function test_super_admin_can_reject_or_suspend_vendor(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->patch(route('admin.vendors.suspend', $this->vendor1));

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $this->vendor1->refresh();
        $this->assertEquals('suspended', $this->vendor1->status);
    }

    /**
     * Test 7: Super Admin dapat menghapus (soft-delete) vendor.
     */
    public function test_super_admin_can_delete_vendor(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->delete(route('admin.vendors.destroy', $this->vendor2));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('vendors', ['id' => $this->vendor2->id]);
    }

    /**
     * Test 8: Vendor berstatus pending dilarang mengakses fitur operasional (produk/kategori).
     */
    public function test_pending_vendor_cannot_access_operational_pages(): void
    {
        $pendingVendor = Vendor::create([
            'name' => 'Pending Shop',
            'slug' => 'pending-shop',
            'phone' => '089900112233',
            'address' => 'Jl. Menunggu No. 1',
            'status' => 'pending',
        ]);

        $pendingOwner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $pendingVendor->id,
        ]);

        // Dashboard owner tetap bisa dilihat (untuk melihat status)
        $this->actingAs($pendingOwner)
            ->get(route('vendor.owner.dashboard'))
            ->assertOk();

        // Fitur operasional seperti kelola produk ditolak 403
        $this->actingAs($pendingOwner)
            ->get(route('vendor.products.index'))
            ->assertStatus(403);

        $this->actingAs($pendingOwner)
            ->get(route('vendor.categories.index'))
            ->assertStatus(403);
    }

    /**
     * Test 9: GmvService mengecualikan rental berstatus pending, cancelled, dan expired.
     */
    public function test_gmv_service_excludes_pending_cancelled_and_expired(): void
    {
        Carbon::setTestNow('2026-10-18 14:00:00');

        // Confirmed (Dihitung)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-CONF-001',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'final_total' => 100000,
            'status' => 'confirmed',
            'created_at' => Carbon::now(),
        ]);

        // Completed (Dihitung)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-COMP-002',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'final_total' => 150000,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        // Pending (TIDAK Dihitung)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-PEND-003',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 80000,
            'grand_total' => 80000,
            'final_total' => 80000,
            'status' => 'pending',
            'created_at' => Carbon::now(),
        ]);

        // Cancelled (TIDAK Dihitung)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-CANC-004',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 90000,
            'grand_total' => 90000,
            'final_total' => 90000,
            'status' => 'cancelled',
            'created_at' => Carbon::now(),
        ]);

        // Expired (TIDAK Dihitung)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-EXPI-005',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 70000,
            'grand_total' => 70000,
            'final_total' => 70000,
            'status' => 'expired',
            'created_at' => Carbon::now(),
        ]);

        $gmv = $this->gmvService->calculateTotal();

        // 100.000 + 150.000 = 250.000 (tidak menyertakan 80k, 90k, 70k)
        $this->assertEquals(250000.0, $gmv);
        $this->assertEquals(2, $this->gmvService->countTransactions());
    }

    /**
     * Test 10: GmvService menjumlahkan transaksi lintas vendor dan per vendor secara akurat.
     */
    public function test_gmv_service_calculates_cross_vendor_and_per_vendor(): void
    {
        Carbon::setTestNow('2026-10-18 10:00:00');

        // Transaksi Vendor 1: Rp 120.000
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-V1-001',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(1),
            'rental_days' => 1,
            'subtotal' => 120000,
            'grand_total' => 120000,
            'final_total' => 120000,
            'status' => 'confirmed',
            'created_at' => Carbon::now(),
        ]);

        // Transaksi Vendor 2: Rp 250.000
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor2->id,
            'booking_code' => 'TND-V2-002',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 250000,
            'grand_total' => 250000,
            'final_total' => 250000,
            'status' => 'picked_up',
            'created_at' => Carbon::now(),
        ]);

        // Total Lintas Vendor = 120.000 + 250.000 = 370.000
        $this->assertEquals(370000.0, $this->gmvService->calculateTotal());

        // Per Vendor
        $this->assertEquals(120000.0, $this->gmvService->calculateTotal($this->vendor1->id));
        $this->assertEquals(250000.0, $this->gmvService->calculateTotal($this->vendor2->id));

        // Peringkat Vendor
        $rankings = $this->gmvService->getGmvByVendor();
        $this->assertCount(2, $rankings);
        $this->assertEquals($this->vendor2->id, $rankings->first()->vendor_id);
        $this->assertEquals(250000.0, $rankings->first()->total_gmv);
    }

    /**
     * Test 11: GmvService memfilter tanggal dengan benar hingga batas tengah malam (23:59:59).
     */
    public function test_gmv_service_filters_date_with_midnight_boundaries(): void
    {
        Carbon::setTestNow('2026-10-18 23:59:59');

        // Transaksi 1: 2026-10-18 23:59:59 (Wajib Masuk Hari Ini)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-MID-001',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(1),
            'rental_days' => 1,
            'subtotal' => 50000,
            'grand_total' => 50000,
            'final_total' => 50000,
            'status' => 'confirmed',
        ]);

        Carbon::setTestNow('2026-10-19 00:00:01');

        // Transaksi 2: 2026-10-19 00:00:01 (Masuk Hari Esok)
        Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-MID-002',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 1,
            'subtotal' => 80000,
            'grand_total' => 80000,
            'final_total' => 80000,
            'status' => 'confirmed',
        ]);

        Carbon::setTestNow('2026-10-19 12:00:00');

        $day18 = Carbon::parse('2026-10-18');
        $day19 = Carbon::parse('2026-10-19');

        // Query khusus tanggal 2026-10-18
        $gmv18 = $this->gmvService->calculateTotal(null, $day18, $day18);
        $this->assertEquals(50000.0, $gmv18);

        // Query khusus tanggal 2026-10-19
        $gmv19 = $this->gmvService->calculateTotal(null, $day19, $day19);
        $this->assertEquals(80000.0, $gmv19);

        Carbon::setTestNow();
    }
}
