<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class AddressAutofillComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));

        config(['database.connections' => ['sqlite' => config('database.connections.sqlite')]]);
        DB::connection()->beforeExecuting(static function (): never {
            throw new LogicException('Database queries are forbidden in address autofill view tests.');
        });
    }

    public function test_checkout_modal_renders_autofill_with_its_existing_bindings_and_lifecycle(): void
    {
        $html = Blade::render("@include('customer.booking.partials.create.add-address-modal')");

        $this->assertStringContainsString('x-data="addressAutofill(', $html);
        $this->assertStringContainsString('reverse-geocode', $html);
        $this->assertStringContainsString('syncAutofillContext(showAddAddressModal &amp;&amp; !newAddressSubmitting, newAddressForm)', $html);
        $this->assertStringContainsString(':inert="newAddressSubmitting"', $html);
        $this->assertStringContainsString('@location-picker:change="syncNewAddressCoordinates($event.detail)"', $html);
        $this->assertStringContainsString('@location-picker:change="scheduleAddressAutofill($event.detail)"', $html);
        $this->assertStringContainsString('x-model="newAddressForm.full_address" data-address-field="full_address"', $html);
        $this->assertStringContainsString('id="checkout-add-address-location-picker"', $html);
    }

    public function test_owner_autofill_preserves_native_input_names_and_receives_no_credentials(): void
    {
        config(['services.locationiq.api_key' => 'synthetic-secret-must-not-reach-html']);

        $html = Blade::render('
            <x-address-autofill picker-id="owner-picker" active-expression="showLocationModal" :as-form="true" method="POST" action="/owner-location">
                <x-text-input name="address" data-address-field="full_address" />
                <x-text-input name="city" data-address-field="city" />
            </x-address-autofill>
        ');

        $this->assertStringContainsString('syncAutofillContext(showLocationModal, null)', $html);
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('method="POST" action="/owner-location"', $html);
        $this->assertStringContainsString('name="address"', $html);
        $this->assertStringContainsString('data-address-field="full_address"', $html);
        $this->assertStringContainsString('name="city"', $html);
        $this->assertStringContainsString('@submit.capture="cancelAddressAutofill()"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringNotContainsString('synthetic-secret-must-not-reach-html', $html);
        $this->assertStringNotContainsString('LOCATIONIQ_API_KEY', $html);
    }
}
