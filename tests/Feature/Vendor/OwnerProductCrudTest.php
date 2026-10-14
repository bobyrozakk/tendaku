<?php

namespace Tests\Feature\Vendor;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected User $owner1;

    protected User $owner2;

    protected ItemCategory $cat1;

    protected ItemCategory $cat2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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
    }

    public function test_owner_can_see_products_list_and_create_page(): void
    {
        $this->actingAs($this->owner1);

        $this->get(route('vendor.products.index'))
            ->assertOk()
            ->assertSee('Produk Owner');

        $this->get(route('vendor.products.create'))
            ->assertOk()
            ->assertSee('Tambah Produk Owner');
    }

    public function test_owner_can_store_valid_product(): void
    {
        $this->actingAs($this->owner1);

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->post(route('vendor.products.store'), [
            'name' => 'Tenda Dome Arpenaz 4.1',
            'category_id' => $this->cat1->id,
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'description' => 'Tenda keluarga 4 orang dengan ruang santai luas.',
            'image' => $file,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_items', [
            'vendor_id' => $this->vendor1->id,
            'name' => 'Tenda Dome Arpenaz 4.1',
            'slug' => 'tenda-dome-arpenaz-41',
            'category_id' => $this->cat1->id,
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists('products/'.$file->hashName());
    }

    public function test_product_validation_rejects_negative_prices_and_invalid_category(): void
    {
        $this->actingAs($this->owner1);

        // Kategori milik vendor 2 tidak boleh dipakai oleh vendor 1
        $response = $this->post(route('vendor.products.store'), [
            'name' => 'Barang Ilegal',
            'category_id' => $this->cat2->id,
            'daily_rate' => -10000,
            'late_fee_per_day' => -5000,
        ]);

        $response->assertSessionHasErrors(['category_id', 'daily_rate', 'late_fee_per_day']);
    }

    public function test_owner_can_update_product(): void
    {
        $this->actingAs($this->owner1);

        $product = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->cat1->id,
            'name' => 'Tenda Lama',
            'slug' => 'tenda-lama',
            'daily_rate' => 30000,
            'late_fee_per_day' => 15000,
        ]);

        $response = $this->put(route('vendor.products.update', $product), [
            'name' => 'Tenda Baru Diupdate',
            'category_id' => $this->cat1->id,
            'daily_rate' => 35000,
            'late_fee_per_day' => 20000,
            'description' => 'Deskripsi baru.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_items', [
            'id' => $product->id,
            'name' => 'Tenda Baru Diupdate',
            'daily_rate' => 35000,
            'late_fee_per_day' => 20000,
        ]);
    }

    public function test_owner_cannot_access_or_modify_other_vendor_product(): void
    {
        $productV2 = MasterItem::create([
            'vendor_id' => $this->vendor2->id,
            'category_id' => $this->cat2->id,
            'name' => 'Produk Milik Toko 2',
            'slug' => 'produk-milik-toko-2',
            'daily_rate' => 40000,
            'late_fee_per_day' => 20000,
        ]);

        $this->actingAs($this->owner1);

        // Edit request harus 404 (karena global scope) atau 403
        $this->get(route('vendor.products.edit', $productV2->id))
            ->assertNotFound();

        // Update request harus 404
        $this->put(route('vendor.products.update', $productV2->id), [
            'name' => 'Hacked Name',
            'category_id' => $this->cat1->id,
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
        ])->assertNotFound();

        // Delete request harus 404
        $this->delete(route('vendor.products.destroy', $productV2->id))
            ->assertNotFound();
    }

    public function test_owner_cannot_delete_product_when_units_are_rented(): void
    {
        $this->actingAs($this->owner1);

        $product = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->cat1->id,
            'name' => 'Tenda Yang Sedang Disewa',
            'slug' => 'tenda-yang-sedang-disewa',
            'daily_rate' => 45000,
            'late_fee_per_day' => 20000,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $product->id,
            'unit_code' => 'TND-RENTED-01',
            'status' => 'rented',
            'condition' => 'good',
        ]);

        $response = $this->delete(route('vendor.products.destroy', $product));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('master_items', ['id' => $product->id]);
    }

    public function test_owner_can_delete_product_safely(): void
    {
        $this->actingAs($this->owner1);

        $product = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->cat1->id,
            'name' => 'Tenda Bebas Hapus',
            'slug' => 'tenda-bebas-hapus',
            'daily_rate' => 45000,
            'late_fee_per_day' => 20000,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $product->id,
            'unit_code' => 'TND-SAFE-01',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $response = $this->delete(route('vendor.products.destroy', $product));

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('master_items', ['id' => $product->id]);
    }
}
