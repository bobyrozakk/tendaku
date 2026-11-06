<?php

namespace Tests\Unit;

use App\Models\CampingPackage;
use App\Models\CampingPackageItem;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use App\Models\VendorShippingSetting;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShippingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ShippingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ShippingCalculator;
    }

    public function test_calculates_total_weight_for_single_item_with_quantity(): void
    {
        $items = [
            ['weight_kg' => 3.5, 'quantity' => 2],
        ];

        $this->assertSame(7.0, $this->calculator->calculateTotalWeight($items));
    }

    public function test_calculates_total_weight_for_multiple_items_and_quantities(): void
    {
        $tenda = new MasterItem(['weight_kg' => 4.25]);
        $kompor = new MasterItem(['weight_kg' => 0.85]);

        $items = [
            ['item' => $tenda, 'qty' => 2],
            ['master_item' => $kompor, 'quantity' => 3],
            ['weight_kg' => 1.2, 'qty' => 1],
        ];

        // (4.25 * 2) + (0.85 * 3) + (1.2 * 1) = 8.50 + 2.55 + 1.20 = 12.25 kg
        $this->assertSame(12.25, $this->calculator->calculateTotalWeight($items));
    }

    public function test_calculates_total_weight_from_master_item_models_and_pivot_quantities(): void
    {
        $itemSingle = new MasterItem(['weight_kg' => 2.5]);

        $itemWithPivot = new MasterItem(['weight_kg' => 1.75]);
        $itemWithPivot->setRelation('pivot', (object) ['quantity' => 4]);

        // 2.5 * 1 + 1.75 * 4 = 2.5 + 7.0 = 9.5 kg
        $this->assertSame(9.5, $this->calculator->calculateTotalWeight([$itemSingle, $itemWithPivot]));
    }

    public function test_calculates_total_weight_from_cart_items_referencing_master_item_id_in_database(): void
    {
        $vendor = $this->createVendor('Toko Alat Outdoor');
        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $itemOne = MasterItem::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
            'deposit_amount' => 0,
            'late_fee_per_day' => 10000,
            'weight_kg' => 4.4,
            'is_active' => true,
        ]);

        $itemTwo = MasterItem::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Matras Gulung',
            'slug' => 'matras-gulung',
            'daily_rate' => 10000,
            'deposit_amount' => 0,
            'late_fee_per_day' => 5000,
            'weight_kg' => 0.6,
            'is_active' => true,
        ]);

        $cartItems = [
            ['master_item_id' => $itemOne->id, 'qty' => 2],
            ['master_item_id' => $itemTwo->id, 'qty' => 3],
        ];

        // (4.4 * 2) + (0.6 * 3) = 8.8 + 1.8 = 10.6 kg
        $this->assertSame(10.6, $this->calculator->calculateTotalWeight($cartItems));
    }

    public function test_calculates_camping_package_weight_with_package_quantity(): void
    {
        $vendor = $this->createVendor('Toko Paket Kemah');
        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Paket',
            'slug' => 'paket',
        ]);

        $tenda = MasterItem::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Kapasitas 4',
            'slug' => 'tenda-kapasitas-4',
            'daily_rate' => 60000,
            'deposit_amount' => 0,
            'late_fee_per_day' => 10000,
            'weight_kg' => 3.5,
            'is_active' => true,
        ]);

        $sleepingBag = MasterItem::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Sleeping Bag Polar',
            'slug' => 'sleeping-bag-polar',
            'daily_rate' => 15000,
            'deposit_amount' => 0,
            'late_fee_per_day' => 5000,
            'weight_kg' => 0.9,
            'is_active' => true,
        ]);

        $package = CampingPackage::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'name' => 'Paket Keluarga',
            'slug' => 'paket-keluarga',
            'price' => 100000,
            'duration_days' => 2,
            'max_people' => 4,
            'is_active' => true,
        ]);

        CampingPackageItem::create([
            'camping_package_id' => $package->id,
            'master_item_id' => $tenda->id,
            'quantity' => 1,
        ]);

        CampingPackageItem::create([
            'camping_package_id' => $package->id,
            'master_item_id' => $sleepingBag->id,
            'quantity' => 4,
        ]);

        // 1 paket = 3.5 + (0.9 * 4) = 7.1 kg; 2 paket = 14.2 kg
        $this->assertSame(7.1, $this->calculator->calculatePackageWeight($package, 1));
        $this->assertSame(14.2, $this->calculator->calculatePackageWeight($package, 2));
    }

    /**
     * @return array<string, array{0: float, 1: float, 2: int}>
     */
    public static function distanceFeeCases(): array
    {
        return [
            'jarak bulat 5 km x Rp2.000' => [5.0, 2000.0, 10000],
            'jarak desimal 4.5 km x Rp2.500' => [4.5, 2500.0, 11250],
            'jarak desimal 7.35 km x Rp1.500 dibulatkan konsisten' => [7.35, 1500.0, 11025],
            'jarak desimal 3.33 km x Rp1.000 dibulatkan ke rupiah terdekat' => [3.333, 1000.0, 3333],
            'jarak desimal dengan pecahan setengah rupiah dibulatkan ke atas' => [1.25, 1505.0, 1881],
            'jarak 0 km menghasilkan Rp0' => [0.0, 3000.0, 0],
            'tarif Rp0 per km menghasilkan Rp0' => [12.4, 0.0, 0],
        ];
    }

    #[DataProvider('distanceFeeCases')]
    public function test_calculates_distance_fee_accurately_with_consistent_rounding(
        float $distanceKm,
        float $ratePerKm,
        int $expectedFee
    ): void {
        $this->assertSame($expectedFee, $this->calculator->calculateDistanceFee($distanceKm, $ratePerKm));
    }

    /**
     * @return array<string, array{0: float, 1: float, 2: float, 3: int, 4: int}>
     */
    public static function weightThresholdBoundaryCases(): array
    {
        return [
            'di bawah batas berat normal (6.5 kg dari 10 kg)' => [6.5, 10.0, 1000.0, 0, 0],
            'tepat pada batas berat normal (10.0 kg dari 10 kg)' => [10.0, 10.0, 1000.0, 0, 0],
            'kelebihan tipis 0.01 kg (10.01 kg dari 10 kg -> ceil 1 kg)' => [10.01, 10.0, 1000.0, 1, 1000],
            'kelebihan desimal 0.2 kg (10.2 kg dari 10 kg -> ceil 1 kg)' => [10.2, 10.0, 1500.0, 1, 1500],
            'kelebihan bulat 1.0 kg (11.0 kg dari 10 kg -> ceil 1 kg)' => [11.0, 10.0, 1500.0, 1, 1500],
            'kelebihan 1.01 kg (11.01 kg dari 10 kg -> ceil 2 kg)' => [11.01, 10.0, 1500.0, 2, 3000],
            'kelebihan 2.4 kg (12.4 kg dari 10 kg -> ceil 3 kg)' => [12.4, 10.0, 2000.0, 3, 6000],
            'batas berat 0 kg menyebabkan seluruh berat dihitung ekstra' => [2.3, 0.0, 1000.0, 3, 3000],
        ];
    }

    #[DataProvider('weightThresholdBoundaryCases')]
    public function test_calculates_weight_fee_across_threshold_boundaries(
        float $totalWeightKg,
        float $weightThresholdKg,
        float $extraWeightFeePerKg,
        int $expectedBillableExtraKg,
        int $expectedWeightFee
    ): void {
        $this->assertSame(
            $expectedBillableExtraKg,
            $this->calculator->calculateBillableExtraWeightKg($totalWeightKg, $weightThresholdKg)
        );
        $this->assertSame(
            $expectedWeightFee,
            $this->calculator->calculateWeightFee($totalWeightKg, $weightThresholdKg, $extraWeightFeePerKg)
        );
    }

    public function test_floating_point_sum_equal_to_threshold_does_not_trigger_false_extra_weight(): void
    {
        // 1.2 * 3 dalam IEEE-754 adalah 3.5999999999999996, dan 0.1 + 0.2 = 0.30000000000000004
        $totalWeight = $this->calculator->calculateTotalWeight([
            ['weight_kg' => 0.1, 'qty' => 1],
            ['weight_kg' => 0.2, 'qty' => 1],
        ]);

        $this->assertSame(0, $this->calculator->calculateBillableExtraWeightKg($totalWeight, 0.3));
        $this->assertSame(0, $this->calculator->calculateWeightFee($totalWeight, 0.3, 2000));
    }

    public function test_calculates_full_shipping_breakdown_without_base_fee(): void
    {
        $setting = new VendorShippingSetting([
            'rate_per_km' => 2000,
            'weight_threshold_kg' => 10,
            'extra_weight_fee_per_kg' => 1000,
        ]);

        // Jarak 6.5 km * Rp2.000 = Rp13.000
        // Berat 12.3 kg - 10 kg = 2.3 kg -> ceil(2.3) = 3 kg * Rp1.000 = Rp3.000
        // Total = Rp16.000 (tanpa base_fee)
        $result = $this->calculator->calculate(
            setting: $setting,
            distanceKm: 6.5,
            totalWeightKg: 12.3,
            pickupMethod: ShippingCalculator::PICKUP_DELIVERY
        );

        $this->assertTrue($result['is_configured']);
        $this->assertSame(ShippingCalculator::STATUS_CONFIGURED, $result['status']);
        $this->assertSame(ShippingCalculator::PICKUP_DELIVERY, $result['pickup_method']);
        $this->assertSame(6.5, $result['distance_km']);
        $this->assertSame(12.3, $result['total_weight_kg']);
        $this->assertSame(2000.0, $result['rate_per_km']);
        $this->assertSame(10.0, $result['weight_threshold_kg']);
        $this->assertSame(1000.0, $result['extra_weight_fee_per_kg']);
        $this->assertSame(2.3, $result['excess_weight_kg']);
        $this->assertSame(3, $result['billable_extra_weight_kg']);
        $this->assertSame(13000, $result['distance_fee']);
        $this->assertSame(3000, $result['weight_fee']);
        $this->assertSame(16000, $result['total_fee']);
        $this->assertArrayNotHasKey('base_fee', $result);
    }

    public function test_self_pickup_always_returns_zero_rupiah_even_with_distance_and_heavy_weight(): void
    {
        $setting = new VendorShippingSetting([
            'rate_per_km' => 3500,
            'weight_threshold_kg' => 5,
            'extra_weight_fee_per_kg' => 2000,
        ]);

        $result = $this->calculator->calculate(
            setting: $setting,
            distanceKm: 15.8,
            totalWeightKg: 25.0,
            pickupMethod: ShippingCalculator::PICKUP_SELF
        );

        $this->assertTrue($result['is_configured']);
        $this->assertSame(ShippingCalculator::PICKUP_SELF, $result['pickup_method']);
        $this->assertSame(0.0, $result['distance_km']);
        $this->assertSame(25.0, $result['total_weight_kg']);
        $this->assertSame(0, $result['distance_fee']);
        $this->assertSame(0, $result['weight_fee']);
        $this->assertSame(0, $result['total_fee']);
    }

    public function test_self_pickup_returns_zero_rupiah_even_when_vendor_has_not_configured_shipping(): void
    {
        $result = $this->calculator->calculate(
            setting: null,
            distanceKm: 10.0,
            totalWeightKg: 8.0,
            pickupMethod: ShippingCalculator::PICKUP_SELF
        );

        $this->assertFalse($result['is_configured']);
        $this->assertSame(ShippingCalculator::STATUS_UNCONFIGURED, $result['status']);
        $this->assertSame(ShippingCalculator::PICKUP_SELF, $result['pickup_method']);
        $this->assertSame(0, $result['distance_fee']);
        $this->assertSame(0, $result['weight_fee']);
        $this->assertSame(0, $result['total_fee']);
    }

    public function test_unconfigured_vendor_returns_unconfigured_state_without_hardcoded_fallback_tariff(): void
    {
        $vendor = $this->createVendor('Vendor Belum Atur Ongkir');

        $result = $this->calculator->calculateForVendor(
            vendor: $vendor,
            distanceKm: 8.2,
            itemsOrTotalWeightKg: [
                ['weight_kg' => 4.0, 'qty' => 3],
            ],
            pickupMethod: ShippingCalculator::PICKUP_DELIVERY
        );

        $this->assertFalse($result['is_configured']);
        $this->assertSame(ShippingCalculator::STATUS_UNCONFIGURED, $result['status']);
        $this->assertSame(8.2, $result['distance_km']);
        $this->assertSame(12.0, $result['total_weight_kg']);
        $this->assertNull($result['rate_per_km']);
        $this->assertNull($result['weight_threshold_kg']);
        $this->assertNull($result['extra_weight_fee_per_kg']);
        $this->assertNull($result['distance_fee']);
        $this->assertNull($result['weight_fee']);
        $this->assertNull($result['total_fee']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_calculate_for_vendor_uses_isolated_vendor_shipping_setting_from_database(): void
    {
        $vendorA = $this->createVendor('Vendor A');
        $vendorB = $this->createVendor('Vendor B');

        VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorA->id,
            'rate_per_km' => 2000,
            'weight_threshold_kg' => 10,
            'extra_weight_fee_per_kg' => 1000,
        ]);

        VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendorB->id,
            'rate_per_km' => 4000,
            'weight_threshold_kg' => 5,
            'extra_weight_fee_per_kg' => 2500,
        ]);

        $items = [
            ['weight_kg' => 4.0, 'qty' => 2], // 8 kg
        ];

        // Vendor A: jarak 5 km * 2000 = 10.000; berat 8 kg <= 10 kg -> 0; total = 10.000
        $resultA = $this->calculator->calculateForVendor($vendorA, 5.0, $items);
        // Vendor B: jarak 5 km * 4000 = 20.000; berat 8 kg - 5 kg = 3 kg * 2500 = 7.500; total = 27.500
        $resultB = $this->calculator->calculateForVendor($vendorB, 5.0, $items);

        $this->assertSame(10000, $resultA['total_fee']);
        $this->assertSame(0, $resultA['weight_fee']);

        $this->assertSame(27500, $resultB['total_fee']);
        $this->assertSame(7500, $resultB['weight_fee']);
    }

    public function test_rejects_negative_distance(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculateDistanceFee(-1.5, 2000);
    }

    public function test_rejects_negative_item_weight(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculateTotalWeight([
            ['weight_kg' => -2.0, 'qty' => 1],
        ]);
    }

    public function test_rejects_negative_item_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculateTotalWeight([
            ['weight_kg' => 2.0, 'qty' => -1],
        ]);
    }

    public function test_rejects_negative_tariff_parameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate(
            setting: [
                'rate_per_km' => -1000,
                'weight_threshold_kg' => 10,
                'extra_weight_fee_per_kg' => 500,
            ],
            distanceKm: 5.0,
            totalWeightKg: 8.0
        );
    }

    private function createVendor(string $name): Vendor
    {
        return Vendor::create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.uniqid())->toString(),
            'phone' => '081234567890',
            'address' => 'Jl. Kemah Raya No. 10, Malang',
            'status' => 'active',
        ]);
    }
}
