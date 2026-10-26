<?php

namespace Tests\Feature\Vendor;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMultiPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $owner;

    protected ItemCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Outdoor',
            'slug' => 'tendaku-vendor-outdoor',
            'phone' => '081234567890',
            'address' => 'Jl. Outdoor No. 12',
            'status' => 'active',
        ]);

        $this->owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor->id,
        ]);

        $this->category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);
    }

    public function test_vendor_can_create_product_with_single_photo_in_images_array(): void
    {
        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->image('tent_main.jpg', 600, 600)->size(500);

        $response = $this->post(route('vendor.products.store'), [
            'name' => 'Tenda Dome Eiger 2P',
            'category_id' => $this->category->id,
            'daily_rate' => 60000,
            'late_fee_per_day' => 20000,
            'description' => 'Tenda 2 orang ringan dan kokoh.',
            'images' => [$file],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        $product = MasterItem::where('slug', 'tenda-dome-eiger-2p')->first();
        $this->assertNotNull($product);
        $this->assertCount(1, $product->images);

        $image = $product->images->first();
        $this->assertTrue($image->is_primary);
        $this->assertEquals(1, $image->sort_order);
        $this->assertEquals($product->image_url, $image->image_url);

        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_vendor_can_create_product_with_maximum_5_photos(): void
    {
        $this->actingAs($this->owner);

        $files = [
            UploadedFile::fake()->image('tent_1.jpg', 600, 600)->size(300),
            UploadedFile::fake()->image('tent_2.png', 600, 600)->size(300),
            UploadedFile::fake()->image('tent_3.webp', 600, 600)->size(300),
            UploadedFile::fake()->image('tent_4.jpeg', 600, 600)->size(300),
            UploadedFile::fake()->image('tent_5.jpg', 600, 600)->size(300),
        ];

        $response = $this->post(route('vendor.products.store'), [
            'name' => 'Tenda Glamping King 6P',
            'category_id' => $this->category->id,
            'daily_rate' => 150000,
            'late_fee_per_day' => 50000,
            'description' => 'Tenda glamping mewah 6 orang.',
            'images' => $files,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        $product = MasterItem::where('slug', 'tenda-glamping-king-6p')->first();
        $this->assertNotNull($product);
        $this->assertCount(5, $product->images);

        $primaryImages = $product->images->where('is_primary', true);
        $this->assertCount(1, $primaryImages);

        $firstImage = $product->images->first();
        $this->assertTrue($firstImage->is_primary);
        $this->assertEquals(1, $firstImage->sort_order);
        $this->assertEquals($product->image_url, $firstImage->image_url);

        foreach ($product->images as $img) {
            Storage::disk('public')->assertExists($img->image_path);
        }
    }

    public function test_creation_fails_when_uploading_more_than_5_photos(): void
    {
        $this->actingAs($this->owner);

        $files = [
            UploadedFile::fake()->image('1.jpg'),
            UploadedFile::fake()->image('2.jpg'),
            UploadedFile::fake()->image('3.jpg'),
            UploadedFile::fake()->image('4.jpg'),
            UploadedFile::fake()->image('5.jpg'),
            UploadedFile::fake()->image('6.jpg'),
        ];

        $response = $this->from(route('vendor.products.create'))->post(route('vendor.products.store'), [
            'name' => 'Tenda Kelebihan Foto',
            'category_id' => $this->category->id,
            'daily_rate' => 40000,
            'late_fee_per_day' => 15000,
            'images' => $files,
        ]);

        $response->assertRedirect(route('vendor.products.create'));
        $response->assertSessionHasErrors('images');

        $this->assertDatabaseMissing('master_items', [
            'name' => 'Tenda Kelebihan Foto',
        ]);
    }

    public function test_creation_fails_with_invalid_file_format(): void
    {
        $this->actingAs($this->owner);

        $invalidFile = UploadedFile::fake()->create('manual.pdf', 500, 'application/pdf');

        $response = $this->from(route('vendor.products.create'))->post(route('vendor.products.store'), [
            'name' => 'Tenda Invalid File',
            'category_id' => $this->category->id,
            'daily_rate' => 40000,
            'late_fee_per_day' => 15000,
            'images' => [$invalidFile],
        ]);

        $response->assertRedirect(route('vendor.products.create'));
        $response->assertSessionHasErrors('images.0');

        $this->assertDatabaseMissing('master_items', [
            'name' => 'Tenda Invalid File',
        ]);
    }

    public function test_creation_fails_when_file_exceeds_2mb(): void
    {
        $this->actingAs($this->owner);

        $oversizedFile = UploadedFile::fake()->image('huge_photo.jpg')->size(3000); // 3MB

        $response = $this->from(route('vendor.products.create'))->post(route('vendor.products.store'), [
            'name' => 'Tenda Oversized File',
            'category_id' => $this->category->id,
            'daily_rate' => 40000,
            'late_fee_per_day' => 15000,
            'images' => [$oversizedFile],
        ]);

        $response->assertRedirect(route('vendor.products.create'));
        $response->assertSessionHasErrors('images.0');

        $this->assertDatabaseMissing('master_items', [
            'name' => 'Tenda Oversized File',
        ]);
    }

    public function test_vendor_can_view_product_detail_with_multiple_photos(): void
    {
        $this->actingAs($this->owner);

        $product = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Carrier Deuter 65L',
            'slug' => 'carrier-deuter-65l',
            'daily_rate' => 45000,
            'late_fee_per_day' => 20000,
            'image_url' => '/storage/products/carrier_1.jpg',
            'is_active' => true,
        ]);

        $product->images()->createMany([
            ['image_path' => 'products/carrier_1.jpg', 'image_url' => '/storage/products/carrier_1.jpg', 'is_primary' => true, 'sort_order' => 1],
            ['image_path' => 'products/carrier_2.jpg', 'image_url' => '/storage/products/carrier_2.jpg', 'is_primary' => false, 'sort_order' => 2],
            ['image_path' => 'products/carrier_3.jpg', 'image_url' => '/storage/products/carrier_3.jpg', 'is_primary' => false, 'sort_order' => 3],
        ]);

        $response = $this->get(route('vendor.products.show', $product));

        $response->assertOk();
        $response->assertSee('/storage/products/carrier_1.jpg');
        $response->assertSee('/storage/products/carrier_2.jpg');
        $response->assertSee('/storage/products/carrier_3.jpg');
    }

    public function test_customer_can_view_product_detail_with_multiple_photos(): void
    {
        $product = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Matras Angin Ultralight',
            'slug' => 'matras-angin-ultralight',
            'daily_rate' => 25000,
            'late_fee_per_day' => 10000,
            'image_url' => '/storage/products/matras_1.jpg',
            'is_active' => true,
        ]);

        $product->images()->createMany([
            ['image_path' => 'products/matras_1.jpg', 'image_url' => '/storage/products/matras_1.jpg', 'is_primary' => true, 'sort_order' => 1],
            ['image_path' => 'products/matras_2.jpg', 'image_url' => '/storage/products/matras_2.jpg', 'is_primary' => false, 'sort_order' => 2],
        ]);

        $response = $this->get(route('customer.product.show', $product->id));

        $response->assertOk();
        $response->assertSee('/storage/products/matras_1.jpg');
        $response->assertSee('/storage/products/matras_2.jpg');
    }

    public function test_vendor_can_edit_product_delete_photos_and_add_new_photos(): void
    {
        $this->actingAs($this->owner);

        // Siapkan 2 file di storage fake
        Storage::disk('public')->put('products/old_1.jpg', 'fake content 1');
        Storage::disk('public')->put('products/old_2.jpg', 'fake content 2');

        $product = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Sleeping Bag Polar',
            'slug' => 'sleeping-bag-polar',
            'daily_rate' => 20000,
            'late_fee_per_day' => 10000,
            'image_url' => Storage::url('products/old_1.jpg'),
            'is_active' => true,
        ]);

        $img1 = $product->images()->create([
            'image_path' => 'products/old_1.jpg',
            'image_url' => Storage::url('products/old_1.jpg'),
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $img2 = $product->images()->create([
            'image_path' => 'products/old_2.jpg',
            'image_url' => Storage::url('products/old_2.jpg'),
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $newFile1 = UploadedFile::fake()->image('new_1.jpg')->size(200);
        $newFile2 = UploadedFile::fake()->image('new_2.png')->size(200);

        // Hapus img1, tambahkan newFile1 dan newFile2 (sisa 1 lama + 2 baru = 3 foto)
        $response = $this->put(route('vendor.products.update', $product), [
            'name' => 'Sleeping Bag Polar Updated',
            'category_id' => $this->category->id,
            'daily_rate' => 25000,
            'late_fee_per_day' => 10000,
            'is_active' => '1',
            'deleted_images' => [$img1->id],
            'images' => [$newFile1, $newFile2],
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        // File lama img1 harus terhapus dari storage
        Storage::disk('public')->assertMissing('products/old_1.jpg');
        $this->assertDatabaseMissing('master_item_images', ['id' => $img1->id]);

        // File lama img2 harus tetap ada
        Storage::disk('public')->assertExists('products/old_2.jpg');
        $this->assertDatabaseHas('master_item_images', ['id' => $img2->id]);

        // Total foto sekarang 3
        $product->refresh();
        $this->assertCount(3, $product->images);

        // Karena img1 (primary awal) dihapus, img2 atau foto baru menjadi primary
        $primary = $product->images->firstWhere('is_primary', true);
        $this->assertNotNull($primary);
        $this->assertEquals($product->image_url, $primary->image_url);
    }

    public function test_edit_validation_fails_when_total_photos_exceed_5(): void
    {
        $this->actingAs($this->owner);

        $product = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Lentera Camping LED',
            'slug' => 'lentera-camping-led',
            'daily_rate' => 15000,
            'late_fee_per_day' => 5000,
            'image_url' => '/storage/products/led_1.jpg',
            'is_active' => true,
        ]);

        // Buat 3 foto lama
        for ($i = 1; $i <= 3; $i++) {
            $product->images()->create([
                'image_path' => "products/led_{$i}.jpg",
                'image_url' => "/storage/products/led_{$i}.jpg",
                'is_primary' => $i === 1,
                'sort_order' => $i,
            ]);
        }

        // Coba unggah 3 foto baru tanpa menghapus (3 lama + 3 baru = 6 > 5)
        $newFiles = [
            UploadedFile::fake()->image('new_1.jpg'),
            UploadedFile::fake()->image('new_2.jpg'),
            UploadedFile::fake()->image('new_3.jpg'),
        ];

        $response = $this->from(route('vendor.products.edit', $product))->put(route('vendor.products.update', $product), [
            'name' => 'Lentera Camping LED',
            'category_id' => $this->category->id,
            'daily_rate' => 15000,
            'late_fee_per_day' => 5000,
            'images' => $newFiles,
        ]);

        $response->assertRedirect(route('vendor.products.edit', $product));
        $response->assertSessionHasErrors('images');

        // Pastikan jumlah foto tetap 3
        $product->refresh();
        $this->assertCount(3, $product->images);
    }

    public function test_deleting_product_cleans_up_all_associated_photos_from_storage(): void
    {
        $this->actingAs($this->owner);

        Storage::disk('public')->put('products/tenda_del_1.jpg', 'content 1');
        Storage::disk('public')->put('products/tenda_del_2.jpg', 'content 2');

        $product = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Akan Dihapus',
            'slug' => 'tenda-akan-dihapus',
            'daily_rate' => 30000,
            'late_fee_per_day' => 10000,
            'image_url' => Storage::url('products/tenda_del_1.jpg'),
            'is_active' => true,
        ]);

        $product->images()->createMany([
            ['image_path' => 'products/tenda_del_1.jpg', 'image_url' => Storage::url('products/tenda_del_1.jpg'), 'is_primary' => true, 'sort_order' => 1],
            ['image_path' => 'products/tenda_del_2.jpg', 'image_url' => Storage::url('products/tenda_del_2.jpg'), 'is_primary' => false, 'sort_order' => 2],
        ]);

        $response = $this->delete(route('vendor.products.destroy', $product));

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        // File-file di storage harus terhapus
        Storage::disk('public')->assertMissing('products/tenda_del_1.jpg');
        Storage::disk('public')->assertMissing('products/tenda_del_2.jpg');
    }
}
