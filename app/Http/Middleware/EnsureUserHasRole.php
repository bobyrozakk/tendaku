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
            abort(Response::HTTP_FORBIDDEN, 'Akses tidak diizinkan untuk peran ini.');
        }

        return $next($request);
    }
}
