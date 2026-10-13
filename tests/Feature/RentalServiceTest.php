<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Jobs\ExpirePendingRentals;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AvailabilityService;
use App\Services\RentalService;
use Carbon\Carbon;
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

        Carbon::setTestNow('2026-10-10 10:00:00');

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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
                ['master_item_id' => $this->item2->id, 'qty' => 1],
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
        $this->assertTrue($rental->relationLoaded('details'));
        $this->assertEquals(Carbon::now()->addHours(2), $rental->expires_at);

        // Pastikan format kode booking TDK-YYYY-XXXXX (<= 20 karakter)
        $this->assertMatchesRegularExpression('/^TDK-\d{4}-[A-Z0-9]{5}$/', $rental->booking_code);
        $this->assertLessThanOrEqual(20, strlen($rental->booking_code));
    }

    public function test_create_booking_calculation_for_single_day_and_multi_day(): void
    {
        $this->createUnit($this->item1, 'TD-001');
        $this->createUnit($this->item1, 'TD-002');

        // 1 hari sewa (10 s/d 10)
        $rental1Day = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-10',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
        $this->assertEquals(1, $rental1Day->rental_days);
        $this->assertEquals(50000, (float) $rental1Day->subtotal);
        $this->assertEquals(50000, (float) $rental1Day->grand_total);

        // Multi hari sewa (10 s/d 14 = 5 hari)
        $rental5Days = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-14',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
        $this->assertEquals(5, $rental5Days->rental_days);
        $this->assertEquals(250000, (float) $rental5Days->subtotal);
        $this->assertEquals(250000, (float) $rental5Days->grand_total);
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
        $this->assertEquals('Jl. Merbabu No. 12', $rental->delivery_address);
    }

    public function test_create_booking_supports_guest_user_with_self_pickup(): void
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
            'pickup_method' => 'self_pickup',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($rental->isGuest());
        $this->assertSame('Budi Walkin', $rental->guest_name);
        $this->assertSame('3507123456780001', $rental->guest_id_number);
    }

    public function test_guest_delivery_is_rejected(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Metode pengantaran (delivery) hanya diperbolehkan untuk pengguna yang telah masuk (login)');

        $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => null,
            'guest_name' => 'Budi Walkin',
            'guest_phone' => '085712345678',
            'guest_id_number' => '3507123456780001',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'pickup_method' => 'delivery',
            'delivery_address' => 'Jl. Ijen No. 12',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_guest_booking_fails_without_required_guest_info(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data guest (nama, nomor telepon, dan nomor identitas/KTP) wajib diisi');

        $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => null,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_mixed_vendor_items_in_cart_is_rejected(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        $vendor2 = Vendor::query()->create([
            'name' => 'Vendor Lain',
            'slug' => 'vendor-lain',
            'phone' => '081299999999',
            'address' => 'Jl. Lain No. 2',
            'status' => 'active',
        ]);

        $category2 = ItemCategory::query()->create([
            'vendor_id' => $vendor2->id,
            'name' => 'Kategori Vendor Lain',
            'slug' => 'kategori-vendor-lain',
        ]);

        $itemOtherVendor = MasterItem::query()->create([
            'vendor_id' => $vendor2->id,
            'category_id' => $category2->id,
            'name' => 'Tenda Vendor Lain',
            'slug' => 'tenda-vendor-lain',
            'daily_rate' => 60000,
            'is_active' => true,
        ]);

        $this->createUnit($itemOtherVendor, 'TVL-001');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('berasal dari vendor yang berbeda');

        $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
                ['master_item_id' => $itemOtherVendor->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_create_booking_throws_insufficient_stock_exception_when_stock_insufficient(): void
    {
        // Hanya 1 unit tersedia, tapi minta 2 unit
        $this->createUnit($this->item1, 'TD-001');

        try {
            $this->service->createBooking([
                'vendor_id' => $this->vendor->id,
                'user_id' => $this->customer->id,
                'pickup_date' => '2026-10-10',
                'return_date' => '2026-10-12',
                'items' => [
                    ['master_item_id' => $this->item1->id, 'quantity' => 2],
                ],
            ]);
            $this->fail('Harusnya melempar InsufficientStockException');
        } catch (InsufficientStockException $e) {
            $this->assertSame($this->item1->id, $e->masterItem?->id);
            $this->assertStringContainsString("Stok alat camping 'Tenda Dome 4P' tidak mencukupi", $e->getMessage());
        }
    }

    public function test_full_rollback_when_booking_fails_midway(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        // Jumlah rental awal
        $initialRentalCount = Rental::count();
        $initialDetailCount = RentalDetail::count();

        // Buat mock AvailabilityService yang throw exception di tengah jalan
        $failingAvailability = $this->createMock(AvailabilityService::class);
        $failingAvailability->method('isAvailable')
            ->willThrowException(new RuntimeException('Simulasi kegagalan server di tengah transaksi'));

        $faultyService = new RentalService($failingAvailability);

        try {
            $faultyService->createBooking([
                'vendor_id' => $this->vendor->id,
                'user_id' => $this->customer->id,
                'pickup_date' => '2026-10-10',
                'return_date' => '2026-10-12',
                'items' => [
                    ['master_item_id' => $this->item1->id, 'quantity' => 1],
                ],
            ]);
            $this->fail('Seharusnya gagal');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulasi kegagalan server di tengah transaksi', $e->getMessage());
        }

        // Pastikan tidak ada data sampah di database (rollback penuh)
        $this->assertSame($initialRentalCount, Rental::count());
        $this->assertSame($initialDetailCount, RentalDetail::count());
    }

    /**
     * Catatan: Pengujian race condition asli hanya teruji di PostgreSQL dengan concurrent connection.
     * Test berikut mensimulasikan dua pemanggilan berurutan atas stok 1 unit, di mana pemanggilan kedua gagal.
     */
    public function test_consecutive_requests_for_single_unit_fails_on_second_request(): void
    {
        // Hanya ada 1 unit fisik tersedia
        $this->createUnit($this->item1, 'TD-001');

        // Request 1 berhasil membuat booking (status pending aktif)
        $rental1 = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
        $this->assertInstanceOf(Rental::class, $rental1);

        // Request 2 untuk tanggal overlap yang sama harus gagal karena stok sudah dipegang oleh request 1
        $this->expectException(InsufficientStockException::class);
        $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-11',
            'return_date' => '2026-10-13',
            'items' => [
                ['master_item_id' => $this->item1->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_expire_pending_rentals_job(): void
    {
        $this->createUnit($this->item1, 'TD-001');

        // 1. Rental pending yang sudah lewat waktu expires_at
        $expiredPending = Rental::query()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TDK-2026-EXP01',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'pending',
            'expires_at' => Carbon::now()->subMinute(), // telah lewat
        ]);

        // 2. Rental pending yang belum kedaluwarsa
        $activePending = Rental::query()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TDK-2026-ACT01',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        // 3. Rental confirmed yang expires_at lampau (TIDAK boleh disentuh)
        $confirmedRental = Rental::query()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TDK-2026-CNF01',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'confirmed',
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $job = new ExpirePendingRentals;
        $affected = $job->handle();

        $this->assertSame(1, $affected);
        $this->assertSame('expired', $expiredPending->fresh()->status);
        $this->assertSame('pending', $activePending->fresh()->status);
        $this->assertSame('confirmed', $confirmedRental->fresh()->status);

        // Eksekusi kedua (idempoten)
        $affectedSecond = $job->handle();
        $this->assertSame(0, $affectedSecond);
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
            'user_id' => $this->customer->id,
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
            'user_id' => $this->customer->id,
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
        $unit2 = $this->createUnit($this->item1, 'TD-002');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
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
