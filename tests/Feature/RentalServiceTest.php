<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AvailabilityService;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class RentalServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private RentalService $service;

    private Vendor $vendor;

    private MasterItem $item1;

    private MasterItem $item2;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $availabilityService = new AvailabilityService;
        $this->service = new RentalService($availabilityService);

        $this->vendor = Vendor::query()->create([
            'name' => 'Tendaku Malang Test',
            'slug' => 'tendaku-malang-test',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10',
            'status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $category = ItemCategory::query()->create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Camping',
            'slug' => 'tenda-camping',
        ]);

        $this->item1 = MasterItem::query()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'is_active' => true,
        ]);

        $this->item2 = MasterItem::query()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Sleeping Bag Polar',
            'slug' => 'sleeping-bag-polar',
            'daily_rate' => 15000,
            'late_fee_per_day' => 10000,
            'is_active' => true,
        ]);
    }

    public function test_create_booking_successfully_creates_rental_and_details_when_stock_available(): void
    {
        // Siapkan 2 unit fisik item1 dan 1 unit item2
        $this->createUnit($this->item1, 'TD-001');
        $this->createUnit($this->item1, 'TD-002');
        $this->createUnit($this->item2, 'SB-001');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12', // 3 hari sewa
            'pickup_method' => 'self_pickup',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 2],
                ['master_item_id' => $this->item2->id, 'quantity' => 1],
            ],
        ]);

        $this->assertInstanceOf(Rental::class, $rental);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'rental_days' => 3,
            'status' => 'pending',
            'ktp_collateral_status' => 'pending',
        ]);

        // item1: 50.000 x 3 hari x 2 unit = 300.000
        // item2: 15.000 x 3 hari x 1 unit = 45.000
        // Total: 345.000
        $this->assertEquals(345000, (float) $rental->subtotal);
        $this->assertEquals(345000, (float) $rental->grand_total);
        $this->assertCount(3, $rental->details);
        $this->assertStringStartsWith('TDK-', $rental->booking_code);
    }

    public function test_create_booking_automatically_creates_pending_settlement_payment(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12', // 3 hari: 50.000 x 3 = 150.000
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => 150000,
            'status' => 'pending',
        ]);
    }

    public function test_create_booking_payment_amount_matches_grand_total_with_delivery_and_discount(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-10', // 1 hari: 50.000
            'pickup_method' => 'delivery',
            'delivery_address' => 'Jl. Merbabu No. 12',
            'delivery_fee' => 20000,
            'discount_amount' => 5000,
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        // grand_total = 50.000 + 20.000 - 5.000 = 65.000
        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'amount' => 65000,
            'status' => 'pending',
        ]);
    }

    public function test_create_booking_throws_exception_when_stock_is_insufficient(): void
    {
        // Hanya 1 unit tersedia, tapi minta 2 unit
        $this->createUnit($this->item1, 'TD-001');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Stok alat camping 'Tenda Dome 4P' tidak mencukupi");

        $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 2],
            ],
        ]);
    }

    public function test_create_booking_calculates_grand_total_with_delivery_and_discounts(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-10', // 1 hari
            'pickup_method' => 'delivery',
            'delivery_address' => 'Jl. Merbabu No. 12',
            'delivery_fee' => 20000,
            'discount_amount' => 10000,
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        // subtotal = 50.000, delivery = 20.000, discount = 10.000 -> grand_total = 60.000
        $this->assertEquals(50000, (float) $rental->subtotal);
        $this->assertEquals(20000, (float) $rental->delivery_fee);
        $this->assertEquals(10000, (float) $rental->discount_amount);
        $this->assertEquals(60000, (float) $rental->grand_total);
    }

    public function test_create_booking_supports_guest_user(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => null,
            'guest_name' => 'Budi Walkin',
            'guest_phone' => '085712345678',
            'guest_id_number' => '3507123456780001',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($rental->isGuest());
        $this->assertSame('Budi Walkin', $rental->guest_name);
        $this->assertSame('3507123456780001', $rental->guest_id_number);
    }

    public function test_create_booking_throws_exception_on_invalid_date_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'pickup_date' => '2026-10-15',
            'return_date' => '2026-10-10',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
    }

    private function createUnit(MasterItem $item, string $code): ItemUnit
    {
        return ItemUnit::query()->create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => $code,
            'condition' => 'good',
            'status' => 'available',
        ]);
    }
}
