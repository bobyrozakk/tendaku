<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingStatusAndHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Vendor $vendor;

    private User $customer1;

    private User $customer2;

    private MasterItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 45',
            'city' => 'Malang',
            'status' => 'active',
        ]);

        $this->customer1 = User::factory()->create([
            'name' => 'Budi Penyewa',
            'email' => 'budi@example.com',
            'phone' => '085712345678',
            'role' => 'customer',
        ]);

        $this->customer2 = User::factory()->create([
            'name' => 'Siti Penyewa Lain',
            'email' => 'siti@example.com',
            'phone' => '085787654321',
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
            'name' => 'Tenda Consina 4P',
            'slug' => 'tenda-consina-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_customer_can_see_booking_history_with_status_filter_and_pagination(): void
    {
        // Buat 3 rental untuk customer 1
        $r1 = $this->createRental($this->customer1->id, 'TDK-2026-BUDI01', 'pending');
        $r2 = $this->createRental($this->customer1->id, 'TDK-2026-BUDI02', 'confirmed');
        $r3 = $this->createRental($this->customer1->id, 'TDK-2026-BUDI03', 'completed');

        // Buat 1 rental untuk customer 2 (tidak boleh terlihat oleh customer 1)
        $rOther = $this->createRental($this->customer2->id, 'TDK-2026-SITI01', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->get(route('customer.booking.index'));

        $response->assertStatus(200);
        $response->assertSee('TDK-2026-BUDI01');
        $response->assertSee('TDK-2026-BUDI02');
        $response->assertSee('TDK-2026-BUDI03');
        $response->assertDontSee('TDK-2026-SITI01');

        // Filter status 'confirmed'
        $responseFilter = $this->actingAs($this->customer1)
            ->get(route('customer.booking.index', ['status' => 'confirmed']));

        $responseFilter->assertStatus(200);
        $responseFilter->assertSee('TDK-2026-BUDI02');
        $responseFilter->assertDontSee('TDK-2026-BUDI01');
        $responseFilter->assertDontSee('TDK-2026-BUDI03');
    }

    public function test_unauthenticated_user_cannot_access_booking_history(): void
    {
        $response = $this->get(route('customer.booking.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_view_booking_status_with_real_data(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-STATUS1', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertStatus(200);
        $response->assertSee('#TDK-2026-STATUS1');
        $response->assertSee('Tendaku Vendor Store');
        $response->assertSee('Tenda Consina 4P');
        $response->assertDontSee('TDK-2026-B8921'); // Bukan data dummy
    }

    public function test_non_owner_is_forbidden_to_view_customer_booking_status(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-STATUS1', 'confirmed');

        // Customer 2 mencoba membuka rental milik customer 1
        $response = $this->actingAs($this->customer2)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertStatus(403);

        // Tamu tanpa login mencoba membuka rental terdaftar
        $responseGuest = $this->get(route('booking.status', $rental->booking_code));
        $responseGuest->assertStatus(403);
    }

    public function test_booking_status_returns_404_when_code_not_found(): void
    {
        $response = $this->actingAs($this->customer1)
            ->get(route('booking.status', 'TDK-NON-EXISTENT'));

        $response->assertStatus(404);
    }

    public function test_guest_can_access_booking_status_with_matching_phone_or_signed_url(): void
    {
        $guestRental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => null,
            'guest_name' => 'Anto Tamu',
            'guest_phone' => '081298765432',
            'guest_id_number' => '3507111122223333',
            'booking_code' => 'TDK-2026-GUEST1',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        RentalDetail::create([
            'rental_id' => $guestRental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);

        // 1. Tanpa nomor telepon -> 403 Forbidden
        $responseWithoutPhone = $this->get(route('booking.status', $guestRental->booking_code));
        $responseWithoutPhone->assertStatus(403);

        // 2. Dengan nomor telepon yang salah -> 403 Forbidden
        $responseWrongPhone = $this->get(route('booking.status', [
            'code' => $guestRental->booking_code,
            'phone' => '089999999999',
        ]));
        $responseWrongPhone->assertStatus(403);

        // 3. Dengan nomor telepon yang cocok -> 200 OK
        $responseValidPhone = $this->get(route('booking.status', [
            'code' => $guestRental->booking_code,
            'phone' => '081298765432',
        ]));
        $responseValidPhone->assertStatus(200);
        $responseValidPhone->assertSee('#TDK-2026-GUEST1');
        $responseValidPhone->assertSee('Tenda Consina 4P');

        // 4. Dengan signed URL -> 200 OK
        $signedUrl = URL::signedRoute('booking.status', ['code' => $guestRental->booking_code]);
        $responseSigned = $this->get($signedUrl);
        $responseSigned->assertStatus(200);
    }

    public function test_booking_status_displays_timeline_countdown_and_fines_correctly(): void
    {
        $rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer1->id,
            'booking_code' => 'TDK-2026-FINES1',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'total_late_fee' => 50000,
            'total_damage_fee' => 20000,
            'final_total' => 220000,
            'status' => 'returned',
            'ktp_collateral_status' => 'held',
            'ktp_received_at' => Carbon::now()->subDays(2),
        ]);

        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertStatus(200);
        $response->assertSee('Denda Keterlambatan');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Denda Kerusakan Unit');
        $response->assertSee('Rp 20.000');
        $response->assertSee('Rp 220.000');
        $response->assertSee('Bayar Denda');
    }

    public function test_customer_can_view_ktp_receipt(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-RCPT01', 'picked_up');
        $rental->update([
            'ktp_collateral_status' => 'held',
            'ktp_received_at' => Carbon::now(),
            'ktp_collateral_notes' => 'Disimpan di Loker A-01',
        ]);

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.receipt', $rental->booking_code));

        $response->assertStatus(200);
        $response->assertSee('REC-TDK-2026-RCPT01');
        $response->assertSee('Budi Penyewa');
        $response->assertSee('Disimpan di Loker A-01');
        $response->assertDontSee('Budi Santoso'); // Bukan data dummy
    }

    public function test_get_snap_token_for_pending_rental(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-SNAP01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-SNAP01-FULL-1',
            'amount' => 150000,
            'snap_token' => 'snap-token-test-12345',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer1)
            ->postJson(route('booking.pay', $rental->booking_code));

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'order_id' => 'TDK-2026-SNAP01-FULL-1',
                'snap_token' => 'snap-token-test-12345',
                'amount' => 150000,
            ]);
    }

    public function test_sync_payment_status_endpoint_returns_payment_status(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-SYNC01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-SYNC01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer1)
            ->postJson(route('booking.sync', $rental->booking_code));

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'order_id' => 'TDK-2026-SYNC01-FULL-1',
                'payment_status' => 'pending',
                'is_paid' => false,
            ]);
    }

    private function createRental(int $userId, string $bookingCode, string $status): Rental
    {
        $rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $userId,
            'booking_code' => $bookingCode,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => $status,
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);

        return $rental;
    }
}
