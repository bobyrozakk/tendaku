<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GmvService
{
    /**
     * Status rental yang dihitung ke dalam Gross Merchandise Value (GMV).
     * Didefinisikan di satu tempat terpusat sesuai kontrak sistem di docs/contract.md.
     */
    public const VALID_GMV_STATUSES = [
        'confirmed',
        'picked_up',
        'returned',
        'completed',
    ];

    /**
     * Hitung total GMV keseluruhan (lintas vendor atau per vendor) dalam rentang tanggal.
     */
    public function calculateTotal(
        ?int $vendorId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $dateColumn = 'created_at'
    ): float {
        $query = Rental::withoutGlobalScope('vendor')
            ->whereIn('status', self::VALID_GMV_STATUSES);

        if ($vendorId !== null) {
            $query->where('vendor_id', $vendorId);
        }

        if ($startDate !== null) {
            $query->where($dateColumn, '>=', $startDate->copy()->startOfDay());
        }

        if ($endDate !== null) {
            $query->where($dateColumn, '<=', $endDate->copy()->endOfDay());
        }

        return (float) $query->sum(DB::raw('COALESCE(final_total, grand_total)'));
    }

    /**
     * Hitung GMV Hari Ini.
     */
    public function calculateToday(?int $vendorId = null): float
    {
        $today = Carbon::today();

        return $this->calculateTotal($vendorId, $today, $today);
    }

    /**
     * Hitung GMV Kemarin (untuk perbandingan).
     */
    public function calculateYesterday(?int $vendorId = null): float
    {
        $yesterday = Carbon::yesterday();

        return $this->calculateTotal($vendorId, $yesterday, $yesterday);
    }

    /**
     * Hitung GMV Bulan Ini.
     */
    public function calculateThisMonth(?int $vendorId = null): float
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return $this->calculateTotal($vendorId, $start, $end);
    }

    /**
     * Hitung GMV Bulan Lalu (untuk perbandingan).
     */
    public function calculateLastMonth(?int $vendorId = null): float
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->subMonth()->endOfMonth();

        return $this->calculateTotal($vendorId, $start, $end);
    }

    /**
     * Hitung persentase pertumbuhan GMV harian vs kemarin.
     */
    public function calculateDailyGrowth(?int $vendorId = null): float
    {
        $today = $this->calculateToday($vendorId);
        $yesterday = $this->calculateYesterday($vendorId);

        if ($yesterday <= 0) {
            return $today > 0 ? 100.0 : 0.0;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    /**
     * Hitung persentase pertumbuhan GMV bulanan vs bulan lalu.
     */
    public function calculateMonthlyGrowth(?int $vendorId = null): float
    {
        $thisMonth = $this->calculateThisMonth($vendorId);
        $lastMonth = $this->calculateLastMonth($vendorId);

        if ($lastMonth <= 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * Total transaksi valid yang masuk hitungan GMV.
     */
    public function countTransactions(
        ?int $vendorId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $dateColumn = 'created_at'
    ): int {
        $query = Rental::withoutGlobalScope('vendor')
            ->whereIn('status', self::VALID_GMV_STATUSES);

        if ($vendorId !== null) {
            $query->where('vendor_id', $vendorId);
        }

        if ($startDate !== null) {
            $query->where($dateColumn, '>=', $startDate->copy()->startOfDay());
        }

        if ($endDate !== null) {
            $query->where($dateColumn, '<=', $endDate->copy()->endOfDay());
        }

        return $query->count();
    }

    /**
     * Ringkasan GMV per vendor (tabel peringkat omzet vendor).
     *
     * @return Collection<int, object{vendor_id: int, vendor_name: string, vendor_city: ?string, total_gmv: float, transaction_count: int, percentage: float}>
     */
    public function getGmvByVendor(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $totalOverallGmv = $this->calculateTotal(null, $startDate, $endDate);

        $query = Rental::withoutGlobalScope('vendor')
            ->whereIn('rentals.status', self::VALID_GMV_STATUSES)
            ->join('vendors', 'rentals.vendor_id', '=', 'vendors.id')
            ->select(
                'vendors.id as vendor_id',
                'vendors.name as vendor_name',
                'vendors.city as vendor_city',
                'vendors.status as vendor_status',
                DB::raw('SUM(COALESCE(rentals.final_total, rentals.grand_total)) as total_gmv'),
                DB::raw('COUNT(rentals.id) as transaction_count')
            )
            ->groupBy('vendors.id', 'vendors.name', 'vendors.city', 'vendors.status');

        if ($startDate !== null) {
            $query->where('rentals.created_at', '>=', $startDate->copy()->startOfDay());
        }

        if ($endDate !== null) {
            $query->where('rentals.created_at', '<=', $endDate->copy()->endOfDay());
        }

        $results = $query->orderByDesc('total_gmv')->get();

        return $results->map(function ($row) use ($totalOverallGmv) {
            $gmv = (float) $row->total_gmv;
            $percentage = $totalOverallGmv > 0 ? round(($gmv / $totalOverallGmv) * 100, 1) : 0.0;

            return (object) [
                'vendor_id' => (int) $row->vendor_id,
                'vendor_name' => (string) $row->vendor_name,
                'vendor_city' => $row->vendor_city ? (string) $row->vendor_city : null,
                'vendor_status' => (string) $row->vendor_status,
                'total_gmv' => $gmv,
                'transaction_count' => (int) $row->transaction_count,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Ambil data tren harian (misal 7 atau 14 hari terakhir) untuk visualisasi bar/chart.
     *
     * @return array<int, array{date: string, label: string, gmv: float, count: int}>
     */
    public function getDailyTrend(int $days = 7, ?int $vendorId = null): array
    {
        $trend = [];
        $startDate = Carbon::today()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $gmv = $this->calculateTotal($vendorId, $date, $date);
            $count = $this->countTransactions($vendorId, $date, $date);

            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->isoFormat('D MMM'),
                'gmv' => $gmv,
                'count' => $count,
            ];
        }

        return $trend;
    }
}
