<?php

namespace Tests\Feature;

use App\Livewire\Customer\ProductBooking;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\CartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutCartDatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-08 10:00:00'));
    }

    /** @return array{user: User, item: MasterItem, cart: array<string, mixed>} */
    private function context(): array
    {
        $vendor = Vendor::create(['name' => 'Date Store', 'slug' => 'date-store', 'phone' => '081234567890', 'address' => 'Malang', 'status' => 'active']);
        $category = ItemCategory::create(['vendor_id' => $vendor->id, 'name' => 'Tenda', 'slug' => 'tenda']);
        $item = MasterItem::create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'name' => 'Tenda', 'slug' => 'tenda', 'daily_rate' => 50000, 'is_active' => true]);
        foreach (range(1, 3) as $unit) {
            ItemUnit::create(['vendor_id' => $vendor->id, 'master_item_id' => $item->id, 'unit_code' => 'UNIT-'.$unit, 'status' => 'available']);
        }
        $user = User::factory()->create(['role' => 'customer']);
        $cart = ['master_item_id' => $item->id, 'vendor_id' => $vendor->id, 'pickup_date' => '2026-10-26', 'return_date' => '2026-10-28', 'pickup_time' => '09:00', 'return_time' => '09:00', 'total_days' => 2, 'quantity' => 1, 'daily_rate' => 50000, 'product_name' => 'Tenda'];
        app(CartService::class)->set($cart, $user);

        return compact('user', 'item', 'cart');
    }

    public function test_expired_session_redirects_to_date_correction_and_preserves_cart(): void
    {
        $c = $this->context();
        $this->actingAs($c['user'])->get(route('checkout.index'))->assertRedirect(route('customer.cart.index'))->assertSessionHasErrors('pickup_date')->assertSessionHas('warning');
        $this->assertSame('2026-10-26', session('booking_cart.pickup_date'));
        $this->assertSame('2026-10-26', $c['user']->fresh()->cart['pickup_date']);
        $this->assertCount(1, session('booking_cart.items'));
        $this->get(route('customer.cart.index'))->assertOk()->assertSee('Jadwal sewa di keranjang tidak valid atau sudah lewat')->assertSee('name="pickup_date"', false);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_expired_database_cart_is_also_rejected_when_session_is_missing(): void
    {
        $c = $this->context();
        session()->forget('booking_cart');
        $this->actingAs($c['user'])->get(route('checkout.index'))->assertRedirect(route('customer.cart.index'))->assertSessionHasErrors('pickup_date');
        $this->assertSame('2026-10-26', session('booking_cart.pickup_date'));
        $this->assertCount(1, $c['user']->fresh()->cart['items']);
    }

    public function test_cart_form_saves_new_dates_to_session_and_database_before_checkout(): void
    {
        $c = $this->context();
        $this->actingAs($c['user'])->post(route('customer.cart.checkout'), ['pickup_date' => '2026-11-12', 'return_date' => '2026-11-15'])->assertRedirect(route('checkout.index'));
        $this->assertSame('2026-11-12', session('booking_cart.pickup_date'));
        $this->assertSame('2026-11-15', $c['user']->fresh()->cart['return_date']);
        $this->assertSame(3, session('booking_cart.total_days'));
        $this->assertSame(150000, session('booking_cart.total_price'));
        $this->get(route('checkout.index'))->assertOk()->assertViewHas('booking', fn (array $booking): bool => $booking['pickup_date_iso'] === '2026-11-12' && $booking['return_date_iso'] === '2026-11-15' && $booking['rental_days'] === 3);
        session()->forget('booking_cart');
        $this->get(route('checkout.index'))->assertOk()->assertViewHas('booking', fn (array $booking): bool => $booking['pickup_date_iso'] === '2026-11-12');
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_adding_product_with_new_dates_updates_existing_cart_instead_of_retaining_old_dates(): void
    {
        $c = $this->context();
        $this->actingAs($c['user']);
        app(CartService::class)->addItem(['master_item_id' => $c['item']->id, 'vendor_id' => $c['item']->vendor_id, 'pickup_date' => '2026-11-12', 'return_date' => '2026-11-14', 'daily_rate' => 50000, 'qty' => 1], $c['user']);
        $this->assertSame('2026-11-12', session('booking_cart.pickup_date'));
        $this->assertSame('2026-11-14', $c['user']->fresh()->cart['return_date']);
        $this->assertSame(2, session('booking_cart.qty'));
        $this->get(route('checkout.index'))->assertOk();
    }

    public function test_direct_checkout_uses_explicit_new_dates_with_an_existing_cart(): void
    {
        $c = $this->context();
        $this->actingAs($c['user'])->get(route('checkout.index', ['item_id' => $c['item']->id, 'pickup_date' => '2026-11-12', 'return_date' => '2026-11-14']))->assertOk()->assertViewHas('booking', fn (array $booking): bool => $booking['pickup_date_iso'] === '2026-11-12');
        $this->assertSame('2026-11-12', $c['user']->fresh()->cart['pickup_date']);
    }

    public function test_product_already_in_cart_saves_selected_dates_without_adding_duplicate_quantity(): void
    {
        $c = $this->context();
        $this->actingAs($c['user']);
        Livewire::test(ProductBooking::class, ['product' => $c['item']])->assertSet('inCart', true)
            ->set('pickup_date', '2026-11-12')->set('duration_days', 2)
            ->call('proceedToBooking')->assertHasNoErrors()->assertRedirect(route('checkout.index'));
        $this->assertSame('2026-11-12', session('booking_cart.pickup_date'));
        $this->assertSame('2026-11-14', $c['user']->fresh()->cart['return_date']);
        $this->assertSame(1, session('booking_cart.qty'));
        $this->get(route('checkout.index'))->assertOk();
    }

    public function test_successful_regular_preview_persists_checkout_date_edits_for_reload(): void
    {
        $c = $this->context();
        Http::fake();
        $this->actingAs($c['user'])->postJson(route('checkout.shipping.preview'), ['checkout_type' => 'regular', 'pickup_method' => 'self_pickup', 'pickup_date' => '2026-11-12', 'return_date' => '2026-11-14', 'pickup_time' => '09:00'])->assertOk();
        $this->assertSame('2026-11-12', session('booking_cart.pickup_date'));
        $this->assertSame('2026-11-14', $c['user']->fresh()->cart['return_date']);
        $this->get(route('checkout.index'))->assertOk()->assertViewHas('booking', fn (array $booking): bool => $booking['pickup_date_iso'] === '2026-11-12');
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_invalid_dates_do_not_overwrite_a_previously_saved_valid_schedule(): void
    {
        $c = $this->context();
        $this->actingAs($c['user']);
        app(CartService::class)->updateDates(['pickup_date' => '2026-11-12', 'return_date' => '2026-11-14'], $c['user']);
        $this->postJson(route('customer.cart.checkout'), ['pickup_date' => '2026-10-26', 'return_date' => '2026-10-28'])->assertUnprocessable()->assertJsonValidationErrors('pickup_date');
        $this->postJson(route('checkout.shipping.preview'), ['checkout_type' => 'regular', 'pickup_method' => 'self_pickup', 'pickup_date' => '2026-10-26', 'return_date' => '2026-10-28'])->assertUnprocessable();
        $this->assertSame('2026-11-12', session('booking_cart.pickup_date'));
        $this->assertSame('2026-11-12', $c['user']->fresh()->cart['pickup_date']);
        $this->get(route('checkout.index'))->assertOk();
    }

    public function test_direct_entry_without_new_dates_does_not_silently_replace_expired_dates(): void
    {
        $c = $this->context();
        $this->actingAs($c['user'])->get(route('checkout.index', ['item_id' => $c['item']->id]))->assertRedirect(route('customer.cart.index'))->assertSessionHasErrors('pickup_date');
        $this->assertSame('2026-10-26', session('booking_cart.pickup_date'));
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_explicit_checkout_dates_override_old_stored_dates_without_readding_product(): void
    {
        $c = $this->context();
        $this->actingAs($c['user'])->get(route('checkout.index', ['pickup_date' => '2026-11-12', 'return_date' => '2026-11-14']))->assertOk()
            ->assertViewHas('booking', fn (array $booking): bool => $booking['pickup_date_iso'] === '2026-11-12');
        $this->assertSame('2026-11-12', $c['user']->fresh()->cart['pickup_date']);
        $this->assertSame(1, session('booking_cart.qty'));
    }
}
