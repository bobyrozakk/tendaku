<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LocationPickerComponentTest extends TestCase
{
    public function test_it_renders_without_coordinates_and_does_not_auto_select_default_malang_center(): void
    {
        $html = Blade::render('<x-location-picker />');

        $this->assertStringContainsString('tendaku-location-picker', $html);
        $this->assertStringContainsString('Gunakan Lokasi Saya', $html);
        $this->assertStringContainsString('Hapus Titik', $html);
        $this->assertStringContainsString('Belum ada titik lokasi yang dipilih (peta menampilkan area Malang Raya)', $html);

        $this->assertStringContainsString('name="latitude"', $html);
        $this->assertStringContainsString('name="longitude"', $html);
        $this->assertMatchesRegularExpression('/name="latitude"\s+value=""/', $html);
        $this->assertMatchesRegularExpression('/name="longitude"\s+value=""/', $html);

        $this->assertStringContainsString('\u0022initialLatitude\u0022:null', $html);
        $this->assertStringContainsString('\u0022initialLongitude\u0022:null', $html);
        $this->assertStringContainsString('-7.9797', $html);
        $this->assertStringContainsString('112.6304', $html);
    }

    public function test_it_renders_with_existing_coordinates_for_edit_mode(): void
    {
        $html = Blade::render(
            '<x-location-picker :latitude="-7.9666204" :longitude="112.6326321" />'
        );

        $this->assertStringContainsString('Titik lokasi dipilih', $html);
        $this->assertStringContainsString('value="-7.9666204"', $html);
        $this->assertStringContainsString('value="112.6326321"', $html);
        $this->assertStringContainsString('-7.9666204', $html);
        $this->assertStringContainsString('112.6326321', $html);
    }

    public function test_it_treats_zero_coordinates_as_valid_and_not_empty(): void
    {
        $html = Blade::render(
            '<x-location-picker :latitude="0" :longitude="0" />'
        );

        $this->assertStringContainsString('Titik lokasi dipilih', $html);
        $this->assertStringContainsString('value="0.0000000"', $html);
        $this->assertStringNotContainsString('\u0022initialLatitude\u0022:null', $html);
        $this->assertStringNotContainsString('\u0022initialLongitude\u0022:null', $html);

        $htmlFromStringZero = Blade::render(
            '<x-location-picker latitude="0" longitude="0.0" />'
        );

        $this->assertStringContainsString('value="0.0000000"', $htmlFromStringZero);
    }

    public function test_it_rejects_out_of_range_or_invalid_initial_coordinates_safely(): void
    {
        $outOfRangeLatHtml = Blade::render(
            '<x-location-picker :latitude="-95.123" :longitude="112.6304" />'
        );

        $this->assertMatchesRegularExpression('/name="latitude"\s+value=""/', $outOfRangeLatHtml);
        $this->assertMatchesRegularExpression('/name="longitude"\s+value=""/', $outOfRangeLatHtml);
        $this->assertStringContainsString('\u0022initialLatitude\u0022:null', $outOfRangeLatHtml);
        $this->assertStringContainsString('\u0022initialLongitude\u0022:null', $outOfRangeLatHtml);

        $outOfRangeLngHtml = Blade::render(
            '<x-location-picker :latitude="-7.9797" :longitude="185.0001" />'
        );

        $this->assertMatchesRegularExpression('/name="latitude"\s+value=""/', $outOfRangeLngHtml);
        $this->assertMatchesRegularExpression('/name="longitude"\s+value=""/', $outOfRangeLngHtml);

        $partialCoordinateHtml = Blade::render(
            '<x-location-picker :latitude="-7.9797" :longitude="null" />'
        );

        $this->assertMatchesRegularExpression('/name="latitude"\s+value=""/', $partialCoordinateHtml);
        $this->assertMatchesRegularExpression('/name="longitude"\s+value=""/', $partialCoordinateHtml);
    }

    public function test_it_supports_custom_input_names_livewire_models_and_disabled_mode(): void
    {
        $html = Blade::render(
            '<x-location-picker
                id="vendor-store-picker"
                latitude-name="store_latitude"
                longitude-name="store_longitude"
                wire-latitude-model="form.latitude"
                wire-longitude-model="form.longitude"
                label="Lokasi Toko Vendor"
                :required="true"
            />'
        );

        $this->assertStringContainsString('id="vendor-store-picker"', $html);
        $this->assertStringContainsString('name="store_latitude"', $html);
        $this->assertStringContainsString('name="store_longitude"', $html);
        $this->assertStringContainsString('form.latitude', $html);
        $this->assertStringContainsString('form.longitude', $html);
        $this->assertStringContainsString('Lokasi Toko Vendor', $html);
        $this->assertStringContainsString('wire:ignore', $html);

        $disabledHtml = Blade::render(
            '<x-location-picker :disabled="true" />'
        );

        $this->assertStringNotContainsString('Gunakan Lokasi Saya', $disabledHtml);
    }

    public function test_multiple_instances_on_same_page_generate_unique_ids(): void
    {
        $html = Blade::render('
            <div>
                <x-location-picker latitude-name="vendor_lat" longitude-name="vendor_lng" />
                <x-location-picker latitude-name="customer_lat" longitude-name="customer_lng" />
            </div>
        ');

        preg_match_all('/id="(location-picker-[a-z0-9]+)"/', $html, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertNotSame($matches[1][0], $matches[1][1]);
        $this->assertStringContainsString('name="vendor_lat"', $html);
        $this->assertStringContainsString('name="customer_lat"', $html);
    }
}
