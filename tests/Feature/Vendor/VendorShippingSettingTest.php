<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPaymentSetting;
use App\Models\VendorShippingSetting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VendorShippingSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_shipping_settings_table_has_expected_columns_without_base_fee(): void
    {
        $this->assertTrue(Schema::hasTable('vendor_shipping_settings'));

        $this->assertTrue(Schema::hasColumns('vendor_shipping_settings', [
            'id',
            'vendor_id',
            'rate_per_km',
            'weight_threshold_kg',
            'extra_weight_fee_per_kg',
            'created_at',
            'updated_at',
        ]));

        $this->assertFalse(Schema::hasColumn('vendor_shipping_settings', 'base_fee'));
    }

    public function test_new_vendor_is_recognized_as_unconfigured_by_default(): void
    {
        $vendor = Vendor::factory()->create();

        $this->assertNull($vendor->shippingSetting);
        $this->assertFalse($vendor->hasShippingSetting());
        $this->assertFalse($vendor->isShippingConfigured());
        $this->assertDatabaseCount('vendor_shipping_settings', 0);
    }

    public function test_vendor_has_one_to_one_shipping_setting_and_casts_decimals_properly(): void
    {
        $vendor = Vendor::factory()->create();

        $paymentSetting = VendorPaymentSetting::query()->create([
            'vendor_id' => $vendor->id,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Tendaku Store',
        ]);

        $shippingSetting = VendorShippingSetting::query()->create([
            'vendor_id' => $vendor->id,
            'rate_per_km' => 4500.5,
            'weight_threshold_kg' => 12.25,
            'extra_weight_fee_per_kg' => 1500,
        ]);

        $this->assertTrue($vendor->hasShippingSetting());
        $this->assertTrue($vendor->isShippingConfigured());
        $this->assertTrue($vendor->shippingSetting->is($shippingSetting));
        $this->assertTrue($shippingSetting->vendor->is($vendor));
        $this->assertTrue($vendor->paymentSetting->is($paymentSetting));

        $this->assertSame('4500.50', $shippingSetting->rate_per_km);
        $this->assertSame('12.25', $shippingSetting->weight_threshold_kg);
        $this->assertSame('1500.00', $shippingSetting->extra_weight_fee_per_kg);
    }

    public function test_vendor_cannot_have_more_than_one_shipping_setting(): void
    {
        $vendor = Vendor::factory()->create();

        VendorShippingSetting::factory()->for($vendor)->create([
            'rate_per_km' => 5000.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 2000.00,
        ]);

        $this->expectException(QueryException::class);

        VendorShippingSetting::factory()->for($vendor)->create([
            'rate_per_km' => 6000.00,
            'weight_threshold_kg' => 15.00,
            'extra_weight_fee_per_kg' => 2500.00,
        ]);
    }

    /**
     * @return array<string, array{string, float}>
     */
    public static function negativeTariffProvider(): array
    {
        return [
            'negative rate_per_km' => ['rate_per_km', -0.01],
            'negative weight_threshold_kg' => ['weight_threshold_kg', -1.00],
            'negative extra_weight_fee_per_kg' => ['extra_weight_fee_per_kg', -500.00],
        ];
    }

    #[DataProvider('negativeTariffProvider')]
    public function test_rejects_negative_tariff_values_on_create(string $field, float $invalidValue): void
    {
        $vendor = Vendor::factory()->create();

        $attributes = [
            'vendor_id' => $vendor->id,
            'rate_per_km' => 5000.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 2000.00,
        ];
        $attributes[$field] = $invalidValue;

        try {
            VendorShippingSetting::query()->create($attributes);
            $this->fail("Expected InvalidArgumentException when {$field} is negative.");
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('vendor_shipping_settings', 0);
        }
    }

    #[DataProvider('negativeTariffProvider')]
    public function test_rejects_negative_tariff_values_on_update(string $field, float $invalidValue): void
    {
        $vendor = Vendor::factory()->create();

        $setting = VendorShippingSetting::factory()->for($vendor)->create([
            'rate_per_km' => 5000.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 2000.00,
        ]);

        try {
            $setting->update([$field => $invalidValue]);
            $this->fail("Expected InvalidArgumentException when updating {$field} to a negative value.");
        } catch (InvalidArgumentException) {
            $setting->refresh();

            $this->assertSame('5000.00', $setting->rate_per_km);
            $this->assertSame('10.00', $setting->weight_threshold_kg);
            $this->assertSame('2000.00', $setting->extra_weight_fee_per_kg);
        }
    }

    public function test_allows_zero_tariff_values(): void
    {
        $vendor = Vendor::factory()->create();

        $setting = VendorShippingSetting::query()->create([
            'vendor_id' => $vendor->id,
            'rate_per_km' => 0,
            'weight_threshold_kg' => 0,
            'extra_weight_fee_per_kg' => 0,
        ]);

        $this->assertSame('0.00', $setting->rate_per_km);
        $this->assertSame('0.00', $setting->weight_threshold_kg);
        $this->assertSame('0.00', $setting->extra_weight_fee_per_kg);
    }

    public function test_shipping_settings_are_isolated_between_vendors(): void
    {
        $vendorA = Vendor::factory()->create(['name' => 'Vendor Alpha']);
        $vendorB = Vendor::factory()->create(['name' => 'Vendor Beta']);
        $vendorC = Vendor::factory()->create(['name' => 'Vendor Unconfigured']);

        $ownerA = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendorA->id,
        ]);

        $settingA = VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorA->id,
            'rate_per_km' => 4000.00,
            'weight_threshold_kg' => 8.00,
            'extra_weight_fee_per_kg' => 1500.00,
        ]);

        $settingB = VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorB->id,
            'rate_per_km' => 7500.00,
            'weight_threshold_kg' => 15.00,
            'extra_weight_fee_per_kg' => 3000.00,
        ]);

        // Ketika login sebagai Owner A, global scope BelongsToVendor mengisolasi data Vendor B
        $this->actingAs($ownerA);

        $visibleSettings = VendorShippingSetting::all();
        $this->assertCount(1, $visibleSettings);
        $this->assertTrue($visibleSettings->first()->is($settingA));
        $this->assertNull(VendorShippingSetting::find($settingB->id));

        // Perubahan pada tarif Vendor A tidak mengubah tarif Vendor B maupun status Vendor C
        $settingA->update([
            'rate_per_km' => 4500.00,
        ]);

        $settingB->refresh();
        $this->assertSame('4500.00', $settingA->fresh()->rate_per_km);
        $this->assertSame('7500.00', $settingB->rate_per_km);
        $this->assertSame('15.00', $settingB->weight_threshold_kg);
        $this->assertSame('3000.00', $settingB->extra_weight_fee_per_kg);
        $this->assertFalse($vendorC->hasShippingSetting());
    }

    public function test_deleting_vendor_cascades_to_shipping_setting(): void
    {
        $vendor = Vendor::factory()->create();

        $setting = VendorShippingSetting::factory()->for($vendor)->create();

        $vendor->forceDelete();

        $this->assertDatabaseMissing('vendor_shipping_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_owner_sees_empty_state_and_shipping_form_when_unconfigured(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('vendor.shipping.index'))
            ->assertOk()
            ->assertSee('Pengaturan Pengiriman')
            ->assertSee('Belum Dikonfigurasi')
            ->assertSee('Tarif Pengiriman Belum Diatur')
            ->assertSee('Tarif per Kilometer (Rp/km) *')
            ->assertSee('Batas Berat Normal (kg) *')
            ->assertSee('Biaya Tambahan per KG (Rp/kg) *')
            ->assertSee('name="rate_per_km"', false)
            ->assertSee('name="weight_threshold_kg"', false)
            ->assertSee('name="extra_weight_fee_per_kg"', false)
            ->assertSee('Simpan Pengaturan');
    }

    public function test_owner_sees_existing_shipping_setting_values_without_other_vendor_data(): void
    {
        $vendorA = Vendor::factory()->create(['name' => 'Vendor Alpha', 'status' => 'active']);
        $vendorB = Vendor::factory()->create(['name' => 'Vendor Beta', 'status' => 'active']);

        $ownerA = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendorA->id,
            'email_verified_at' => now(),
        ]);

        VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorA->id,
            'rate_per_km' => 6500.00,
            'weight_threshold_kg' => 12.50,
            'extra_weight_fee_per_kg' => 2500.00,
        ]);

        VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorB->id,
            'rate_per_km' => 9900.00,
            'weight_threshold_kg' => 25.00,
            'extra_weight_fee_per_kg' => 4500.00,
        ]);

        $this->actingAs($ownerA)
            ->get(route('vendor.shipping.index'))
            ->assertOk()
            ->assertSee('Tarif Terkonfigurasi')
            ->assertSee('Ringkasan Tarif Pengiriman Aktif')
            ->assertSee('Edit Tarif')
            ->assertSee('Edit Tarif Pengiriman Toko')
            ->assertDontSee('Parameter Tarif Pengiriman Toko')
            ->assertSee('Rp 6.500')
            ->assertSee('12,5')
            ->assertSee('Rp 2.500')
            ->assertSee('value="6500.00"', false)
            ->assertSee('value="12.50"', false)
            ->assertSee('value="2500.00"', false)
            ->assertDontSee('Rp 9.900')
            ->assertDontSee('value="9900.00"', false);
    }

    public function test_non_owner_roles_cannot_access_shipping_settings_page(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
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
            'rate_per_km' => 5000,
            'weight_threshold_kg' => 10,
            'extra_weight_fee_per_kg' => 2000,
        ];

        $this->actingAs($kasir)
            ->get(route('vendor.shipping.index'))
            ->assertForbidden();

        $this->actingAs($kasir)
            ->put(route('vendor.shipping.update'), $payload)
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('vendor.shipping.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->put(route('vendor.shipping.update'), $payload)
            ->assertForbidden();

        $this->assertDatabaseCount('vendor_shipping_settings', 0);
    }

    public function test_owner_can_save_first_shipping_setting_and_page_displays_latest_values_without_empty_state(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => 1500,
                'weight_threshold_kg' => 10,
                'extra_weight_fee_per_kg' => 1000,
            ]);

        $response->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHas('success', 'Pengaturan tarif pengiriman berhasil disimpan.');

        $this->assertDatabaseCount('vendor_shipping_settings', 1);
        $this->assertDatabaseHas('vendor_shipping_settings', [
            'vendor_id' => $vendor->id,
            'rate_per_km' => 1500.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 1000.00,
        ]);

        $this->actingAs($owner)
            ->get(route('vendor.shipping.index'))
            ->assertOk()
            ->assertSee('Tarif Terkonfigurasi')
            ->assertSee('Ringkasan Tarif Pengiriman Aktif')
            ->assertSee('Edit Tarif')
            ->assertSee('Edit Tarif Pengiriman Toko')
            ->assertDontSee('Tarif Pengiriman Belum Diatur')
            ->assertDontSee('Parameter Tarif Pengiriman Toko')
            ->assertSee('Rp 1.500')
            ->assertSee('10')
            ->assertSee('Rp 1.000')
            ->assertSee('value="1500.00"', false)
            ->assertSee('value="10.00"', false)
            ->assertSee('value="1000.00"', false);
    }

    public function test_owner_can_update_existing_shipping_setting_without_creating_duplicates(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $existing = VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'rate_per_km' => 3000.00,
            'weight_threshold_kg' => 8.00,
            'extra_weight_fee_per_kg' => 1500.00,
        ]);

        $this->actingAs($owner)
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => 4750.50,
                'weight_threshold_kg' => 12.25,
                'extra_weight_fee_per_kg' => 2250.75,
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHas('success', 'Pengaturan tarif pengiriman berhasil disimpan.');

        $this->assertDatabaseCount('vendor_shipping_settings', 1);

        $existing->refresh();
        $this->assertSame('4750.50', $existing->rate_per_km);
        $this->assertSame('12.25', $existing->weight_threshold_kg);
        $this->assertSame('2250.75', $existing->extra_weight_fee_per_kg);
    }

    public function test_rejects_empty_and_non_numeric_inputs_on_http_endpoint(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->from(route('vendor.shipping.index'))
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => '',
                'weight_threshold_kg' => '',
                'extra_weight_fee_per_kg' => '',
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHasErrors([
                'rate_per_km',
                'weight_threshold_kg',
                'extra_weight_fee_per_kg',
            ]);

        $this->actingAs($owner)
            ->from(route('vendor.shipping.index'))
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => 'bukan-angka',
                'weight_threshold_kg' => 'abc',
                'extra_weight_fee_per_kg' => 'xyz',
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHasErrors([
                'rate_per_km',
                'weight_threshold_kg',
                'extra_weight_fee_per_kg',
            ]);

        $this->assertDatabaseCount('vendor_shipping_settings', 0);
    }

    public function test_rejects_negative_and_exceeding_database_capacity_values_on_http_endpoint(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        // Nilai negatif ditolak
        $this->actingAs($owner)
            ->from(route('vendor.shipping.index'))
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => -100,
                'weight_threshold_kg' => -1,
                'extra_weight_fee_per_kg' => -50,
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHasErrors([
                'rate_per_km',
                'weight_threshold_kg',
                'extra_weight_fee_per_kg',
            ]);

        // Nilai melebihi batas kolom decimal(12,2) dan decimal(8,2) ditolak
        $this->actingAs($owner)
            ->from(route('vendor.shipping.index'))
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => 10000000000.00,
                'weight_threshold_kg' => 1000000.00,
                'extra_weight_fee_per_kg' => 10000000000.00,
            ])
            ->assertRedirect(route('vendor.shipping.index'))
            ->assertSessionHasErrors([
                'rate_per_km',
                'weight_threshold_kg',
                'extra_weight_fee_per_kg',
            ]);

        $this->assertDatabaseCount('vendor_shipping_settings', 0);
    }

    public function test_owner_cannot_overwrite_another_vendors_shipping_setting_via_request_payload(): void
    {
        $vendorA = Vendor::factory()->create(['name' => 'Vendor Alpha', 'status' => 'active']);
        $vendorB = Vendor::factory()->create(['name' => 'Vendor Beta', 'status' => 'active']);

        $ownerA = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendorA->id,
            'email_verified_at' => now(),
        ]);

        $settingB = VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorB->id,
            'rate_per_km' => 8000.00,
            'weight_threshold_kg' => 20.00,
            'extra_weight_fee_per_kg' => 3500.00,
        ]);

        // Owner A mencoba menyisipkan vendor_id milik Vendor B pada request
        $this->actingAs($ownerA)
            ->put(route('vendor.shipping.update'), [
                'vendor_id' => $vendorB->id,
                'rate_per_km' => 2000.00,
                'weight_threshold_kg' => 5.00,
                'extra_weight_fee_per_kg' => 1000.00,
            ])
            ->assertRedirect(route('vendor.shipping.index'));

        // Konfigurasi Vendor B tetap utuh dan tidak berubah
        $settingB->refresh();
        $this->assertSame('8000.00', $settingB->rate_per_km);
        $this->assertSame('20.00', $settingB->weight_threshold_kg);
        $this->assertSame('3500.00', $settingB->extra_weight_fee_per_kg);

        // Konfigurasi justru tersimpan untuk Vendor A milik Owner A
        $this->assertDatabaseHas('vendor_shipping_settings', [
            'vendor_id' => $vendorA->id,
            'rate_per_km' => 2000.00,
            'weight_threshold_kg' => 5.00,
            'extra_weight_fee_per_kg' => 1000.00,
        ]);
    }

    public function test_failed_validation_during_edit_keeps_modal_open_and_preserves_user_input(): void
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
            'email_verified_at' => now(),
        ]);

        VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'rate_per_km' => 5000.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 2000.00,
        ]);

        $response = $this->actingAs($owner)
            ->from(route('vendor.shipping.index'))
            ->followingRedirects()
            ->put(route('vendor.shipping.update'), [
                'rate_per_km' => -250,
                'weight_threshold_kg' => 14.5,
                'extra_weight_fee_per_kg' => 3200,
            ]);

        $response->assertOk()
            ->assertSee('x-data="{ showEditModal: true }"', false)
            ->assertSee('Tarif per kilometer tidak boleh bernilai negatif.')
            ->assertSee('value="-250"', false)
            ->assertSee('value="14.5"', false)
            ->assertSee('value="3200"', false)
            ->assertSee('Rp 5.000');

        $this->assertDatabaseHas('vendor_shipping_settings', [
            'vendor_id' => $vendor->id,
            'rate_per_km' => 5000.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 2000.00,
        ]);
    }
}
