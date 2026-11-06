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
}
