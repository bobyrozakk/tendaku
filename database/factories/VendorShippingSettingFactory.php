<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorShippingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorShippingSetting>
 */
class VendorShippingSettingFactory extends Factory
{
    protected $model = VendorShippingSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'rate_per_km' => 5000.00,
            'weight_threshold_kg' => 10.00,
            'extra_weight_fee_per_kg' => 2000.00,
        ];
    }
}
