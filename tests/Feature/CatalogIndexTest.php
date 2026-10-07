<?php

namespace Tests\Feature;

use App\Livewire\Customer\CatalogIndex;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_page_can_be_rendered(): void
    {
        $response = $this->get(route('customer.catalog.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(CatalogIndex::class);
    }

    public function test_catalog_index_can_search_items_in_realtime(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'slug' => 'test-vendor',
            'email' => 'vendor@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $item1 = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Consina Magnum',
            'slug' => 'tenda-consina-magnum',
            'description' => 'Tenda 4 orang',
            'daily_rate' => 45000,
            'is_active' => true,
        ]);

        $item2 = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Sleeping Bag Eiger',
            'slug' => 'sleeping-bag-eiger',
            'description' => 'SB hangat',
            'daily_rate' => 20000,
            'is_active' => true,
        ]);

        Livewire::test(CatalogIndex::class)
            ->assertSee('Tenda Consina Magnum')
            ->assertSee('Sleeping Bag Eiger')
            ->set('search', 'Consina')
            ->assertSee('Tenda Consina Magnum')
            ->assertDontSee('Sleeping Bag Eiger');
    }

    public function test_catalog_index_can_filter_by_category(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'slug' => 'test-vendor',
            'email' => 'vendor2@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $catTenda = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $catCooking = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cooking',
            'slug' => 'cooking',
        ]);

        $itemTenda = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $catTenda->id,
            'name' => 'Tenda Dome 2P',
            'slug' => 'tenda-dome-2p',
            'daily_rate' => 30000,
            'is_active' => true,
        ]);

        $itemKompor = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $catCooking->id,
            'name' => 'Kompor Portable Kovar',
            'slug' => 'kompor-portable-kovar',
            'daily_rate' => 15000,
            'is_active' => true,
        ]);

        Livewire::test(CatalogIndex::class)
            ->call('selectCategory', $catCooking->id)
            ->assertSee('Kompor Portable Kovar')
            ->assertDontSee('Tenda Dome 2P');
    }
}
