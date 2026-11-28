<?php

namespace App\Services\Auth;

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureVendorOwner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * Forget an intended URL when the authenticated user cannot access its route.
     */
    public function clearInaccessibleIntendedDestination(User $user): void
    {
        $intended = session('url.intended');

        if (! is_string($intended) || $intended === '') {
            return;
        }

        $router = app(Router::class);

        try {
            $route = $router->getRoutes()->match(Request::create($intended));
        } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
            session()->forget('url.intended');

            return;
        }

        foreach ($router->gatherRouteMiddleware($route) as $middleware) {
            [$middlewareClass, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

            if (
                $middlewareClass === EnsureUserHasRole::class &&
                $parameters !== null &&
                ! $user->hasRole(explode(',', $parameters))
            ) {
                session()->forget('url.intended');

                return;
            }

            if (
                $middlewareClass === EnsureVendorOwner::class &&
                (! $user->isVendorOwner() || empty($user->vendor_id))
            ) {
                session()->forget('url.intended');

                return;
            }

            if ($middlewareClass === EnsureSuperAdmin::class && ! $user->isSuperAdmin()) {
                session()->forget('url.intended');

                return;
            }
        }
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
