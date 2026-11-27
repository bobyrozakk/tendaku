<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class OwnerDashboardPresenter
{
    /**
     * Daftar periode analitik beserta label tampilannya.
     *
     * @var array<string, string>
     */
    public const PERIOD_OPTIONS = [
        'today' => 'Hari Ini',
        '7_days' => '7 Hari',
        '30_days' => '30 Hari',
        'this_month' => 'Bulan Ini',
        'this_year' => 'Tahun Ini',
    ];

    /**
     * Daftar kunci periode valid untuk validasi query string.
     *
     * @return array<int, string>
     */
    public function validPeriods(): array
    {
        return array_keys(self::PERIOD_OPTIONS);
    }

    /**
     * Ambil label bahasa Indonesia untuk periode terpilih.
     */
    public function periodLabel(string $period): string
    {
        return self::PERIOD_OPTIONS[$period] ?? self::PERIOD_OPTIONS['30_days'];
    }

    /**
     * Bangun struktur data siap tampil untuk Dashboard Owner tanpa mengubah perhitungan metrik.
     *
     * @param  array<string, mixed>  $metrics
     * @return array{
     *     selected_period: string,
     *     selected_period_label: string,
     *     date_range_label: string,
     *     period_options: array<int, array{key: string, label: string, active: bool}>,
     *     financial_cards: array<int, array{title: string, value: string, description: string, icon: string, emphasis: bool}>,
     *     issue_summary: array{damaged: int, lost: int, overdue: int, total_units: int, has_issues: bool},
     *     revenue_chart: array<string, mixed>,
     *     top_products: array<int, array{rank: int, name: string, category: string, times_rented: int, earnings_formatted: string, share_percent: int}>,
     *     overdue_rows: array<int, array{booking_code: string, customer_name: string, customer_phone: string, return_date: string, days_overdue: int, late_fee_formatted: string, has_late_fee: bool, status_label: string, status_tone: string, status_icon: ?string}>
     * }
     */
    public function build(array $metrics, string $selectedPeriod): array
    {
        $periodLabel = $this->periodLabel($selectedPeriod);
        $dateRangeLabel = $this->formatDateRange(
            (string) ($metrics['start_date'] ?? ''),
            (string) ($metrics['end_date'] ?? '')
        );

        $periodOptions = [];

        foreach (self::PERIOD_OPTIONS as $key => $label) {
            $periodOptions[] = [
                'key' => $key,
                'label' => $label,
                'active' => $key === $selectedPeriod,
            ];
        }

        $damagedCount = (int) ($metrics['damaged_assets_count'] ?? 0);
        $lostCount = (int) ($metrics['lost_assets_count'] ?? 0);
        $overdueCount = (int) ($metrics['overdue_count'] ?? 0);
        $totalUnitsCount = (int) ($metrics['total_units_count'] ?? 0);

        return [
            'selected_period' => $selectedPeriod,
            'selected_period_label' => $periodLabel,
            'date_range_label' => $dateRangeLabel,
            'period_options' => $periodOptions,
            'financial_cards' => [
                [
                    'title' => 'Omzet Harian',
                    'value' => $this->formatRupiah((float) ($metrics['daily_revenue'] ?? 0)),
                    'description' => 'Total transaksi sewa valid hari ini',
                    'icon' => 'banknote',
                    'emphasis' => false,
                ],
                [
                    'title' => "Omzet ({$periodLabel})",
                    'value' => $this->formatRupiah((float) ($metrics['period_revenue'] ?? 0)),
                    'description' => "Rentang {$dateRangeLabel}",
                    'icon' => 'trending-up',
                    'emphasis' => true,
                ],
            ],
            'issue_summary' => [
                'damaged' => $damagedCount,
                'lost' => $lostCount,
                'overdue' => $overdueCount,
                'total_units' => $totalUnitsCount,
                'has_issues' => ($damagedCount + $lostCount + $overdueCount) > 0,
            ],
            'revenue_chart' => $this->buildRevenueChart(
                $metrics['revenue_trend'] ?? collect()
            ),
            'top_products' => $this->buildTopProducts(
                $metrics['top_products'] ?? collect()
            ),
            'overdue_rows' => $this->buildOverdueRows(
                $metrics['overdue_rentals'] ?? collect()
            ),
        ];
    }

    /**
     * Format nominal ke format Rupiah lengkap.
     */
    public function formatRupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    /**
     * Format nominal ke bentuk ringkas (rb / jt) untuk sumbu grafik dan label bar.
     */
    public function formatCompactRupiah(float $value): string
    {
        if ($value >= 1000000) {
            return 'Rp '.rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',').' jt';
        }

        if ($value >= 1000) {
            return 'Rp '.rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',').' rb';
        }

        return 'Rp '.number_format($value, 0, ',', '.');
    }

    /**
     * Bangun data skala sumbu Y, tick, ringkasan, dan bar grafik tren pendapatan harian.
     *
     * @param  Collection<int, array{date: string, label: string, total: float|int}>  $trend
     * @return array<string, mixed>
     */
    private function buildRevenueChart(Collection $trend): array
    {
        $maxTotal = (float) ($trend->max('total') ?? 0);
        $periodTotal = (float) $trend->sum('total');
        $totalDays = $trend->count();
        $daysWithRevenue = $trend->filter(fn (array $point): bool => (float) $point['total'] > 0)->count();
        $averagePerDay = $totalDays > 0 ? $periodTotal / $totalDays : 0.0;
        $peakPoint = $trend->sortByDesc('total')->first();
        $showAllValueLabels = $totalDays <= 14;

        $scaleStep = $this->resolveNiceScaleStep($maxTotal);
        $scaleMax = $maxTotal > 0 ? ceil($maxTotal / $scaleStep) * $scaleStep : $scaleStep;
        $scaleIntervals = (int) round($scaleMax / $scaleStep);

        $ticks = collect(range($scaleIntervals, 0))
            ->map(function (int $step) use ($scaleStep): array {
                $value = (float) ($step * $scaleStep);

                return [
                    'value' => $value,
                    'label' => $value > 0 ? $this->formatCompactRupiah($value) : '0',
                ];
            })
            ->all();

        $bars = $trend->map(function (array $point) use ($maxTotal, $scaleMax, $showAllValueLabels): array {
            $total = (float) $point['total'];
            $heightPercent = $total > 0
                ? max(6, round(($total / $scaleMax) * 100, 2))
                : 2;
            $isPeak = $total > 0 && $total == $maxTotal;

            return [
                'date' => (string) $point['date'],
                'label' => (string) $point['label'],
                'total' => $total,
                'formatted_total' => $this->formatRupiah($total),
                'formatted_compact' => $this->formatCompactRupiah($total),
                'height_percent' => $heightPercent,
                'is_peak' => $isPeak,
                'has_revenue' => $total > 0,
                'show_label' => $total > 0 && ($showAllValueLabels || $isPeak),
            ];
        })->all();

        $peakTotal = (float) ($peakPoint['total'] ?? 0);

        return [
            'has_data' => $trend->isNotEmpty() && $periodTotal > 0,
            'period_total_compact' => $this->formatCompactRupiah($periodTotal),
            'period_total_full' => $this->formatRupiah($periodTotal),
            'average_per_day_compact' => $this->formatCompactRupiah($averagePerDay),
            'peak_total_compact' => $this->formatCompactRupiah($peakTotal),
            'peak_label' => (string) ($peakPoint['label'] ?? '-'),
            'days_with_revenue' => $daysWithRevenue,
            'total_days' => $totalDays,
            'scale_intervals' => $scaleIntervals,
            'ticks' => $ticks,
            'bars' => $bars,
        ];
    }

    /**
     * Bulatkan langkah sumbu Y ke kelipatan 1/2/5 x 10^n agar menghasilkan 2-4 interval yang rapi.
     */
    private function resolveNiceScaleStep(float $maxTotal): float
    {
        $scaleStep = 100000.0;

        if ($maxTotal <= 0) {
            return $scaleStep;
        }

        for ($exponent = 0; $exponent <= 12; $exponent++) {
            $magnitude = 10 ** $exponent;
            $found = null;

            foreach ([1, 2, 5] as $multiplier) {
                $candidate = $multiplier * $magnitude;
                $intervals = (int) ceil($maxTotal / $candidate);

                if ($intervals >= 2 && $intervals <= 4) {
                    $found = $candidate;

                    break;
                }
            }

            if ($found !== null) {
                return (float) $found;
            }
        }

        return $scaleStep;
    }

    /**
     * Bangun daftar 3 produk terlaris dengan persentase rasio sewa relatif terhadap peringkat pertama.
     *
     * @param  Collection<int, object>  $topProducts
     * @return array<int, array{rank: int, name: string, category: string, times_rented: int, earnings_formatted: string, share_percent: int}>
     */
    private function buildTopProducts(Collection $topProducts): array
    {
        $limitedProducts = $topProducts->values()->take(3);
        $maxTimesRented = max(1, (int) ($limitedProducts->max('times_rented') ?? 1));

        return $limitedProducts->map(function (object $item, int $index) use ($maxTimesRented): array {
            $timesRented = (int) ($item->times_rented ?? 0);
            $earnings = (float) ($item->total_earnings ?? 0);

            return [
                'rank' => $index + 1,
                'name' => (string) $item->name,
                'category' => (string) ($item->category_name ?? 'Umum'),
                'times_rented' => $timesRented,
                'earnings_formatted' => $this->formatRupiah($earnings),
                'share_percent' => max(8, (int) round(($timesRented / $maxTimesRented) * 100)),
            ];
        })->all();
    }

    /**
     * Format rentang tanggal ringkas dalam bahasa Indonesia.
     */
    private function formatDateRange(string $startDate, string $endDate): string
    {
        if ($startDate === '' || $endDate === '') {
            return '-';
        }

        $start = Carbon::parse($startDate)->locale('id');
        $end = Carbon::parse($endDate)->locale('id');

        if ($start->isSameDay($end)) {
            return $start->translatedFormat('d M Y');
        }

        if ($start->year === $end->year) {
            return $start->translatedFormat('d M').' s.d. '.$end->translatedFormat('d M Y');
        }

        return $start->translatedFormat('d M Y').' s.d. '.$end->translatedFormat('d M Y');
    }

    /**
     * Bangun baris tampilan keterlambatan pengembalian agar dipakai ulang di mobile dan desktop.
     *
     * @param  Collection<int, object>  $rentals
     * @return array<int, array{booking_code: string, customer_name: string, customer_phone: string, return_date: string, days_overdue: int, late_fee_formatted: string, has_late_fee: bool, status_label: string, status_tone: string, status_icon: ?string}>
     */
    private function buildOverdueRows(Collection $rentals): array
    {
        return $rentals->map(function (object $rental): array {
            $status = (string) ($rental->status ?? '');
            $lateFee = (float) ($rental->total_late_fee ?? 0);

            [$statusLabel, $statusTone, $statusIcon] = match ($status) {
                'picked_up' => ['Belum Kembali', 'goldenrod', 'clock'],
                'completed', 'returned' => ['Sudah Dikembalikan', 'avocado', 'circle-check'],
                default => [$status !== '' ? $status : '-', 'neutral', null],
            };

            return [
                'booking_code' => (string) ($rental->booking_code ?? '-'),
                'customer_name' => (string) ($rental->customer->name ?? $rental->guest_name ?? 'Pelanggan'),
                'customer_phone' => (string) ($rental->customer->phone ?? $rental->guest_phone ?? '-'),
                'return_date' => ! empty($rental->return_date)
                    ? Carbon::parse($rental->return_date)->format('d M Y')
                    : '-',
                'days_overdue' => (int) ($rental->days_overdue ?? 0),
                'late_fee_formatted' => $this->formatRupiah($lateFee),
                'has_late_fee' => $lateFee > 0,
                'status_label' => $statusLabel,
                'status_tone' => $statusTone,
                'status_icon' => $statusIcon,
            ];
        })->all();
    }
}
