<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function __construct(
        protected VendorAnalyticsService $analyticsService
    ) {}

    /**
     * Tampilkan Dashboard Analitik Owner Vendor.
     */
    public function index(Request $request): View
    {
        $vendorId = (int) $request->user()->vendor_id;
        $period = $request->query('period', '30_days');

        $validPeriods = ['today', '7_days', '30_days', 'this_month', 'this_year'];
        if (! in_array($period, $validPeriods, true)) {
            $period = '30_days';
        }

        $metrics = $this->analyticsService->getDashboardMetrics($vendorId, $period);
        $vendor = $request->user()->vendor;

        return view('vendor.owner.dashboard', [
            'metrics' => $metrics,
            'vendor' => $vendor,
            'selectedPeriod' => $period,
        ]);
    }
}
