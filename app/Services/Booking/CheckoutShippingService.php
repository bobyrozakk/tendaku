<?php

namespace App\Services\Booking;

use App\Exceptions\ShippingPreviewException;
use App\Models\CampingPackage;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OpenRouteService;
use App\Services\ShippingCalculator;

class CheckoutShippingService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly AvailabilityService $availability,
        private readonly OpenRouteService $routes,
        private readonly ShippingCalculator $calculator,
    ) {}

    /**
     * @param  array{checkout_type: string, pickup_method: string, customer_address_id?: int, package_id?: int, items?: array<int, array{id: int, qty: int}>, pickup_date?: string, return_date?: string}  $input
     * @return array{success: bool, status: string, pickup_method: string, distance_km: float|null, total_weight_kg: float, distance_fee: int, weight_fee: int, delivery_fee: int}
     */
    public function preview(array $input, ?User $customer): array
    {
        $package = $input['checkout_type'] === 'package';
        $delivery = $input['pickup_method'] === 'delivery';
        if (($package || $delivery) && $customer === null) {
            throw new ShippingPreviewException('authentication_required', 'Silakan masuk untuk menggunakan layanan ini.', 401);
        }
        if (($package || $delivery) && ! $customer->isCustomer()) {
            throw new ShippingPreviewException('access_denied', 'Layanan ini hanya tersedia untuk Customer.', 403);
        }
        if ($package && ! $customer->hasVerifiedEmail()) {
            throw new ShippingPreviewException('email_unverified', 'Silakan verifikasi email terlebih dahulu.', 403);
        }
        [$vendor, $weight] = $package
            ? $this->packageContext((int) $input['package_id'])
            : $this->regularContext($input, $customer);

        $result = ['success' => true, 'status' => 'ready', 'pickup_method' => $input['pickup_method'], 'distance_km' => null, 'total_weight_kg' => $weight, 'distance_fee' => 0, 'weight_fee' => 0, 'delivery_fee' => 0];
        if (! $delivery) {
            return $result;
        }
        $address = $customer->addresses()->whereKey($input['customer_address_id'] ?? 0)->first();
        if ($address === null) {
            throw new ShippingPreviewException('address_unavailable', 'Alamat pengantaran tidak tersedia untuk akun Anda.');
        }
        if (! $address->hasCoordinates()) {
            throw new ShippingPreviewException('customer_coordinates_missing', 'Lengkapi titik lokasi alamat pengantaran terlebih dahulu.');
        }
        if (! $vendor->hasCoordinates()) {
            throw new ShippingPreviewException('vendor_coordinates_missing', 'Lokasi pengiriman toko belum tersedia. Silakan pilih ambil sendiri.');
        }
        $setting = $this->calculator->resolveVendorSetting($vendor);
        if ($setting === null) {
            throw new ShippingPreviewException('shipping_unconfigured', 'Tarif pengiriman toko belum tersedia.');
        }
        foreach (['rate_per_km', 'weight_threshold_kg', 'extra_weight_fee_per_kg'] as $field) {
            if (! is_numeric($setting->$field) || ! is_finite((float) $setting->$field) || (float) $setting->$field < 0) {
                throw new ShippingPreviewException('shipping_unconfigured', 'Tarif pengiriman toko belum valid.');
            }
        }
        $route = $this->routes->getDrivingDistance($vendor->latitude, $vendor->longitude, $address->latitude, $address->longitude);
        if (! $route['success']) {
            throw new ShippingPreviewException($route['status'],
                $route['status'] === OpenRouteService::STATUS_ROUTE_NOT_FOUND
                    ? 'Rute berkendara menuju alamat ini tidak ditemukan.'
                    : 'Jarak pengiriman belum dapat dihitung. Silakan coba lagi.',
                $route['status'] === OpenRouteService::STATUS_ROUTE_NOT_FOUND ? 422 : 503);
        }
        $shipping = $this->calculator->calculate($setting, $route['distance_km'], $weight, $input['pickup_method']);
        $result['distance_km'] = $route['distance_km'];
        $result['distance_fee'] = $shipping['distance_fee'];
        $result['weight_fee'] = $shipping['weight_fee'];
        $result['delivery_fee'] = $shipping['total_fee'];

        return $result;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{Vendor, float}
     */
    private function regularContext(array $input, ?User $customer): array
    {
        $cart = $this->cart->get($customer);
        if ($cart === null || empty($cart['items'])) {
            throw new ShippingPreviewException('checkout_context_missing', 'Pilih produk melalui keranjang atau checkout produk terlebih dahulu.');
        }
        $quantities = [];
        foreach ($cart['items'] as $entry) {
            $id = (int) $entry['master_item_id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) ($entry['qty'] ?? $entry['quantity'] ?? 1);
        }
        foreach ($input['items'] ?? [] as $entry) {
            if (! array_key_exists((int) $entry['id'], $quantities)) {
                throw new ShippingPreviewException('invalid_cart_item', 'Produk tidak termasuk dalam checkout ini.');
            }
            $quantities[(int) $entry['id']] = (int) $entry['qty'];
        }
        $products = MasterItem::publicCatalog()->with('vendor')->whereIn('id', array_keys($quantities))->get()->keyBy('id');
        if ($products->count() !== count($quantities)) {
            throw new ShippingPreviewException('product_unavailable', 'Produk checkout tidak tersedia.');
        }
        $vendor = $products->first()->vendor;
        $items = [];
        foreach ($quantities as $id => $quantity) {
            $product = $products->get($id);
            if ((int) $product->vendor_id !== (int) $vendor->id) {
                throw new ShippingPreviewException('mixed_vendors', 'Satu checkout hanya boleh berisi produk dari satu toko.');
            }
            $this->validateWeight($product, $quantity);
            if (! $this->availability->isAvailable($id, $quantity, $input['pickup_date'] ?? $cart['pickup_date'], $input['return_date'] ?? $cart['return_date'])) {
                throw new ShippingPreviewException('insufficient_stock', 'Jumlah produk melebihi stok yang tersedia untuk jadwal ini.');
            }
            $items[] = ['item' => $product, 'quantity' => $quantity];
        }

        return [$vendor, $this->calculator->calculateTotalWeight($items)];
    }

    /** @return array{Vendor, float} */
    private function packageContext(int $packageId): array
    {
        $package = CampingPackage::publicCatalog()->with(['vendor', 'packageItems.masterItem'])->find($packageId);
        if ($package === null || $package->packageItems->isEmpty()) {
            throw new ShippingPreviewException('package_unavailable', 'Paket camping tidak tersedia.', 404);
        }
        foreach ($package->packageItems as $component) {
            $product = $component->masterItem;
            if ($product === null || ! $product->is_active || (int) $product->vendor_id !== (int) $package->vendor_id) {
                throw new ShippingPreviewException('invalid_package_component', 'Produk penyusun paket tidak tersedia atau tidak sesuai toko.');
            }
            $this->validateWeight($product, $component->quantity);
        }

        return [$package->vendor, $this->calculator->calculatePackageWeight($package)];
    }

    private function validateWeight(MasterItem $product, int $quantity): void
    {
        $weight = $product->getRawOriginal('weight_kg');
        if ($quantity < 1 || ! is_numeric($weight) || ! is_finite((float) $weight) || (float) $weight < 0) {
            throw new ShippingPreviewException('invalid_weight', 'Berat atau jumlah produk belum valid.');
        }
    }
}
