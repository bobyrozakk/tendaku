<?php

namespace App\Livewire\Customer;

use App\Models\CampingPackage;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PackageIndex extends Component
{
    use WithPagination;

    #[Url(as: 'search', history: true, keep: false)]
    public string $search = '';

    #[Url(as: 'vendor_id', history: true, keep: false)]
    public ?int $vendorId = null;

    #[Url(as: 'sort', history: true, keep: false)]
    public string $sort = 'latest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedVendorId(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'vendorId', 'sort']);
        $this->sort = 'latest';
        $this->resetPage();
    }

    public function render(): View
    {
        $vendors = Vendor::whereIn('status', ['active', 'approved'])
            ->whereHas('campingPackages', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $search = trim($this->search);

        $query = CampingPackage::publicCatalog()
            ->with(['vendor', 'packageItems.masterItem', 'masterItems'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->vendorId, fn ($q) => $q->where('vendor_id', $this->vendorId));

        match ($this->sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'duration_asc' => $query->orderBy('duration_days', 'asc'),
            default => $query->latest(),
        };

        return view('livewire.customer.package-index', [
            'packages' => $query->paginate(9),
            'vendors' => $vendors,
        ]);
    }
}
