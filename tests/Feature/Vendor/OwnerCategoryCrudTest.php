<?php

namespace Tests\Feature\Vendor;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected User $owner1;

    protected User $owner2;

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
    }

    public function test_owner_can_see_category_list(): void
    {
        $this->actingAs($this->owner1);

        $cat = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Peralatan Masak',
            'slug' => 'peralatan-masak',
        ]);

        $this->get(route('vendor.categories.index'))
            ->assertOk()
            ->assertSee('Kategori Owner')
            ->assertSee('Peralatan Masak');
    }

    public function test_owner_can_store_new_category(): void
    {
        $this->actingAs($this->owner1);

        $response = $this->post(route('vendor.categories.store'), [
            'name' => 'Lampu & Penerangan',
            'icon' => 'sun',
            'description' => 'Lampu LED tenda dan headlamp.',
        ]);

        $response->assertRedirect(route('vendor.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('item_categories', [
            'vendor_id' => $this->vendor1->id,
            'name' => 'Lampu & Penerangan',
            'slug' => 'lampu-penerangan',
            'icon' => 'sun',
        ]);
    }

    public function test_owner_can_update_category(): void
    {
        $this->actingAs($this->owner1);

        $cat = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Alat Camping',
            'slug' => 'alat-camping',
        ]);

        $response = $this->put(route('vendor.categories.update', $cat), [
            'name' => 'Alat Camping & Survival',
            'icon' => 'bag',
            'description' => 'Deskripsi baru.',
        ]);

        $response->assertRedirect(route('vendor.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('item_categories', [
            'id' => $cat->id,
            'name' => 'Alat Camping & Survival',
            'slug' => 'alat-camping-survival',
        ]);
    }

    public function test_owner_cannot_delete_category_with_existing_products(): void
    {
        $this->actingAs($this->owner1);

        $cat = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $cat->id,
            'name' => 'Tenda Eiger 4P',
            'slug' => 'tenda-eiger-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
        ]);

        $response = $this->delete(route('vendor.categories.destroy', $cat));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('item_categories', ['id' => $cat->id]);
    }

    public function test_owner_can_delete_empty_category(): void
    {
        $this->actingAs($this->owner1);

        $cat = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Kategori Kosong',
            'slug' => 'kategori-kosong',
        ]);

        $response = $this->delete(route('vendor.categories.destroy', $cat));

        $response->assertRedirect(route('vendor.categories.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('item_categories', ['id' => $cat->id]);
    }

    public function test_owner_cannot_access_other_vendor_category(): void
    {
        $catV2 = ItemCategory::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Kategori Milik Vendor 2',
            'slug' => 'kategori-milik-vendor-2',
        ]);

        $this->actingAs($this->owner1);

        $this->put(route('vendor.categories.update', $catV2->id), [
            'name' => 'Hacked Name',
        ])->assertNotFound();

        $this->delete(route('vendor.categories.destroy', $catV2->id))
            ->assertNotFound();
    }
}
