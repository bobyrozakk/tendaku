<?php

namespace Tests\Feature;

use App\Models\CampingPackage;
use App\Models\CustomerAddress;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageCheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openrouteservice.api_key' => 'test-key', 'services.openrouteservice.retry_times' => 1, 'services.openrouteservice.retry_sleep_ms' => 0]);
        Http::preventStrayRequests();
    }

    /** @return array{customer: User, vendor: Vendor, package: CampingPackage, tent: MasterItem, stove: MasterItem, address: CustomerAddress} */
    private function context(): array
    {
        $vendor = Vendor::create(['name' => 'Package Store', 'slug' => fake()->unique()->slug(), 'phone' => '081234567890', 'address' => 'Malang', 'status' => 'active', 'latitude' => -7.9, 'longitude' => 112.6]);
        $vendor->shippingSetting()->create(['rate_per_km' => 2000, 'weight_threshold_kg' => 5, 'extra_weight_fee_per_kg' => 1000]);
        $category = ItemCategory::create(['vendor_id' => $vendor->id, 'name' => 'Camping', 'slug' => fake()->unique()->slug()]);
        $tent = MasterItem::create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'name' => 'Tenda', 'slug' => fake()->unique()->slug(), 'daily_rate' => 50000, 'weight_kg' => 3.25, 'is_active' => true]);
        $stove = $tent->replicate();
        $stove->fill(['name' => 'Kompor', 'slug' => fake()->unique()->slug(), 'weight_kg' => 1.5])->save();
        foreach ([$tent, $stove] as $item) {
            foreach (range(1, 4) as $unit) {
                ItemUnit::create(['vendor_id' => $vendor->id, 'master_item_id' => $item->id, 'unit_code' => $item->id.'-'.$unit, 'status' => 'available']);
            }
        }
        $package = CampingPackage::create(['vendor_id' => $vendor->id, 'name' => 'Paket Camping', 'slug' => fake()->unique()->slug(), 'price' => 300000, 'duration_days' => 2, 'max_people' => 8, 'is_active' => true]);
        $package->packageItems()->createMany([['master_item_id' => $tent->id, 'quantity' => 2], ['master_item_id' => $stove->id, 'quantity' => 3]]);
        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::factory()->for($customer)->withCoordinates()->create();

        return compact('vendor', 'package', 'tent', 'stove', 'customer', 'address');
    }

    /** @param array{package: CampingPackage, address: CustomerAddress} $context
     * @return array<string, mixed>
     */
    private function input(array $context, string $method = 'delivery'): array
    {
        return ['checkout_type' => 'package', 'package_id' => $context['package']->id, 'pickup_method' => $method, 'customer_address_id' => $context['address']->id, 'pickup_date' => now()->addDay()->toDateString(), 'pickup_time' => '09:00', 'return_date' => now()->addDays(3)->toDateString(), 'return_time' => '09:00'];
    }

    private function fakeRoute(): void
    {
        $transactionLevel = DB::transactionLevel();
        Http::fake(['api.openrouteservice.org/*' => function () use ($transactionLevel) {
            $this->assertSame($transactionLevel, DB::transactionLevel());

            return Http::response(['routes' => [['summary' => ['distance' => 12340, 'duration' => 900]]]]);
        }]);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function confirmed(array $input): array
    {
        $preview = $this->postJson(route('checkout.shipping.preview'), $input)->assertOk()->json();

        return $input + ['expected_delivery_fee' => $preview['delivery_fee'], 'expected_shipping_context' => $preview['shipping_context']];
    }

    public function test_delivery_uses_all_component_quantities_and_saves_recalculated_fee_and_total(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmed($this->input($c));
        $this->assertSame(30680, $input['expected_delivery_fee']);
        Cache::flush();
        $this->postJson(route('customer.packages.checkout.store', $c['package']->id), $input + ['delivery_fee' => 0, 'vendor_id' => 999, 'weight_kg' => 0, 'rate_per_km' => 0, 'distance_km' => 0, 'items' => [['id' => $c['tent']->id, 'qty' => 999]]])->assertOk()->assertJsonPath('payment_amount', 630680)->assertJsonStructure(['redirect_url', 'booking_code']);
        $rental = Rental::sole();
        $this->assertSame('30680.00', $rental->delivery_fee);
        $this->assertSame('600000.00', $rental->subtotal);
        $this->assertSame('630680.00', $rental->grand_total);
        $this->assertSame(5, $rental->details()->count());
        $this->assertSame($c['address']->toDeliverySnapshot(), $rental->delivery_address);
        $this->assertSame($c['vendor']->id, $rental->vendor_id);
        $this->assertSame('full', Payment::sole()->payment_type);
        $this->assertNull(Payment::sole()->snap_token);
        Http::assertSentCount(2);
    }

    public function test_pickup_preview_and_submit_are_free_without_ors_and_duration_does_not_multiply_weight(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        Http::fake();
        $input = $this->input($c, 'self_pickup');
        $preview = $this->postJson(route('checkout.shipping.preview'), $input)->assertOk()->assertJsonPath('total_weight_kg', 11)->assertJsonPath('distance_km', null)->assertJsonPath('delivery_fee', 0)->json();
        $longer = array_replace($input, ['return_date' => now()->addDays(6)->toDateString()]);
        $this->postJson(route('checkout.shipping.preview'), $longer)->assertOk()->assertJsonPath('total_weight_kg', 11)->assertJsonPath('subtotal', 1500000);
        $this->postJson(route('customer.packages.checkout.store', $c['package']->id), $input + ['expected_delivery_fee' => 0, 'expected_shipping_context' => $preview['shipping_context']])->assertOk()->assertJsonPath('payment_amount', 600000);
        $this->assertSame('0.00', Rental::sole()->delivery_fee);
        Http::assertNothingSent();
    }

    public static function contextChanges(): array
    {
        return ['tariff' => ['tariff'], 'vendor location' => ['vendor'], 'customer address' => ['address'], 'package price' => ['price'], 'component quantity' => ['quantity'], 'component weight' => ['weight'], 'rental schedule' => ['schedule']];
    }

    #[DataProvider('contextChanges')]
    public function test_changed_quote_blocks_booking_until_customer_resubmits_confirmation(string $change): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmed($this->input($c));
        match ($change) {
            'tariff' => $c['vendor']->shippingSetting()->update(['rate_per_km' => 3000]),
            'vendor' => $c['vendor']->update(['latitude' => -7.8]),
            'address' => $c['address']->update(['full_address' => 'Alamat terbaru']),
            'price' => $c['package']->update(['price' => 400000]),
            'quantity' => $c['package']->packageItems()->where('master_item_id', $c['tent']->id)->update(['quantity' => 3]),
            'weight' => $c['tent']->update(['weight_kg' => 3.3]),
            'schedule' => $input['return_date'] = now()->addDays(4)->toDateString(),
        };
        $latest = $this->postJson(route('customer.packages.checkout.store', $c['package']->id), $input)->assertStatus(409)->assertJsonPath('status', 'shipping_changed')->json('shipping');
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->postJson(route('customer.packages.checkout.store', $c['package']->id), array_replace($input, ['expected_delivery_fee' => $latest['delivery_fee'], 'expected_shipping_context' => $latest['shipping_context']]))->assertOk();
        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_other_address_recalculates_and_foreign_address_is_rejected_without_default_fallback(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        Http::fake(['api.openrouteservice.org/*' => Http::sequence()
            ->push(['routes' => [['summary' => ['distance' => 12340]]]])
            ->push(['routes' => [['summary' => ['distance' => 20000]]]])]);
        $input = $this->confirmed($this->input($c));
        $other = CustomerAddress::factory()->for($c['customer'])->withCoordinates(-7.8, 112.7)->create();
        $this->postJson(route('checkout.shipping.preview'), array_replace($input, ['customer_address_id' => $other->id]))->assertOk()->assertJsonPath('distance_km', 20)->assertJsonPath('delivery_fee', 46000);
        $foreign = CustomerAddress::factory()->withCoordinates()->create();
        $this->postJson(route('customer.packages.checkout.store', $c['package']->id), array_replace($input, ['customer_address_id' => $foreign->id]))->assertUnprocessable()->assertJsonPath('status', 'address_unavailable');
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(2);
    }

    public function test_route_package_identity_cannot_be_replaced_by_hidden_input(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer'])->postJson(route('customer.packages.checkout.store', $c['package']->id), array_replace($this->input($c), ['package_id' => $c['package']->id + 999]))->assertUnprocessable()->assertJsonValidationErrors('package_id');
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_submit_rejects_dates_with_embedded_time_before_shipping_calculation(): void
    {
        $c = $this->context();
        Http::fake();
        $input = array_replace($this->input($c), ['pickup_date' => now()->addDay()->format('Y-m-d').' 09:00:00']);
        $this->actingAs($c['customer'])->postJson(route('customer.packages.checkout.store', $c['package']->id), $input)->assertUnprocessable()->assertJsonValidationErrors('pickup_date');
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public static function unavailableData(): array
    {
        return ['inactive package' => ['package', 'is_active', false, 404], 'missing vendor location' => ['vendor', 'latitude', null, 422], 'missing customer location' => ['address', 'longitude', null, 422], 'invalid component' => ['tent', 'is_active', false, 422], 'invalid weight' => ['tent', 'weight_kg', -1, 422]];
    }

    #[DataProvider('unavailableData')]
    public function test_unusable_package_or_coordinates_never_create_booking(string $model, string $field, mixed $value, int $status): void
    {
        $c = $this->context();
        $c[$model]->update([$field => $value]);
        Http::fake();
        $this->actingAs($c['customer'])->postJson(route('customer.packages.checkout.store', $c['package']->id), $this->input($c))->assertStatus($status);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_ors_failure_on_submit_never_creates_rental_or_payment(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmed($this->input($c));
        Cache::flush();
        Http::fake(['api.openrouteservice.org/*' => Http::failedConnection('timeout')]);
        $this->postJson(route('customer.packages.checkout.store', $c['package']->id), $input)->assertStatus(503)->assertJsonPath('delivery_fee', null);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }

    public function test_repeated_successful_submission_returns_same_booking_and_payment(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmed($this->input($c)) + ['checkout_attempt_id' => (string) Str::uuid()];
        $url = route('customer.packages.checkout.store', $c['package']->id);
        $first = $this->postJson($url, $input)->assertOk()->json();
        $this->postJson($url, $input)->assertOk()->assertJsonPath('booking_code', $first['booking_code']);
        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    public function test_new_checkout_attempt_can_create_a_new_order_with_identical_shipping_context(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        Http::fake();
        $input = $this->confirmed($this->input($c, 'self_pickup'));
        $url = route('customer.packages.checkout.store', $c['package']->id);
        $first = $this->postJson($url, $input + ['checkout_attempt_id' => (string) Str::uuid()])->assertOk()->json('booking_code');
        Rental::sole()->update(['status' => 'cancelled']);
        $second = $this->postJson($url, $input + ['checkout_attempt_id' => (string) Str::uuid()])->assertOk()->json('booking_code');
        $this->assertNotSame($first, $second);
        $this->assertDatabaseCount('rentals', 2);
        Http::assertNothingSent();
    }

    public function test_retry_with_refreshed_shipping_context_keeps_the_original_checkout_attempt_receipt(): void
    {
        $c = $this->context();
        foreach (range(1, 2) as $unit) {
            ItemUnit::create(['vendor_id' => $c['vendor']->id, 'master_item_id' => $c['stove']->id, 'unit_code' => 'retry-'.$unit, 'status' => 'available']);
        }
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmed($this->input($c)) + ['checkout_attempt_id' => (string) Str::uuid()];
        $url = route('customer.packages.checkout.store', $c['package']->id);
        $first = $this->postJson($url, $input)->assertOk()->json();
        $c['vendor']->shippingSetting()->update(['rate_per_km' => 3000]);
        $retry = $this->confirmed($this->input($c)) + ['checkout_attempt_id' => $input['checkout_attempt_id']];
        $this->assertNotSame($input['expected_shipping_context'], $retry['expected_shipping_context']);

        $this->postJson($url, $retry)->assertOk()->assertJsonPath('booking_code', $first['booking_code'])->assertJsonPath('payment_amount', $first['payment_amount']);

        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    public function test_normal_form_conflicts_redirect_back_with_explicit_review_requirement(): void
    {
        $c = $this->context();
        $this->actingAs($c['customer']);
        $this->fakeRoute();
        $input = $this->confirmed($this->input($c));
        $c['package']->update(['price' => 400000]);
        $this->post(route('customer.packages.checkout.store', $c['package']->id), $input)->assertRedirect(route('customer.packages.checkout', $c['package']->id))->assertSessionHas('shipping_conflict')->assertSessionHasInput('pickup_method', 'delivery');
        $this->assertDatabaseCount('rentals', 0);
        Http::assertSentCount(1);
    }

    public function test_page_uses_shared_address_modal_outside_booking_form_and_external_js(): void
    {
        $c = $this->context();
        $html = $this->actingAs($c['customer'])->get(route('customer.packages.checkout', $c['package']->id))->assertOk()->assertSee('packageCheckout(')->assertSee('shippingNeedsApproval')->assertDontSee('Rp 25.000')->getContent();
        $this->assertSame(1, substr_count($html, 'name="package_id"'));
        $modal = strpos($html, 'aria-labelledby="modal-title"');
        $formEnd = strrpos(substr($html, 0, $modal), '</form>');
        $this->assertNotFalse($formEnd);
        $between = substr($html, $formEnd + 7, $modal - $formEnd - 7);
        $this->assertStringNotContainsString('<form', $between);
        $this->assertSame(1, substr_count($html, 'id="checkout-add-address-location-picker"'));
    }

    public function test_new_address_can_be_saved_and_used_in_package_shipping_preview(): void
    {
        $c = $this->context();
        $this->fakeRoute();
        $created = $this->actingAs($c['customer'])->postJson(route('customer.addresses.store'), [
            'label' => 'Basecamp', 'recipient_name' => 'Budi', 'phone_number' => '081234567890',
            'full_address' => 'Jl. Basecamp 10', 'province' => 'Jawa Timur', 'city' => 'Malang', 'district' => 'Klojen',
            'latitude' => -7.8, 'longitude' => 112.7,
        ])->assertCreated()->json('data');
        $this->postJson(route('checkout.shipping.preview'), array_replace($this->input($c), ['customer_address_id' => $created['id']]))->assertOk()->assertJsonPath('delivery_fee', 30680)->assertJsonPath('customer_address_id', $created['id']);
        $this->assertDatabaseCount('rentals', 0);
        Http::assertSentCount(1);
    }

    public function test_vendor_tariffs_are_isolated_and_foreign_package_components_are_rejected(): void
    {
        $a = $this->context();
        $b = $this->context();
        $b['vendor']->shippingSetting()->update(['rate_per_km' => 9000]);
        $this->fakeRoute();
        $this->actingAs($a['customer'])->postJson(route('checkout.shipping.preview'), $this->input($a) + ['vendor_id' => $b['vendor']->id])->assertOk()->assertJsonPath('distance_fee', 24680);
        $a['package']->packageItems()->where('master_item_id', $a['tent']->id)->update(['master_item_id' => $b['tent']->id]);
        $this->postJson(route('customer.packages.checkout.store', $a['package']->id), $this->input($a))->assertUnprocessable()->assertJsonPath('status', 'invalid_package_component');
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }

    public function test_package_submit_preserves_csrf_protection_and_does_not_book_without_confirmation(): void
    {
        $c = $this->context();
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        Http::fake();
        $url = route('customer.packages.checkout.store', $c['package']->id);
        $input = $this->input($c, 'self_pickup');
        $this->actingAs($c['customer'])->withSession(['_token' => 'package-shipping-token'])->postJson($url, $input)->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'package-shipping-token')->postJson($url, $input)->assertStatus(409);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_submit_session_lock_is_held_during_shipping_recalculation(): void
    {
        $c = $this->context();
        Http::fake(['api.openrouteservice.org/*' => function () {
            $this->assertFalse(Cache::lock('session:'.session()->getId(), 120)->get());

            return Http::response(['routes' => [['summary' => ['distance' => 12340]]]]);
        }]);
        $this->actingAs($c['customer'])->postJson(route('customer.packages.checkout.store', $c['package']->id), $this->input($c))->assertStatus(409);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertSentCount(1);
    }
}
