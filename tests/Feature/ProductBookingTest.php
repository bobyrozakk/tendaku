<?php

namespace Tests\Feature;

use App\Livewire\Customer\ProductBooking;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductBookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Vendor $vendor;

    private ItemCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Store Central',
            'slug' => 'tendaku-store-central',
            'email' => 'store@tendaku.com',
            'phone' => '08123456789',
            'address' => 'Jl. Outdoor No. 1, Malang',
            'city' => 'Malang',
            'status' => 'active',
        ]);

        $this->category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda & Canopy',
            'slug' => 'tenda-canopy',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Helper: buat produk lengkap dengan vendor & kategori. */
    private function createProduct(array $overrides = []): MasterItem
    {
        return MasterItem::create(array_merge([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Tenda Consina Magnum 4P',
            'slug' => 'tenda-consina-magnum-4p',
            'description' => 'Tenda 4 orang double layer.',
            'daily_rate' => 45000,
            'is_active' => true,
        ], $overrides));
    }

    private function createUnit(MasterItem $item, string $code, string $status = 'available'): ItemUnit
    {
        return ItemUnit::create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => $code,
            'condition' => 'good',
            'status' => $status,
            'purchase_date' => Carbon::now()->subMonths(4),
        ]);
    }

    public function test_it_mounts_with_default_pickup_tomorrow_and_return_day_after(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->assertSet('pickup_date', Carbon::tomorrow()->format('Y-m-d'))
            ->assertSet('return_date', Carbon::today()->addDays(2)->format('Y-m-d'))
            ->assertSet('quantity', 1);
    }

    public function test_it_calculates_total_days_correctly(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-10')
            ->set('pickup_time', '09:00')
            ->set('return_date', '2026-10-11')
            ->set('return_time', '09:00')
            // 24 jam penuh = 1 hari
            ->assertSet('totalDays', 1)
            ->set('return_date', '2026-10-13')
            // 3 x 24 jam = 3 hari
            ->assertSet('totalDays', 3);
    }

    public function test_it_calculates_total_price_as_daily_rate_times_qty_times_total_days(): void
    {
        $product = $this->createProduct(['daily_rate' => 45000]);

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-10')
            ->set('pickup_time', '09:00')
            ->set('return_date', '2026-10-13') // 3 hari (72 jam)
            ->set('return_time', '09:00')
            ->set('quantity', 2) // 2 unit
            ->assertSet('totalPrice', 45000 * 2 * 3);
    }

    public function test_it_returns_zero_days_when_return_before_pickup(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-15')
            ->set('return_date', '2026-10-10') // lebih awal dari pickup
            ->assertSet('totalDays', 0)
            ->assertSet('totalPrice', 0);
    }

    public function test_it_auto_corrects_return_date_when_pickup_set_later(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('duration_days', 1)
            ->set('return_date', '2026-10-10')
            ->set('pickup_date', '2026-10-15') // pickup diset setelah return
            ->assertSet('return_date', '2026-10-16'); // return harus ikut naik (24 jam = keesokan harinya)
    }

    public function test_it_shows_real_time_available_stock_via_availability_service(): void
    {
        $product = $this->createProduct();
        $this->createUnit($product, 'TD-001');
        $this->createUnit($product, 'TD-002');

        // Booking 1 unit di rentang 10-12 Oktober
        $rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'booking_code' => 'TDK-2026-TEST1',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 135000,
            'grand_total' => 135000,
            'status' => 'confirmed',
        ]);
        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $product->id,
            'daily_rate_snapshot' => 45000,
        ]);

        // Cek via Livewire untuk tanggal overlap (10-12 Okt): sisa 1 unit
        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-10')
            ->set('return_date', '2026-10-12')
            ->assertSet('availableStock', 1)
            ->assertSee('1 Unit');

        // Untuk tanggal lain di masa depan (15-16 Okt): sisa 2 unit
        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-15')
            ->set('return_date', '2026-10-16')
            ->assertSet('availableStock', 2)
            ->assertSee('2 Unit');
    }

    public function test_it_fails_when_requested_quantity_exceeds_available_stock(): void
    {
        $product = $this->createProduct();
        $this->createUnit($product, 'TD-001'); // Hanya ada 1 unit

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('duration_days', 2)
            ->set('pickup_date', '2026-10-10')
            ->set('return_date', '2026-10-12')
            ->set('quantity', 2) // Minta 2 unit padahal stok 1
            ->call('proceedToBooking')
            ->assertHasErrors(['quantity']);
    }

    public function test_it_fails_validation_when_pickup_date_is_in_the_past(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2020-01-01')
            ->set('return_date', '2020-01-03')
            ->call('proceedToBooking')
            ->assertHasErrors(['pickup_date' => 'after_or_equal']);
    }

    public function test_it_stores_booking_cart_in_session_and_redirects_on_success(): void
    {
        $product = $this->createProduct(['daily_rate' => 45000]);
        $this->createUnit($product, 'TD-001');
        $this->createUnit($product, 'TD-002');

        $pickup = '2026-10-10';
        $return = '2026-10-13'; // 3 hari (72 jam)

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('duration_days', 3)
            ->set('pickup_date', $pickup)
            ->set('return_date', $return)
            ->set('quantity', 2)
            ->call('proceedToBooking')
            ->assertHasNoErrors()
            ->assertRedirect(route('checkout.index'));

        $this->assertEquals($product->id, session('booking_cart.master_item_id'));
        $this->assertEquals($product->id, session('booking_cart.product_id'));
        $this->assertEquals(2, session('booking_cart.quantity'));
        $this->assertEquals(2, session('booking_cart.qty'));
        $this->assertEquals($pickup, session('booking_cart.pickup_date'));
        $this->assertEquals($return, session('booking_cart.return_date'));
        $this->assertEquals(3, session('booking_cart.total_days'));
        $this->assertEquals(45000 * 2 * 3, session('booking_cart.total_price'));
    }
}
