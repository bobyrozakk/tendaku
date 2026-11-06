<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorShippingSetting;
use App\Services\OpenRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CoordinateDatabaseAndModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendors_and_customer_addresses_tables_have_latitude_and_longitude_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('vendors', [
            'id',
            'name',
            'address',
            'city',
            'latitude',
            'longitude',
        ]));

        $this->assertTrue(Schema::hasColumns('customer_addresses', [
            'id',
            'user_id',
            'full_address',
            'province',
            'city',
            'district',
            'postal_code',
            'latitude',
            'longitude',
        ]));
    }

    public function test_vendor_coordinates_can_be_stored_and_read_as_float_compatible_with_open_route_service(): void
    {
        $vendor = Vendor::factory()
            ->withCoordinates(-7.9553123, 112.6145678)
            ->create([
                'name' => 'Tendaku Malang Central',
                'address' => 'Jl. Soekarno Hatta No. 9',
                'city' => 'Malang',
            ]);

        $freshVendor = $vendor->fresh();

        $this->assertNotNull($freshVendor);
        $this->assertIsFloat($freshVendor->latitude);
        $this->assertIsFloat($freshVendor->longitude);
        $this->assertEqualsWithDelta(-7.9553123, $freshVendor->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6145678, $freshVendor->longitude, 0.0000001);
        $this->assertTrue($freshVendor->hasCoordinates());

        $openRouteService = app(OpenRouteService::class);
        $this->assertTrue($openRouteService->isValidCoordinatePair(
            $freshVendor->latitude,
            $freshVendor->longitude
        ));
    }

    public function test_customer_address_coordinates_can_be_stored_and_read_without_breaking_existing_accessors(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $address = CustomerAddress::factory()
            ->for($customer, 'user')
            ->default()
            ->withCoordinates(-7.9797125, 112.6304125)
            ->create([
                'label' => 'Rumah Utama',
                'recipient_name' => 'Budi Santoso',
                'phone_number' => '081234567890',
                'full_address' => 'Jl. Ijen No. 15',
                'province' => 'Jawa Timur',
                'city' => 'Kota Malang',
                'district' => 'Klojen',
                'village' => 'Oro-oro Dowo',
                'postal_code' => '65119',
                'notes' => 'Depan taman',
            ]);

        $freshAddress = $address->fresh();

        $this->assertNotNull($freshAddress);
        $this->assertIsFloat($freshAddress->latitude);
        $this->assertIsFloat($freshAddress->longitude);
        $this->assertEqualsWithDelta(-7.9797125, $freshAddress->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6304125, $freshAddress->longitude, 0.0000001);
        $this->assertTrue($freshAddress->hasCoordinates());
        $this->assertTrue($freshAddress->user->is($customer));

        $this->assertStringContainsString('Jl. Ijen No. 15', $freshAddress->formatted_address);
        $this->assertStringContainsString('Kota Malang', $freshAddress->formatted_address);
        $this->assertStringContainsString('Patokan: Depan taman', $freshAddress->formatted_address);
        $this->assertStringContainsString('Budi Santoso (081234567890)', $freshAddress->delivery_snapshot);
    }

    public function test_vendor_without_coordinates_remains_accessible_and_shipping_configuration_helper_is_unchanged(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Vendor Lama Tanpa Koordinat',
            'address' => 'Jl. Veteran No. 1',
            'city' => 'Malang',
        ]);

        $freshVendor = $vendor->fresh();

        $this->assertNotNull($freshVendor);
        $this->assertNull($freshVendor->latitude);
        $this->assertNull($freshVendor->longitude);
        $this->assertFalse($freshVendor->hasCoordinates());
        $this->assertFalse($freshVendor->isShippingConfigured());

        VendorShippingSetting::factory()->for($freshVendor)->create([
            'rate_per_km' => 5000,
            'weight_threshold_kg' => 10,
            'extra_weight_fee_per_kg' => 2000,
        ]);

        // isShippingConfigured() tetap mengacu pada ketersediaan tarif pengiriman
        $this->assertTrue($freshVendor->fresh()->isShippingConfigured());
        $this->assertFalse($freshVendor->fresh()->hasCoordinates());
    }

    public function test_customer_address_without_coordinates_remains_accessible_and_functional(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $address = CustomerAddress::factory()->for($customer, 'user')->create([
            'label' => 'Kos Lama',
            'recipient_name' => 'Siti Aminah',
            'phone_number' => '081298765432',
            'full_address' => 'Jl. Sumbersari No. 22',
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'district' => 'Lowokwaru',
        ]);

        $freshAddress = $address->fresh();

        $this->assertNotNull($freshAddress);
        $this->assertNull($freshAddress->latitude);
        $this->assertNull($freshAddress->longitude);
        $this->assertFalse($freshAddress->hasCoordinates());

        $freshAddress->setAsDefault();
        $this->assertTrue($freshAddress->fresh()->is_default);
        $this->assertStringContainsString('Jl. Sumbersari No. 22', $freshAddress->formatted_address);
    }

    /**
     * @return array<string, array{mixed, mixed, bool}>
     */
    public static function coordinateValidationProvider(): array
    {
        return [
            'both null' => [null, null, false],
            'only latitude present' => [-7.9553, null, false],
            'only longitude present' => [null, 112.6145, false],
            'empty strings' => ['', '', false],
            'non numeric strings' => ['invalid', 'coord', false],
            'latitude below -90' => [-90.0001, 112.6145, false],
            'latitude above 90' => [90.0001, 112.6145, false],
            'longitude below -180' => [-7.9553, -180.0001, false],
            'longitude above 180' => [-7.9553, 180.0001, false],
            'valid Malang coordinates' => [-7.9797, 112.6304, true],
            'valid boundary coordinates' => [-90.0, 180.0, true],
            'valid zero coordinates' => [0.0, 0.0, true],
        ];
    }

    #[DataProvider('coordinateValidationProvider')]
    public function test_has_coordinates_helper_validates_presence_and_geographic_bounds(
        mixed $latitude,
        mixed $longitude,
        bool $expected
    ): void {
        $vendor = new Vendor;
        $vendor->setRawAttributes([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $address = new CustomerAddress;
        $address->setRawAttributes([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $this->assertSame($expected, $vendor->hasCoordinates());
        $this->assertSame($expected, $address->hasCoordinates());
    }
}
