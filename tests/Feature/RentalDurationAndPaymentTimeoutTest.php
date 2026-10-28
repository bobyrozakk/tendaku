<?php

namespace Tests\Feature;

use App\Jobs\ExpirePendingRentals;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

class RentalDurationAndPaymentTimeoutTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-server-key';

    private Vendor $vendor;

    private User $customer;

    private MasterItem $item;

    private RentalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Official',
            'slug' => 'tendaku-vendor-official',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10, Malang',
            'status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Customer Antigravity',
            'email' => 'customer@tendaku.test',
            'role' => 'customer',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->item = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome Arpenaz 4.1',
            'slug' => 'tenda-dome-arpenaz-41',
            'daily_rate' => 60000,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            ItemUnit::create([
                'vendor_id' => $this->vendor->id,
                'master_item_id' => $this->item->id,
                'unit_code' => "TND-ARP-00{$i}",
                'status' => 'available',
                'condition' => 'good',
            ]);
        }

        $this->service = app(RentalService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Skenario 1:
     * Sewa 24 jam dari pukul 05.00 sampai pukul 05.00 keesokan harinya tetap dihitung 1 hari.
     * Meskipun tanggal pengembalian berbeda dengan tanggal pengambilan, customer hanya dikenakan tarif 1 hari.
     */
    public function test_scenario_1_rental_24_hours_crossing_calendar_dates_is_calculated_as_one_day(): void
    {
        // Bekukan waktu sebelum tanggal pickup agar validasi 'tanggal lampau' tidak gagal.
        Carbon::setTestNow('2026-10-25 09:00:00');

        $pickupDate = '2026-10-26';
        $pickupTime = '05:00:00';
        $returnDate = '2026-10-27';
        $returnTime = '05:00:00';

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'return_date' => $returnDate,
            'return_time' => $returnTime,
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(1, $rental->rental_days);
        $this->assertEquals(60000.0, (float) $rental->subtotal);
        // grand_total = subtotal + biaya layanan & admin 5%
        $this->assertEquals(63000.0, (float) $rental->grand_total);
        $this->assertSame('2026-10-26', $rental->pickup_date->format('Y-m-d'));
        $this->assertSame('05:00:00', $rental->pickup_time);
        $this->assertSame('2026-10-27', $rental->return_date->format('Y-m-d'));
        $this->assertSame('05:00:00', $rental->return_time);
    }

    /**
     * Skenario 2:
     * Durasi yang melewati 24 jam dihitung sesuai aturan tarif project (misal 25 jam = 2 hari).
     */
    public function test_scenario_2_rental_duration_exceeding_24_hours_is_billed_accordingly(): void
    {
        // Bekukan waktu sebelum tanggal pickup agar validasi 'tanggal lampau' tidak gagal.
        Carbon::setTestNow('2026-10-25 09:00:00');

        // 25 jam sewa (26 Okt 05:00 s/d 27 Okt 06:00): lebih 1 jam dari 24 jam => 2 hari
        $rental25Hours = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-26',
            'pickup_time' => '05:00:00',
            'return_date' => '2026-10-27',
            'return_time' => '06:00:00',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(2, $rental25Hours->rental_days);
        $this->assertEquals(120000.0, (float) $rental25Hours->subtotal);
        $this->assertEquals(126000.0, (float) $rental25Hours->grand_total);

        // 48 jam pas (26 Okt 05:00 s/d 28 Okt 05:00) => tepat 2 hari
        $rental48Hours = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-26',
            'pickup_time' => '05:00:00',
            'return_date' => '2026-10-28',
            'return_time' => '05:00:00',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(2, $rental48Hours->rental_days);
        $this->assertEquals(120000.0, (float) $rental48Hours->subtotal);

        // 49 jam sewa (26 Okt 05:00 s/d 28 Okt 06:00) => 3 hari
        $rental49Hours = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-26',
            'pickup_time' => '05:00:00',
            'return_date' => '2026-10-28',
            'return_time' => '06:00:00',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(3, $rental49Hours->rental_days);
        $this->assertEquals(180000.0, (float) $rental49Hours->subtotal);
    }

    /**
     * Skenario 3:
     * Countdown pembayaran berjalan realtime dan tetap akurat setelah halaman direfresh.
     * Menggunakan expires_at tersimpan dari server (5 menit), tidak kembali ke 5 menit saat refresh.
     */
    public function test_scenario_3_payment_countdown_is_server_timestamp_driven_and_persists_across_refreshes(): void
    {
        Carbon::setTestNow('2026-10-26 10:00:00');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-27',
            'return_date' => '2026-10-28',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        // Verifikasi batas waktu tersimpan di server sesuai config rental.payment_expiry_hours
        $expiryHours = (int) config('rental.payment_expiry_hours');
        $this->assertEquals(Carbon::now()->addHours($expiryHours), $rental->expires_at);
        $expectedTimestamp = (string) $rental->fresh()->expires_at->timestamp;
        // View mengirim batas waktu ke Alpine (statusPage) dalam format ISO 8601
        $expectedIso = $rental->fresh()->expires_at->toIso8601String();

        // Kunjungan awal saat T = 10:00:00
        $response1 = $this->actingAs($this->customer)
            ->get(route('booking.status', $rental->booking_code));

        $response1->assertOk();
        $response1->assertSee('Bayar sebelum');
        $response1->assertSee($expectedIso);

        // Customer me-refresh halaman 2 menit kemudian (T = 10:02:00)
        Carbon::setTestNow('2026-10-26 10:02:00');

        $response2 = $this->actingAs($this->customer)
            ->get(route('booking.status', $rental->booking_code));

        $response2->assertOk();
        // Server tetap mengirim batas waktu yang sama, tidak ter-reset
        $response2->assertSee($expectedIso);
        $this->assertSame((int) $expectedTimestamp, $rental->fresh()->expires_at->timestamp);

        Carbon::setTestNow();
    }

    /**
     * Skenario 4:
     * Booking yang belum dibayar dibatalkan/diubah menjadi expired setelah 5 menit.
     * Diuji melalui scheduler ExpirePendingRentals dan on-demand check.
     */
    public function test_scenario_4_unpaid_booking_expires_automatically_after_5_minutes(): void
    {
        Carbon::setTestNow('2026-10-26 10:00:00');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-27',
            'return_date' => '2026-10-28',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => $rental->grand_total,
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $rental->status);
        $this->assertSame('pending', $payment->status);

        // Majukan waktu sedikit melewati batas pembayaran (config rental.payment_expiry_hours)
        Carbon::setTestNow(
            Carbon::parse('2026-10-26 10:00:00')
                ->addHours((int) config('rental.payment_expiry_hours'))
                ->addSecond()
        );

        // Jalankan Scheduler Job ExpirePendingRentals
        ExpirePendingRentals::dispatchSync();

        $this->assertSame('expired', $rental->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);

        // Saat halaman status dibuka, booking sudah berstatus expired
        $response = $this->actingAs($this->customer)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertOk();
        $response->assertSee('Kedaluwarsa');

        Carbon::setTestNow();
    }

    /**
     * Skenario 5:
     * Booking yang dibayar sebelum batas waktu tidak dibatalkan oleh timeout.
     */
    public function test_scenario_5_booking_paid_before_timeout_is_not_cancelled(): void
    {
        Carbon::setTestNow('2026-10-26 10:00:00');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-27',
            'return_date' => '2026-10-28',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => $rental->grand_total,
            'status' => 'pending',
        ]);

        // Pembayaran berhasil diselesaikan pada menit ke-2 (T = 10:02:00)
        Carbon::setTestNow('2026-10-26 10:02:00');
        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        $rental->update(['status' => 'confirmed']);

        // Waktu bergulir hingga lewat 10 menit (T = 10:10:00)
        Carbon::setTestNow('2026-10-26 10:10:00');

        // Scheduler job berjalan
        ExpirePendingRentals::dispatchSync();

        // On-demand method juga dipanggil
        $wasExpired = $rental->fresh()->expireIfPastDue();

        $this->assertFalse($wasExpired);
        $this->assertSame('confirmed', $rental->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);

        Carbon::setTestNow();
    }

    /**
     * Skenario 6:
     * Booking yang sudah kedaluwarsa tidak dapat melanjutkan pembayaran.
     */
    public function test_scenario_6_expired_booking_cannot_proceed_with_payment(): void
    {
        Carbon::setTestNow('2026-10-26 10:00:00');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-27',
            'return_date' => '2026-10-28',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        Payment::create([
            'rental_id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => $rental->grand_total,
            'status' => 'pending',
        ]);

        // Waktu kedaluwarsa habis (T = 10:06:00)
        Carbon::setTestNow('2026-10-26 10:06:00');

        // Customer mencoba melakukan request Snap Token pembayaran
        $response = $this->actingAs($this->customer)
            ->postJson(route('rentals.pay', $rental));

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Batas waktu pembayaran telah habis. Booking telah kedaluwarsa dan tidak dapat dilanjutkan.',
        ]);

        $this->assertSame('expired', $rental->fresh()->status);

        Carbon::setTestNow();
    }

    /**
     * Skenario 7:
     * Pembayaran yang berhasil dikonfirmasi tidak tertimpa oleh proses pembatalan otomatis.
     * Mekanisme idempotent dan race-condition-safe dengan lockForUpdate.
     */
    public function test_scenario_7_confirmed_payment_is_never_overwritten_by_auto_cancellation(): void
    {
        Carbon::setTestNow('2026-10-26 10:00:00');

        $rental = $this->service->createBooking([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'pickup_date' => '2026-10-27',
            'return_date' => '2026-10-28',
            'items' => [
                ['master_item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => $rental->grand_total,
            'status' => 'paid', // Pembayaran berhasil masuk via webhook
            'paid_at' => now(),
        ]);

        // Simulasikan status rental masih pending karena proses async webhook delay
        $rental->update(['status' => 'pending']);

        // Waktu melampaui 5 menit
        Carbon::setTestNow('2026-10-26 10:15:00');

        // Jalankan scheduler pembatalan
        ExpirePendingRentals::dispatchSync();

        // Rental TIDAK BOLEH berubah menjadi 'expired' karena ada payment berstatus 'paid'
        $this->assertNotSame('expired', $rental->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);

        // Eksekusi expireIfPastDue() berulang kali (idempotent)
        $result = $rental->fresh()->expireIfPastDue();
        $this->assertFalse($result);
        $this->assertNotSame('expired', $rental->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);

        Carbon::setTestNow();
    }
}
