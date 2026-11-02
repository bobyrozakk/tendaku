<?php

namespace App\Http\Controllers;

use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementPayoutProofController extends Controller
{
    /**
     * Tampilkan bukti payout Midtrans Iris untuk Super Admin atau vendor pemilik.
     */
    public function show(Request $request, Settlement $settlement): View
    {
        $user = $request->user();

        abort_unless($user !== null, 403);
        abort_unless($user->isSuperAdmin() || $user->isVendorOwner(), 403);
        abort_unless(
            $user->isSuperAdmin() || $settlement->vendor_id === $user->vendor_id,
            404,
        );

        abort_if($settlement->iris_reference_no === null, 404, 'Bukti payout Midtrans Iris belum tersedia.');

        $settlement->load(['vendor', 'invoices']);

        return view('settlements.payout-proof', [
            'settlement' => $settlement,
            'invoice' => $settlement->invoices->first(),
        ]);
    }
}
