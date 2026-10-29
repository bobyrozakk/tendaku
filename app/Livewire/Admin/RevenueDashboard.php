<?php

namespace App\Livewire\Admin;

use App\Models\Vendor;
use App\Services\RevenueReportService;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class RevenueDashboard extends Component
{
    public string $period = 'this_month';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $selectedVendorId = null;

    public string $paymentMethod = '';

    public string $escrowStatus = '';

    public string $paymentType = '';

    public string $chartTab = 'daily';

    public function mount(): void
    {
        $this->ensureSuperAdmin();
        $this->applyPeriodDates();
    }

    public function updatedPeriod(): void
    {
        if ($this->period === 'custom') {
            $this->startDate ??= Carbon::today()->startOfMonth()->format('Y-m-d');
            $this->endDate ??= Carbon::today()->format('Y-m-d');

            return;
        }

        $this->applyPeriodDates();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'selectedVendorId',
            'paymentMethod',
            'escrowStatus',
            'paymentType',
            'chartTab',
        ]);

        $this->period = 'this_month';
        $this->applyPeriodDates();
    }

    public function render(RevenueReportService $revenueReportService): View
    {
        $this->ensureSuperAdmin();

        $filters = $this->filters();
        $summary = $revenueReportService->getSummary($filters);
        $kpis = $revenueReportService->getKpis($filters);
        $chart = $revenueReportService->getCommissionSeries($this->chartTab, $filters);
        $vendorSummaries = $revenueReportService->getVendorSummaries($filters);
        $transactions = $revenueReportService->getTransactions($filters, 50);
        $vendors = Vendor::orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.revenue-dashboard', [
            'summary' => $summary,
            'kpis' => $kpis,
            'chart' => $chart,
            'vendorSummaries' => $vendorSummaries,
            'transactions' => $transactions,
            'vendors' => $vendors,
            'exportUrl' => $this->exportUrl(),
            'paymentTypeLabels' => [
                'dp' => 'DP',
                'settlement' => 'Pelunasan',
                'fine' => 'Denda',
                'deposit' => 'Deposit',
                'deposit_refund' => 'Refund Deposit',
                'refund' => 'Refund',
            ],
            'paymentMethodLabels' => [
                'cash' => 'Tunai',
                'midtrans_snap' => 'Midtrans',
                'transfer' => 'Transfer',
            ],
            'escrowStatusLabels' => [
                'held' => 'Ditahan',
                'released' => 'Dicairkan',
                'refunded' => 'Dikembalikan',
                'void' => 'Void',
                'not_applicable' => 'Tidak berlaku',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'vendor_id' => $this->selectedVendorId,
            'payment_method' => $this->paymentMethod !== '' ? $this->paymentMethod : null,
            'escrow_status' => $this->escrowStatus !== '' ? $this->escrowStatus : null,
            'payment_type' => $this->paymentType !== '' ? $this->paymentType : null,
        ];
    }

    protected function exportUrl(): string
    {
        $query = array_filter([
            'period' => $this->period,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'vendor_id' => $this->selectedVendorId,
            'payment_method' => $this->paymentMethod,
            'escrow_status' => $this->escrowStatus,
            'payment_type' => $this->paymentType,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        return route('admin.pendapatan.export', $query);
    }

    protected function applyPeriodDates(): void
    {
        $today = Carbon::today();

        match ($this->period) {
            'today' => [
                $this->startDate = $today->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            'yesterday' => [
                $this->startDate = $today->copy()->subDay()->format('Y-m-d'),
                $this->endDate = $today->copy()->subDay()->format('Y-m-d'),
            ],
            '7_days' => [
                $this->startDate = $today->copy()->subDays(6)->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            '30_days' => [
                $this->startDate = $today->copy()->subDays(29)->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            'this_month' => [
                $this->startDate = $today->copy()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $today->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'last_month' => [
                $this->startDate = $today->copy()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $today->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d'),
            ],
            'this_year' => [
                $this->startDate = $today->copy()->startOfYear()->format('Y-m-d'),
                $this->endDate = $today->copy()->endOfYear()->format('Y-m-d'),
            ],
            'all_time' => [
                $this->startDate = null,
                $this->endDate = null,
            ],
            'custom' => null,
            default => null,
        };
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
