<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorAnalyticsService;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OwnerDashboardController extends Controller
{
    /**
     * Periode analitik yang didukung dashboard.
     *
     * @var array<int, string>
     */
    private const VALID_PERIODS = ['today', '7_days', '30_days', 'this_month', 'this_year'];

    public function __construct(
        protected VendorAnalyticsService $analyticsService
    ) {}

    /**
     * Tampilkan Dashboard Analitik Owner Vendor.
     */
    public function index(Request $request): View
    {
        $vendorId = (int) $request->user()->vendor_id;
        $period = $this->resolvePeriod($request);

        $metrics = $this->analyticsService->getDashboardMetrics($vendorId, $period);
        $vendor = $request->user()->vendor;

        return view('vendor.owner.dashboard', [
            'metrics' => $metrics,
            'vendor' => $vendor,
            'selectedPeriod' => $period,
        ]);
    }

    /**
     * Export rincian omzet per hari pada periode terpilih ke file Excel (.xlsx).
     */
    public function exportDailyRevenue(Request $request, XlsxExportService $excel): BinaryFileResponse
    {
        $vendorId = (int) $request->user()->vendor_id;
        $period = $this->resolvePeriod($request);

        $metrics = $this->analyticsService->getDashboardMetrics($vendorId, $period);
        $vendor = $request->user()->vendor;

        $rows = [
            [['value' => 'Laporan Omzet Harian', 'style' => 'bold']],
            [['value' => 'Nama Toko', 'style' => 'bold'], $vendor?->name ?? '-'],
            [['value' => 'Periode', 'style' => 'bold'], $metrics['start_date'].' s.d '.$metrics['end_date']],
            [['value' => 'Diekspor Pada', 'style' => 'bold'], Carbon::now()->locale('id')->translatedFormat('d F Y H:i')],
            [],
            [
                ['value' => 'No', 'style' => 'header'],
                ['value' => 'Tanggal', 'style' => 'header'],
                ['value' => 'Hari', 'style' => 'header'],
                ['value' => 'Total Omzet (Rp)', 'style' => 'header'],
            ],
        ];

        $totalRevenue = 0.0;

        foreach ($metrics['revenue_trend']->values() as $index => $point) {
            $totalRevenue += (float) $point['total'];

            $rows[] = [
                $index + 1,
                $point['date'],
                Carbon::parse($point['date'])->locale('id')->translatedFormat('l'),
                ['value' => (float) $point['total'], 'style' => 'number'],
            ];
        }

        $rows[] = [];
        $rows[] = [
            ['value' => 'TOTAL', 'style' => 'bold'],
            '',
            '',
            ['value' => $totalRevenue, 'style' => 'bold_number'],
        ];

        $fileName = sprintf(
            'omzet-harian-%s-%s-sd-%s.xlsx',
            Str::slug($vendor?->name ?? 'vendor'),
            $metrics['start_date'],
            $metrics['end_date']
        );

        return $excel->download($fileName, 'Omzet Harian', $rows, [6, 14, 14, 20]);
    }

    /**
     * Validasi periode dari query string, fallback ke 30 hari terakhir.
     */
    private function resolvePeriod(Request $request): string
    {
        $period = $request->query('period', '30_days');

        return in_array($period, self::VALID_PERIODS, true) ? $period : '30_days';
    }
}
