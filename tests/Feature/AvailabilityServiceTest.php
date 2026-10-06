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

    public function test_check_availability_returns_total_units_when_no_active_bookings(): void
    {
        // Buat 3 unit fisik
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

    public function test_check_availability_subtracts_overlapping_active_rentals(): void
    {
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');
        $this->createUnit('TD-003');

        // Buat rental aktif yang overlap (10-12 Oktober)
        $rental = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        // Cek pada tanggal yang overlap
        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-11',
            '2026-10-13'
        );

        // Dari 3 unit, 1 terpakai -> sisa 2
        $this->assertSame(2, $available);
    }

    public function test_check_availability_ignores_non_overlapping_rentals(): void
    {
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        // Rental di tanggal 1-3 Oktober (tidak overlap dengan 10-12 Oktober)
        $rental = $this->createRental('2026-10-01', '2026-10-03', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertSame(2, $available);
    }

    public function test_check_availability_ignores_cancelled_or_completed_rentals(): void
    {
        $unit1 = $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        // Rental cancelled
        $cancelledRental = $this->createRental('2026-10-10', '2026-10-12', 'cancelled');
        $this->createRentalDetail($cancelledRental, $unit1);

        // Rental completed
        $completedRental = $this->createRental('2026-10-10', '2026-10-12', 'completed');
        $this->createRentalDetail($completedRental, $unit1);

        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertSame(2, $available);
    }

    public function test_check_availability_excludes_maintenance_and_retired_units(): void
    {
        $this->createUnit('TD-001', 'available');
        $this->createUnit('TD-002', 'maintenance');
        $this->createUnit('TD-003', 'retired');

        $available = $this->service->checkAvailability(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        // Hanya TD-001 yang layak sewa
        $this->assertSame(1, $available);
    }

    public function test_is_available_checks_requested_quantity(): void
    {
        $this->createUnit('TD-001');
        $this->createUnit('TD-002');

        $this->assertTrue($this->service->isAvailable($this->item->id, 2, '2026-10-10', '2026-10-12'));
        $this->assertFalse($this->service->isAvailable($this->item->id, 3, '2026-10-10', '2026-10-12'));
        $this->assertFalse($this->service->isAvailable($this->item->id, 0, '2026-10-10', '2026-10-12'));
    }

    public function test_get_available_units_returns_correct_unallocated_models(): void
    {
        $unit1 = $this->createUnit('TD-001');
        $unit2 = $this->createUnit('TD-002');

        $rental = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        $availableUnits = $this->service->getAvailableUnits(
            $this->item->id,
            '2026-10-10',
            '2026-10-12'
        );

        $this->assertCount(1, $availableUnits);
        $this->assertTrue($availableUnits->first()->is($unit2));
    }

    public function test_check_batch_availability_for_multiple_items(): void
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

    public function test_get_booked_dates_returns_all_dates_in_active_booking(): void
    {
        $unit1 = $this->createUnit('TD-001');

        $rental = $this->createRental('2026-10-10', '2026-10-12', 'confirmed');
        $this->createRentalDetail($rental, $unit1);

        $bookedDates = $this->service->getBookedDates($this->item->id);

        $this->assertEqualsCanonicalizing([
            '2026-10-10',
            '2026-10-11',
            '2026-10-12',
        ], $bookedDates);
    }

    public function test_throws_exception_when_end_date_is_before_start_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->checkAvailability(
            $this->item->id,
            '2026-10-15',
            '2026-10-10'
        );
    }

    private function createUnit(string $code, string $status = 'available'): ItemUnit
    {
        return ItemUnit::query()->create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->item->id,
            'unit_code' => $code,
            'condition' => 'good',
            'status' => $status,
        ]);
    }

    private function createRental(string $pickupDate, string $returnDate, string $status = 'confirmed'): Rental
    {
        return Rental::query()->create([
            'vendor_id' => $this->vendor->id,
            'booking_code' => 'TDK-'.uniqid(),
            'pickup_date' => $pickupDate,
            'return_date' => $returnDate,
            'rental_days' => Carbon::parse($pickupDate)->diffInDays(Carbon::parse($returnDate)) + 1,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'status' => $status,
        ]);
    }

    private function createRentalDetail(Rental $rental, ?ItemUnit $unit = null): RentalDetail
    {
        return RentalDetail::query()->create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'item_unit_id' => $unit?->id,
            'daily_rate_snapshot' => 50000,
        ]);
    }
}
