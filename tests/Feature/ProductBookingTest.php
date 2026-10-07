<?php

namespace Tests\Feature;

use App\Livewire\Customer\ProductBooking;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductBookingTest extends TestCase
{
    use RefreshDatabase;

    /** Helper: buat produk lengkap dengan vendor & kategori. */
    private function createProduct(array $overrides = []): MasterItem
    {
        $vendor = Vendor::create([
            'name' => 'Tendaku Store Central',
            'slug' => 'tendaku-store-central',
            'email' => 'store@tendaku.com',
            'phone' => '08123456789',
            'address' => 'Jl. Outdoor No. 1, Malang',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda & Canopy',
            'slug' => 'tenda-canopy',
        ]);

        return MasterItem::create(array_merge([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Consina Magnum 4P',
            'slug' => 'tenda-consina-magnum-4p',
            'description' => 'Tenda 4 orang double layer.',
            'daily_rate' => 45000,
            'is_active' => true,
        ], $overrides));
    }

    public function test_it_mounts_with_default_pickup_tomorrow_and_return_day_after(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->assertSet('pickup_date', Carbon::tomorrow()->format('Y-m-d'))
            ->assertSet('return_date', Carbon::today()->addDays(2)->format('Y-m-d'));
    }

    public function test_it_calculates_total_days_correctly(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-10')
            ->set('return_date', '2026-10-12')
            // 10, 11, 12 = 3 hari inklusif
            ->assertSet('totalDays', 3);
    }

    public function test_it_calculates_total_price_as_daily_rate_times_total_days(): void
    {
        $product = $this->createProduct(['daily_rate' => 45000]);

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', '2026-10-10')
            ->set('return_date', '2026-10-12') // 3 hari
            ->assertSet('totalPrice', 45000 * 3);
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
            ->set('return_date', '2026-10-10')
            ->set('pickup_date', '2026-10-15') // pickup diset setelah return
            ->assertSet('return_date', '2026-10-15'); // return harus ikut naik
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

    public function test_it_fails_validation_when_return_date_before_pickup(): void
    {
        $product = $this->createProduct();

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', Carbon::tomorrow()->format('Y-m-d'))
            ->set('return_date', Carbon::today()->format('Y-m-d')) // sebelum pickup
            ->call('proceedToBooking')
            ->assertHasErrors(['return_date' => 'after_or_equal']);
    }

    public function test_it_stores_booking_cart_in_session_and_redirects_on_success(): void
    {
        $product = $this->createProduct(['daily_rate' => 45000]);

        $pickup = Carbon::tomorrow()->format('Y-m-d');
        $return = Carbon::today()->addDays(3)->format('Y-m-d'); // 3 hari

        Livewire::test(ProductBooking::class, ['product' => $product])
            ->set('pickup_date', $pickup)
            ->set('return_date', $return)
            ->call('proceedToBooking')
            ->assertHasNoErrors()
            ->assertRedirect(route('checkout.index'));

        $this->assertEquals($product->id, session('booking_cart.product_id'));
        $this->assertEquals($pickup, session('booking_cart.pickup_date'));
        $this->assertEquals($return, session('booking_cart.return_date'));
        $this->assertEquals(3, session('booking_cart.total_days'));
        $this->assertEquals(45000 * 3, session('booking_cart.total_price'));
    }
}
