<?php

namespace Tests\Feature\Customer;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ReturnReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected MasterItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        config([
            'services.fonnte.token' => 'token-uji-123',
            'services.fonnte.retry_times' => 1,
            'services.fonnte.retry_sleep_ms' => 1,
            'notifications.channel' => 'whatsapp',
            'notifications.reminder.timezone' => 'Asia/Jakarta',
            'notifications.reminder.days' => [1, 0],
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Store Gudang',
            'slug' => 'tendaku-store-gudang',
            'email' => 'gudang@tendaku.com',
            'phone' => '081234567890',
            'address' => 'Jl. Outdoor No. 1, Malang',
            'city' => 'Malang',
            'status' => 'active',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda & Canopy',
            'slug' => 'tenda-canopy',
        ]);

        $this->item = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Consina Magnum 4P',
            'slug' => 'tenda-consina-magnum-4p',
            'description' => 'Tenda 4 orang double layer.',
            'daily_rate' => 45000,
            'is_active' => true,
        ]);

        foreach (['TD-001', 'TD-002'] as $code) {
            ItemUnit::create([
                'vendor_id' => $this->vendor->id,
                'master_item_id' => $this->item->id,
                'unit_code' => $code,
                'condition' => 'good',
                'status' => 'available',
                'purchase_date' => Carbon::now()->subMonths(4),
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_checkout_creates_rental_and_stores_whatsapp_number(): void
    {
        $response = $this->withSession($this->cartSession())->post(route('checkout.store'), [
            'pickup_method' => 'self_pickup',
            'payment_channel' => 'qris',
            'guest_name' => 'Tamu Tanpa Akun',
            'guest_phone' => '081299999999',
            'guest_id_number' => '1234567890123456',
        ]);

        $rental = Rental::withoutGlobalScopes()->sole();

        $response->assertRedirect(route('booking.status', $rental->booking_code));

        $this->assertNull($rental->user_id);
        $this->assertSame('Tamu Tanpa Akun', $rental->guest_name);
        $this->assertSame('081299999999', $rental->guest_phone);
        $this->assertSame('pending', $rental->status);
        $this->assertSame('2026-10-11', $rental->pickup_date->toDateString());
        $this->assertSame('2026-10-13', $rental->return_date->toDateString());
        $this->assertSame(3, (int) $rental->rental_days);
        $this->assertSame(1, $rental->details()->count());

        $this->assertNull(session('booking_cart'), 'Keranjang harus dikosongkan setelah checkout berhasil.');
    }

    public function test_logged_in_checkout_creates_a_pending_payment_for_the_grand_total(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->withSession($this->cartSession())
            ->post(route('checkout.store'), [
                'pickup_method' => 'self_pickup',
                'payment_channel' => 'qris',
            ])
            ->assertRedirect();

        $rental = Rental::withoutGlobalScopes()->sole();
        $payment = Payment::withoutGlobalScope('vendor')->sole();

        $this->assertSame($rental->id, $payment->rental_id);
        $this->assertSame($rental->vendor_id, $payment->vendor_id);
        $this->assertSame('settlement', $payment->payment_type);
        $this->assertSame('midtrans_snap', $payment->payment_method);
        $this->assertSame('qris', $payment->payment_channel);
        $this->assertSame('pending', $payment->status);
        $this->assertSame((float) $rental->grand_total, (float) $payment->amount);
    }

    public function test_guest_checkout_page_shows_whatsapp_input(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('No. WhatsApp Aktif')
            ->assertSee('name="guest_phone"', false)
            ->assertSee('name="guest_name"', false)
            ->assertSee('name="guest_id_number"', false);
    }

    public function test_guest_checkout_page_is_reachable_after_booking(): void
    {
        $this->withSession($this->cartSession())->post(route('checkout.store'), [
            'pickup_method' => 'self_pickup',
            'payment_channel' => 'qris',
            'guest_name' => 'Tamu Tanpa Akun',
            'guest_phone' => '081299999999',
            'guest_id_number' => '1234567890123456',
        ]);

        $rental = Rental::withoutGlobalScopes()->sole();

        $this->get(route('booking.status', $rental->booking_code).'?phone=081299999999')
            ->assertOk()
            ->assertSee($rental->booking_code);
    }

    public function test_guest_checkout_requires_whatsapp_number(): void
    {
        $this->withSession($this->cartSession())
            ->post(route('checkout.store'), [
                'pickup_method' => 'self_pickup',
                'payment_channel' => 'qris',
                'guest_name' => 'Tamu Tanpa Akun',
                'guest_id_number' => '1234567890123456',
            ])
            ->assertSessionHasErrors('guest_phone');

        $this->assertSame(0, Rental::withoutGlobalScopes()->count());
    }

    public function test_guest_checkout_rejects_invalid_whatsapp_number(): void
    {
        $this->withSession($this->cartSession())
            ->post(route('checkout.store'), [
                'pickup_method' => 'self_pickup',
                'payment_channel' => 'qris',
                'guest_name' => 'Tamu Tanpa Akun',
                'guest_phone' => '12345',
                'guest_id_number' => '1234567890123456',
            ])
            ->assertSessionHasErrors('guest_phone');

        $this->assertSame(0, Rental::withoutGlobalScopes()->count());
    }

    public function test_logged_in_checkout_links_rental_to_account_and_uses_profile_phone(): void
    {
        $customer = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
            'phone' => '081234567890',
            'role' => 'customer',
        ]);

        $this->actingAs($customer)
            ->withSession($this->cartSession())
            ->post(route('checkout.store'), [
                'pickup_method' => 'self_pickup',
                'payment_channel' => 'qris',
            ])
            ->assertRedirect();

        $rental = Rental::withoutGlobalScopes()->sole();

        $this->assertSame($customer->id, (int) $rental->user_id);
        $this->assertNull($rental->guest_phone, 'Pelanggan terdaftar memakai nomor profil, bukan kolom guest.');
    }

    public function test_checkout_page_shows_profile_phone_for_logged_in_customer(): void
    {
        $customer = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
            'phone' => '081234567890',
            'role' => 'customer',
        ]);

        $this->actingAs($customer)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('081234567890')
            ->assertSee('Pengingat Pengembalian via WhatsApp');
    }

    public function test_checkout_redirects_to_catalog_when_cart_is_empty(): void
    {
        $this->post(route('checkout.store'), [
            'pickup_method' => 'self_pickup',
            'payment_channel' => 'qris',
        ])->assertRedirect(route('customer.catalog.index'));

        $this->assertSame(0, Rental::withoutGlobalScopes()->count());
    }

    public function test_guest_is_redirected_to_login_when_choosing_delivery(): void
    {
        $this->withSession($this->cartSession())
            ->post(route('checkout.store'), [
                'pickup_method' => 'delivery',
                'payment_channel' => 'qris',
                'guest_name' => 'Tamu Tanpa Akun',
                'guest_phone' => '081299999999',
                'guest_id_number' => '1234567890123456',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Rental::withoutGlobalScopes()->count());
    }

    public function test_return_reminder_uses_whatsapp_number_captured_at_checkout(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->withSession($this->cartSession())->post(route('checkout.store'), [
            'pickup_method' => 'self_pickup',
            'payment_channel' => 'qris',
            'guest_name' => 'Tamu Tanpa Akun',
            'guest_phone' => '081299999999',
            'guest_id_number' => '1234567890123456',
        ]);

        $rental = Rental::withoutGlobalScopes()->sole();

        // Simulasikan barang sudah diambil dan jatuh tempo besok (H-1).
        $rental->update([
            'status' => 'picked_up',
            'return_date' => Carbon::now('Asia/Jakarta')->addDay()->toDateString(),
        ]);

        $this->artisan('rentals:send-return-reminders')->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request['target'] === '6281299999999'
            && str_contains($request['message'], 'Tamu Tanpa Akun')
            && str_contains($request['message'], 'BESOK'));

        $notification = NotificationLog::withoutGlobalScope('vendor')->sole();

        $this->assertSame('6281299999999', $notification->recipient_phone);
        $this->assertSame(ReturnReminderService::TYPE_H_MINUS_ONE, $notification->type);
        $this->assertSame('sent', $notification->status);
    }

    /**
     * Keranjang session hasil pilihan produk di halaman detail.
     *
     * @return array<string, mixed>
     */
    protected function cartSession(): array
    {
        return [
            'booking_cart' => [
                'master_item_id' => $this->item->id,
                'product_id' => $this->item->id,
                'product_name' => $this->item->name,
                'daily_rate' => 45000,
                'quantity' => 1,
                'qty' => 1,
                'pickup_date' => '2026-10-11',
                'return_date' => '2026-10-13',
                'total_days' => 3,
                'total_price' => 135000,
                'vendor_id' => $this->vendor->id,
                'category_name' => 'Tenda & Canopy',
            ],
        ];
    }
}
