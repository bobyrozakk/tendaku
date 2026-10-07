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

    public function test_process_pickup_successfully_assigns_units_holds_ktp_and_updates_status(): void
    {
        $unit1 = $this->createUnit($this->item1, 'TD-001');
        $unit2 = $this->createUnit($this->item2, 'SB-001');
        $staff = User::factory()->create(['role' => 'vendor_admin']);

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
                ['master_item_id' => $this->item2->id, 'quantity' => 1],
            ],
        ]);

        $detail1 = $rental->details->where('master_item_id', $this->item1->id)->first();
        $detail2 = $rental->details->where('master_item_id', $this->item2->id)->first();

        $pickedUpRental = $this->service->processPickup(
            $rental,
            [
                $detail1->id => ['item_unit_id' => $unit1->id, 'condition_before' => 'excellent'],
                $detail2->id => ['item_unit_id' => $unit2->id, 'condition_before' => 'good', 'checklist_notes' => 'Lengkap dengan cover'],
            ],
            'https://storage.tendaku.com/collaterals/ktp_budi_20261010.jpg',
            $staff->id,
            'Disimpan di Loker Jaminan A-04'
        );

        $this->assertSame('picked_up', $pickedUpRental->status);
        $this->assertSame('held', $pickedUpRental->ktp_collateral_status);
        $this->assertSame('https://storage.tendaku.com/collaterals/ktp_budi_20261010.jpg', $pickedUpRental->ktp_collateral_photo_url);
        $this->assertSame($staff->id, $pickedUpRental->picked_up_by);
        $this->assertSame('Disimpan di Loker Jaminan A-04', $pickedUpRental->ktp_collateral_notes);
        $this->assertNotNull($pickedUpRental->picked_up_at);
        $this->assertNotNull($pickedUpRental->ktp_received_at);

        // Pastikan status unit fisik berubah jadi rented
        $this->assertSame('rented', $unit1->fresh()->status);
        $this->assertSame('rented', $unit2->fresh()->status);

        // Pastikan detail terupdate dengan unit dan kondisi awal
        $this->assertSame($unit1->id, $detail1->fresh()->item_unit_id);
        $this->assertSame('excellent', $detail1->fresh()->condition_before);
        $this->assertSame($unit2->id, $detail2->fresh()->item_unit_id);
        $this->assertSame('Lengkap dengan cover', $detail2->fresh()->checklist_notes);
    }

    public function test_process_pickup_throws_exception_when_ktp_photo_is_empty(): void
    {
        $unit1 = $this->createUnit($this->item1, 'TD-001');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        $detail1 = $rental->details->first();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Foto verifikasi wajah penyewa memegang KTP fisik wajib disertakan');

        $this->service->processPickup(
            $rental,
            [$detail1->id => $unit1->id],
            '   '
        );
    }

    public function test_process_pickup_throws_exception_when_unit_is_in_maintenance(): void
    {
        // Siapkan 1 unit bagus agar booking berhasil dibuat
        $this->createUnit($this->item1, 'TD-001');

        $brokenUnit = ItemUnit::query()->create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'TD-BROKEN',
            'condition' => 'damaged',
            'status' => 'maintenance',
        ]);

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        $detail1 = $rental->details->first();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unit fisik 'TD-BROKEN' tidak siap pakai");

        $this->service->processPickup(
            $rental,
            [$detail1->id => $brokenUnit->id],
            'https://storage.tendaku.com/ktp.jpg'
        );
    }

    public function test_process_pickup_throws_exception_when_same_unit_assigned_twice(): void
    {
        $unit1 = $this->createUnit($this->item1, 'TD-001');
        $this->createUnit($this->item1, 'TD-002');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 2],
            ],
        ]);

        $details = $rental->details->values();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak boleh dialokasikan lebih dari satu kali');

        $this->service->processPickup(
            $rental,
            [
                $details[0]->id => $unit1->id,
                $details[1]->id => $unit1->id,
            ],
            'https://storage.tendaku.com/ktp.jpg'
        );
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
