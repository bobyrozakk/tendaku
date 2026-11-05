<?php

namespace App\Livewire\Admin;

use App\Models\Vendor;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class VendorManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = 'all';

    public ?int $vendorToConfirm = null;

    public string $actionType = ''; // 'approve' | 'suspend' | 'delete'

    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success';

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->ensureSuperAdmin();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function confirmAction(int $vendorId, string $action): void
    {
        $this->ensureSuperAdmin();

        $this->vendorToConfirm = $vendorId;
        $this->actionType = $action;
    }

    public function cancelAction(): void
    {
        $this->vendorToConfirm = null;
        $this->actionType = '';
    }

    public function executeAction(): void
    {
        $this->ensureSuperAdmin();

        if (! $this->vendorToConfirm) {
            return;
        }

        $vendor = Vendor::find($this->vendorToConfirm);
        if (! $vendor) {
            $this->feedbackMessage = 'Vendor tidak ditemukan.';
            $this->feedbackType = 'danger';
            $this->cancelAction();

            return;
        }

        match ($this->actionType) {
            'approve' => $this->approveVendor($vendor),
            'suspend' => $this->suspendVendor($vendor),
            'delete' => $this->deleteVendor($vendor),
            default => null,
        };

        $this->dispatch('vendor-updated');
        $this->cancelAction();
    }

    protected function approveVendor(Vendor $vendor): void
    {
        $vendor->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->feedbackMessage = "Vendor '{$vendor->name}' berhasil disetujui (Approved). Toko kini aktif dan dapat beroperasi.";
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
    }

    protected function suspendVendor(Vendor $vendor): void
    {
        $vendor->update([
            'status' => 'suspended',
            'approved_by' => auth()->id(),
        ]);

        $this->feedbackMessage = "Operasional Vendor '{$vendor->name}' berhasil ditangguhkan (Suspended).";
        $this->feedbackType = 'warning';
        $this->dispatch('toast', [
            'type' => 'warning',
            'title' => 'Perhatian',
            'message' => $this->feedbackMessage,
        ]);
    }

    protected function deleteVendor(Vendor $vendor): void
    {
        $vendorName = $vendor->name;
        $vendor->delete();

        $this->feedbackMessage = "Data Vendor '{$vendorName}' berhasil dihapus.";
        $this->feedbackType = 'info';
        $this->dispatch('toast', [
            'type' => 'info',
            'title' => 'Informasi',
            'message' => $this->feedbackMessage,
        ]);
    }

    public function render(): View
    {
        $this->ensureSuperAdmin();

        $query = Vendor::withCount(['items', 'rentals']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if (! empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $vendors = $query->orderByDesc('created_at')->paginate(10);

        $pendingCount = Vendor::where('status', 'pending')->count();
        $activeCount = Vendor::where('status', 'active')->count();
        $suspendedCount = Vendor::where('status', 'suspended')->count();
        $totalCount = Vendor::count();

        return view('livewire.admin.vendor-manager', [
            'vendors' => $vendors,
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'suspendedCount' => $suspendedCount,
            'totalCount' => $totalCount,
        ]);
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
