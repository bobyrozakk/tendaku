<?php

namespace Tests\Feature\MultiTenant;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CurrentVendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected User $ownerA;

    protected User $ownerB;

    protected User $superAdmin;

    protected ItemCategory $categoryA;

    protected ItemCategory $categoryB;

    protected MasterItem $itemA;

    protected MasterItem $itemB;

    protected ItemUnit $unitA;

    protected ItemUnit $unitB;

    protected Rental $rentalA;

    protected Rental $rentalB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat Vendor A & Vendor B
        $this->vendorA = Vendor::create([
            'name' => 'Vendor Alpha Outdoor',
            'slug' => 'vendor-alpha-outdoor',
            'phone' => '081111111111',
            'address' => 'Jl. Alpha No. 1',
            'status' => 'active',
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Vendor Beta Camping',
            'slug' => 'vendor-beta-camping',
            'phone' => '082222222222',
            'address' => 'Jl. Beta No. 2',
            'status' => 'active',
        ]);

        // 2. Buat User Owner untuk masing-masing vendor
        $this->ownerA = User::factory()->create([
            'name' => 'Owner Alpha',
            'email' => 'owner.alpha@tendaku.com',
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendorA->id,
            'email_verified_at' => now(),
        ]);

        $this->ownerB = User::factory()->create([
            'name' => 'Owner Beta',
            'email' => 'owner.beta@tendaku.com',
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendorB->id,
            'email_verified_at' => now(),
        ]);

        // 3. Buat Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Tendaku',
            'email' => 'superadmin@tendaku.com',
            'role' => 'super_admin',
            'vendor_id' => null,
            'email_verified_at' => now(),
        ]);

        // 4. Buat Data untuk Vendor A
        $this->categoryA = ItemCategory::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Kategori Alpha Tenda',
            'slug' => 'kategori-alpha-tenda',
        ]);

        $this->itemA = MasterItem::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $this->categoryA->id,
            'name' => 'Tenda Dome Alpha 4P',
            'slug' => 'tenda-dome-alpha-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'is_active' => true,
        ]);

        $this->unitA = ItemUnit::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorA->id,
            'master_item_id' => $this->itemA->id,
            'unit_code' => 'ALP-001',
            'barcode' => 'BC-ALP-001',
            'status' => 'available',
            'condition' => 'excellent',
        ]);

        $this->rentalA = Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorA->id,
            'booking_code' => 'TND-ALP-101',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'final_total' => 100000,
            'status' => 'confirmed',
        ]);

        // 5. Buat Data untuk Vendor B
        $this->categoryB = ItemCategory::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Kategori Beta Lampu',
            'slug' => 'kategori-beta-lampu',
        ]);

        $this->itemB = MasterItem::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorB->id,
            'category_id' => $this->categoryB->id,
            'name' => 'Lampu Badai Beta',
            'slug' => 'lampu-badai-beta',
            'daily_rate' => 25000,
            'late_fee_per_day' => 10000,
            'is_active' => true,
        ]);

        $this->unitB = ItemUnit::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorB->id,
            'master_item_id' => $this->itemB->id,
            'unit_code' => 'BET-001',
            'barcode' => 'BC-BET-001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $this->rentalB = Rental::withoutGlobalScope('vendor')->create([
            'vendor_id' => $this->vendorB->id,
            'booking_code' => 'TND-BET-201',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 50000,
            'grand_total' => 50000,
            'final_total' => 50000,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Test 1: Vendor A tidak bisa membaca data Vendor B (produk, unit, rental).
     */
    public function test_vendor_a_cannot_read_vendor_b_data(): void
    {
        $this->actingAsVendor($this->ownerA);

        // Kategori: hanya kategori Vendor A yang terbaca
        $categories = ItemCategory::all();
        $this->assertTrue($categories->contains('id', $this->categoryA->id));
        $this->assertFalse($categories->contains('id', $this->categoryB->id));

        // Master Produk: hanya produk Vendor A yang terbaca
        $items = MasterItem::all();
        $this->assertTrue($items->contains('id', $this->itemA->id));
        $this->assertFalse($items->contains('id', $this->itemB->id));
        $this->assertNull(MasterItem::find($this->itemB->id));

        // Unit Fisik: hanya unit Vendor A yang terbaca
        $units = ItemUnit::all();
        $this->assertTrue($units->contains('id', $this->unitA->id));
        $this->assertFalse($units->contains('id', $this->unitB->id));
        $this->assertNull(ItemUnit::find($this->unitB->id));

        // Transaksi Rental: hanya rental Vendor A yang terbaca
        $rentals = Rental::all();
        $this->assertTrue($rentals->contains('id', $this->rentalA->id));
        $this->assertFalse($rentals->contains('id', $this->rentalB->id));
        $this->assertNull(Rental::find($this->rentalB->id));
    }

    /**
     * Test 2: Vendor A tidak bisa update/delete data Vendor B (harus 404, bukan 403).
     */
    public function test_vendor_a_cannot_update_or_delete_vendor_b_data_returns_404(): void
    {
        $this->actingAsVendor($this->ownerA);

        // 1. Coba GET halaman edit produk milik Vendor B -> 404
        $this->get(route('vendor.products.edit', $this->itemB->id))
            ->assertStatus(404);

        // 2. Coba PUT update produk milik Vendor B -> 404
        $this->put(route('vendor.products.update', $this->itemB->id), [
            'category_id' => $this->categoryA->id,
            'name' => 'Nama Bajakan Oleh Vendor A',
            'daily_rate' => 10000,
            'late_fee_per_day' => 5000,
        ])->assertStatus(404);

        // Verifikasi nama produk Vendor B tidak berubah
        $this->assertDatabaseHas('master_items', [
            'id' => $this->itemB->id,
            'name' => 'Lampu Badai Beta',
        ]);

        // 3. Coba DELETE produk milik Vendor B -> 404
        $this->delete(route('vendor.products.destroy', $this->itemB->id))
            ->assertStatus(404);

        // Verifikasi produk Vendor B tidak terhapus
        $this->assertDatabaseHas('master_items', [
            'id' => $this->itemB->id,
            'deleted_at' => null,
        ]);

        // 4. Coba PUT update kategori milik Vendor B -> 404
        $this->put(route('vendor.categories.update', $this->categoryB->id), [
            'name' => 'Kategori Bajakan',
        ])->assertStatus(404);

        // 5. Coba DELETE kategori milik Vendor B -> 404
        $this->delete(route('vendor.categories.destroy', $this->categoryB->id))
            ->assertStatus(404);
    }

    /**
     * Test 3: vendor_id terisi otomatis saat create model bila kosong.
     */
    public function test_vendor_id_is_automatically_filled_when_creating_model(): void
    {
        $this->actingAsVendor($this->ownerA);

        // Buat kategori baru tanpa menyertakan vendor_id
        $category = ItemCategory::create([
            'name' => 'Tenda Dome Baru',
            'slug' => 'tenda-dome-baru',
        ]);

        $this->assertEquals($this->vendorA->id, $category->vendor_id);
        $this->assertDatabaseHas('item_categories', [
            'id' => $category->id,
            'vendor_id' => $this->vendorA->id,
            'name' => 'Tenda Dome Baru',
        ]);

        // Buat master item baru tanpa menyertakan vendor_id
        $item = MasterItem::create([
            'category_id' => $category->id,
            'name' => 'Tenda Auto Scoped',
            'slug' => 'tenda-auto-scoped',
            'daily_rate' => 35000,
            'late_fee_per_day' => 15000,
            'is_active' => true,
        ]);

        $this->assertEquals($this->vendorA->id, $item->vendor_id);
        $this->assertDatabaseHas('master_items', [
            'id' => $item->id,
            'vendor_id' => $this->vendorA->id,
            'name' => 'Tenda Auto Scoped',
        ]);
    }

    /**
     * Test 4: Super Admin melihat data semua vendor (bypass scope).
     */
    public function test_super_admin_sees_data_across_all_vendors(): void
    {
        $this->actingAs($this->superAdmin);

        // Super Admin harus melihat kategori dari semua vendor
        $categories = ItemCategory::all();
        $this->assertTrue($categories->contains('id', $this->categoryA->id));
        $this->assertTrue($categories->contains('id', $this->categoryB->id));

        // Super Admin harus melihat item dari semua vendor
        $items = MasterItem::all();
        $this->assertTrue($items->contains('id', $this->itemA->id));
        $this->assertTrue($items->contains('id', $this->itemB->id));

        // Super Admin harus melihat unit dari semua vendor
        $units = ItemUnit::all();
        $this->assertTrue($units->contains('id', $this->unitA->id));
        $this->assertTrue($units->contains('id', $this->unitB->id));

        // Super Admin harus melihat rental dari semua vendor
        $rentals = Rental::all();
        $this->assertTrue($rentals->contains('id', $this->rentalA->id));
        $this->assertTrue($rentals->contains('id', $this->rentalB->id));
    }

    /**
     * Test 5: Job queue / internal process menjalankan scope vendor yang benar via CurrentVendor::runAs().
     */
    public function test_job_queue_executes_with_correct_vendor_scoping(): void
    {
        // Pastikan tidak ada user login (seperti di worker background)
        auth()->logout();
        CurrentVendor::clear();

        // 1. Jalankan konteks Vendor A
        CurrentVendor::runAs($this->vendorA->id, function () {
            $items = MasterItem::all();
            $this->assertCount(1, $items);
            $this->assertEquals($this->itemA->id, $items->first()->id);

            $rentals = Rental::all();
            $this->assertCount(1, $rentals);
            $this->assertEquals($this->rentalA->id, $rentals->first()->id);
        });

        // 2. Jalankan konteks Vendor B
        CurrentVendor::runAs($this->vendorB->id, function () {
            $items = MasterItem::all();
            $this->assertCount(1, $items);
            $this->assertEquals($this->itemB->id, $items->first()->id);

            $rentals = Rental::all();
            $this->assertCount(1, $rentals);
            $this->assertEquals($this->rentalB->id, $rentals->first()->id);
        });

        // 3. Setelah job selesai, context kembali bersih
        $this->assertFalse(CurrentVendor::has());
    }

    /**
     * Test 6: Helper scopeForVendor dan scopeAllVendors berfungsi dengan tepat.
     */
    public function test_scope_for_vendor_and_all_vendors_work(): void
    {
        $this->actingAsVendor($this->ownerA);

        // Secara default hanya melihat itemA
        $this->assertCount(1, MasterItem::all());

        // Dengan forVendor(vendorB), secara eksplisit membaca data vendor B
        $vendorBItems = MasterItem::forVendor($this->vendorB->id)->get();
        $this->assertTrue($vendorBItems->contains('id', $this->itemB->id));
        $this->assertFalse($vendorBItems->contains('id', $this->itemA->id));

        // Dengan allVendors(), membaca seluruh vendor
        $allItems = MasterItem::allVendors()->get();
        $this->assertTrue($allItems->contains('id', $this->itemA->id));
        $this->assertTrue($allItems->contains('id', $this->itemB->id));
    }
}
