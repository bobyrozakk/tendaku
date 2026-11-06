<?php

namespace App\Services;

use App\Models\CampingPackage;
use App\Models\CampingPackageItem;
use App\Models\MasterItem;
use App\Models\Vendor;
use App\Models\VendorShippingSetting;
use InvalidArgumentException;

class ShippingCalculator
{
    public const PICKUP_SELF = 'self_pickup';

    public const PICKUP_DELIVERY = 'delivery';

    public const STATUS_CONFIGURED = 'configured';

    public const STATUS_UNCONFIGURED = 'unconfigured';

    /**
     * Hitung total berat (kg) dari kumpulan item beserta kuantitas masing-masing.
     *
     * Mendukung elemen berupa:
     * - MasterItem (menggunakan pivot->quantity jika ada, atau default 1)
     * - CampingPackageItem (menggunakan masterItem->weight_kg * quantity)
     * - Array/ objek dengan struktur ['item' => MasterItem, 'qty'|'quantity' => int]
     * - Array dengan struktur ['weight_kg' => float, 'qty'|'quantity' => int]
     * - Array keranjang dengan ['master_item_id' => int, 'qty'|'quantity' => int]
     *
     * @param  iterable<int|string, mixed>  $items
     */
    public function calculateTotalWeight(iterable $items): float
    {
        $totalWeightKg = 0.0;

        foreach ($items as $entry) {
            [$unitWeightKg, $quantity] = $this->extractWeightAndQuantity($entry);

            if ($unitWeightKg < 0) {
                throw new InvalidArgumentException('Berat produk (weight_kg) tidak boleh bernilai negatif.');
            }

            if ($quantity < 0) {
                throw new InvalidArgumentException('Kuantitas produk tidak boleh bernilai negatif.');
            }

            if ($quantity === 0) {
                continue;
            }

            $totalWeightKg += $unitWeightKg * $quantity;
        }

        return round($totalWeightKg, 2);
    }

    /**
     * Hitung total berat (kg) untuk satu paket camping dikalikan kuantitas paket.
     */
    public function calculatePackageWeight(CampingPackage $package, int $packageQuantity = 1): float
    {
        if ($packageQuantity < 0) {
            throw new InvalidArgumentException('Kuantitas paket tidak boleh bernilai negatif.');
        }

        if ($packageQuantity === 0) {
            return 0.0;
        }

        if ($package->relationLoaded('masterItems')) {
            $singlePackageWeight = $this->calculateTotalWeight($package->masterItems);
        } elseif ($package->relationLoaded('packageItems')) {
            $singlePackageWeight = $this->calculateTotalWeight($package->packageItems);
        } else {
            $packageItems = $package->packageItems()
                ->with(['masterItem' => fn ($query) => $query->withoutGlobalScope('vendor')])
                ->get();
            $singlePackageWeight = $this->calculateTotalWeight($packageItems);
        }

        return round($singlePackageWeight * $packageQuantity, 2);
    }

    /**
     * Hitung selisih kelebihan berat aktual (kg) di atas batas berat normal.
     * Rumus: max(0, Total Berat - weight_threshold_kg)
     */
    public function calculateExcessWeightKg(float $totalWeightKg, float $weightThresholdKg): float
    {
        $this->assertNonNegative($totalWeightKg, 'Total berat');
        $this->assertNonNegative($weightThresholdKg, 'Batas berat normal');

        return max(0.0, round($totalWeightKg - $weightThresholdKg, 4));
    }

    /**
     * Hitung kelebihan berat yang dikenakan biaya (dibulatkan ke atas per kg).
     * Rumus: ceil(max(0, Total Berat - weight_threshold_kg))
     */
    public function calculateBillableExtraWeightKg(float $totalWeightKg, float $weightThresholdKg): int
    {
        $excessWeightKg = $this->calculateExcessWeightKg($totalWeightKg, $weightThresholdKg);

        if ($excessWeightKg <= 0.0) {
            return 0;
        }

        return (int) ceil($excessWeightKg);
    }

