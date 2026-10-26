<?php

namespace Tests\Feature\Customer;

use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    private ItemCategory $category;

    private MasterItem $tenda;

    private MasterItem $kompor;

    private CampingPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Active Vendor',
            'slug' => 'tendaku-active-vendor',
            'phone' => '08123456789',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        $this->category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->tenda = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
        ]);

        $this->kompor = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Kompor Portable',
            'slug' => 'kompor-portable',
            'daily_rate' => 15000,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->tenda->id,
            'unit_code' => 'TND-001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->tenda->id,
            'unit_code' => 'TND-002',
            'status' => 'available',
            'condition' => 'good',
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->kompor->id,
            'unit_code' => 'KMP-001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $this->package = CampingPackage::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Paket Camping 2 Hari',
            'slug' => 'paket-camping-2-hari',
            'price' => 300000,
            'duration_days' => 2,
            'max_people' => 4,
            'is_active' => true,
        ]);

        $this->package->packageItems()->create([
            'master_item_id' => $this->tenda->id,
            'quantity' => 2,
        ]);

        $this->package->packageItems()->create([
            'master_item_id' => $this->kompor->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_page_renders_package_summary(): void
    {
        $response = $this->get(route('customer.packages.checkout', $this->package->id));

        $response->assertOk();
        $response->assertSee('Paket Camping 2 Hari');
        $response->assertSee('Rp 300.000');
        $response->assertSee('Tenda Dome 4P');
    }

    public function test_guest_checkout_creates_rental_using_package_price(): void
    {
        $pickupDate = Carbon::tomorrow();

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => $pickupDate->toDateString(),
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->firstOrFail();

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $response->assertSessionHas('success');

        $this->assertSame('300000.00', $rental->subtotal);
        $this->assertSame('300000.00', $rental->grand_total);
        $this->assertSame(2, $rental->rental_days);
        $this->assertSame('pending', $rental->status);
        $this->assertSame('Budi Santoso', $rental->guest_name);
        $this->assertSame($this->vendor->id, $rental->vendor_id);
        $this->assertSame($pickupDate->toDateString(), $rental->pickup_date->toDateString());
        $this->assertSame($pickupDate->copy()->addDays(2)->toDateString(), $rental->return_date->toDateString());
        $this->assertCount(3, $rental->details);
        $this->assertDatabaseHas('rental_details', [
            'rental_id' => $rental->id,
            'master_item_id' => $this->tenda->id,
        ]);
    }

    public function test_guest_checkout_requires_guest_identity(): void
    {
        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => Carbon::tomorrow()->toDateString(),
            'pickup_method' => 'self_pickup',
        ]);

        $response->assertSessionHasErrors(['guest_name', 'guest_phone', 'guest_id_number']);
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_guest_cannot_choose_delivery(): void
    {
        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => Carbon::tomorrow()->toDateString(),
            'pickup_method' => 'delivery',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('warning');
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_checkout_redirects_back_with_error_when_units_are_unavailable(): void
    {
        $this->package->packageItems()->where('master_item_id', $this->tenda->id)->update(['quantity' => 3]);

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => Carbon::tomorrow()->toDateString(),
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ]);

        $response->assertRedirect(route('customer.packages.show', $this->package->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_checkout_returns_404_when_package_contains_a_deleted_product(): void
    {
        $this->tenda->delete();

        $this->get(route('customer.packages.checkout', $this->package->id))
            ->assertNotFound();

        $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => Carbon::tomorrow()->toDateString(),
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ])->assertNotFound();

        $this->assertDatabaseCount('rentals', 0);
    }
}
