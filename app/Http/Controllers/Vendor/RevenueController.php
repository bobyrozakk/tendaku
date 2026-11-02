<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    /**
     * Halaman pendapatan vendor: invoice settlement, status pencairan, dan
     * rekening tujuan transfer.
     */
    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        abort_if($vendor === null, 403);

        $invoiceQuery = VendorInvoice::query()
            ->where('vendor_id', $vendor->id)
            ->with('settlement');

        $invoices = (clone $invoiceQuery)
            ->latest('period_end')
            ->latest('id')
            ->paginate(8)
            ->withQueryString();

        return view('vendor.revenue.index', [
            'invoices' => $invoices,
            'paidTotal' => (float) (clone $invoiceQuery)->where('status', 'paid')->sum('total'),
            'awaitingTotal' => (float) (clone $invoiceQuery)->where('status', 'issued')->sum('total'),
            'commissionTotal' => (float) (clone $invoiceQuery)->where('status', '!=', 'void')->sum('commission_amount'),
            'awaitingCount' => (clone $invoiceQuery)->where('status', 'issued')->count(),
        ]);
    }
}