    /**
     * Hitung biaya jarak pengiriman dalam Rupiah.
     * Rumus: round(Jarak KM * rate_per_km)
     */
    public function calculateDistanceFee(float $distanceKm, float $ratePerKm): int
    {
        $this->assertNonNegative($distanceKm, 'Jarak pengiriman');
        $this->assertNonNegative($ratePerKm, 'Tarif per kilometer');

        return (int) round($distanceKm * $ratePerKm);
    }

    /**
     * Hitung biaya tambahan kelebihan berat dalam Rupiah.
     * Rumus: round(ceil(max(0, Total Berat - weight_threshold_kg)) * extra_weight_fee_per_kg)
     */
    public function calculateWeightFee(float $totalWeightKg, float $weightThresholdKg, float $extraWeightFeePerKg): int
    {
        $this->assertNonNegative($totalWeightKg, 'Total berat');
        $this->assertNonNegative($weightThresholdKg, 'Batas berat normal');
        $this->assertNonNegative($extraWeightFeePerKg, 'Biaya tambahan per kg');

        $billableExtraWeightKg = $this->calculateBillableExtraWeightKg($totalWeightKg, $weightThresholdKg);

        return (int) round($billableExtraWeightKg * $extraWeightFeePerKg);
    }

    /**
     * Hitung rincian ongkir lengkap berdasarkan pengaturan tarif vendor, jarak (km), dan total berat (kg).
     *
     * @param  VendorShippingSetting|array{rate_per_km: numeric, weight_threshold_kg: numeric, extra_weight_fee_per_kg: numeric}|null  $setting
     * @return array{
     *     is_configured: bool,
     *     status: 'configured'|'unconfigured',
     *     pickup_method: string,
     *     distance_km: float,
     *     total_weight_kg: float,
     *     rate_per_km: float|null,
     *     weight_threshold_kg: float|null,
     *     extra_weight_fee_per_kg: float|null,
     *     excess_weight_kg: float,
     *     billable_extra_weight_kg: int,
     *     distance_fee: int|null,
     *     weight_fee: int|null,
     *     total_fee: int|null,
     *     message: string|null
     * }
     */
    public function calculate(
        VendorShippingSetting|array|null $setting,
        float $distanceKm,
        float $totalWeightKg,
        string $pickupMethod = self::PICKUP_DELIVERY
    ): array {
        $this->assertNonNegative($distanceKm, 'Jarak pengiriman');
        $this->assertNonNegative($totalWeightKg, 'Total berat');

        $normalizedDistanceKm = round($distanceKm, 2);
        $normalizedTotalWeightKg = round($totalWeightKg, 2);
        $normalizedSetting = $this->normalizeSetting($setting);

        if ($pickupMethod === self::PICKUP_SELF) {
            return [
                'is_configured' => $normalizedSetting !== null,
                'status' => $normalizedSetting !== null ? self::STATUS_CONFIGURED : self::STATUS_UNCONFIGURED,
                'pickup_method' => self::PICKUP_SELF,
                'distance_km' => 0.0,
                'total_weight_kg' => $normalizedTotalWeightKg,
                'rate_per_km' => $normalizedSetting['rate_per_km'] ?? null,
                'weight_threshold_kg' => $normalizedSetting['weight_threshold_kg'] ?? null,
                'extra_weight_fee_per_kg' => $normalizedSetting['extra_weight_fee_per_kg'] ?? null,
                'excess_weight_kg' => 0.0,
                'billable_extra_weight_kg' => 0,
                'distance_fee' => 0,
                'weight_fee' => 0,
                'total_fee' => 0,
                'message' => null,
            ];
        }

        if ($normalizedSetting === null) {
            return [
                'is_configured' => false,
                'status' => self::STATUS_UNCONFIGURED,
                'pickup_method' => self::PICKUP_DELIVERY,
                'distance_km' => $normalizedDistanceKm,
                'total_weight_kg' => $normalizedTotalWeightKg,
                'rate_per_km' => null,
                'weight_threshold_kg' => null,
                'extra_weight_fee_per_kg' => null,
                'excess_weight_kg' => 0.0,
                'billable_extra_weight_kg' => 0,
                'distance_fee' => null,
                'weight_fee' => null,
                'total_fee' => null,
                'message' => 'Tarif pengiriman belum dikonfigurasi oleh toko ini.',
            ];
        }

        $ratePerKm = $normalizedSetting['rate_per_km'];
        $weightThresholdKg = $normalizedSetting['weight_threshold_kg'];
        $extraWeightFeePerKg = $normalizedSetting['extra_weight_fee_per_kg'];

        $excessWeightKg = round($this->calculateExcessWeightKg($normalizedTotalWeightKg, $weightThresholdKg), 2);
        $billableExtraWeightKg = $this->calculateBillableExtraWeightKg($normalizedTotalWeightKg, $weightThresholdKg);
        $distanceFee = $this->calculateDistanceFee($distanceKm, $ratePerKm);
        $weightFee = $this->calculateWeightFee($normalizedTotalWeightKg, $weightThresholdKg, $extraWeightFeePerKg);
        $totalFee = $distanceFee + $weightFee;

        return [
            'is_configured' => true,
            'status' => self::STATUS_CONFIGURED,
            'pickup_method' => self::PICKUP_DELIVERY,
            'distance_km' => $normalizedDistanceKm,
            'total_weight_kg' => $normalizedTotalWeightKg,
            'rate_per_km' => $ratePerKm,
            'weight_threshold_kg' => $weightThresholdKg,
            'extra_weight_fee_per_kg' => $extraWeightFeePerKg,
            'excess_weight_kg' => $excessWeightKg,
            'billable_extra_weight_kg' => $billableExtraWeightKg,
            'distance_fee' => $distanceFee,
            'weight_fee' => $weightFee,
            'total_fee' => $totalFee,
            'message' => null,
        ];
    }

