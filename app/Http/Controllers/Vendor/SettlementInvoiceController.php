<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementInvoiceController extends Controller
{
    /**
     * Cetak invoice settlement milik vendor yang sedang login.
     */
    public function print(Request $request, Settlement $settlement): View
    {
        $user = $request->user();

        abort_unless($user?->isVendorOwner(), 403);
        abort_unless($settlement->vendor_id === $user->vendor_id, 404);

        $settlement->load(['vendor', 'items.payment.rental', 'invoices']);

        $invoice = $settlement->invoices->firstWhere('status', '!=', 'void');

        abort_if($invoice === null, 404, 'Invoice belum diterbitkan untuk batch ini.');

        return view('invoices.settlement', [
            'settlement' => $settlement,
            'invoice' => $invoice,
            'vendor' => $settlement->vendor,
        ]);
    }
}
