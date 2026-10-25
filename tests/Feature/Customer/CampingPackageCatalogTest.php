<?php

namespace Tests\Feature\Customer;

use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampingPackageCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $activeVendor;

    private Vendor $suspendedVendor;

    private ItemCategory $category;

    private MasterItem $tenda;

    private ItemCategory $suspendedCategory;

    private MasterItem $suspendedItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeVendor = Vendor::create([
            'name' => 'Tendaku Active Vendor',
            'slug' => 'tendaku-active-vendor',
            'phone' => '08123456789',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        $this->suspendedVendor = Vendor::create([
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

        $this->suspendedCategory = ItemCategory::create([
            'vendor_id' => $this->suspendedVendor->id,
            'name' => 'Tenda Suspend',
            'slug' => 'tenda-suspend',
        ]);

        $this->tenda = MasterItem::create([
            'vendor_id' => $this->activeVendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
        ]);

        $this->suspendedItem = MasterItem::create([
            'vendor_id' => $this->suspendedVendor->id,
            'category_id' => $this->suspendedCategory->id,
            'name' => 'Tenda Vendor Suspend',
            'slug' => 'tenda-vendor-suspend',
            'daily_rate' => 40000,
        ]);
    }

    public function test_package_catalog_lists_only_active_packages_from_active_vendors(): void
    {
        $activePackage = $this->createPackage($this->activeVendor, 'Paket Aktif Akhir Pekan', 'paket-aktif-akhir-pekan');

        $this->createPackage($this->activeVendor, 'Paket Non-Aktif', 'paket-non-aktif', ['is_active' => false]);

        $this->createPackage($this->suspendedVendor, 'Paket Vendor Suspend', 'paket-vendor-suspend');

        $response = $this->get(route('customer.packages.index'));

        $response->assertOk();
        $response->assertSee('Paket Aktif Akhir Pekan');
        $response->assertDontSee('Paket Non-Aktif');
        $response->assertDontSee('Paket Vendor Suspend');
        $response->assertSee($activePackage->vendor->name);
    }

    public function test_package_detail_renders_package_items_and_price(): void
    {
        $package = $this->createPackage($this->activeVendor, 'Paket Detail Lengkap', 'paket-detail-lengkap', [
            'price' => 275000,
            'duration_days' => 3,
        ]);

        $package->packageItems()->create([
            'master_item_id' => $this->tenda->id,
            'quantity' => 2,
        ]);

        $response = $this->get(route('customer.packages.show', $package->id));

        $response->assertOk();
        $response->assertSee('Paket Detail Lengkap');
        $response->assertSee('Tenda Dome 4P');
        $response->assertSee('Rp 275.000');
        $response->assertSee('3 hari');
    }

    public function test_package_detail_returns_404_for_inactive_package_or_suspended_vendor(): void
    {
        $inactivePackage = $this->createPackage($this->activeVendor, 'Paket Tidak Tayang', 'paket-tidak-tayang', [
            'is_active' => false,
        ]);

        $this->get(route('customer.packages.show', $inactivePackage->id))
            ->assertNotFound();

        $suspendedPackage = $this->createPackage($this->suspendedVendor, 'Paket Suspend Detail', 'paket-suspend-detail');

        $this->get(route('customer.packages.show', $suspendedPackage->id))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPackage(Vendor $vendor, string $name, string $slug, array $overrides = []): CampingPackage
    {
        return CampingPackage::create(array_merge([
            'vendor_id' => $vendor->id,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Paket camping lengkap untuk petualangan akhir pekan.',
            'price' => 200000,
            'duration_days' => 2,
            'max_people' => 4,
            'is_active' => true,
        ], $overrides));
    }
}
