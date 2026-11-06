<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ShippingSettingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingSettingController extends Controller
{
    /**
     * Tampilkan halaman pengaturan tarif pengiriman milik vendor yang sedang login.
     */
    public function index(Request $request): View
    {
        $vendor = $request->user()?->vendor;

        abort_if($vendor === null, 403, 'Akses ditolak. Akun Anda tidak terhubung ke vendor yang valid.');

        return view('vendor.shipping.index', [
            'vendor' => $vendor,
            'shippingSetting' => $vendor->shippingSetting,
        ]);
    }

    /**
     * Simpan konfigurasi baru atau perbarui konfigurasi tarif pengiriman milik vendor yang sedang login.
     */
    public function update(ShippingSettingRequest $request): RedirectResponse
    {
        $vendor = $request->user()?->vendor;

        abort_if($vendor === null, 403, 'Akses ditolak. Akun Anda tidak terhubung ke vendor yang valid.');

        $validated = $request->safe()->only([
            'rate_per_km',
            'weight_threshold_kg',
            'extra_weight_fee_per_kg',
        ]);

        $vendor->shippingSetting()->updateOrCreate(
            ['vendor_id' => $vendor->id],
            $validated,
        );

        return redirect()
            ->route('vendor.shipping.index')
            ->with('success', 'Pengaturan tarif pengiriman berhasil disimpan.');
    }
}
