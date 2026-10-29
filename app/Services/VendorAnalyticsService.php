<?php

namespace App\Services;

use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VendorAnalyticsService
{
    /**
     * Status rental yang diakui sebagai omzet/pendapatan (tidak termasuk cancelled dan expired).
     */
    public const VALID_REVENUE_STATUSES = ['confirmed', 'picked_up', 'returned', 'completed'];

    /**
     * Dapatkan ringkasan analitik lengkap untuk vendor tertentu.
     *
     * @param  string  $period  'today' | '7_days' | '30_days' | 'this_month' | 'this_year'
     */
    public function getDashboardMetrics(int $vendorId, string $period = '30_days'): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($period);

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'daily_revenue' => $this->getDailyRevenue($vendorId),
            'monthly_revenue' => $this->getMonthlyRevenue($vendorId),
            'period_revenue' => $this->getRevenueForPeriod($vendorId, $startDate, $endDate),
            'revenue_trend' => $this->getRevenueTrend($vendorId, $startDate, $endDate),
            'top_products' => $this->getTopProducts($vendorId, $startDate, $endDate, 5),
            'damaged_assets_count' => $this->getDamagedAssetsCount($vendorId),
            'lost_assets_count' => $this->getLostAssetsCount($vendorId),
            'overdue_rentals' => $this->getOverdueRentals($vendorId),
            'overdue_count' => $this->getOverdueRentals($vendorId)->count(),
            'unit_status_breakdown' => $this->getUnitStatusBreakdown($vendorId),
            'unit_condition_breakdown' => $this->getUnitConditionBreakdown($vendorId),
            'total_units_count' => $this->getTotalUnitsCount($vendorId),
            'total_products_count' => MasterItem::withoutGlobalScope('vendor')->where('vendor_id', $vendorId)->count(),
            'active_rentals_count' => Rental::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendorId)
                ->whereIn('status', ['confirmed', 'picked_up'])
                ->count(),
        ];
    }

    /**
     * Omzet hari ini (berdasarkan transaksi rental sah hari ini).
     */
    public function getDailyRevenue(int $vendorId, ?Carbon $date = null): float
    {
        $targetDate = $date ?? Carbon::today();

        // Cek pembayaran paid hari ini
        $paymentRevenue = (float) Payment::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->whereDate('paid_at', $targetDate)
            ->sum('amount');

        if ($paymentRevenue > 0) {
            return $paymentRevenue;
        }

        // Fallback ke nilai grand_total transaksi sah hari ini jika pembayaran dicatat via model rental
        return (float) Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', self::VALID_REVENUE_STATUSES)
            ->whereDate('created_at', $targetDate)
            ->sum(DB::raw('COALESCE(final_total, grand_total)'));
    }

    /**
     * Omzet bulan ini.
     */
    public function getMonthlyRevenue(int $vendorId, ?Carbon $monthDate = null): float
    {
        $targetMonth = $monthDate ?? Carbon::now();

        $paymentRevenue = (float) Payment::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->whereYear('paid_at', $targetMonth->year)
            ->whereMonth('paid_at', $targetMonth->month)
            ->sum('amount');

        if ($paymentRevenue > 0) {
            return $paymentRevenue;
        }

        return (float) Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', self::VALID_REVENUE_STATUSES)
            ->whereYear('created_at', $targetMonth->year)
            ->whereMonth('created_at', $targetMonth->month)
            ->sum(DB::raw('COALESCE(final_total, grand_total)'));
    }

    /**
     * Omzet dalam periode tertentu.
     */
    public function getRevenueForPeriod(int $vendorId, Carbon $startDate, Carbon $endDate): float
    {
        $paymentRevenue = (float) Payment::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->sum('amount');

        if ($paymentRevenue > 0) {
            return $paymentRevenue;
        }

        return (float) Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', self::VALID_REVENUE_STATUSES)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->sum(DB::raw('COALESCE(final_total, grand_total)'));
    }

    /**
     * Tren omzet harian dalam rentang tanggal tertentu.
     * Mengembalikan collection berisi objek dengan tanggal, label, dan total omzet.
     */
    public function getRevenueTrend(int $vendorId, Carbon $startDate, Carbon $endDate): Collection
    {
        // Ambil data pembayaran paid per hari jika ada
        $payments = Payment::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        // Ambil data rental per hari sebagai perbandingan / fallback
        $rentals = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', self::VALID_REVENUE_STATUSES)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as date, SUM(COALESCE(final_total, grand_total)) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $trend = collect();
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');
            $val = (float) ($payments->get($dateStr) ?? $rentals->get($dateStr) ?? 0);

            $trend->push([
                'date' => $dateStr,
                'label' => $current->translatedFormat('d M'),
                'total' => $val,
            ]);

            $current->addDay();
        }

        return $trend;
    }

    /**
     * Dapatkan produk terlaris berdasarkan jumlah disewa dan pendapatan dalam periode.
     */
    public function getTopProducts(int $vendorId, Carbon $startDate, Carbon $endDate, int $limit = 5): Collection
    {
        return RentalDetail::query()
            ->join('rentals', 'rental_details.rental_id', '=', 'rentals.id')
            ->join('master_items', 'rental_details.master_item_id', '=', 'master_items.id')
            ->leftJoin('item_categories', 'master_items.category_id', '=', 'item_categories.id')
            ->where('rentals.vendor_id', $vendorId)
            ->whereIn('rentals.status', self::VALID_REVENUE_STATUSES)
            ->whereBetween('rentals.created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->select(
                'master_items.id',
                'master_items.name',
                'master_items.image_url',
                'item_categories.name as category_name',
                DB::raw('COUNT(rental_details.id) as times_rented'),
                DB::raw('SUM(rental_details.daily_rate_snapshot * rentals.rental_days) as total_earnings')
            )
            ->groupBy('master_items.id', 'master_items.name', 'master_items.image_url', 'item_categories.name')
            ->orderByDesc('times_rented')
            ->orderByDesc('total_earnings')
            ->limit($limit)
            ->get();
    }

    /**
     * Jumlah unit fisik dengan kondisi rusak.
     */
    public function getDamagedAssetsCount(int $vendorId): int
    {
        return ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('condition', 'damaged')
            ->count();
    }

    /**
     * Jumlah unit fisik dengan kondisi hilang.
     */
    public function getLostAssetsCount(int $vendorId): int
    {
        return ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('condition', 'lost')
            ->count();
    }

    /**
     * Dapatkan daftar transaksi yang mengalami keterlambatan pengembalian.
     * Termasuk:
     * 1. Rental aktif (picked_up) dengan return_date yang sudah lewat dari hari ini.
     * 2. Rental yang sudah dikembalikan tetapi memiliki total_late_fee > 0 atau actual_return_date > return_date.
     */
    public function getOverdueRentals(int $vendorId, int $limit = 10): Collection
    {
        $today = Carbon::today()->format('Y-m-d');

        return Rental::withoutGlobalScope('vendor')
            ->with(['customer', 'details.masterItem', 'details.itemUnit'])
            ->where('vendor_id', $vendorId)
            ->where(function ($query) use ($today) {
                // Sedang disewa dan tanggal kembali sudah lewat hari ini
                $query->where(function ($q) use ($today) {
                    $q->where('status', 'picked_up')
                        ->where('return_date', '<', $today);
                })
                // Atau sudah selesai/kembali dengan denda telat
                    ->orWhere(function ($q) {
                        $q->where('total_late_fee', '>', 0);
                    });
            })
            ->orderByDesc('return_date')
            ->limit($limit)
            ->get()
            ->map(function ($rental) {
                $isStillActive = $rental->status === 'picked_up';
                $returnDate = Carbon::parse($rental->return_date);
                $daysOverdue = 0;

                if ($isStillActive && $returnDate->isPast()) {
                    $daysOverdue = (int) $returnDate->diffInDays(Carbon::today());
                } elseif ($rental->actual_return_date && Carbon::parse($rental->actual_return_date)->gt($returnDate)) {
                    $daysOverdue = (int) $returnDate->diffInDays(Carbon::parse($rental->actual_return_date));
                }

                $rental->days_overdue = $daysOverdue;
                $rental->is_currently_overdue = $isStillActive && $returnDate->isPast();

                return $rental;
            });
    }

    /**
     * Breakdown unit fisik berdasarkan status.
     */
    public function getUnitStatusBreakdown(int $vendorId): array
    {
        $counts = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'available' => $counts['available'] ?? 0,
            'rented' => $counts['rented'] ?? 0,
            'maintenance' => $counts['maintenance'] ?? 0,
            'retired' => $counts['retired'] ?? 0,
        ];
    }

    /**
     * Breakdown unit fisik berdasarkan kondisi.
     */
    public function getUnitConditionBreakdown(int $vendorId): array
    {
        $counts = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->select('condition', DB::raw('count(*) as total'))
            ->groupBy('condition')
            ->pluck('total', 'condition')
            ->all();

        return [
            'excellent' => $counts['excellent'] ?? 0,
            'good' => $counts['good'] ?? 0,
            'fair' => $counts['fair'] ?? 0,
            'damaged' => $counts['damaged'] ?? 0,
            'lost' => $counts['lost'] ?? 0,
        ];
    }

    /**
     * Total unit fisik milik vendor.
     */
    public function getTotalUnitsCount(int $vendorId): int
    {
        return ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->count();
    }

    /**
     * Konversi string periode menjadi rentang Carbon.
     */
    protected function resolveDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [Carbon::today(), Carbon::today()],
            '7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()], // '30_days'
        };
    }
}
