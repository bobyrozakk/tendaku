<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OpenRouteService;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShippingPreviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function errorStatuses(): array
    {
        return ['authentication' => [401], 'authorization' => [403], 'validation' => [422], 'server' => [500]];
    }

    #[DataProvider('errorStatuses')]
    public function test_other_endpoint_json_errors_do_not_use_shipping_envelope(int $status): void
    {
        config(['app.debug' => false]);
        Route::post('/katalog/booking/shipping-preview-other', function (Request $request) use ($status): void {
            if ($status === 422) {
                $request->validate(['required_field' => ['required']]);
            }
            abort($status, 'Regression error');
        })->middleware('web');

        $response = $this->postJson('/katalog/booking/shipping-preview-other');
        $response->assertStatus($status)->assertJsonStructure(['message'])->assertJsonMissingPath('success')->assertJsonMissingPath('status')->assertJsonMissingPath('delivery_fee');
        if ($status === 422) {
            $response->assertJsonValidationErrors('required_field');
        }
    }

    public function test_other_web_validation_keeps_redirect_and_session_errors(): void
    {
        Route::post('/shipping-audit/validate', function (Request $request): void {
            $request->validate(['required_field' => ['required']]);
        })->middleware('web');

        $this->from('/keranjang')->post('/shipping-audit/validate')->assertRedirect('/keranjang')->assertSessionHasErrors('required_field');
    }

    public static function htmlErrorStatuses(): array
    {
        return ['authorization' => [403], 'server' => [500]];
    }

    #[DataProvider('htmlErrorStatuses')]
    public function test_other_web_errors_keep_html_responses(int $status): void
    {
        config(['app.debug' => false]);
        Route::get('/shipping-audit/html-error', function () use ($status): void {
            abort($status);
        })->middleware('web');

        $this->get('/shipping-audit/html-error')->assertStatus($status)->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function test_existing_authenticated_route_keeps_login_redirect_and_json_401(): void
    {
        $url = route('customer.addresses.index');
        $this->get($url)->assertRedirect(route('login'));
        $this->getJson($url)->assertUnauthorized()->assertJsonMissingPath('delivery_fee')->assertJsonMissingPath('success');
    }

    public function test_existing_address_endpoint_keeps_standard_validation_errors(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->postJson(route('customer.addresses.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['label', 'recipient_name'])->assertJsonMissingPath('delivery_fee')->assertJsonMissingPath('success');
    }

    public static function deniedPreviewRequests(): array
    {
        return [
            'guest delivery' => [null, true, ['checkout_type' => 'regular', 'pickup_method' => 'delivery', 'customer_address_id' => 999], 401, 'authentication_required'],
            'guest package' => [null, true, ['checkout_type' => 'package', 'pickup_method' => 'delivery', 'package_id' => 999, 'customer_address_id' => 999], 401, 'authentication_required'],
            'unverified package' => ['customer', false, ['checkout_type' => 'package', 'pickup_method' => 'delivery', 'package_id' => 999, 'customer_address_id' => 999], 403, 'email_unverified'],
            'wrong role package' => ['vendor_owner', true, ['checkout_type' => 'package', 'pickup_method' => 'delivery', 'package_id' => 999, 'customer_address_id' => 999], 403, 'access_denied'],
            'wrong role regular delivery' => ['vendor_owner', true, ['checkout_type' => 'regular', 'pickup_method' => 'delivery', 'customer_address_id' => 999], 403, 'access_denied'],
            'guest malformed package' => [null, true, ['checkout_type' => 'package', 'pickup_method' => 'delivery'], 422, 'validation_failed'],
            'unverified malformed package' => ['customer', false, ['checkout_type' => 'package', 'pickup_method' => 'delivery'], 422, 'validation_failed'],
            'wrong role malformed package' => ['vendor_owner', true, ['checkout_type' => 'package', 'pickup_method' => 'delivery'], 422, 'validation_failed'],
            'malformed checkout type' => [null, true, ['checkout_type' => ['package'], 'pickup_method' => 'delivery'], 422, 'validation_failed'],
        ];
    }

    #[DataProvider('deniedPreviewRequests')]
    public function test_denied_or_malformed_preview_never_reaches_shipping_calculation(?string $role, bool $verified, array $payload, int $status, string $code): void
    {
        if ($role !== null) {
            $factory = User::factory();
            if (! $verified) {
                $factory = $factory->unverified();
            }
            $user = $factory->create(['role' => $role]);
            $this->actingAs($user);
        }
        Http::preventStrayRequests();
        Http::fake();
        $this->mock(OpenRouteService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('getDrivingDistance');
        });
        $this->mock(ShippingCalculator::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('calculateTotalWeight');
            $mock->shouldNotReceive('calculatePackageWeight');
            $mock->shouldNotReceive('calculate');
        });

        $this->postJson(route('checkout.shipping.preview'), $payload)->assertStatus($status)->assertJsonPath('success', false)->assertJsonPath('status', $code)->assertJsonPath('delivery_fee', null);
        Http::assertNothingSent();
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);
        if ($role !== null) {
            $this->assertAuthenticatedAs($user);
        }
    }

    public function test_existing_package_checkout_keeps_auth_verification_and_role_guards(): void
    {
        $url = route('customer.packages.checkout', ['id' => 999]);
        $this->get($url)->assertRedirect(route('login'));
        $this->post($url)->assertRedirect(route('login'));

        $unverified = User::factory()->unverified()->create(['role' => 'customer']);
        $this->actingAs($unverified)->get($url)->assertRedirect(route('verification.notice'));
        $this->post($url)->assertRedirect(route('verification.notice'));
        $this->getJson($url)->assertForbidden()->assertJsonMissingPath('delivery_fee');

        $owner = User::factory()->create(['role' => 'vendor_owner']);
        $this->actingAs($owner)->getJson($url)->assertForbidden()->assertJsonMissingPath('delivery_fee');
        $this->postJson($url)->assertForbidden()->assertJsonMissingPath('delivery_fee');

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->getJson($url)->assertNotFound()->assertJsonMissingPath('delivery_fee');
    }
}
