<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * Memastikan pengguna memiliki role 'super_admin'.
     * Pengguna yang belum login diarahkan ke login/supadmin.
     * Pengguna dengan role lain langsung ditolak dengan HTTP 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->route('superadmin.login');
        }

        if (! $user->isSuperAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Akses ditolak. Fitur ini hanya dapat diakses oleh Super Administrator.');
        }

        return $next($request);
    }
}
