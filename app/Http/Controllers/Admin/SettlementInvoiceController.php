<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementInvoiceController extends Controller
{
    /**
     * Tampilkan invoice settlement dalam format siap cetak (A4).
     */
    public function print(Request $request, Settlement $settlement): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $settlement->load(['vendor', 'items.payment.rental', 'invoices']);

        $invoice = $settlement->invoices->firstWhere('status', '!=', 'void');

        abort_if($invoice === null, 404, 'Invoice belum diterbitkan untuk batch ini.');

        return view('invoices.settlement', [
            'settlement' => $settlement,
            'invoice' => $invoice,
            'vendor' => $settlement->vendor,
        ]);
    }

    /**
     * Unduh invoice sebagai file HTML (fallback tanpa printer).
     */
    public function download(Request $request, Settlement $settlement): View
    {
        return $this->print($request, $settlement);
    }
}
