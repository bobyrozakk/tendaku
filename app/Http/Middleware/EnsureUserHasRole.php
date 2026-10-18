<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->guest(route('login'));
        }

        if (! empty($roles) && ! $user->hasRole($roles)) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, 'Akses tidak diizinkan untuk peran ini.');
            }

            // Jika user/customer mencoba ke dashboard, arahkan kembali ke landing page tanpa peringatan apapun
            if ($user->isCustomer()) {
                return redirect()->route('home');
            }

            $destination = match (true) {
                $user->isVendorStaff() => route('dashboard'),
                default => route('home'),
            };

            return redirect($destination)->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
