<?php

namespace Tests\Feature;

use App\Models\CampingPackage;
use App\Models\CustomerAddress;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\CartService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShippingPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/katalog/booking/shipping-preview';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openrouteservice.api_key' => 'test-key', 'services.openrouteservice.base_url' => 'https://api.openrouteservice.org', 'services.openrouteservice.profile' => 'driving-car', 'services.openrouteservice.retry_times' => 1, 'services.openrouteservice.retry_sleep_ms' => 0]);
        Http::preventStrayRequests();
    }

    /** @return array{vendor: Vendor, item: MasterItem, customer: User, address: CustomerAddress, package: CampingPackage} */
    private function context(): array
    {
        $vendor = Vendor::create(['name' => 'Shipping Store', 'phone' => '081234567890', 'address' => 'Jl. Camping', 'slug' => fake()->unique()->slug(), 'status' => 'active', 'latitude' => -7.9, 'longitude' => 112.6]);
        $vendor->shippingSetting()->create(['rate_per_km' => 2000, 'weight_threshold_kg' => 5, 'extra_weight_fee_per_kg' => 1000]);
        $category = ItemCategory::create(['vendor_id' => $vendor->id, 'name' => 'Tenda', 'slug' => fake()->unique()->slug()]);
        $item = MasterItem::create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'name' => 'Tenda', 'slug' => fake()->unique()->slug(), 'daily_rate' => 50000, 'weight_kg' => 3.25, 'is_active' => true]);
        foreach (range(1, 4) as $number) {
            ItemUnit::create(['vendor_id' => $vendor->id, 'master_item_id' => $item->id, 'unit_code' => 'UNIT-'.$number, 'status' => 'available']);
        }
        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::factory()->for($customer)->withCoordinates()->create();
        $package = CampingPackage::create(['vendor_id' => $vendor->id, 'name' => 'Paket', 'slug' => fake()->unique()->slug(), 'price' => 100000, 'duration_days' => 3, 'max_people' => 9, 'is_active' => true]);
        $package->packageItems()->create(['master_item_id' => $item->id, 'quantity' => 2]);
        app(CartService::class)->clear();
        app(CartService::class)->addItem(['master_item_id' => $item->id, 'vendor_id' => $vendor->id, 'quantity' => 1, 'pickup_date' => now()->addDay()->toDateString(), 'return_date' => now()->addDays(2)->toDateString()], $customer);

        return compact('vendor', 'item', 'customer', 'address', 'package');
    }

    /** @param array{address: CustomerAddress} $context
     * @return array{checkout_type: string, pickup_method: string, customer_address_id: int}
     */
    private function delivery(array $context): array
    {
        return ['checkout_type' => 'regular', 'pickup_method' => 'delivery', 'customer_address_id' => $context['address']->id];
    }

    private function fakeRoute(): void
    {
        Http::fake(['https://api.openrouteservice.org/v2/directions/driving-car' => Http::response(['routes' => [['summary' => ['distance' => 12340, 'duration' => 1000]]]])]);
    }

    public function test_guest_pickup_has_no_distance_and_does_not_call_ors(): void
    {
        $this->context();
        Http::fake();
        $this->postJson(self::ENDPOINT, ['checkout_type' => 'regular', 'pickup_method' => 'self_pickup'])
            ->assertOk()->assertExactJson(['success' => true, 'status' => 'ready', 'pickup_method' => 'self_pickup', 'distance_km' => null, 'total_weight_kg' => 3.25, 'distance_fee' => 0, 'weight_fee' => 0, 'delivery_fee' => 0]);
        Http::assertNothingSent();
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_regular_delivery_uses_database_coordinates_and_checkout_quantity_ignoring_injected_prices(): void
    {
        $c = $this->context();
        $this->fakeRoute();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c) + ['items' => [['id' => $c['item']->id, 'qty' => 2, 'weight_kg' => 0]], 'vendor_id' => 999, 'delivery_fee' => 0, 'distance_km' => 0, 'rate_per_km' => 0, 'latitude' => 0, 'longitude' => 0])
            ->assertOk()->assertExactJson(['success' => true, 'status' => 'ready', 'pickup_method' => 'delivery', 'distance_km' => 12.34, 'total_weight_kg' => 6.5, 'distance_fee' => 24680, 'weight_fee' => 2000, 'delivery_fee' => 26680]);
        Http::assertSent(fn ($request) => $request['coordinates'] === [[112.6, -7.9], [112.6304, -7.9797]]);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_regular_quantity_and_vendor_tariff_changes_are_reflected(): void
    {
        $c = $this->context();
        $this->fakeRoute();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c))->assertOk()->assertJsonPath('total_weight_kg', 3.25)->assertJsonPath('delivery_fee', 24680);
        $c['vendor']->shippingSetting()->update(['rate_per_km' => 3000]);
        $this->postJson(self::ENDPOINT, $this->delivery($c) + ['items' => [['id' => $c['item']->id, 'qty' => 3]]])->assertOk()->assertJsonPath('total_weight_kg', 9.75)->assertJsonPath('delivery_fee', 42020);
        Http::assertSentCount(1);
    }

    public function test_package_weight_uses_component_quantity_without_duration_or_people_multiplier(): void
    {
        $c = $this->context();
        $this->fakeRoute();
        $payload = ['checkout_type' => 'package', 'package_id' => $c['package']->id, 'pickup_method' => 'delivery', 'customer_address_id' => $c['address']->id, 'quantity' => 20, 'duration_days' => 90];
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $payload)->assertOk()->assertJsonPath('total_weight_kg', 6.5)->assertJsonPath('delivery_fee', 26680);
        $c['package']->packageItems()->update(['quantity' => 3]);
        $this->postJson(self::ENDPOINT, $payload)->assertOk()->assertJsonPath('total_weight_kg', 9.75)->assertJsonPath('delivery_fee', 29680);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_foreign_address_is_rejected_even_when_owned_default_exists(): void
    {
        $c = $this->context();
        $c['address']->setAsDefault();
        $foreign = CustomerAddress::factory()->withCoordinates()->create();
        Http::fake();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, array_replace($this->delivery($c), ['customer_address_id' => $foreign->id]))->assertUnprocessable()->assertJsonPath('status', 'address_unavailable')->assertJsonPath('delivery_fee', null);
        Http::assertNothingSent();
    }

    public static function unavailableData(): array
    {
        return [
            'customer latitude' => ['address', 'latitude', null, 'customer_coordinates_missing'],
            'customer longitude' => ['address', 'longitude', null, 'customer_coordinates_missing'],
            'vendor latitude' => ['vendor', 'latitude', null, 'vendor_coordinates_missing'],
            'vendor longitude' => ['vendor', 'longitude', null, 'vendor_coordinates_missing'],
            'negative weight' => ['item', 'weight_kg', -1, 'invalid_weight'],
            'inactive product' => ['item', 'is_active', false, 'product_unavailable'],
        ];
    }

    #[DataProvider('unavailableData')]
    public function test_invalid_database_data_blocks_delivery_before_ors(string $model, string $field, mixed $value, string $code): void
    {
        $c = $this->context();
        $c[$model]->update([$field => $value]);
        Http::fake();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c))->assertUnprocessable()->assertJsonPath('status', $code)->assertJsonPath('delivery_fee', null);
        Http::assertNothingSent();
    }

    public function test_missing_tariff_does_not_use_flat_fee_fallback(): void
    {
        $c = $this->context();
        $c['vendor']->shippingSetting()->delete();
        Http::fake();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c))->assertUnprocessable()->assertJsonPath('status', 'shipping_unconfigured')->assertJsonPath('delivery_fee', null);
        Http::assertNothingSent();
    }

    public static function apiFailures(): array
    {
        return ['server error' => [500, ['error' => 'internal'], 503, 'api_error'], 'no route' => [404, ['error' => ['code' => 2009]], 422, 'route_not_found'], 'empty route' => [200, ['routes' => []], 422, 'route_not_found']];
    }

    #[DataProvider('apiFailures')]
    public function test_ors_errors_never_return_free_delivery(int $httpCode, array $body, int $expectedHttp, string $code): void
    {
        $c = $this->context();
        Http::fake(['https://api.openrouteservice.org/v2/directions/driving-car' => Http::response($body, $httpCode)]);
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c))->assertStatus($expectedHttp)->assertJsonPath('success', false)->assertJsonPath('status', $code)->assertJsonPath('delivery_fee', null)->assertJsonStructure(['message']);
        Http::assertSentCount(1);
    }

    public function test_ors_connection_timeout_is_safe(): void
    {
        $c = $this->context();
        Http::fake(['https://api.openrouteservice.org/v2/directions/driving-car' => Http::failedConnection('timeout')]);
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c))->assertStatus(503)->assertJsonPath('status', 'api_error')->assertJsonPath('delivery_fee', null);
        Http::assertSentCount(1);
    }

    public function test_guest_cannot_preview_delivery_or_packages(): void
    {
        $c = $this->context();
        Http::fake();
        $this->postJson(self::ENDPOINT, $this->delivery($c))->assertUnauthorized()->assertJsonPath('status', 'authentication_required');
        $this->postJson(self::ENDPOINT, ['checkout_type' => 'package', 'package_id' => $c['package']->id, 'pickup_method' => 'self_pickup'])->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_package_requires_verified_customer_and_does_not_log_out_unverified_user(): void
    {
        $c = $this->context();
        $payload = ['checkout_type' => 'package', 'package_id' => $c['package']->id, 'pickup_method' => 'self_pickup'];
        $unverified = User::factory()->unverified()->create(['role' => 'customer']);
        $this->actingAs($unverified)->postJson(self::ENDPOINT, $payload)->assertForbidden()->assertJsonPath('status', 'email_unverified');
        $this->assertAuthenticatedAs($unverified);
        $owner = User::factory()->create(['role' => 'vendor_owner', 'vendor_id' => $c['vendor']->id]);
        $this->actingAs($owner)->postJson(self::ENDPOINT, $payload)->assertForbidden()->assertJsonPath('status', 'access_denied');
    }

    public function test_products_outside_cart_and_unavailable_quantities_are_rejected(): void
    {
        $c = $this->context();
        $other = $this->context();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $this->delivery($c) + ['items' => [['id' => $c['item']->id, 'qty' => 1]]])->assertUnprocessable()->assertJsonPath('status', 'invalid_cart_item');
        $this->postJson(self::ENDPOINT, $this->delivery($other) + ['items' => [['id' => $other['item']->id, 'qty' => 5]]])->assertUnprocessable()->assertJsonPath('status', 'insufficient_stock');
    }

    public function test_mixed_vendor_cart_is_rejected(): void
    {
        $a = $this->context();
        $b = $this->context();
        $cart = app(CartService::class)->get();
        $cart['items'][] = ['master_item_id' => $a['item']->id, 'qty' => 1];
        app(CartService::class)->set($cart);
        $this->actingAs($b['customer'])->postJson(self::ENDPOINT, $this->delivery($b))->assertUnprocessable()->assertJsonPath('status', 'mixed_vendors');
    }

    public function test_package_rejects_deleted_or_foreign_components(): void
    {
        $a = $this->context();
        $b = $this->context();
        $payload = ['checkout_type' => 'package', 'package_id' => $a['package']->id, 'pickup_method' => 'self_pickup'];
        $a['package']->packageItems()->update(['master_item_id' => $b['item']->id]);
        $this->actingAs($a['customer'])->postJson(self::ENDPOINT, $payload)->assertUnprocessable()->assertJsonPath('status', 'invalid_package_component');
        $b['item']->delete();
        $this->postJson(self::ENDPOINT, $payload)->assertUnprocessable()->assertJsonPath('status', 'invalid_package_component');
    }

    public function test_inactive_package_and_vendor_are_not_available(): void
    {
        $c = $this->context();
        $payload = ['checkout_type' => 'package', 'package_id' => $c['package']->id, 'pickup_method' => 'self_pickup'];
        $c['package']->update(['is_active' => false]);
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $payload)->assertNotFound()->assertJsonPath('status', 'package_unavailable');
        $c['package']->update(['is_active' => true]);
        $c['vendor']->update(['status' => 'suspended']);
        $this->postJson(self::ENDPOINT, $payload)->assertNotFound();
    }

    public function test_missing_context_cannot_be_replaced_with_frontend_product_id(): void
    {
        $c = $this->context();
        app(CartService::class)->clear($c['customer']);
        $this->postJson(self::ENDPOINT, ['checkout_type' => 'regular', 'pickup_method' => 'self_pickup', 'product_id' => $c['item']->id])->assertUnprocessable()->assertJsonPath('status', 'checkout_context_missing');
    }

    public function test_validation_errors_follow_json_contract(): void
    {
        $this->postJson(self::ENDPOINT, ['checkout_type' => 'regular', 'pickup_method' => 'delivery', 'items' => [['id' => 1, 'qty' => -1]]])->assertUnprocessable()->assertJsonPath('status', 'validation_failed')->assertJsonPath('delivery_fee', null)->assertJsonValidationErrors(['customer_address_id', 'items.0.qty']);
    }

    public function test_direct_checkout_entry_creates_valid_preview_context(): void
    {
        $c = $this->context();
        app(CartService::class)->clear($c['customer']);
        $this->get(route('checkout.index', ['item_id' => $c['item']->id, 'qty' => 2]))->assertOk();
        Http::fake();
        $this->postJson(self::ENDPOINT, ['checkout_type' => 'regular', 'pickup_method' => 'self_pickup'])->assertOk()->assertJsonPath('total_weight_kg', 6.5);
        Http::assertNothingSent();
    }

    public function test_package_sums_different_products_and_keeps_vendor_tariff_isolated(): void
    {
        $a = $this->context();
        $b = $this->context();
        $secondItem = $a['item']->replicate();
        $secondItem->fill(['name' => 'Kompor', 'slug' => 'kompor', 'weight_kg' => 1.5])->save();
        $a['package']->packageItems()->create(['master_item_id' => $secondItem->id, 'quantity' => 3]);
        $b['vendor']->shippingSetting()->update(['rate_per_km' => 9000]);
        $this->fakeRoute();
        $this->actingAs($a['customer'])->postJson(self::ENDPOINT, ['checkout_type' => 'package', 'package_id' => $a['package']->id, 'pickup_method' => 'delivery', 'customer_address_id' => $a['address']->id, 'vendor_id' => $b['vendor']->id])->assertOk()->assertJsonPath('total_weight_kg', 11)->assertJsonPath('distance_fee', 24680)->assertJsonPath('delivery_fee', 30680);
        Http::assertSentCount(1);
    }

    public function test_self_pickup_works_without_coordinates_and_shipping_configuration(): void
    {
        $c = $this->context();
        $c['vendor']->update(['latitude' => null, 'longitude' => null]);
        $c['vendor']->shippingSetting()->delete();
        Http::fake();
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, ['checkout_type' => 'package', 'package_id' => $c['package']->id, 'pickup_method' => 'self_pickup'])->assertOk()->assertJsonPath('delivery_fee', 0)->assertJsonPath('distance_km', null);
        Http::assertNothingSent();
    }

    public function test_empty_package_and_zero_component_quantity_are_rejected(): void
    {
        $c = $this->context();
        $payload = ['checkout_type' => 'package', 'package_id' => $c['package']->id, 'pickup_method' => 'self_pickup'];
        $c['package']->packageItems()->update(['quantity' => 0]);
        $this->actingAs($c['customer'])->postJson(self::ENDPOINT, $payload)->assertUnprocessable()->assertJsonPath('status', 'invalid_weight');
        $c['package']->packageItems()->delete();
        $this->postJson(self::ENDPOINT, $payload)->assertNotFound()->assertJsonPath('status', 'package_unavailable');
    }

    public static function invalidInputs(): array
    {
        return [
            'unknown type' => [['checkout_type' => 'other', 'pickup_method' => 'self_pickup'], 'checkout_type'],
            'unknown method' => [['checkout_type' => 'regular', 'pickup_method' => 'other'], 'pickup_method'],
            'missing package' => [['checkout_type' => 'package', 'pickup_method' => 'self_pickup'], 'package_id'],
            'fractional quantity' => [['checkout_type' => 'regular', 'pickup_method' => 'self_pickup', 'items' => [['id' => 1, 'qty' => 1.5]]], 'items.0.qty'],
            'duplicate products' => [['checkout_type' => 'regular', 'pickup_method' => 'self_pickup', 'items' => [['id' => 1, 'qty' => 1], ['id' => 1, 'qty' => 2]]], 'items.0.id'],
            'missing return date' => [['checkout_type' => 'regular', 'pickup_method' => 'self_pickup', 'pickup_date' => '2090-01-01'], 'return_date'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_requests_are_rejected_before_calculation(array $input, string $field): void
    {
        Http::fake();
        $this->postJson(self::ENDPOINT, $input)->assertUnprocessable()->assertJsonPath('status', 'validation_failed')->assertJsonValidationErrors($field)->assertJsonPath('delivery_fee', null);
        Http::assertNothingSent();
    }

    public function test_preview_is_throttled_without_affecting_other_routes(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->postJson(self::ENDPOINT, [])->assertUnprocessable();
        }
        $this->postJson(self::ENDPOINT, [])->assertStatus(429)->assertJsonPath('status', 'too_many_requests')->assertJsonPath('delivery_fee', null)->assertHeader('Retry-After');
    }

    public function test_csrf_is_required_and_accepts_valid_token(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->withSession(['_token' => 'shipping-test-token'])->postJson(self::ENDPOINT, [])->assertStatus(419)->assertJsonPath('status', 'csrf_token_mismatch')->assertJsonPath('delivery_fee', null);
        $this->withHeader('X-CSRF-TOKEN', 'shipping-test-token')->postJson(self::ENDPOINT, [])->assertUnprocessable()->assertJsonPath('status', 'validation_failed');
    }
}
