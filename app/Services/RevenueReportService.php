<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Models\Rental;
use App\Models\Settlement;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class RevenueReportService
{
    /**
     * Preset periode yang didukung dashboard dan export.
     *
     * @var list<string>
     */
    public const PERIODS = [
        'today',
        'yesterday',
        '7_days',
        '30_days',
        'this_month',
        'last_month',
        'this_year',
        'all_time',
        'custom',
    ];

    /**
     * Metode pembayaran yang dapat difilter. `qris` dipetakan ke payment_channel,
     * sedangkan `midtrans` mencakup semua transaksi Midtrans (QRIS/VA).
     *
     * @var list<string>
     */
    public const PAYMENT_METHODS = ['qris', 'cash', 'transfer', 'midtrans'];

    /**
     * @var list<string>
     */
    public const ESCROW_STATUSES = ['not_applicable', 'held', 'released', 'refunded', 'void'];

    /**
     * @var list<string>
     */
    public const PAYMENT_TYPES = ['dp', 'full', 'remaining', 'fine', 'refund'];

    /**
     * Normalisasi input filter dari Livewire/HTTP menjadi rentang tanggal Carbon.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     period: string,
     *     vendor_id: ?int,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     payment_method: ?string,
     *     escrow_status: ?string,
     *     payment_type: ?string
     * }
     */
    public function normalizeFilters(array $input): array
    {
        $period = $input['period'] ?? 'all_time';

        if (! in_array($period, self::PERIODS, true)) {
            $period = 'all_time';
        }

        [$start, $end] = $this->resolveDateRange(
            $period,
            isset($input['start_date']) ? (string) $input['start_date'] : null,
            isset($input['end_date']) ? (string) $input['end_date'] : null,
        );

        $vendorId = isset($input['vendor_id']) && $input['vendor_id'] !== ''
            ? (int) $input['vendor_id']
            : null;

        $paymentMethod = $input['payment_method'] ?? null;
        $escrowStatus = $input['escrow_status'] ?? null;
        $paymentType = $input['payment_type'] ?? null;

        return [
            'period' => $period,
            'vendor_id' => $vendorId !== null && $vendorId > 0 ? $vendorId : null,
            'start' => $start,
            'end' => $end,
            'payment_method' => in_array($paymentMethod, self::PAYMENT_METHODS, true) ? $paymentMethod : null,
            'escrow_status' => in_array($escrowStatus, self::ESCROW_STATUSES, true) ? $escrowStatus : null,
            'payment_type' => in_array($paymentType, self::PAYMENT_TYPES, true) ? $paymentType : null,
        ];
    }

    /**
     * Ubah preset periode menjadi tanggal awal dan akhir.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function resolveDateRange(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => [$today->copy(), $today->copy()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            '7_days' => [$today->copy()->subDays(6), $today->copy()],
            '30_days' => [$today->copy()->subDays(29), $today->copy()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'all_time' => [null, null],
            'custom' => $this->parseCustomRange($startDate, $endDate),
            default => [null, null],
        };
    }

    /**
     * Ringkasan KPI tetap (hari ini, 7 hari, bulan ini, tahun ini) dan saldo.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getKpis(array $filters): array
    {
        $filters = $this->ensureNormalized($filters);
        $today = Carbon::today();

        $ranges = [
            'today' => [$today->copy(), $today->copy()],
            '7_days' => [$today->copy()->subDays(6), $today->copy()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
        ];

        $kpis = [];

        foreach ($ranges as $key => [$start, $end]) {
            $rangeFilters = array_merge($filters, [
                'period' => $key,
                'start' => $start->copy()->startOfDay(),
                'end' => $end->copy()->endOfDay(),
            ]);

            $current = $this->sumCommission($rangeFilters);
            $previousRange = $this->previousRange($rangeFilters);
            $previous = $previousRange === null
                ? 0.0
                : $this->sumCommission(array_merge($rangeFilters, $previousRange));

            $kpis["commission_{$key}"] = $current;
            $kpis["commission_{$key}_delta"] = $this->percentChange($current, $previous);
        }

        $escrowFilters = $filters;
        $escrowFilters['start'] = null;
        $escrowFilters['end'] = null;
        $escrowFilters['escrow_status'] = null;

        $cashFilters = $filters;
        $cashFilters['start'] = null;
        $cashFilters['end'] = null;
        $cashFilters['escrow_status'] = null;
        $cashFilters['payment_type'] = null;
        $cashFilters['payment_method'] = 'cash';

        $kpis['escrow_held_total'] = (float) $this->paymentQuery($escrowFilters)
            ->where('payments.escrow_status', 'held')
            ->sum('payments.amount');

        $kpis['cash_receivable'] = (float) $this->commissionQuery($cashFilters, false)
            ->where('platform_commissions.status', 'accrued')
            ->sum('platform_commissions.amount');

        $kpis['pending_batches'] = Settlement::withoutGlobalScope('vendor')
            ->where('status', 'draft')
            ->count();

        $monthStart = $today->copy()->startOfMonth()->startOfDay();
        $monthEnd = $today->copy()->endOfMonth()->endOfDay();

        $kpis['payout_this_month'] = (float) Settlement::withoutGlobalScope('vendor')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('net_payout');

        $kpis['settlements_available'] = true;

        return $kpis;
    }

    /**
     * Ringkasan keuangan untuk periode/filter terpilih beserta delta periode sebelumnya.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummary(array $filters): array
    {
        $filters = $this->ensureNormalized($filters);
        $current = $this->aggregateSummary($filters);

        $previous = null;
        $previousRange = $this->previousRange($filters);

        if ($previousRange !== null) {
            $previous = $this->aggregateSummary(array_merge($filters, $previousRange));
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => [
                'gross_revenue' => $this->percentChange($current['gross_revenue'], $previous['gross_revenue'] ?? 0.0),
                'commission' => $this->percentChange($current['commission'], $previous['commission'] ?? 0.0),
                'platform_net' => $this->percentChange($current['platform_net'], $previous['platform_net'] ?? 0.0),
            ],
            'range' => [
                'start' => $filters['start'],
                'end' => $filters['end'],
            ],
            'previous_range' => $previousRange,
        ];
    }

    /**
     * Ringkasan per vendor: GMV, komisi, escrow, estimasi payout, dan piutang tunai.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getVendorSummaries(array $filters): Collection
    {
        $filters = $this->ensureNormalized($filters);

        $rentalRows = Rental::withoutGlobalScope('vendor')
            ->whereIn('rentals.status', GmvService::VALID_GMV_STATUSES)
            ->when($filters['vendor_id'] !== null, fn (Builder $query) => $query->where('rentals.vendor_id', $filters['vendor_id']))
            ->when($filters['start'] !== null, fn (Builder $query) => $query->where('rentals.created_at', '>=', $filters['start']))
            ->when($filters['end'] !== null, fn (Builder $query) => $query->where('rentals.created_at', '<=', $filters['end']))
            ->selectRaw('rentals.vendor_id, COALESCE(SUM(COALESCE(rentals.final_total, rentals.grand_total)), 0) as gmv, COUNT(*) as rental_count')
            ->groupBy('rentals.vendor_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->vendor_id);

        $paymentRows = $this->paymentQuery($filters)
            ->selectRaw("
                payments.vendor_id,
                COALESCE(SUM(CASE WHEN payments.payment_type <> 'fine' THEN payments.amount ELSE 0 END), 0) as gross_revenue,
                COALESCE(SUM(CASE WHEN payments.payment_type <> 'fine' AND payments.payment_method <> 'cash' THEN payments.amount ELSE 0 END), 0) as gross_non_cash,
                COALESCE(SUM(CASE WHEN payments.payment_type = 'fine' AND payments.payment_method <> 'cash' THEN payments.amount ELSE 0 END), 0) as gross_fine_non_cash,
                COALESCE(SUM(CASE WHEN payments.payment_type <> 'fine' AND payments.payment_method <> 'cash' THEN payments.gateway_fee_amount ELSE 0 END), 0) as gateway_fee_total,
                COALESCE(SUM(CASE WHEN payments.payment_type = 'fine' AND payments.payment_method <> 'cash' THEN payments.gateway_fee_amount ELSE 0 END), 0) as fine_fee_total,
                COALESCE(SUM(CASE WHEN payments.escrow_status = 'held' THEN payments.amount ELSE 0 END), 0) as escrow_held,
                COUNT(*) as transaction_count
            ")
            ->groupBy('payments.vendor_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->vendor_id);

        $commissionRows = $this->commissionQuery($filters)
            ->selectRaw("
                platform_commissions.vendor_id,
                COALESCE(SUM(platform_commissions.amount), 0) as commission_total,
                COALESCE(SUM(CASE WHEN payments.payment_method = 'cash' THEN platform_commissions.amount ELSE 0 END), 0) as cash_commission,
                COALESCE(SUM(CASE WHEN payments.payment_method <> 'cash' THEN platform_commissions.amount ELSE 0 END), 0) as non_cash_commission,
                COALESCE(SUM(CASE WHEN payments.payment_method = 'cash' AND platform_commissions.status = 'accrued' THEN platform_commissions.amount ELSE 0 END), 0) as cash_receivable
            ")
            ->groupBy('platform_commissions.vendor_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->vendor_id);

        $vendorIds = $rentalRows->keys()
            ->merge($paymentRows->keys())
            ->merge($commissionRows->keys())
            ->unique()
            ->values();

        if ($filters['vendor_id'] !== null && ! $vendorIds->contains($filters['vendor_id'])) {
            $vendorIds->push($filters['vendor_id']);
        }

        $vendors = Vendor::withTrashed()
            ->whereIn('id', $vendorIds)
            ->get(['id', 'name', 'city', 'status'])
            ->keyBy(fn (Vendor $vendor) => $vendor->id);

        $totalCommission = (float) $commissionRows->sum('commission_total');

        $rows = $vendorIds
            ->map(function (int $vendorId) use ($rentalRows, $paymentRows, $commissionRows, $vendors, $totalCommission): ?array {
                $vendor = $vendors->get($vendorId);

                if ($vendor === null) {
                    return null;
                }

                $rental = $rentalRows->get($vendorId);
                $payment = $paymentRows->get($vendorId);
                $commission = $commissionRows->get($vendorId);

                $gmv = (float) ($rental->gmv ?? 0);
                $commissionTotal = (float) ($commission->commission_total ?? 0);
                $cashCommission = (float) ($commission->cash_commission ?? 0);
                $nonCashCommission = (float) ($commission->non_cash_commission ?? 0);
                $grossNonCash = (float) ($payment->gross_non_cash ?? 0);
                $grossFineNonCash = (float) ($payment->gross_fine_non_cash ?? 0);
                $fineFeeTotal = (float) ($payment->fine_fee_total ?? 0);

                $payoutEstimate = $grossNonCash + $grossFineNonCash - $nonCashCommission - $fineFeeTotal - $cashCommission;

                return [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor->name,
                    'vendor_city' => $vendor->city,
                    'vendor_status' => $vendor->status,
                    'gmv' => $gmv,
                    'gross_revenue' => (float) ($payment->gross_revenue ?? 0),
                    'commission_total' => $commissionTotal,
                    'commission_percentage' => $totalCommission > 0 ? round(($commissionTotal / $totalCommission) * 100, 1) : 0.0,
                    'escrow_held' => (float) ($payment->escrow_held ?? 0),
                    'payout_estimate' => $payoutEstimate,
                    'cash_receivable' => (float) ($commission->cash_receivable ?? 0),
                    'transaction_count' => (int) ($payment->transaction_count ?? 0),
                    'rental_count' => (int) ($rental->rental_count ?? 0),
                ];
            })
            ->filter()
            ->values();

        return $rows->sort(function (array $left, array $right): int {
            return ($right['commission_total'] <=> $left['commission_total'])
                ?: ($right['gmv'] <=> $left['gmv']);
        })->values();
    }

    /**
     * Deret komisi untuk chart CSS bar.
     *
     * @param  array<string, mixed>  $filters
     * @return array{granularity: string, start: Carbon, end: Carbon, total: float, points: list<array{key: string, label: string, value: float, count: int}>}
     */
    public function getCommissionSeries(string $granularity, array $filters): array
    {
        $filters = $this->ensureNormalized($filters);
        $granularity = in_array($granularity, ['daily', 'weekly', 'monthly', 'yearly'], true)
            ? $granularity
            : 'daily';

        $today = Carbon::today();
        $buckets = [];

        if ($granularity === 'daily') {
            $start = $today->copy()->subDays(29)->startOfDay();
            $end = $today->copy()->endOfDay();

            for ($i = 0; $i < 30; $i++) {
                $date = $start->copy()->addDays($i);
                $key = $date->format('Y-m-d');
                $buckets[$key] = [
                    'key' => $key,
                    'label' => $date->isoFormat('D MMM'),
                    'value' => 0.0,
                    'count' => 0,
                ];
            }
        } elseif ($granularity === 'weekly') {
            $start = $today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(11)->startOfDay();
            $end = $today->copy()->endOfDay();

            for ($i = 0; $i < 12; $i++) {
                $week = $start->copy()->addWeeks($i);
                $key = $week->format('Y-m-d');
                $buckets[$key] = [
                    'key' => $key,
                    'label' => 'Mgg '.$week->isoFormat('D MMM'),
                    'value' => 0.0,
                    'count' => 0,
                ];
            }
        } elseif ($granularity === 'monthly') {
            $start = $today->copy()->startOfMonth()->subMonthsNoOverflow(11)->startOfDay();
            $end = $today->copy()->endOfDay();

            for ($i = 0; $i < 12; $i++) {
                $month = $start->copy()->addMonthsNoOverflow($i);
                $key = $month->format('Y-m');
                $buckets[$key] = [
                    'key' => $key,
                    'label' => $month->isoFormat('MMM YY'),
                    'value' => 0.0,
                    'count' => 0,
                ];
            }
        } else {
            $start = $today->copy()->startOfYear()->subYears(4)->startOfDay();
            $end = $today->copy()->endOfDay();

            for ($i = 0; $i < 5; $i++) {
                $year = $start->copy()->addYears($i);
                $key = $year->format('Y');
                $buckets[$key] = [
                    'key' => $key,
                    'label' => $key,
                    'value' => 0.0,
                    'count' => 0,
                ];
            }
        }

        $rows = $this->commissionQuery($filters, false)
            ->whereBetween('platform_commissions.recognized_at', [$start, $end])
            ->select(['platform_commissions.amount', 'platform_commissions.recognized_at'])
            ->get();

        foreach ($rows as $row) {
            $recognizedAt = Carbon::parse($row->recognized_at);

            $key = match ($granularity) {
                'weekly' => $recognizedAt->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
                'monthly' => $recognizedAt->format('Y-m'),
                'yearly' => $recognizedAt->format('Y'),
                default => $recognizedAt->format('Y-m-d'),
            };

            if (! isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['value'] += (float) $row->amount;
            $buckets[$key]['count']++;
        }

        return [
            'granularity' => $granularity,
            'start' => $start,
            'end' => $end,
            'total' => (float) collect($buckets)->sum('value'),
            'points' => array_values($buckets),
        ];
    }

    /**
     * Query transaksi terfilter. Dipakai tabel Livewire dan streaming CSV.
     *
     * @param  array<string, mixed>  $filters
     */
    public function transactionQuery(array $filters): Builder
    {
        $filters = $this->ensureNormalized($filters);

        $query = Payment::query()
            ->withoutGlobalScope('vendor')
            ->join('rentals', 'rentals.id', '=', 'payments.rental_id')
            ->join('vendors', 'vendors.id', '=', 'payments.vendor_id')
            ->leftJoin('platform_commissions', function (JoinClause $join): void {
                $join->on('platform_commissions.payment_id', '=', 'payments.id')
                    ->where('platform_commissions.status', '!=', 'void');
            })
            ->where('payments.status', 'paid');

        $this->applyPaymentFilters($query, $filters);

        return $query->select([
            'payments.id as payment_id',
            'payments.rental_id',
            'payments.vendor_id',
            'payments.payment_type',
            'payments.payment_method',
            'payments.payment_channel',
            'payments.amount',
            'payments.gateway_fee_amount',
            'payments.escrow_status',
            'payments.settlement_id',
            'payments.paid_at',
            'rentals.booking_code',
            'vendors.name as vendor_name',
            'platform_commissions.amount as commission_amount',
            'platform_commissions.status as commission_status',
        ]);
    }

    /**
     * Transaksi terbaru untuk tabel dashboard.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function getTransactions(array $filters, int $limit = 50): Collection
    {
        return $this->transactionQuery($filters)
            ->orderByDesc('payments.paid_at')
            ->orderByDesc('payments.id')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn ($row) => [
                'payment_id' => (int) $row->payment_id,
                'booking_code' => (string) $row->booking_code,
                'vendor_name' => (string) $row->vendor_name,
                'payment_type' => (string) $row->payment_type,
                'payment_method' => (string) $row->payment_method,
                'payment_channel' => $row->payment_channel ? (string) $row->payment_channel : null,
                'amount' => (float) $row->amount,
                'commission_amount' => (float) ($row->commission_amount ?? 0),
                'gateway_fee_amount' => (float) $row->gateway_fee_amount,
                'escrow_status' => (string) $row->escrow_status,
                'settlement_id' => $row->settlement_id !== null ? (int) $row->settlement_id : null,
                'paid_at' => $row->paid_at !== null ? Carbon::parse($row->paid_at) : null,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function aggregateSummary(array $filters): array
    {
        $paymentRow = $this->paymentQuery($filters)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payments.payment_type <> 'fine' THEN payments.amount ELSE 0 END), 0) as gross_revenue,
                COALESCE(SUM(CASE WHEN payments.payment_type <> 'fine' THEN payments.gateway_fee_amount ELSE 0 END), 0) as gateway_fee_total,
                COALESCE(SUM(CASE WHEN payments.payment_type = 'fine' THEN payments.amount ELSE 0 END), 0) as fine_gross,
                COALESCE(SUM(CASE WHEN payments.payment_type = 'fine' THEN payments.gateway_fee_amount ELSE 0 END), 0) as fine_fee_total,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT payments.vendor_id) as vendor_count
            ")
            ->first();

        $commission = (float) $this->commissionQuery($filters)->sum('platform_commissions.amount');
        $cashCommission = (float) $this->commissionQuery($filters)
            ->where('payments.payment_method', 'cash')
            ->sum('platform_commissions.amount');

        $escrowHeld = (float) $this->paymentQuery($filters)
            ->where('payments.escrow_status', 'held')
            ->sum('payments.amount');

        $gatewayFeeTotal = (float) ($paymentRow->gateway_fee_total ?? 0);
        $vendorCount = (int) ($paymentRow->vendor_count ?? 0);
        $transferFee = (float) config('monetization.settlement.transfer_fee', 3500);
        $transferFeeTotal = $transferFee * $vendorCount;

        return [
            'gross_revenue' => (float) ($paymentRow->gross_revenue ?? 0),
            'commission' => $commission,
            'cash_commission' => $cashCommission,
            'gateway_fee_total' => $gatewayFeeTotal,
            'fine_gross' => (float) ($paymentRow->fine_gross ?? 0),
            'fine_fee_total' => (float) ($paymentRow->fine_fee_total ?? 0),
            'escrow_held' => $escrowHeld,
            'transaction_count' => (int) ($paymentRow->transaction_count ?? 0),
            'vendor_count' => $vendorCount,
            'transfer_fee' => $transferFee,
            'transfer_fee_total' => $transferFeeTotal,
            'platform_net' => $commission - $gatewayFeeTotal - $transferFeeTotal,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paymentQuery(array $filters): Builder
    {
        $query = Payment::query()
            ->withoutGlobalScope('vendor')
            ->where('payments.status', 'paid');

        return $this->applyPaymentFilters($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function commissionQuery(array $filters, bool $applyDate = true): Builder
    {
        $query = PlatformCommission::query()
            ->withoutGlobalScope('vendor')
            ->join('payments', 'payments.id', '=', 'platform_commissions.payment_id')
            ->where('payments.status', 'paid')
            ->whereIn('platform_commissions.status', ['accrued', 'settled']);

        if ($filters['vendor_id'] !== null) {
            $query->where('platform_commissions.vendor_id', $filters['vendor_id']);
        }

        $query = $this->applyPaymentFilters($query, $filters, false);

        if ($applyDate) {
            if ($filters['start'] !== null) {
                $query->where('platform_commissions.recognized_at', '>=', $filters['start']);
            }

            if ($filters['end'] !== null) {
                $query->where('platform_commissions.recognized_at', '<=', $filters['end']);
            }
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyPaymentFilters(Builder $query, array $filters, bool $applyDate = true): Builder
    {
        if ($filters['vendor_id'] !== null) {
            $query->where('payments.vendor_id', $filters['vendor_id']);
        }

        if ($applyDate && $filters['start'] !== null) {
            $query->where('payments.paid_at', '>=', $filters['start']);
        }

        if ($applyDate && $filters['end'] !== null) {
            $query->where('payments.paid_at', '<=', $filters['end']);
        }

        if ($filters['payment_method'] !== null) {
            match ($filters['payment_method']) {
                'qris' => $query->where('payments.payment_channel', 'qris'),
                'cash' => $query->where('payments.payment_method', 'cash'),
                'transfer' => $query->where('payments.payment_method', 'transfer'),
                'midtrans' => $query->where('payments.payment_method', 'midtrans_snap'),
                default => null,
            };
        }

        if ($filters['escrow_status'] !== null) {
            $query->where('payments.escrow_status', $filters['escrow_status']);
        }

        if ($filters['payment_type'] !== null) {
            $query->where('payments.payment_type', $filters['payment_type']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sumCommission(array $filters): float
    {
        return (float) $this->commissionQuery($filters)->sum('platform_commissions.amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}|null
     */
    private function previousRange(array $filters): ?array
    {
        if (! $filters['start'] instanceof Carbon || ! $filters['end'] instanceof Carbon) {
            return null;
        }

        $period = $filters['period'] ?? 'custom';
        $today = Carbon::today();

        if ($period === 'this_month') {
            return [
                'start' => $today->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay(),
                'end' => $today->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay(),
            ];
        }

        if ($period === 'last_month') {
            return [
                'start' => $today->copy()->subMonthsNoOverflow(2)->startOfMonth()->startOfDay(),
                'end' => $today->copy()->subMonthsNoOverflow(2)->endOfMonth()->endOfDay(),
            ];
        }

        if ($period === 'this_year') {
            return [
                'start' => $today->copy()->subYear()->startOfYear()->startOfDay(),
                'end' => $today->copy()->subYear()->endOfYear()->endOfDay(),
            ];
        }

        $days = (int) $filters['start']->copy()->startOfDay()
            ->diffInDays($filters['end']->copy()->startOfDay()) + 1;

        $previousEnd = $filters['start']->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays(max(0, $days - 1))->startOfDay();

        return ['start' => $previousStart, 'end' => $previousEnd];
    }

    private function percentChange(float $current, float $previous): float
    {
        if (abs($previous) < 0.0001) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function ensureNormalized(array $filters): array
    {
        return array_key_exists('start', $filters) ? $filters : $this->normalizeFilters($filters);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function parseCustomRange(?string $startDate, ?string $endDate): array
    {
        if ($startDate === null && $endDate === null) {
            return [null, null];
        }

        try {
            $start = $startDate !== null && $startDate !== ''
                ? Carbon::parse($startDate)->startOfDay()
                : null;
            $end = $endDate !== null && $endDate !== ''
                ? Carbon::parse($endDate)->endOfDay()
                : null;
        } catch (\Exception) {
            return [null, null];
        }

        if ($start !== null && $end !== null && $start->gt($end)) {
            [$start, $end] = [
                $end->copy()->startOfDay(),
                $start->copy()->endOfDay(),
            ];
        }

        return [$start, $end];
    }
}
