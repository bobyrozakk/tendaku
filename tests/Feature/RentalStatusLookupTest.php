<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RentalStatusLookupTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Vendor $vendor;

    private MasterItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 45',
            'city' => 'Malang',
            'status' => 'active',
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

    public function test_guest_can_open_status_lookup_page_and_see_navigation(): void
    {
        $response = $this->get(route('customer.rental-status.index'));

        $response->assertOk();
        $response->assertSee('Status Sewa');
        $response->assertSee('Cek Status');
        $response->assertSee('receipt pemesanan');
        $response->assertSee('Tidak perlu membuat akun atau login');
    }

    public function test_guest_can_lookup_guest_rental_using_booking_code(): void
    {
        $rental = $this->createRental([
            'user_id' => null,
            'guest_name' => 'Anto Tamu',
            'guest_phone' => '081298765432',
            'guest_id_number' => '3507111122223333',
            'booking_code' => 'TDK-2026-GUEST1',
            'status' => 'picked_up',
            'picked_up_at' => now(),
        ]);

        $this->createDetail($rental);

        $lookup = $this->post(route('customer.rental-status.lookup'), [
            'booking_code' => '  #tdk-2026-guest1 ',
        ]);

        $lookup->assertRedirect(route('customer.rental-status.show', [
            'code' => 'TDK-2026-GUEST1',
        ]));

        $response = $this->get(route('customer.rental-status.show', [
            'code' => 'TDK-2026-GUEST1',
        ]));

        $response->assertOk();
        $response->assertSee('#TDK-2026-GUEST1');
        $response->assertSee('Anto Tamu');
        $response->assertSee('Tenda Consina 4P');
        $response->assertSee('Rp 150.000');
        $response->assertSee('Sedang Disewa');
        $response->assertSee('Alat Diambil');
        $response->assertSee('Saat ini');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // Data pribadi yang tidak diperlukan untuk pengecekan tidak ikut tampil.
        $response->assertDontSee('081298765432');
        $response->assertDontSee('3507111122223333');
    }

    public function test_registered_customer_rental_can_be_checked_without_login(): void
    {
        $customer = User::factory()->create([
            'name' => 'Budi Penyewa',
            'email' => 'budi@example.com',
            'phone' => '085712345678',
            'role' => 'customer',
        ]);

        $rental = $this->createRental([
            'user_id' => $customer->id,
            'booking_code' => 'TDK-2026-MEMBER1',
            'status' => 'returned',
            'returned_at' => now(),
        ]);

        $this->createDetail($rental);

        $response = $this->get(route('customer.rental-status.show', [
            'code' => 'TDK-2026-MEMBER1',
        ]));

        $response->assertOk();
        $response->assertSee('Budi Penyewa');
        $response->assertSee('Tenda Consina 4P');
        $response->assertSee('Menunggu Penyelesaian');
        $response->assertDontSee('budi@example.com');
        $response->assertDontSee('085712345678');
    }

    public function test_invalid_booking_code_shows_an_error_without_exposing_order_data(): void
    {
        $response = $this->from(route('customer.rental-status.index'))
            ->post(route('customer.rental-status.lookup'), [
                'booking_code' => 'TDK-TIDAK-ADA',
            ]);

        $response->assertRedirect(route('customer.rental-status.index'));
        $response->assertSessionHasErrors('booking_code');

        // Teruskan cookie sesi dari redirect agar halaman berikutnya memakai
        // sesi yang sama, seperti browser sungguhan.
        $page = $this->call(
            'GET',
            route('customer.rental-status.index'),
            [],
            collect($response->headers->getCookies())
                ->mapWithKeys(fn ($cookie) => [$cookie->getName() => $cookie->getValue()])
                ->all(),
        );

        $page->assertOk();
        $page->assertSee('Kode sewa tidak valid atau tidak ditemukan.');
    }

    public function test_direct_result_url_with_unknown_code_redirects_back_with_error(): void
    {
        $response = $this->get(route('customer.rental-status.show', [
            'code' => 'TDK-NOT-FOUND',
        ]));

        $response->assertRedirect(route('customer.rental-status.index'));
        $response->assertSessionHas(
            'error',
            'Kode sewa tidak valid atau tidak ditemukan. Periksa kembali kode pada receipt pemesanan Anda.'
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRental(array $attributes): Rental
    {
        return Rental::create($attributes + [
            'vendor_id' => $this->vendor->id,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'pickup_time' => '09:00:00',
            'return_time' => '09:00:00',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'pending',
        ]);
    }

    private function createDetail(Rental $rental): RentalDetail
    {
        return RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);
    }
}