    /**
     * Hitung ongkir untuk Vendor tertentu berdasarkan jarak (km) dan daftar item atau total berat (kg).
     *
     * @param  iterable<int|string, mixed>|float|int  $itemsOrTotalWeightKg
     * @return array{
     *     is_configured: bool,
     *     status: 'configured'|'unconfigured',
     *     pickup_method: string,
     *     distance_km: float,
     *     total_weight_kg: float,
     *     rate_per_km: float|null,
     *     weight_threshold_kg: float|null,
     *     extra_weight_fee_per_kg: float|null,
     *     excess_weight_kg: float,
     *     billable_extra_weight_kg: int,
     *     distance_fee: int|null,
     *     weight_fee: int|null,
     *     total_fee: int|null,
     *     message: string|null
     * }
     */
    public function calculateForVendor(
        Vendor $vendor,
        float $distanceKm,
        iterable|float|int $itemsOrTotalWeightKg,
        string $pickupMethod = self::PICKUP_DELIVERY
    ): array {
        $totalWeightKg = is_iterable($itemsOrTotalWeightKg)
            ? $this->calculateTotalWeight($itemsOrTotalWeightKg)
            : (float) $itemsOrTotalWeightKg;

        $setting = $this->resolveVendorSetting($vendor);

        return $this->calculate($setting, $distanceKm, $totalWeightKg, $pickupMethod);
    }

