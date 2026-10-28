<?php

namespace App\Livewire\Kasir;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ItemUnitList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = 'all';

    public string $filterCategory = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->filterStatus = $tab;
        $this->resetPage();
    }

    public function moveToAvailable(int $unitId): void
    {
        $vendorId = (int) Auth::user()->vendor_id;

        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($unitId);

        $unit->update([
            'status' => 'available',
            'condition' => in_array($unit->condition, ['damaged', 'lost']) ? 'good' : $unit->condition,
        ]);

        $msg = "Unit {$unit->unit_code} selesai diperbaiki dan siap disewakan kembali (Tersedia).";
        session()->flash('success', $msg);
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $msg,
        ]);
    }

    public function moveToMaintenance(int $unitId): void
    {
        $vendorId = (int) Auth::user()->vendor_id;

        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($unitId);

        if ($unit->status === 'rented') {
            $msg = "Unit {$unit->unit_code} sedang disewa pelanggan dan tidak bisa langsung dipindahkan ke perbaikan.";
            session()->flash('error', $msg);
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Gagal',
                'message' => $msg,
            ]);

            return;
        }

        $unit->update([
            'status' => 'maintenance',
        ]);

        $msg = "Unit {$unit->unit_code} berhasil dipindahkan ke status Perbaikan/Servis.";
        session()->flash('success', $msg);
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $msg,
        ]);
    }

    /**
     * Daftar kategori vendor — di-cache agar tidak re-query tiap render.
     *
     * @return Collection<int, ItemCategory>
     */
    #[Computed(cache: true, seconds: 60)]
    public function categories(): Collection
    {
        return ItemCategory::withoutGlobalScope('vendor')
            ->where('vendor_id', (int) Auth::user()->vendor_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Status counts dalam 1 query GROUP BY, disesuaikan dengan filter kategori yang sedang aktif (tidak termasuk retired).
     *
     * @return array{all: int, available: int, rented: int, maintenance: int}
     */
    #[Computed]
    public function statusCounts(): array
    {
        $base = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', (int) Auth::user()->vendor_id)
            ->where('status', '!=', 'retired');

        if (! empty($this->filterCategory)) {
            $base->whereHas('masterItem', fn ($itemQ) => $itemQ->where('category_id', $this->filterCategory));
        }

        $rows = $base
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $available = (int) ($rows['available'] ?? 0);
        $rented = (int) ($rows['rented'] ?? 0);
        $maintenance = (int) ($rows['maintenance'] ?? 0);

        return [
            'all' => $available + $rented + $maintenance,
            'available' => $available,
            'rented' => $rented,
            'maintenance' => $maintenance,
        ];
    }

    public function render()
    {
        $vendorId = (int) Auth::user()->vendor_id;

        $query = ItemUnit::withoutGlobalScope('vendor')
            ->with([
                'masterItem:id,name,daily_rate,category_id',
                'masterItem.category:id,name',
            ])
            ->where('vendor_id', $vendorId)
            ->where('status', '!=', 'retired');

        // Filter Search (kode unit, barcode, nama produk)
        if (! empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('unit_code', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%")
                    ->orWhereHas('masterItem', fn ($itemQ) => $itemQ->where('name', 'like', "%{$s}%"));
            });
        }

        // Filter Status Tab
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Filter Kategori
        if (! empty($this->filterCategory)) {
            $query->whereHas('masterItem', fn ($itemQ) => $itemQ->where('category_id', $this->filterCategory));
        }

        $units = $query->orderBy('unit_code')->paginate(12);

        $counts = $this->statusCounts;

        return view('livewire.kasir.item-unit-list', [
            'units' => $units,
            'categories' => $this->categories,
            'totalAll' => $counts['all'],
            'totalAvailable' => $counts['available'],
            'totalRented' => $counts['rented'],
            'totalMaintenance' => $counts['maintenance'],
        ]);
    }
}
