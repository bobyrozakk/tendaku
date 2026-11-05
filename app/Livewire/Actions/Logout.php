<?php

namespace App\Livewire\Actions;

use App\Services\Booking\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        if ($user = Auth::user()) {
            app(CartService::class)->syncOnLogout($user);
        }

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