    /**
     * Ambil pengaturan tarif pengiriman milik vendor secara terisolasi.
     */
    public function resolveVendorSetting(Vendor $vendor): ?VendorShippingSetting
    {
        if (! $vendor->exists) {
            return null;
        }

        if ($vendor->relationLoaded('shippingSetting')) {
            /** @var VendorShippingSetting|null $loaded */
            $loaded = $vendor->getRelation('shippingSetting');

            if ($loaded !== null && (int) $loaded->vendor_id === (int) $vendor->id) {
                return $loaded;
            }
        }

        return VendorShippingSetting::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendor->id)
            ->first();
    }

    /**
     * Ekstrak pasangan [weight_kg, quantity] dari berbagai format item.
     *
     * @return array{0: float, 1: int}
     */
    protected function extractWeightAndQuantity(mixed $entry): array
    {
        if ($entry instanceof MasterItem) {
            $quantity = isset($entry->pivot) && isset($entry->pivot->quantity)
                ? (int) $entry->pivot->quantity
                : 1;

            return [(float) ($entry->weight_kg ?? 0.0), $quantity];
        }

        if ($entry instanceof CampingPackageItem) {
            $masterItem = $entry->relationLoaded('masterItem')
                ? $entry->masterItem
                : $entry->masterItem()->withoutGlobalScope('vendor')->first();

            $weightKg = $masterItem instanceof MasterItem ? (float) ($masterItem->weight_kg ?? 0.0) : 0.0;

            return [$weightKg, (int) ($entry->quantity ?? 1)];
        }

        if (is_array($entry)) {
            $quantity = $this->extractQuantityFromArray($entry);

            if (isset($entry['item']) && $entry['item'] instanceof MasterItem) {
                return [(float) ($entry['item']->weight_kg ?? 0.0), $quantity];
            }

            if (isset($entry['master_item']) && $entry['master_item'] instanceof MasterItem) {
                return [(float) ($entry['master_item']->weight_kg ?? 0.0), $quantity];
            }

            if (array_key_exists('weight_kg', $entry) && $entry['weight_kg'] !== null) {
                if (! is_numeric($entry['weight_kg'])) {
                    throw new InvalidArgumentException('Berat produk (weight_kg) harus berupa angka.');
                }

                return [(float) $entry['weight_kg'], $quantity];
            }

            $masterItemId = (int) ($entry['master_item_id'] ?? $entry['product_id'] ?? 0);
            if ($masterItemId > 0) {
                $masterItem = MasterItem::withoutGlobalScope('vendor')->find($masterItemId);

                return [$masterItem ? (float) ($masterItem->weight_kg ?? 0.0) : 0.0, $quantity];
            }

            return [0.0, $quantity];
        }

        throw new InvalidArgumentException('Format item untuk perhitungan berat tidak didukung.');
    }

    /**
     * Ambil nilai kuantitas dari struktur array item.
     *
     * @param  array<string, mixed>  $entry
     */
    protected function extractQuantityFromArray(array $entry): int
    {
        $rawQuantity = $entry['quantity'] ?? $entry['qty'] ?? 1;

        if (! is_numeric($rawQuantity)) {
            throw new InvalidArgumentException('Kuantitas produk harus berupa angka.');
        }

        return (int) $rawQuantity;
    }

    /**
     * Normalisasi model atau array pengaturan tarif menjadi struktur float yang tervalidasi.
     *
     * @param  VendorShippingSetting|array<string, mixed>|null  $setting
     * @return array{rate_per_km: float, weight_threshold_kg: float, extra_weight_fee_per_kg: float}|null
     */
    protected function normalizeSetting(VendorShippingSetting|array|null $setting): ?array
    {
        if ($setting === null) {
            return null;
        }

        if ($setting instanceof VendorShippingSetting) {
            $ratePerKm = (float) $setting->rate_per_km;
            $weightThresholdKg = (float) $setting->weight_threshold_kg;
            $extraWeightFeePerKg = (float) $setting->extra_weight_fee_per_kg;
        } else {
            if (
                ! isset($setting['rate_per_km'], $setting['weight_threshold_kg'], $setting['extra_weight_fee_per_kg'])
                || ! is_numeric($setting['rate_per_km'])
                || ! is_numeric($setting['weight_threshold_kg'])
                || ! is_numeric($setting['extra_weight_fee_per_kg'])
            ) {
                return null;
            }

            $ratePerKm = (float) $setting['rate_per_km'];
            $weightThresholdKg = (float) $setting['weight_threshold_kg'];
            $extraWeightFeePerKg = (float) $setting['extra_weight_fee_per_kg'];
        }

        $this->assertNonNegative($ratePerKm, 'Tarif per kilometer');
        $this->assertNonNegative($weightThresholdKg, 'Batas berat normal');
        $this->assertNonNegative($extraWeightFeePerKg, 'Biaya tambahan per kg');

        return [
            'rate_per_km' => $ratePerKm,
            'weight_threshold_kg' => $weightThresholdKg,
            'extra_weight_fee_per_kg' => $extraWeightFeePerKg,
        ];
    }

    /**
     * Pastikan parameter numerik tidak bernilai negatif.
     */
    protected function assertNonNegative(float $value, string $label): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException("{$label} tidak boleh bernilai negatif.");
        }
    }
}
