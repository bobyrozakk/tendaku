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

        $this->assertSame('600000.00', $rental->subtotal);
        $this->assertSame('600000.00', $rental->grand_total);
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scenario_1_rental_24_hours_from_0500_to_0500_next_day_is_calculated_as_one_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00'));

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-29',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame(1, $rental->rental_days);
        $this->assertSame('300000.00', $rental->subtotal);
        $this->assertSame('300000.00', $rental->grand_total);
        $this->assertSame('2026-10-28', $rental->pickup_date->toDateString());
        $this->assertSame('2026-10-29', $rental->return_date->toDateString());
        $this->assertStringStartsWith('05:00', (string) $rental->pickup_time);
        $this->assertStringStartsWith('05:00', (string) $rental->return_time);
    }

    public function test_scenario_2_rental_48_hours_is_calculated_as_two_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00'));

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-30',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame(2, $rental->rental_days);
        $this->assertSame('600000.00', $rental->subtotal);
        $this->assertSame('600000.00', $rental->grand_total);
    }

    public function test_scenario_3_rental_72_hours_is_calculated_as_three_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00'));

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-31',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame(3, $rental->rental_days);
        $this->assertSame('900000.00', $rental->subtotal);
        $this->assertSame('900000.00', $rental->grand_total);
    }

    public function test_scenario_4_rental_crossing_month_calculated_from_actual_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-30 00:00:00'));

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-31',
            'pickup_time' => '05:00',
            'return_date' => '2026-11-02',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame(2, $rental->rental_days);
        $this->assertSame('600000.00', $rental->subtotal);
        $this->assertSame('600000.00', $rental->grand_total);
        $this->assertSame('2026-10-31', $rental->pickup_date->toDateString());
        $this->assertSame('2026-11-02', $rental->return_date->toDateString());
    }

    public function test_scenario_5_rental_crossing_year_calculated_from_actual_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-30 00:00:00'));

        $response = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-12-31',
            'pickup_time' => '05:00',
            'return_date' => '2027-01-02',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame(2, $rental->rental_days);
        $this->assertSame('600000.00', $rental->subtotal);
        $this->assertSame('600000.00', $rental->grand_total);
        $this->assertSame('2026-12-31', $rental->pickup_date->toDateString());
        $this->assertSame('2027-01-02', $rental->return_date->toDateString());
    }

    public function test_scenario_6_return_time_before_or_equal_to_pickup_time_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00'));

        // Case A: Tanggal sama dan jam sama
        $responseA = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-28',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ]);

        $responseA->assertSessionHasErrors(['return_time']);
        $this->assertDatabaseCount('rentals', 0);

        // Case B: Tanggal sama, jam pengembalian lebih awal
        $responseB = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-28',
            'return_time' => '04:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ]);

        $responseB->assertSessionHasErrors(['return_time']);
        $this->assertDatabaseCount('rentals', 0);

        // Case C: Tanggal pengembalian sebelum tanggal pengambilan
        $responseC = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-27',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ]);

        $responseC->assertSessionHasErrors(['return_date']);
        $this->assertDatabaseCount('rentals', 0);

        // Case D: Tanggal pengambilan di masa lalu
        $responseD = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-26',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-28',
            'return_time' => '05:00',
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
        ]);

        $responseD->assertSessionHasErrors(['pickup_date']);
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_scenario_7_changing_dates_or_times_updates_duration_and_price_in_checkout_view(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00'));

        // Query 3 hari (72 jam)
        $response3Days = $this->get(route('customer.packages.checkout', [
            'id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-31',
            'return_time' => '05:00',
        ]));

        $response3Days->assertOk();
        $response3Days->assertViewHas('rentalDays', 3);
        $response3Days->assertViewHas('subtotal', 900000.0);
        $response3Days->assertSee('3 hari sewa');
        $response3Days->assertSee('Rp 900.000');

        // Query 1 hari (24 jam)
        $response1Day = $this->get(route('customer.packages.checkout', [
            'id' => $this->package->id,
            'pickup_date' => '2026-10-28',
            'pickup_time' => '05:00',
            'return_date' => '2026-10-29',
            'return_time' => '05:00',
        ]));

        $response1Day->assertOk();
        $response1Day->assertViewHas('rentalDays', 1);
        $response1Day->assertViewHas('subtotal', 300000.0);
        $response1Day->assertSee('1 hari sewa');
        $response1Day->assertSee('Rp 300.000');
    }

    public function test_scenario_8_frontend_and_backend_calculation_consistency_with_ceil_rounding(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00'));

        // Durasi 25 jam (05:00 ke 06:00 keesokan hari) melebihi 24 jam -> dibulatkan ke atas jadi 2 hari sewa
        $pickupDate = '2026-10-28';
        $pickupTime = '05:00';
        $returnDate = '2026-10-29';
        $returnTime = '06:00';

        // 1. Konsistensi tampilan frontend / checkout view
        $responseView = $this->get(route('customer.packages.checkout', [
            'id' => $this->package->id,
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'return_date' => $returnDate,
            'return_time' => $returnTime,
        ]));

        $responseView->assertOk();
        $responseView->assertViewHas('rentalDays', 2);
        $responseView->assertViewHas('subtotal', 600000.0);
        $responseView->assertSee('2 hari sewa');
        $responseView->assertSee('Rp 600.000');

        // 2. Konsistensi backend penyimpanan rental
        $responseStore = $this->post(route('customer.packages.checkout.store', $this->package->id), [
            'package_id' => $this->package->id,
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'return_date' => $returnDate,
            'return_time' => $returnTime,
            'pickup_method' => 'self_pickup',
            'guest_name' => 'Budi Santoso',
            'guest_phone' => '081234567890',
            'guest_id_number' => '3273010101010001',
            'payment_channel' => 'qris',
        ]);

        $rental = Rental::query()->latest('id')->firstOrFail();
        $responseStore->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame(2, $rental->rental_days);
        $this->assertSame('600000.00', $rental->subtotal);
        $this->assertSame('600000.00', $rental->grand_total);
    }
}
