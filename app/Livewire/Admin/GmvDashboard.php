<?php

namespace App\Livewire\Admin;

use App\Models\Vendor;
use App\Services\GmvService;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class GmvDashboard extends Component
{
    public string $period = 'this_month';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $selectedVendorId = null;

    public function mount(): void
    {
        $this->applyPeriodDates();
    }

    public function updatedPeriod(): void
    {
        $this->applyPeriodDates();
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
                $this->startDate = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            'all_time' => [
                $this->startDate = null,
                $this->endDate = null,
            ],
            default => null, // custom dates preserved
        };
    }

    public function render(GmvService $gmvService): View
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : null;
        $end = $this->endDate ? Carbon::parse($this->endDate) : null;

        $gmvToday = $gmvService->calculateToday($this->selectedVendorId);
        $dailyGrowth = $gmvService->calculateDailyGrowth($this->selectedVendorId);

        $gmvThisMonth = $gmvService->calculateThisMonth($this->selectedVendorId);
        $monthlyGrowth = $gmvService->calculateMonthlyGrowth($this->selectedVendorId);

        $filteredGmv = $gmvService->calculateTotal($this->selectedVendorId, $start, $end);
        $filteredTransactionCount = $gmvService->countTransactions($this->selectedVendorId, $start, $end);

        $vendorRankings = $gmvService->getGmvByVendor($start, $end);
        $dailyTrend = $gmvService->getDailyTrend(7, $this->selectedVendorId);

        $totalVendors = Vendor::count();
        $pendingVendorsCount = Vendor::where('status', 'pending')->count();
        $activeVendorsCount = Vendor::where('status', 'active')->count();

        $allVendors = Vendor::orderBy('name')->get(['id', 'name', 'status']);

        return view('livewire.admin.gmv-dashboard', [
            'gmvToday' => $gmvToday,
            'dailyGrowth' => $dailyGrowth,
            'gmvThisMonth' => $gmvThisMonth,
            'monthlyGrowth' => $monthlyGrowth,
            'filteredGmv' => $filteredGmv,
            'filteredTransactionCount' => $filteredTransactionCount,
            'vendorRankings' => $vendorRankings,
            'dailyTrend' => $dailyTrend,
            'totalVendors' => $totalVendors,
            'pendingVendorsCount' => $pendingVendorsCount,
            'activeVendorsCount' => $activeVendorsCount,
            'allVendors' => $allVendors,
        ]);
    }
}
