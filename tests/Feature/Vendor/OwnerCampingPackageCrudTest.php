<?php

namespace Tests\Feature\Vendor;

use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerCampingPackageCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected User $owner1;

    protected User $owner2;

    protected ItemCategory $cat1;

    protected ItemCategory $cat2;

    protected MasterItem $tendaV1;

    protected MasterItem $komporV1;

    protected MasterItem $tendaV2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor1 = Vendor::create([
            'name' => 'Tendaku Vendor 1',
            'slug' => 'tendaku-vendor-1',
            'phone' => '081234567891',
            'address' => 'Jl. Vendor 1',
            'status' => 'active',
        ]);

        $this->vendor2 = Vendor::create([
            'name' => 'Mountaineer Vendor 2',
            'slug' => 'mountaineer-vendor-2',
            'phone' => '081234567892',
            'address' => 'Jl. Vendor 2',
            'status' => 'active',
        ]);

        $this->owner1 = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor1->id,
        ]);

        $this->owner2 = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor2->id,
        ]);

        $this->cat1 = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->cat2 = ItemCategory::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Tenda Ekspedisi',
            'slug' => 'tenda-ekspedisi',
        ]);

        $this->tendaV1 = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->cat1->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
        ]);

        $this->komporV1 = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->cat1->id,
            'name' => 'Kompor Portable',
            'slug' => 'kompor-portable',
            'daily_rate' => 15000,
        ]);

        $this->tendaV2 = MasterItem::create([
            'vendor_id' => $this->vendor2->id,
            'category_id' => $this->cat2->id,
            'name' => 'Tenda Milik Vendor 2',
            'slug' => 'tenda-milik-vendor-2',
            'daily_rate' => 45000,
        ]);
    }

    public function test_owner_can_see_package_list_and_create_page(): void
    {
        $this->actingAs($this->owner1);

        $package = CampingPackage::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Paket Akhir Pekan',
            'slug' => 'paket-akhir-pekan',
            'price' => 200000,
            'duration_days' => 2,
        ]);

        $package->packageItems()->create([
            'master_item_id' => $this->tendaV1->id,
            'quantity' => 2,
        ]);

        $this->get(route('vendor.packages.index'))
            ->assertOk()
            ->assertSee('Paket Camping Owner')
            ->assertSee('Paket Akhir Pekan')
            ->assertSee('2 unit');

        $this->get(route('vendor.packages.create'))
            ->assertOk()
            ->assertSee('Tambah Paket Camping');
    }

    public function test_owner_can_store_package_with_items_and_flat_price(): void
    {
        $this->actingAs($this->owner1);

        $response = $this->post(route('vendor.packages.store'), [
            'name' => 'Paket Camping Hemat 2 Hari',
            'description' => 'Tenda dan kompor untuk dua hari.',
            'price' => 250000,
            'duration_days' => 2,
            'max_people' => 4,
            'is_active' => '1',
            'items' => [
                ['master_item_id' => $this->tendaV1->id, 'quantity' => 1],
                ['master_item_id' => $this->komporV1->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('vendor.packages.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('camping_packages', [
            'vendor_id' => $this->vendor1->id,
            'name' => 'Paket Camping Hemat 2 Hari',
            'slug' => 'paket-camping-hemat-2-hari',
            'price' => 250000,
            'duration_days' => 2,
            'max_people' => 4,
            'is_active' => true,
        ]);

        $package = CampingPackage::withoutGlobalScope('vendor')
            ->where('name', 'Paket Camping Hemat 2 Hari')
            ->firstOrFail();

        $this->assertDatabaseHas('camping_package_items', [
            'camping_package_id' => $package->id,
            'master_item_id' => $this->tendaV1->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('camping_package_items', [
            'camping_package_id' => $package->id,
            'master_item_id' => $this->komporV1->id,
            'quantity' => 2,
        ]);
    }

    public function test_store_rejects_product_owned_by_another_vendor(): void
    {
        $this->actingAs($this->owner1);

        $response = $this->post(route('vendor.packages.store'), [
            'name' => 'Paket Ilegal',
            'price' => 100000,
            'duration_days' => 2,
            'items' => [
                ['master_item_id' => $this->tendaV2->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.master_item_id');
        $this->assertDatabaseMissing('camping_packages', [
            'name' => 'Paket Ilegal',
        ]);
    }

    public function test_store_requires_at_least_one_product(): void
    {
        $this->actingAs($this->owner1);

        $response = $this->post(route('vendor.packages.store'), [
            'name' => 'Paket Kosong',
            'price' => 100000,
            'duration_days' => 2,
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('camping_packages', [
            'name' => 'Paket Kosong',
        ]);
    }

    public function test_owner_can_update_package_and_replace_items(): void
    {
        $this->actingAs($this->owner1);

        $package = CampingPackage::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Paket Lama',
            'slug' => 'paket-lama',
            'price' => 150000,
            'duration_days' => 1,
        ]);

        $package->packageItems()->create([
            'master_item_id' => $this->tendaV1->id,
            'quantity' => 1,
        ]);

        $this->get(route('vendor.packages.edit', $package))
            ->assertOk()
            ->assertSee('Paket Lama');

        $response = $this->put(route('vendor.packages.update', $package), [
            'name' => 'Paket Baru Diperbarui',
            'price' => 300000,
            'duration_days' => 3,
            'is_active' => '1',
            'items' => [
                ['master_item_id' => $this->komporV1->id, 'quantity' => 4],
            ],
        ]);

        $response->assertRedirect(route('vendor.packages.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('camping_packages', [
            'id' => $package->id,
            'name' => 'Paket Baru Diperbarui',
            'slug' => 'paket-baru-diperbarui',
            'price' => 300000,
            'duration_days' => 3,
        ]);

        $this->assertDatabaseMissing('camping_package_items', [
            'camping_package_id' => $package->id,
            'master_item_id' => $this->tendaV1->id,
        ]);

        $this->assertDatabaseHas('camping_package_items', [
            'camping_package_id' => $package->id,
            'master_item_id' => $this->komporV1->id,
            'quantity' => 4,
        ]);
    }

    public function test_owner_cannot_access_or_modify_another_vendor_package(): void
    {
        $otherPackage = CampingPackage::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Paket Milik Vendor 2',
            'slug' => 'paket-milik-vendor-2',
            'price' => 200000,
            'duration_days' => 2,
        ]);

        $this->actingAs($this->owner1);

        $this->get(route('vendor.packages.edit', $otherPackage->id))
            ->assertNotFound();

        $this->put(route('vendor.packages.update', $otherPackage->id), [
            'name' => 'Nama Diambil Alih',
            'price' => 1000,
            'duration_days' => 1,
            'items' => [
                ['master_item_id' => $this->tendaV1->id, 'quantity' => 1],
            ],
        ])->assertNotFound();

        $this->delete(route('vendor.packages.destroy', $otherPackage->id))
            ->assertNotFound();

        $this->assertDatabaseHas('camping_packages', [
            'id' => $otherPackage->id,
            'name' => 'Paket Milik Vendor 2',
        ]);
    }

    public function test_owner_can_delete_package_and_its_items(): void
    {
        $this->actingAs($this->owner1);

        $package = CampingPackage::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Paket Akan Dihapus',
            'slug' => 'paket-akan-dihapus',
            'price' => 120000,
            'duration_days' => 1,
        ]);

        $package->packageItems()->create([
            'master_item_id' => $this->tendaV1->id,
            'quantity' => 1,
        ]);

        $response = $this->delete(route('vendor.packages.destroy', $package));

        $response->assertRedirect(route('vendor.packages.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('camping_packages', ['id' => $package->id]);
        $this->assertDatabaseMissing('camping_package_items', [
            'camping_package_id' => $package->id,
        ]);
    }
}
