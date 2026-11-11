<?php

namespace Tests\Feature\Customer;

use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\User;
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

    public function test_guest_cannot_access_camping_package_catalog_and_is_redirected_to_login(): void
    {
        $this->get(route('customer.packages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_camping_package_detail_and_is_redirected_to_login(): void
    {
        $activePackage = $this->createPackage($this->activeVendor, 'Paket Aktif', 'paket-aktif');

        $this->get(route('customer.packages.show', $activePackage->id))
            ->assertRedirect(route('login'));
    }

    public function test_non_customer_cannot_access_camping_package_catalog(): void
    {
        $vendorStaff = User::factory()->create(['role' => 'vendor_owner']);

        $this->actingAs($vendorStaff)
            ->get(route('customer.packages.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_package_catalog_lists_only_active_packages_from_active_vendors(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $activePackage = $this->createPackage($this->activeVendor, 'Paket Aktif Akhir Pekan', 'paket-aktif-akhir-pekan');

        $this->createPackage($this->activeVendor, 'Paket Non-Aktif', 'paket-non-aktif', ['is_active' => false]);

        $this->createPackage($this->suspendedVendor, 'Paket Vendor Suspend', 'paket-vendor-suspend');

        $response = $this->actingAs($customer)->get(route('customer.packages.index'));

        $response->assertOk();
        $response->assertSee('Paket Aktif Akhir Pekan');
        $response->assertDontSee('Paket Non-Aktif');
        $response->assertDontSee('Paket Vendor Suspend');
        $response->assertSee($activePackage->vendor->name);
    }

    public function test_package_detail_renders_package_items_and_price(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $package = $this->createPackage($this->activeVendor, 'Paket Detail Lengkap', 'paket-detail-lengkap', [
            'price' => 275000,
            'duration_days' => 3,
        ]);

        $package->packageItems()->create([
            'master_item_id' => $this->tenda->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($customer)->get(route('customer.packages.show', $package->id));

        $response->assertOk();
        $response->assertSee('Paket Detail Lengkap');
        $response->assertSee('Tenda Dome 4P');
        $response->assertSee('Rp 275.000');
        $response->assertSee('Harga Paket per Hari');
        $response->assertSee('/ hari');
        $response->assertSee($package->description);
        $response->assertSee($this->activeVendor->name);
        $response->assertSee('2 item');
        $response->assertSee('2x');
        $response->assertSee('3 hari');

        foreach (['pickup_date', 'pickup_time', 'return_date', 'return_time', 'duration_days', 'pickup_method', 'customer_address_id'] as $field) {
            $response->assertDontSee('name="'.$field.'"', false);
        }
        $response->assertDontSee('Estimasi Paket:');
        $response->assertDontSee('Diantar (+Rp 25.000)');
        $response->assertDontSee('packagePrice:', false);

        preg_match_all('/<a\b[^>]*href="([^"]+)"[^>]*>\s*Sewa Paket\s*<\/a>/u', $response->getContent(), $buttons);

        $this->assertSame([route('customer.packages.checkout', ['id' => $package->id])], $buttons[1]);
    }

    public function test_package_detail_returns_404_for_inactive_package_or_suspended_vendor(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $inactivePackage = $this->createPackage($this->activeVendor, 'Paket Tidak Tayang', 'paket-tidak-tayang', [
            'is_active' => false,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.packages.show', $inactivePackage->id))
            ->assertNotFound();

        $suspendedPackage = $this->createPackage($this->suspendedVendor, 'Paket Suspend Detail', 'paket-suspend-detail');

        $this->actingAs($customer)
            ->get(route('customer.packages.show', $suspendedPackage->id))
            ->assertNotFound();
    }

    public function test_catalog_rent_buttons_link_to_each_selected_package_detail(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $first = $this->createPackage($this->activeVendor, 'Paket Pertama', 'paket-pertama', ['price' => 50000]);
        $second = $this->createPackage($this->activeVendor, 'Paket Kedua', 'paket-kedua', ['price' => 70000]);

        $html = $this->actingAs($customer)->get(route('customer.packages.index'))->assertOk()->getContent();
        preg_match_all('/<a\b[^>]*href="([^"]+)"[^>]*>\s*Sewa Paket\s*<\/a>/u', $html, $buttons);

        $this->assertCount(2, $buttons[1]);
        $this->assertContains(route('customer.packages.show', ['id' => $first->id]), $buttons[1]);
        $this->assertContains(route('customer.packages.show', ['id' => $second->id]), $buttons[1]);
    }

    public function test_selected_package_checkout_ignores_unrelated_regular_cart(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $package = $this->createPackage($this->activeVendor, 'Paket Dua Alat', 'paket-dua-alat', ['price' => 50000]);
        $stove = $this->tenda->replicate();
        $stove->fill(['name' => 'Kompor', 'slug' => 'kompor', 'daily_rate' => 15000])->save();
        $package->packageItems()->createMany([
            ['master_item_id' => $this->tenda->id, 'quantity' => 1],
            ['master_item_id' => $stove->id, 'quantity' => 1],
        ]);
        $this->actingAs($customer)->withSession(['booking_cart' => [
            'master_item_id' => $stove->id, 'quantity' => 1,
            'pickup_date' => now()->addDay()->toDateString(), 'return_date' => now()->addDays(2)->toDateString(),
        ]]);
        $html = $this->get(route('customer.packages.index'))->assertOk()->getContent();
        preg_match('/<a\b[^>]*href="([^"]+)"[^>]*>\s*Sewa Paket\s*<\/a>/u', $html, $button);
        $this->assertArrayHasKey(1, $button);

        $detail = $this->get($button[1])->assertOk()->assertViewIs('customer.packages.show');
        preg_match('/<a\b[^>]*href="([^"]+)"[^>]*>\s*Sewa Paket\s*<\/a>/u', $detail->getContent(), $bookingButton);
        $this->assertArrayHasKey(1, $bookingButton);
        $this->assertSame(route('customer.packages.checkout', ['id' => $package->id]), $bookingButton[1]);

        $this->get($bookingButton[1])->assertOk()->assertViewIs('customer.checkout.package')
            ->assertViewHas('package', fn (CampingPackage $selected): bool => $selected->id === $package->id
                && (float) $selected->price === 50000.0
                && $selected->packageItems->count() === 2
                && (int) $selected->packageItems->sum('quantity') === 2)
            ->assertSee('Rp 50.000')->assertSee('Tenda Dome 4P')->assertSee('Kompor')
            ->assertSee('action="'.route('customer.packages.checkout.store', ['id' => $package->id]).'"', false)
            ->assertSee('name="package_id" value="'.$package->id.'"', false)
            ->assertSee('name="pickup_date"', false)->assertSee('name="pickup_time"', false)
            ->assertSee('name="return_date"', false)->assertSee('name="return_time"', false)
            ->assertSee('Durasi Sewa')->assertSee('name="pickup_method"', false)
            ->assertSee('Pilih Alamat Pengantaran')->assertSee('Tambah Alamat Baru')
            ->assertSee('Diantar (ongkir otomatis)');
        $this->assertSame($stove->id, session('booking_cart.master_item_id'));
        $this->assertSame(1, session('booking_cart.quantity'));
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
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
