<?php

namespace Tests\Feature;

use App\Livewire\Customer\CatalogIndex;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Vendor $activeVendor;

    private Vendor $inactiveVendor;

    private ItemCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        $this->activeVendor = Vendor::create([
            'name' => 'Tendaku Active Vendor',
            'slug' => 'tendaku-active-vendor',
            'phone' => '08123456789',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        $this->inactiveVendor = Vendor::create([
            'name' => 'Vendor Non-Aktif',
            'slug' => 'vendor-non-aktif',
            'phone' => '08999999999',
            'address' => 'Jl. Tutup No. 2',
            'status' => 'suspended',
        ]);

        $this->category = ItemCategory::create([
            'vendor_id' => $this->activeVendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_catalog_index_page_can_be_rendered(): void
    {
        $response = $this->get(route('customer.catalog.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(CatalogIndex::class);
    }

    public function test_items_from_inactive_vendor_are_not_displayed(): void
    {
        $activeItem = MasterItem::create([
            'vendor_id' => $this->activeVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Aktif 4P',
            'slug' => 'tenda-aktif-4p',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);

        $inactiveItem = MasterItem::create([
            'vendor_id' => $this->inactiveVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Dari Toko Suspend',
            'slug' => 'tenda-dari-toko-suspend',
            'daily_rate' => 40000,
            'is_active' => true,
        ]);

        Livewire::test(CatalogIndex::class)
            ->assertSee('Tenda Aktif 4P')
            ->assertDontSee('Tenda Dari Toko Suspend');
    }

    public function test_inactive_items_are_not_displayed(): void
    {
        $activeItem = MasterItem::create([
            'vendor_id' => $this->activeVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Consina Aktif',
            'slug' => 'tenda-consina-aktif',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);

        $nonActiveItem = MasterItem::create([
            'vendor_id' => $this->activeVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Rusak Non-Aktif',
            'slug' => 'tenda-rusak-non-aktif',
            'daily_rate' => 30000,
            'is_active' => false,
        ]);

        Livewire::test(CatalogIndex::class)
            ->assertSee('Tenda Consina Aktif')
            ->assertDontSee('Tenda Rusak Non-Aktif');
    }

    public function test_catalog_filters_by_search_price_vendor_and_sort(): void
    {
        $vendor2 = Vendor::create([
            'name' => 'Vendor Kedua',
            'slug' => 'vendor-kedua',
            'phone' => '08777777777',
            'address' => 'Jl. Lawang No. 3',
            'status' => 'active',
        ]);

        $categoryCooking = ItemCategory::create([
            'vendor_id' => $this->activeVendor->id,
            'name' => 'Alat Masak',
            'slug' => 'alat-masak',
        ]);

        $itemTenda = MasterItem::create([
            'vendor_id' => $this->activeVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Consina 4P',
            'slug' => 'tenda-consina-4p',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);

        $itemKompor = MasterItem::create([
            'vendor_id' => $vendor2->id,
            'category_id' => $categoryCooking->id,
            'name' => 'Kompor Portable',
            'slug' => 'kompor-portable',
            'daily_rate' => 15000,
            'is_active' => true,
        ]);

        // Filter search
        Livewire::test(CatalogIndex::class)
            ->set('search', 'Consina')
            ->assertSee('Tenda Consina 4P')
            ->assertDontSee('Kompor Portable');

        // Filter vendor
        Livewire::test(CatalogIndex::class)
            ->set('vendorId', $vendor2->id)
            ->assertSee('Kompor Portable')
            ->assertDontSee('Tenda Consina 4P');

        // Filter harga min/max
        Livewire::test(CatalogIndex::class)
            ->set('minPrice', '40000')
            ->assertSee('Tenda Consina 4P')
            ->assertDontSee('Kompor Portable');

        // Filter kategori
        Livewire::test(CatalogIndex::class)
            ->call('selectCategory', $categoryCooking->id)
            ->assertSee('Kompor Portable')
            ->assertDontSee('Tenda Consina 4P');
    }

    public function test_catalog_pagination(): void
    {
        // Buat 15 item untuk menguji pagination 12 per halaman
        for ($i = 1; $i <= 15; $i++) {
            MasterItem::create([
                'vendor_id' => $this->activeVendor->id,
                'category_id' => $this->category->id,
                'name' => sprintf('Barang Camping %02d', $i),
                'slug' => "barang-camping-{$i}",
                'daily_rate' => 20000 + ($i * 1000),
                'is_active' => true,
            ]);
        }

        $component = Livewire::test(CatalogIndex::class);
        $this->assertCount(12, $component->viewData('items')->items());

        $component->call('setPage', 2);
        $this->assertCount(3, $component->viewData('items')->items());
    }

    public function test_product_detail_page_renders_with_real_data(): void
    {
        $item = MasterItem::create([
            'vendor_id' => $this->activeVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Dome Asli 4P',
            'slug' => 'tenda-dome-asli-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'is_active' => true,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->activeVendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TDA-001',
            'condition' => 'excellent',
            'status' => 'available',
            'purchase_date' => Carbon::now()->subMonths(6),
        ]);

        $response = $this->get(route('customer.product.show', $item->id));

        $response->assertStatus(200);
        $response->assertSee('Tenda Dome Asli 4P');
        $response->assertSee('Tendaku Active Vendor');
        $response->assertSee('Dibeli 6 bulan lalu');
    }

    public function test_product_detail_returns_404_when_item_not_found_or_vendor_inactive(): void
    {
        // ID tidak ada
        $response = $this->get(route('customer.product.show', 99999));
        $response->assertStatus(404);

        // Item milik vendor non-aktif (suspended)
        $suspendedItem = MasterItem::create([
            'vendor_id' => $this->inactiveVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Toko Suspended',
            'slug' => 'tenda-toko-suspended',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);

        $response2 = $this->get(route('customer.product.show', $suspendedItem->id));
        $response2->assertStatus(404);
    }
}
