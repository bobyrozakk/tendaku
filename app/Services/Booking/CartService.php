<?php

namespace App\Services\Booking;

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

        if (is_array($cart) && ! empty($cart['master_item_id'])) {
            return $cart;
        }

        if ($user !== null && is_array($user->cart) && ! empty($user->cart['master_item_id'])) {
            Session::put(self::SESSION_KEY, $user->cart);

            return $user->cart;
        }

        return is_array($cart) ? $cart : null;
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
        Session::put(self::SESSION_KEY, $cartData);

        $user ??= Auth::user();

        if ($user !== null) {
            $user->update(['cart' => $cartData]);
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
            Session::put(self::SESSION_KEY, $user->cart);
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
                $user->update(['cart' => $cart]);
            }
        }

        Session::forget(self::SESSION_KEY);
    }
}
