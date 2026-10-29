<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    public const SESSION_KEY = 'booking_cart';

    /**
     * Ambil data keranjang saat ini.
     * Jika user terautentikasi dan session belum ada, pulihkan dari database user.
     *
     * @return array<string, mixed>|null
     */
    public function get(?User $user = null): ?array
    {
        $user ??= Auth::user();

        $cart = Session::get(self::SESSION_KEY);

        if (! is_array($cart) || empty($cart['master_item_id'])) {
            if ($user !== null && is_array($user->cart) && ! empty($user->cart['master_item_id'])) {
                $cart = $user->cart;
                Session::put(self::SESSION_KEY, $cart);
            }
        }

        if (! is_array($cart) || empty($cart['master_item_id'])) {
            return null;
        }

        return $this->normalizeCart($cart);
    }

    /**
     * Tambahkan item ke keranjang (mendukung multi-item dari toko yang sama).
     *
     * @param  array<string, mixed>  $itemData
     * @return array{status: 'success'|'diff_vendor', existing_vendor?: string, cart?: array<string, mixed>, total_count?: int}
     */
    public function addItem(array $itemData, ?User $user = null): array
    {
        $user ??= Auth::user();
        $cart = $this->get($user);

        $newItemVendorId = (int) ($itemData['vendor_id'] ?? 0);
        $masterItemId = (int) ($itemData['master_item_id'] ?? $itemData['product_id'] ?? 0);
        $qtyToAdd = max(1, (int) ($itemData['qty'] ?? $itemData['quantity'] ?? 1));

        // Aturan PM & Bisnis: 1 keranjang sewa hanya boleh dari 1 vendor toko yang sama
        if ($cart !== null && ! empty($cart['vendor_id']) && (int) $cart['vendor_id'] !== $newItemVendorId) {
            return [
                'status' => 'diff_vendor',
                'existing_vendor' => $cart['vendor_name'] ?? 'Toko Lain',
            ];
        }

        if ($cart === null) {
            $cart = [
                'vendor_id' => $newItemVendorId,
                'vendor_name' => $itemData['vendor_name'] ?? 'Vendor',
                'pickup_date' => $itemData['pickup_date'] ?? now()->addDay()->toDateString(),
                'return_date' => $itemData['return_date'] ?? now()->addDays(2)->toDateString(),
                'pickup_time' => $itemData['pickup_time'] ?? '10:00',
                'return_time' => $itemData['return_time'] ?? '16:00',
                'duration_days' => max(1, (int) ($itemData['duration_days'] ?? $itemData['total_days'] ?? 1)),
                'total_days' => max(1, (int) ($itemData['total_days'] ?? $itemData['duration_days'] ?? 1)),
                'items' => [],
            ];
        }

        $durationDays = max(1, (int) ($cart['total_days'] ?? 1));
        $dailyRate = (int) ($itemData['daily_rate'] ?? 0);

        if (isset($cart['items'][$masterItemId])) {
            $existingQty = (int) ($cart['items'][$masterItemId]['qty'] ?? 1);
            $newQty = $existingQty + $qtyToAdd;
            $cart['items'][$masterItemId]['qty'] = $newQty;
            $cart['items'][$masterItemId]['quantity'] = $newQty;
            $cart['items'][$masterItemId]['total_price'] = $dailyRate * $newQty * $durationDays;
        } else {
            $cart['items'][$masterItemId] = [
                'master_item_id' => $masterItemId,
                'product_name' => $itemData['product_name'] ?? '',
                'product_image' => $itemData['product_image'] ?? '',
                'daily_rate' => $dailyRate,
                'qty' => $qtyToAdd,
                'quantity' => $qtyToAdd,
                'category_name' => $itemData['category_name'] ?? '',
                'duration_days' => $durationDays,
                'total_price' => $dailyRate * $qtyToAdd * $durationDays,
            ];
        }

        $cart = $this->recalculateSummary($cart);
        $this->set($cart, $user);

        return [
            'status' => 'success',
            'cart' => $cart,
            'total_count' => (int) ($cart['qty'] ?? 1),
        ];
    }

    /**
     * Update jumlah unit satu item di keranjang.
     */
    public function updateItemQty(int $masterItemId, int $qty, ?User $user = null): void
    {
        $user ??= Auth::user();
        $cart = $this->get($user);

        if ($cart === null) {
            return;
        }

        if ($qty <= 0) {
            $this->removeItem($masterItemId, $user);

            return;
        }

        if (isset($cart['items'][$masterItemId])) {
            $durationDays = max(1, (int) ($cart['total_days'] ?? 1));
            $dailyRate = (int) ($cart['items'][$masterItemId]['daily_rate'] ?? 0);

            $cart['items'][$masterItemId]['qty'] = $qty;
            $cart['items'][$masterItemId]['quantity'] = $qty;
            $cart['items'][$masterItemId]['total_price'] = $dailyRate * $qty * $durationDays;

            $cart = $this->recalculateSummary($cart);
            $this->set($cart, $user);
        }
    }

    /**
     * Hapus satu item dari keranjang.
     */
    public function removeItem(int $masterItemId, ?User $user = null): void
    {
        $user ??= Auth::user();
        $cart = $this->get($user);

        if ($cart === null) {
            return;
        }

        unset($cart['items'][$masterItemId]);

        if (empty($cart['items'])) {
            $this->clear($user);

            return;
        }

        $cart = $this->recalculateSummary($cart);
        $this->set($cart, $user);
    }

    /**
     * Hitung total item/unit yang ada di keranjang.
     */
    public function totalItemsCount(?User $user = null): int
    {
        $cart = $this->get($user);

        if ($cart === null) {
            return 0;
        }

        return (int) ($cart['qty'] ?? 0);
    }

    /**
     * Simpan data keranjang.
     * Untuk guest: simpan di session saja (non-permanen).
     * Untuk customer login: simpan di session dan simpan permanen ke database user.
     *
     * @param  array<string, mixed>  $cartData
     */
    public function set(array $cartData, ?User $user = null): void
    {
        $normalized = $this->normalizeCart($cartData);
        Session::put(self::SESSION_KEY, $normalized);

        $user ??= Auth::user();

        if ($user !== null) {
            $user->update(['cart' => $normalized]);
        }
    }

    /**
     * Hapus keranjang (setelah checkout atau dibersihkan).
     */
    public function clear(?User $user = null): void
    {
        Session::forget(self::SESSION_KEY);

        $user ??= Auth::user();

        if ($user !== null) {
            $user->update(['cart' => null]);
        }
    }

    /**
     * Sinkronisasi saat customer login:
     * - Pulihkan cart milik user dari database ke session.
     * - Jika user tidak memiliki cart tersimpan di DB, bersihkan session agar cart guest / user sebelumnya tidak tercampur.
     */
    public function syncOnLogin(User $user): void
    {
        if (is_array($user->cart) && ! empty($user->cart['master_item_id'])) {
            Session::put(self::SESSION_KEY, $this->normalizeCart($user->cart));
        } else {
            Session::forget(self::SESSION_KEY);
        }
    }

    /**
     * Sinkronisasi saat customer logout:
     * - Pastikan cart di session tersimpan ke database user sebelum session di-invalidate.
     * - Bersihkan session agar guest berikutnya tidak mewarisi cart user.
     */
    public function syncOnLogout(?User $user = null): void
    {
        $user ??= Auth::user();

        if ($user !== null) {
            $cart = Session::get(self::SESSION_KEY);
            if (is_array($cart) && ! empty($cart['master_item_id'])) {
                $user->update(['cart' => $this->normalizeCart($cart)]);
            }
        }

        Session::forget(self::SESSION_KEY);
    }

    /**
     * Normalisasi data cart agar selalu memiliki struktur multi-item yang konsisten
     * sekaligus mempertahankan field backward-compatibility untuk single item.
     *
     * @param  array<string, mixed>  $cart
     * @return array<string, mixed>
     */
    protected function normalizeCart(array $cart): array
    {
        if (empty($cart['items']) || ! is_array($cart['items'])) {
            $masterItemId = (int) ($cart['master_item_id'] ?? 0);
            if ($masterItemId > 0) {
                $qty = max(1, (int) ($cart['quantity'] ?? $cart['qty'] ?? 1));
                $cart['items'] = [
                    $masterItemId => [
                        'master_item_id' => $masterItemId,
                        'product_name' => $cart['product_name'] ?? '',
                        'product_image' => $cart['product_image'] ?? '',
                        'daily_rate' => (int) ($cart['daily_rate'] ?? 0),
                        'qty' => $qty,
                        'quantity' => $qty,
                        'category_name' => $cart['category_name'] ?? '',
                        'duration_days' => max(1, (int) ($cart['total_days'] ?? $cart['duration_days'] ?? 1)),
                        'total_price' => (int) ($cart['total_price'] ?? 0),
                    ],
                ];
            }
        }

        return $this->recalculateSummary($cart);
    }

    /**
     * Hitung ulang summary root cart dari koleksi items.
     *
     * @param  array<string, mixed>  $cart
     * @return array<string, mixed>
     */
    protected function recalculateSummary(array $cart): array
    {
        if (empty($cart['items'])) {
            return $cart;
        }

        $totalQty = 0;
        $totalPrice = 0;
        $firstItem = null;

        foreach ($cart['items'] as $item) {
            $firstItem ??= $item;
            $qty = (int) ($item['qty'] ?? $item['quantity'] ?? 1);
            $totalQty += $qty;
            $totalPrice += (int) ($item['total_price'] ?? 0);
        }

        $cart['qty'] = $totalQty;
        $cart['quantity'] = $totalQty;
        $cart['total_price'] = $totalPrice;

        if ($firstItem) {
            $cart['master_item_id'] = $firstItem['master_item_id'];
            $cart['product_name'] = $firstItem['product_name'];
            $cart['product_image'] = $firstItem['product_image'];
            $cart['daily_rate'] = $firstItem['daily_rate'];
            $cart['category_name'] = $firstItem['category_name'] ?? '';
        }

        return $cart;
    }
}
