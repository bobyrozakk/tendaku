<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    /**
     * Tampilkan halaman kelola dan approval vendor.
     */
    public function index(): View
    {
        return view('admin.vendors.index');
    }

    /**
     * Setujui (Approve) toko vendor baru.
     */
    public function approve(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', "Vendor '{$vendor->name}' berhasil disetujui (Approved).");
    }

    /**
     * Tolak pengajuan toko vendor baru.
     */
    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'suspended',
            'approved_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('warning', "Vendor '{$vendor->name}' telah ditolak/ditangguhkan.");
    }

    /**
     * Tangguhkan (Suspend) operasional toko vendor.
     */
    public function suspend(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'suspended',
            'approved_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('warning', "Vendor '{$vendor->name}' telah ditangguhkan.");
    }

    /**
     * Hapus data vendor (Soft Delete).
     */
    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendorName = $vendor->name;
        $vendor->delete();

        return redirect()->back()->with('success', "Vendor '{$vendorName}' berhasil dihapus.");
    }
}
