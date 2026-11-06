<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VendorStoreLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_shipping_settings_and_see_unconfigured_store_location_and_leaflet_picker(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Malang Camp Gear',
            'address' => 'Jl. Veteran No. 12',
            'city' => 'Kota Malang',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('vendor.shipping.index'))
            ->assertOk()
            ->assertSee('Lokasi Toko')
            ->assertSee('Titik Lokasi Toko Belum Diatur')
            ->assertSee('Jl. Veteran No. 12')
            ->assertSee('Kota Malang')
            ->assertSee('tendaku-location-picker', false)
            ->assertSee('id="vendor-store-location-picker"', false)
            ->assertSee('Gunakan Lokasi Saya')
            ->assertSee('Simpan Lokasi Toko');

        $vendor->refresh();
        $this->assertFalse($vendor->hasCoordinates());
        $this->assertNull($vendor->latitude);
        $this->assertNull($vendor->longitude);
    }

    public function test_owner_can_see_saved_store_location_and_edit_location_button(): void
    {
        $vendor = Vendor::factory()->withCoordinates(-7.9553123, 112.6140987)->create([
            'name' => 'Batu Outdoor Rental',
            'address' => 'Jl. Diponegoro No. 45',
            'city' => 'Kota Batu',
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('vendor.shipping.index'))
            ->assertOk()
            ->assertSee('Lokasi Toko Tersimpan')
            ->assertSee('Edit Lokasi')
            ->assertSee('Jl. Diponegoro No. 45')
            ->assertSee('Kota Batu')
            ->assertSee('-7.9553123')
            ->assertSee('112.6140987')
            ->assertSee('id="vendor-store-location-modal-picker"', false);
    }

    public function test_owner_can_save_new_store_coordinates(): void
    {
        $vendor = Vendor::factory()->create([
            'address' => 'Jl. Soekarno Hatta No. 9',
            'city' => 'Kota Malang',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->put(route('vendor.shipping.location.update'), [
                'latitude' => -7.9467123,
                'longitude' => 112.6156789,
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHas('success', 'Lokasi toko berhasil disimpan.');

        $vendor->refresh();

        $this->assertTrue($vendor->hasCoordinates());
        $this->assertEqualsWithDelta(-7.9467123, $vendor->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6156789, $vendor->longitude, 0.0000001);
        $this->assertSame('Jl. Soekarno Hatta No. 9', $vendor->address);
        $this->assertSame('Kota Malang', $vendor->city);
    }

    public function test_owner_can_update_existing_store_coordinates_and_address_details(): void
    {
        $vendor = Vendor::factory()->withCoordinates(-7.9797000, 112.6304000)->create([
            'address' => 'Jl. Ijen No. 1',
            'city' => 'Kota Malang',
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->put(route('vendor.shipping.location.update'), [
                'address' => 'Jl. Raya Tlogomas No. 246',
                'city' => 'Kota Malang',
                'latitude' => -7.9225104,
                'longitude' => 112.5968412,
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHas('success', 'Lokasi toko berhasil disimpan.');

        $vendor->refresh();

        $this->assertTrue($vendor->hasCoordinates());
        $this->assertSame('Jl. Raya Tlogomas No. 246', $vendor->address);
        $this->assertSame('Kota Malang', $vendor->city);
        $this->assertEqualsWithDelta(-7.9225104, $vendor->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.5968412, $vendor->longitude, 0.0000001);
    }

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>}>
     */
    public static function invalidCoordinatePayloadProvider(): array
    {
        return [
            'empty coordinates' => [
                ['latitude' => '', 'longitude' => ''],
                ['latitude', 'longitude'],
            ],
            'missing longitude' => [
                ['latitude' => -7.9797],
                ['longitude'],
            ],
            'missing latitude' => [
                ['longitude' => 112.6304],
                ['latitude'],
            ],
            'non-numeric coordinates' => [
                ['latitude' => 'bukan-angka', 'longitude' => 'invalid'],
                ['latitude', 'longitude'],
            ],
            'latitude below -90' => [
                ['latitude' => -90.0000001, 'longitude' => 112.6304],
                ['latitude'],
            ],
            'latitude above 90' => [
                ['latitude' => 90.5, 'longitude' => 112.6304],
                ['latitude'],
            ],
            'longitude below -180' => [
                ['latitude' => -7.9797, 'longitude' => -180.0001],
                ['longitude'],
            ],
            'longitude above 180' => [
                ['latitude' => -7.9797, 'longitude' => 181.25],
                ['longitude'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $expectedErrorFields
     */
    #[DataProvider('invalidCoordinatePayloadProvider')]
    public function test_invalid_coordinates_are_rejected(array $payload, array $expectedErrorFields): void
    {
        $vendor = Vendor::factory()->create([
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->from(route('vendor.shipping.index'))
            ->put(route('vendor.shipping.location.update'), $payload)
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHasErrors($expectedErrorFields);

        $vendor->refresh();
        $this->assertFalse($vendor->hasCoordinates());
        $this->assertNull($vendor->latitude);
        $this->assertNull($vendor->longitude);
    }

    public function test_owner_cannot_modify_another_vendors_location(): void
    {
        $vendorA = Vendor::factory()->create([
            'name' => 'Vendor Alpha',
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $vendorB = Vendor::factory()->withCoordinates(-7.9812345, 112.6312345)->create([
            'name' => 'Vendor Beta',
            'address' => 'Alamat Asli Vendor Beta',
            'city' => 'Kota Batu',
            'status' => 'active',
        ]);

        $ownerA = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendorA->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($ownerA)
            ->put(route('vendor.shipping.location.update'), [
                'vendor_id' => $vendorB->id,
                'address' => 'Alamat Bajakan',
                'latitude' => -7.9100000,
                'longitude' => 112.5900000,
            ])
            ->assertRedirect(route('vendor.shipping.index'));

        $vendorB->refresh();
        $this->assertSame('Alamat Asli Vendor Beta', $vendorB->address);
        $this->assertEqualsWithDelta(-7.9812345, $vendorB->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6312345, $vendorB->longitude, 0.0000001);

        $vendorA->refresh();
        $this->assertEqualsWithDelta(-7.9100000, $vendorA->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.5900000, $vendorA->longitude, 0.0000001);
    }

    public function test_non_owner_roles_cannot_update_vendor_location(): void
    {
        $vendor = Vendor::factory()->create([
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $kasir = User::factory()->create([
            'role' => 'vendor_admin',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $payload = [
            'latitude' => -7.9666204,
            'longitude' => 112.6326321,
        ];

        $this->actingAs($kasir)
            ->put(route('vendor.shipping.location.update'), $payload)
            ->assertForbidden();

        $this->actingAs($customer)
            ->put(route('vendor.shipping.location.update'), $payload)
            ->assertForbidden();

        $vendor->refresh();
        $this->assertFalse($vendor->hasCoordinates());
    }

    public function test_updating_store_location_does_not_alter_shipping_tariff_and_vice_versa(): void
    {
        $vendor = Vendor::factory()->create([
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $shippingSetting = VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'rate_per_km' => 5500.00,
            'weight_threshold_kg' => 12.00,
            'extra_weight_fee_per_kg' => 2500.00,
        ]);

        // 1. Simpan lokasi toko dan pastikan tarif pengiriman tidak berubah
        $this->actingAs($owner)
            ->put(route('vendor.shipping.location.update'), [
                'latitude' => -7.9666204,
                'longitude' => 112.6326321,
            ])
            ->assertRedirect(route('vendor.shipping.index'));

        $shippingSetting->refresh();
        $this->assertSame('5500.00', $shippingSetting->rate_per_km);
        $this->assertSame('12.00', $shippingSetting->weight_threshold_kg);
        $this->assertSame('2500.00', $shippingSetting->extra_weight_fee_per_kg);

        // 2. Perbarui tarif pengiriman dan pastikan koordinat lokasi toko tidak berubah
        $this->actingAs($owner)
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => 6000.00,
                'weight_threshold_kg' => 15.00,
                'extra_weight_fee_per_kg' => 3000.00,
            ])
            ->assertRedirect(route('vendor.shipping.index'));

        $vendor->refresh();
        $shippingSetting->refresh();

        $this->assertSame('6000.00', $shippingSetting->rate_per_km);
        $this->assertTrue($vendor->hasCoordinates());
        $this->assertEqualsWithDelta(-7.9666204, $vendor->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6326321, $vendor->longitude, 0.0000001);
    }
}
