<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorOwner
{
    /**
     * Handle an incoming request.
     *
     * Memastikan pengguna yang mengakses adalah pengguna yang sudah login,
     * memiliki role 'vendor_owner', dan terikat pada vendor yang sah.
     * Vendor berstatus pending dilarang mengakses fitur operasional sebelum disetujui.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role !== 'vendor_owner' || empty($user->vendor_id)) {
            abort(403, 'Akses ditolak. Fitur ini hanya dapat diakses oleh Owner Vendor.');
        }

        if ($user->vendor && $user->vendor->status === 'pending' && ! $request->routeIs('vendor.owner.dashboard')) {
            abort(403, 'Akses ditolak. Toko Anda masih berstatus pending dan menunggu persetujuan Super Admin.');
        }

        return $next($request);
    }
}
