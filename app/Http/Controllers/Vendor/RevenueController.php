<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\VendorInvoice;
use App\Services\Finance\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    /**
     * Halaman pendapatan vendor: invoice settlement, status pencairan, dan
     * rekening tujuan transfer.
     */
    public function index(Request $request, SettlementService $settlementService): View
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

        $withdrawableSummary = $settlementService->getWithdrawableSummary($vendor->id);
        $activeSettlement = Settlement::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['draft', 'approved'])
            ->first();

        return view('vendor.revenue.index', [
            'invoices' => $invoices,
            'paidTotal' => (float) (clone $invoiceQuery)->where('status', 'paid')->sum('total'),
            'awaitingTotal' => (float) (clone $invoiceQuery)->where('status', 'issued')->sum('total'),
            'commissionTotal' => (float) (clone $invoiceQuery)->where('status', '!=', 'void')->sum('commission_amount'),
            'awaitingCount' => (clone $invoiceQuery)->where('status', 'issued')->count(),
            'withdrawableSummary' => $withdrawableSummary,
            'activeSettlement' => $activeSettlement,
            'vendor' => $vendor->loadMissing('paymentSetting'),
        ]);
    }

    /**
     * Vendor mengajukan permohonan penarikan dana ke admin platform.
     */
    public function requestPayout(Request $request, SettlementService $settlementService): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        abort_if($vendor === null, 403);

        try {
            $settlement = $settlementService->requestPayoutForVendor($vendor->id);

            return redirect()
                ->route('vendor.pendapatan')
                ->with('success', 'Permohonan penarikan dana (Batch #'.$settlement->id.') berhasil diajukan! Admin platform akan memverifikasi dan mentransfer ke rekening Anda.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('vendor.pendapatan')
                ->with('error', $e->getMessage());
        }
    }
}
