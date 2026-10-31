<?php

namespace App\Services\Auth;

use App\Models\User;

class AuthRedirectService
{
    /**
     * Dapatkan rute tujuan redirect setelah login/verifikasi berdasarkan role user.
     */
    public function getDestination(User $user, bool $absolute = false): string
    {
        return match (true) {
            $user->isVendorOwner() => route('vendor.owner.dashboard', absolute: $absolute),
            $user->isVendorAdmin() => route('kasir.dashboard', absolute: $absolute),
            $user->isVendorStaff() => route('dashboard', absolute: $absolute),
            $user->isCustomer() => route('home', absolute: $absolute),
            default => route('dashboard', absolute: $absolute),
        };
    }

    /**
     * Dapatkan pesan sambutan (flash message) sesuai role user.
     */
    public function getWelcomeMessage(User $user): string
    {
        return match (true) {
            $user->isVendorOwner() => "Selamat datang Owner {$user->name}!",
            $user->isVendorAdmin() => "Selamat datang Kasir {$user->name}!",
            $user->isSuperAdmin() => "Selamat datang Superadmin {$user->name}!",
            default => "Selamat datang, {$user->name}!",
        };
    }
}
