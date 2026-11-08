<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\CartService;
use App\Services\Payment\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegularCheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openrouteservice.api_key' => 'test-key', 'services.openrouteservice.retry_times' => 1, 'services.openrouteservice.retry_sleep_ms' => 0]);
        Http::preventStrayRequests();
    }

    /** @return array{customer: User, vendor: Vendor, item: MasterItem, address: CustomerAddress} */
    private function context(): array
    {
        $vendor = Vendor::create(['name' => 'Store', 'slug' => 'store', 'phone' => '081234567890', 'address' => 'Malang', 'status' => 'active', 'latitude' => -7.9, 'longitude' => 112.6]);
        $vendor->shippingSetting()->create(['rate_per_km' => 2000, 'weight_threshold_kg' => 5, 'extra_weight_fee_per_kg' => 1000]);
        $category = ItemCategory::create(['vendor_id' => $vendor->id, 'name' => 'Tenda', 'slug' => 'tenda']);
        $item = MasterItem::create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'name' => 'Tenda', 'slug' => 'tenda', 'daily_rate' => 50000, 'weight_kg' => 3.25, 'is_active' => true]);
        foreach (range(1, 4) as $unit) {
            ItemUnit::create(['vendor_id' => $vendor->id, 'master_item_id' => $item->id, 'unit_code' => 'UNIT-'.$unit, 'status' => 'available']);
        }
        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::factory()->for($customer)->withCoordinates()->create();
        app(CartService::class)->addItem(['master_item_id' => $item->id, 'vendor_id' => $vendor->id, 'quantity' => 2, 'pickup_date' => now()->addDay()->toDateString(), 'return_date' => now()->addDays(2)->toDateString()], $customer);

        return compact('vendor', 'item', 'customer', 'address');
    }

    /** @param array{address: CustomerAddress} $context
     * @return array{checkout_type: string, pickup_method: string, customer_address_id: int}
     */
    private function input(array $context): array
    {
        return ['checkout_type' => 'regular', 'pickup_method' => 'delivery', 'customer_address_id' => $context['address']->id];
    }

    private function fakeRoute(): void
    {
        $transactionLevel = DB::transactionLevel();
        Http::fake(['api.openrouteservice.org/*' => function () use ($transactionLevel) {
            $this->assertSame($transactionLevel, DB::transactionLevel(), 'ORS must not run inside an additional booking transaction.');

            return Http::response(['routes' => [['summary' => ['distance' => 12340, 'duration' => 900]]]]);
        }]);
    }

    private function mockPaymentGateway(): void
    {
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')->once()->andReturn('shipping-snap');
        });
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function confirmedInput(array $input): array
    {
        $preview = $this->postJson(route('checkout.shipping.preview'), $input)->assertOk()->json();

        return $input + ['payment_option' => 'full', 'expected_delivery_fee' => $preview['delivery_fee'], 'expected_shipping_context' => $preview['shipping_context']];
    }

    public function test_delivery_recalculates_and_persists_fee_and_grand_total_ignoring_injected_values(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmedInput($this->input($c));
        Cache::flush();
        $this->mockPaymentGateway();
        $this->postJson(route('checkout.store'), $input + ['delivery_fee' => 0, 'rate_per_km' => 0, 'weight_kg' => 0, 'distance_km' => 0, 'latitude' => 0, 'longitude' => 0])->assertOk()->assertJsonPath('payment_amount', 126680);
        $rental = Rental::sole();
        $this->assertSame('26680.00', $rental->delivery_fee);
        $this->assertSame('100000.00', $rental->subtotal);
        $this->assertSame('126680.00', $rental->grand_total);
        $this->assertSame($c['address']->id, $rental->customer_address_id);
        $this->assertSame($c['address']->toDeliverySnapshot(), $rental->delivery_address);
        $this->assertSame(2, $rental->details()->count());
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(2);
        $this->postJson(route('checkout.store'), $input)->assertUnprocessable();
        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public static function changedContexts(): array
    {
        return ['tariff' => ['tariff'], 'price with same shipping fee' => ['price'], 'address with same coordinates' => ['address'], 'quantity' => ['quantity'], 'weight with same shipping fee' => ['weight']];
    }

    #[DataProvider('changedContexts')]
    public function test_changed_context_returns_409_before_creating_rental_or_payment(string $change): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmedInput($this->input($c));
        match ($change) {
            'tariff' => $c['vendor']->shippingSetting()->update(['rate_per_km' => 3000]),
            'price' => $c['item']->update(['daily_rate' => 60000]),
            'address' => $c['address']->update(['full_address' => 'Alamat baru']),
            'quantity' => $input['items'] = [['id' => $c['item']->id, 'qty' => 3]],
            'weight' => $c['item']->update(['weight_kg' => 3.3]),
        };
        $this->postJson(route('checkout.store'), $input)->assertStatus(409)->assertJsonPath('status', 'shipping_changed')->assertJsonStructure(['shipping' => ['shipping_context', 'delivery_fee', 'subtotal', 'grand_total']]);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }

    public function test_new_quote_requires_explicit_resubmit_and_then_succeeds(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmedInput($this->input($c));
        $c['vendor']->shippingSetting()->update(['rate_per_km' => 3000]);
        $latest = $this->postJson(route('checkout.store'), $input)->assertStatus(409)->json('shipping');
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->mockPaymentGateway();
        $this->postJson(route('checkout.store'), array_replace($input, ['expected_delivery_fee' => $latest['delivery_fee'], 'expected_shipping_context' => $latest['shipping_context']]))->assertOk()->assertJsonPath('payment_amount', 139020);
        $this->assertSame('39020.00', Rental::sole()->delivery_fee);
        Http::assertSentCount(1);
    }

    public function test_expected_fee_cannot_override_calculation_and_missing_confirmation_conflicts(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmedInput($this->input($c));
        $this->postJson(route('checkout.store'), array_replace($input, ['expected_delivery_fee' => 0]))->assertStatus(409)->assertJsonPath('shipping.delivery_fee', 26680);
        $this->postJson(route('checkout.store'), $this->input($c))->assertStatus(409);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }

    public function test_ors_failure_at_submit_does_not_create_booking(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmedInput($this->input($c));
        Cache::flush();
        Http::fake(['api.openrouteservice.org/*' => Http::failedConnection('timeout')]);
        $this->postJson(route('checkout.store'), $input)->assertStatus(503);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }

    public function test_missing_coordinates_and_foreign_address_cannot_create_booking(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        Http::fake();
        $c['address']->update(['latitude' => null]);
        $this->postJson(route('checkout.store'), $this->input($c))->assertUnprocessable();
        $foreign = CustomerAddress::factory()->withCoordinates()->create();
        $this->postJson(route('checkout.store'), array_replace($this->input($c), ['customer_address_id' => $foreign->id]))->assertUnprocessable();
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_switching_address_changes_preview_and_old_context_cannot_be_submitted(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        Http::fake(['api.openrouteservice.org/*' => Http::sequence()
            ->push(['routes' => [['summary' => ['distance' => 12340]]]])
            ->push(['routes' => [['summary' => ['distance' => 20000]]]])]);
        $input = $this->confirmedInput($this->input($c));
        $other = CustomerAddress::factory()->for($c['customer'])->withCoordinates(-7.8, 112.7)->create();
        $this->postJson(route('checkout.store'), array_replace($input, ['customer_address_id' => $other->id]))->assertStatus(409)->assertJsonPath('shipping.delivery_fee', 42000)->assertJsonPath('shipping.distance_km', 20);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(2);
    }

    public function test_guest_pickup_keeps_zero_shipping_without_ors(): void
    {
        $this->context();
        Http::fake();
        $this->mockPaymentGateway();
        $this->postJson(route('checkout.store'), ['pickup_method' => 'self_pickup', 'guest_name' => 'Guest Camping', 'guest_phone' => '081234567890', 'guest_id_number' => '1234567890123456', 'delivery_fee' => 999999])->assertOk()->assertJsonPath('payment_amount', 100000);
        $this->assertSame('0.00', Rental::sole()->delivery_fee);
        $this->assertNull(Rental::sole()->user_id);
        Http::assertNothingSent();
    }

    public function test_delivery_page_has_no_flat_fee_or_final_total_until_preview(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer'])->get(route('checkout.index', ['pickup_method' => 'delivery']))->assertOk()->assertViewHas('booking', fn (array $booking): bool => $booking['delivery_fee'] === null && $booking['grand_total'] === null)->assertSee('Ongkir otomatis')->assertSee('shippingPreviewUrl')->assertSee('shippingNeedsApproval');
    }

    public function test_regular_submit_holds_session_lock_before_ors_and_releases_it_after_response(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $sessionId = null;
        Http::fake(['api.openrouteservice.org/*' => function () use (&$sessionId) {
            $sessionId = session()->getId();
            $this->assertFalse(Cache::lock('session:'.$sessionId, 120)->get());

            return Http::response(['routes' => [['summary' => ['distance' => 12340]]]]);
        }]);
        $this->postJson(route('checkout.store'), $this->input($c))->assertStatus(409);
        $lock = Cache::lock('session:'.$sessionId, 120);
        $this->assertTrue($lock->get());
        $lock->release();
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }
}
