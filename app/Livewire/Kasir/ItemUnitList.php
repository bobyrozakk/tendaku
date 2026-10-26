<?php

namespace App\Livewire\Kasir;

use App\Models\ItemUnit;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ItemUnitList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = 'all';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
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

    public function render()
    {
        $vendorId = (int) Auth::user()->vendor_id;

        $query = ItemUnit::withoutGlobalScope('vendor')
            ->with(['masterItem'])
            ->where('vendor_id', $vendorId);

        // Filter Search (kode unit, barcode, nama produk)
        if (! empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('unit_code', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%")
                    ->orWhereHas('masterItem', fn ($itemQ) => $itemQ->where('name', 'like', "%{$s}%"));
            });
        }

        // Filter Status Tab (available, rented, maintenance, retired)
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        $units = $query->orderBy('unit_code')->paginate(12);

        // Counts per status untuk kartu metrik & tab pills
        $baseCounts = ItemUnit::withoutGlobalScope('vendor')->where('vendor_id', $vendorId);
        $totalAll = (clone $baseCounts)->count();
        $totalAvailable = (clone $baseCounts)->where('status', 'available')->count();
        $totalRented = (clone $baseCounts)->where('status', 'rented')->count();
        $totalMaintenance = (clone $baseCounts)->where('status', 'maintenance')->count();

        return view('livewire.kasir.item-unit-list', [
            'units' => $units,
            'totalAll' => $totalAll,
            'totalAvailable' => $totalAvailable,
            'totalRented' => $totalRented,
            'totalMaintenance' => $totalMaintenance,
        ]);
    }
}
