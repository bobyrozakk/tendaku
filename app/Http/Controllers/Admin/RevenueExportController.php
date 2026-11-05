<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RevenueCsvExporter;
use App\Services\RevenueReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueExportController extends Controller
{
    /**
     * Export transaksi pendapatan sesuai filter halaman.
     */
    public function csv(Request $request, RevenueCsvExporter $exporter): StreamedResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validator = Validator::make($request->query(), [
            'period' => ['nullable', Rule::in(RevenueReportService::PERIODS)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'payment_method' => ['nullable', Rule::in(RevenueReportService::PAYMENT_METHODS)],
            'escrow_status' => ['nullable', Rule::in(RevenueReportService::ESCROW_STATUSES)],
            'payment_type' => ['nullable', Rule::in(RevenueReportService::PAYMENT_TYPES)],
        ]);

        $validator->validate();

        $filters = $validator->validated();

        $filename = 'pendapatan-tendaku-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($exporter, $filters): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($exporter->stream($filters) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
