<?php

namespace App\Http\Middleware;

use App\Services\CurrentVendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveVendor
{
    /**
     * Handle an incoming request.
     *
     * Membaca vendor dari user yang sedang login dan menyimpannya ke CurrentVendor
     * sebagai vendor aktif dalam request lifecycle.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->isSuperAdmin()) {
                CurrentVendor::bypass(true);
            } elseif ($user->vendor_id !== null) {
                CurrentVendor::set((int) $user->vendor_id);
            }
        }

        return $next($request);
    }
}
