<?php

namespace App\Livewire\Admin;

use App\Models\Vendor;
use App\Services\Finance\MonetizationMonitorService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionMonitor extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stage = '';

    public ?int $vendorId = null;

    public function mount(): void
    {
        $this->ensureSuperAdmin();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStage(): void
    {
        $this->resetPage();
    }

    public function updatedVendorId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'stage', 'vendorId']);
        $this->resetPage();
    }

    public function render(MonetizationMonitorService $monitorService): View
    {
        $this->ensureSuperAdmin();

        $filters = [
            'search' => $this->search,
            'vendor_id' => $this->vendorId,
            'stage' => $this->stage,
        ];

        $rentals = $monitorService->query($filters)
            ->latest('rentals.created_at')
            ->latest('rentals.id')
            ->paginate(12)
            ->through(fn ($rental): array => $monitorService->summarize($rental));

        return view('livewire.admin.transaction-monitor', [
            'rentals' => $rentals,
            'summary' => $monitorService->summary($filters),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'stageLabels' => MonetizationMonitorService::STAGE_LABELS,
        ]);
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
