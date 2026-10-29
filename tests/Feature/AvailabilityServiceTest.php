<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\Vendor;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private AvailabilityService $service;

    private Vendor $vendor;

    private MasterItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        $this->service = new AvailabilityService;

        $this->vendor = Vendor::query()->create([
            'name' => 'Tendaku Test Vendor',
            'slug' => 'tendaku-test-vendor',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1',
            'status' => 'active',
        ]);

        $category = ItemCategory::query()->create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->item = MasterItem::query()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_check_availability_returns_total_units_without_booking(): void
    {
        // Tanpa booking
        $this->createUnit('TD-001');
        $this->createUnit('TD-002');
        $this->createUnit('TD-003');

        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertSame(3, $available);
    }

    public function test_check_availability_subtracts_partial_overlapping_booking(): void
    {
        // Booking overlap sebagian
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');
        $this->createUnit('TD-003');

        // Booking 10-12 Oktober
        $rental = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        // Dicek pada 11-13 Oktober (overlap pada 11-12 Oktober)
        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-11',
            '2026-10-13'
        );

        // Dari 3 unit, 1 terpakai -> sisa 2
        $this->assertSame(2, $available);
    }

    public function test_check_availability_ignores_adjacent_non_overlapping_bookings(): void
    {
        // Booking berdampingan (tidak overlap)
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        // Booking pertama: 10-12 Oktober
        $rental = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        // Booking berdampingan tepat setelahnya (13-15 Oktober) tidak boleh mengurangi ketersediaan pada 10-12 Oktober
        $rentalAfter = $this->createRental('2026-10-13', '2026-10-15', 'confirmed');
        $this->createRentalDetail($rentalAfter, $unit1);

        // Cek pada tanggal 10-12 Oktober: hanya rental pertama yang overlap (1 unit), sisa 1
        $this->assertSame(1, $this->service->checkAvailability($this->item->id, '2026-10-10', '2026-10-12'));

        // Cek pada tanggal berdampingan tanpa overlap (16-18 Oktober): tidak ada overlap sama sekali
        $this->assertSame(2, $this->service->checkAvailability($this->item->id, '2026-10-16', '2026-10-18'));
    }

    public function test_expired_pending_rental_does_not_hold_stock(): void
    {
        // Pending kedaluwarsa tidak menahan stok
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        // Pending yang sudah kedaluwarsa (expires_at di masa lampau)
        $expiredPending = $this->createRental(
            '2026-10-10',
            '2026-10-12',
            'pending',
            Carbon::now()->subHour()
        );
        $this->createRentalDetail($expiredPending, $unit1);

        // Pending yang masih aktif (expires_at di masa depan)
        $activePending = $this->createRental(
            '2026-10-10',
            '2026-10-12',
            'pending',
            Carbon::now()->addHour()
        );
        $this->createRentalDetail($activePending, null); // unit_unit_id bisa null sebelum pickup

        // Dari 2 unit: expired pending tidak menahan, active pending menahan 1 unit -> sisa 1
        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertSame(1, $available);
    }

    public function test_damaged_lost_and_maintenance_units_are_not_counted(): void
    {
        // Unit damaged/maintenance tidak dihitung
        $this->createUnit('TD-001', 'available', 'good');
        $this->createUnit('TD-002', 'maintenance', 'good');
        $this->createUnit('TD-003', 'retired', 'good');
        $this->createUnit('TD-004', 'available', 'damaged');
        $this->createUnit('TD-005', 'available', 'lost');

        // Hanya TD-001 yang masuk stok kandidat
        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertSame(1, $available);
    }

    public function test_booked_dates_returns_only_dates_when_stock_is_exhausted(): void
    {
        // bookedDates HANYA saat stok habis (sisa stok = 0)
        $unit1 = $this->createUnit('TD-001');
        $unit2 = $this->createUnit('TD-002');

        // Rental 1: 10-12 Oktober memakai 1 unit
        $rental1 = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental1, $unit1);

        // Tanggal 10 dan 12 Oktober: terpakai 1 dari 2 unit -> stok belum habis (sisa 1)
        // Tanggal 11 Oktober: ada rental kedua yang memakai 1 unit lagi -> total terpakai 2 dari 2 unit -> stok habis!
        $rental2 = $this->createRental('2026-10-11', '2026-10-11', 'confirmed');
        $this->createRentalDetail($rental2, $unit2);

        $bookedDates = $this->service->bookedDates($this->item->id, '2026-10-10', '2026-10-13');

        // Hanya tanggal 2026-10-11 yang stoknya 0
        $this->assertSame(['2026-10-11'], $bookedDates);
    }

    public function test_two_vendors_do_not_affect_each_other(): void
    {
        // Dua vendor tidak saling mempengaruhi
        $vendor2 = Vendor::query()->create([
            'name' => 'Vendor Kedua',
            'slug' => 'vendor-kedua',
            'phone' => '08987654321',
            'address' => 'Jl. Lain',
            'status' => 'active',
        ]);

        $category2 = ItemCategory::query()->create([
            'vendor_id' => $vendor2->id,
            'name' => 'Kategori Vendor 2',
            'slug' => 'kategori-vendor-2',
        ]);

        $item2 = MasterItem::query()->create([
            'vendor_id' => $vendor2->id,
            'category_id' => $category2->id,
            'name' => 'Tenda Vendor 2',
            'slug' => 'tenda-vendor-2',
            'daily_rate' => 60000,
            'is_active' => true,
        ]);

        // Item 1 (vendor 1) punya 2 unit
        $this->createUnit('V1-001');
        $this->createUnit('V1-002');

        // Item 2 (vendor 2) punya 1 unit
        ItemUnit::query()->create([
            'vendor_id' => $vendor2->id,
            'master_item_id' => $item2->id,
            'unit_code' => 'V2-001',
            'condition' => 'good',
            'status' => 'available',
        ]);

        // Rental vendor 2 membooking item 2
        $rentalV2 = Rental::query()->create([
            'vendor_id' => $vendor2->id,
            'booking_code' => 'TDK-V2-'.uniqid(),
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 180000,
            'grand_total' => 180000,
            'status' => 'confirmed',
        ]);
        RentalDetail::query()->create([
            'rental_id' => $rentalV2->id,
            'master_item_id' => $item2->id,
            'daily_rate_snapshot' => 60000,
        ]);

        // Ketersediaan item 1 di vendor 1 tetap 2 (tidak terpengaruh oleh booking vendor 2)
        $this->assertSame(2, $this->service->checkAvailability($this->item->id, '2026-10-10', '2026-10-12'));
        // Ketersediaan item 2 di vendor 2 menjadi 0
        $this->assertSame(0, $this->service->checkAvailability($item2->id, '2026-10-10', '2026-10-12'));
    }

    public function test_available_units_returns_concrete_collection_for_pickup_assignment(): void
    {
        $unit1 = $this->createUnit('TD-001');
        $unit2 = $this->createUnit('TD-002');

        $rental = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        $availableUnits = $this->service->availableUnits(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertCount(1, $availableUnits);
        $this->assertTrue($availableUnits->first()->is($unit2));
    }

    public function test_returned_completed_and_cancelled_rentals_do_not_hold_stock(): void
    {
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        // Cancelled
        $cancelledRental = $this->createRental('2026-10-10', '2026-10-12', 'cancelled');
        $this->createRentalDetail($cancelledRental, $unit1);

        // Completed
        $completedRental = $this->createRental('2026-10-10', '2026-10-12', 'completed');
        $this->createRentalDetail($completedRental, $unit1);

        // Returned
        $returnedRental = $this->createRental('2026-10-10', '2026-10-12', 'returned');
        $this->createRentalDetail($returnedRental, $unit1);

        // Detail dengan is_returned = true
        $activeRental = $this->createRental('2026-10-10', '2026-10-12', 'picked_up');
        $detail = $this->createRentalDetail($activeRental, $unit1);
        $detail->update(['is_returned' => true]);

        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertSame(2, $available);
    }

    public function test_validation_throws_exception_when_end_date_before_start_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->checkAvailability(
            $this->item->id,
            '2026-10-15',
            '2026-10-10'
        );
    }

    public function test_validation_throws_exception_when_start_date_is_in_the_past(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->checkAvailability(
            $this->item->id,
            '2026-10-05',
            '2026-10-07'
        );
    }

    public function test_is_available_returns_correct_boolean(): void
    {
        $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        $this->assertTrue($this->service->isAvailable($this->item->id, 2, '2026-10-10', '2026-10-12'));
        $this->assertFalse($this->service->isAvailable($this->item->id, 3, '2026-10-10', '2026-10-12'));
        $this->assertFalse($this->service->isAvailable($this->item->id, 0, '2026-10-10', '2026-10-12'));
    }

    public function test_check_batch_availability(): void
    {
        $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        $result = $this->service->checkBatchAvailability([
            ['master_item_id' => $this->item->id, 'quantity' => 2],
        ], '2026-10-10', '2026-10-12');

        $this->assertTrue($result['all_available']);
        $this->assertSame(2, $result['items'][0]['available']);
        $this->assertTrue($result['items'][0]['is_available']);
    }

    private function createUnit(string $code, string $status = 'available', string $condition = 'good'): ItemUnit
    {
        return ItemUnit::query()->create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->item->id,
            'unit_code' => $code,
            'condition' => $condition,
            'status' => $status,
        ]);
    }

    private function createRental(
        string $pickupDate,
        string $returnDate,
        string $status = 'confirmed',
        ?Carbon $expiresAt = null
    ): Rental {
        return Rental::query()->create([
            'vendor_id' => $this->vendor->id,
            'booking_code' => 'TDK-'.uniqid(),
            'pickup_date' => $pickupDate,
            'return_date' => $returnDate,
            'rental_days' => Carbon::parse($pickupDate)->diffInDays(Carbon::parse($returnDate)) + 1,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);
    }

    private function createRentalDetail(Rental $rental, ?ItemUnit $unit = null): RentalDetail
    {
        return RentalDetail::query()->create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'item_unit_id' => $unit?->id,
            'daily_rate_snapshot' => 50000,
            'is_returned' => false,
        ]);
    }
}
