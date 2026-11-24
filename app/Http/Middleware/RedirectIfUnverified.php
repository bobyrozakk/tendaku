<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUnverified
{
    /**
     * Izinkan akses ke halaman publik sebagai guest untuk pengguna yang belum verifikasi email,
     * atau arahkan ke halaman verifikasi jika mencoba mengakses area akun.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Superadmin tidak dibatasi oleh verifikasi email
        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            // Rute verifikasi email, logout, endpoint Livewire, serta alur checkout & status booking diizinkan
            if ($request->routeIs(
                'verification.notice',
                'verification.verify',
                'verification.send',
                'logout',
                'checkout.*',
                'booking.*'
            ) || $request->is('verify-email*', 'logout', 'livewire*', 'katalog/booking*')) {
                return $next($request);
            }

            // Jika user mencoba ke halaman publik (Home, Katalog, Toko),
            // keluarkan dari sesi akun agar dapat mengakses halaman secara bebas sebagai GUEST.
            if ($request->routeIs('home', 'customer.catalog.index', 'customer.product.show', 'customer.stores.show')
                || $request->is('/', 'toko*')
                || ($request->is('katalog*') && ! $request->is('katalog/booking*'))
            ) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $next($request);
            }

            // Jika mencoba mengakses area akun/privat tanpa verifikasi email
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, 'Email Anda belum diverifikasi.');
            }

            return redirect()->route('verification.notice')->with(
                'status',
                'Silakan verifikasi email Anda terlebih dahulu sebelum menggunakan akun.'
            );
        }

        return $next($request);
    }
}
