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
        if (! $this->vendorToConfirm) {
            return;
        }

        $vendor = Vendor::find($this->vendorToConfirm);
        if (! $vendor) {
            $this->feedbackMessage = 'Vendor tidak ditemukan.';
            $this->feedbackType = 'error';
            $this->cancelAction();

            return;
        }

        match ($this->actionType) {
            'approve' => $this->approveVendor($vendor),
            'suspend' => $this->suspendVendor($vendor),
            'delete' => $this->deleteVendor($vendor),
            default => null,
        };

        $this->cancelAction();
    }

    public function approveVendor(Vendor $vendor): void
    {
        $vendor->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->feedbackMessage = "Vendor '{$vendor->name}' berhasil disetujui (Approved). Toko kini aktif dan dapat beroperasi.";
        $this->feedbackType = 'success';
    }

    public function suspendVendor(Vendor $vendor): void
    {
        $vendor->update([
            'status' => 'suspended',
            'approved_by' => auth()->id(),
        ]);

        $this->feedbackMessage = "Operasional Vendor '{$vendor->name}' berhasil ditangguhkan (Suspended).";
        $this->feedbackType = 'warning';
    }

    public function deleteVendor(Vendor $vendor): void
    {
        $vendorName = $vendor->name;
        $vendor->delete();

        $this->feedbackMessage = "Data Vendor '{$vendorName}' berhasil dihapus.";
        $this->feedbackType = 'info';
    }

    public function render(): View
    {
        $query = Vendor::with(['approvedBy', 'staff' => function ($q) {
            $q->where('role', 'vendor_owner');
        }])
            ->withCount(['items', 'rentals']);

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

        return view('livewire.admin.vendor-manager', [
            'vendors' => $vendors,
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'suspendedCount' => $suspendedCount,
        ]);
    }
}
