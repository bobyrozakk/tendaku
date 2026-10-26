<?php

namespace App\Livewire\Vendor;

use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\RentalDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ItemUnitManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterProduct = '';

    public string $filterStatus = 'all';

    public string $filterCondition = 'all';

    // State Modal
    public bool $showAddModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $selectedUnitId = null;

    // Form inputs
    public string $masterItemId = '';

    public string $unitCode = '';

    public string $barcode = '';

    public string $condition = 'excellent';

    public string $status = 'available';

    public ?string $purchaseDate = null;

    public string $purchasePrice = '';

    public string $notes = '';

    // Notifikasi aksi
    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success'; // 'success' | 'error'

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterProduct(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCondition(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->filterStatus = $tab;
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->showAddModal = true;
    }

    public function openEditModal(int $unitId): void
    {
        $this->resetValidation();
        $vendorId = (int) Auth::user()->vendor_id;

        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($unitId);

        $this->selectedUnitId = $unit->id;
        $this->masterItemId = (string) $unit->master_item_id;
        $this->unitCode = $unit->unit_code;
        $this->barcode = $unit->barcode ?? '';
        $this->condition = $unit->condition;
        $this->status = $unit->status;
        $this->purchaseDate = $unit->purchase_date ? $unit->purchase_date->format('Y-m-d') : null;
        $this->purchasePrice = $unit->purchase_price !== null ? (string) (int) $unit->purchase_price : '';
        $this->notes = $unit->notes ?? '';

        $this->showEditModal = true;
    }

    public function openDeleteModal(int $unitId): void
    {
        $vendorId = (int) Auth::user()->vendor_id;
        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($unitId);

        $this->selectedUnitId = $unit->id;
        $this->showDeleteModal = true;
    }

    public function generateBarcode(): void
    {
        $vendorId = (int) Auth::user()->vendor_id;
        do {
            $code = 'BC-'.rand(100000, 999999);
        } while (ItemUnit::withoutGlobalScope('vendor')->where('vendor_id', $vendorId)->where('barcode', $code)->exists());

        $this->barcode = $code;
    }

    public function autoGenerateUnitCode(): void
    {
        if (empty($this->masterItemId)) {
            return;
        }

        $vendorId = (int) Auth::user()->vendor_id;
        $product = MasterItem::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->find($this->masterItemId);

        if (! $product) {
            return;
        }

        $prefix = strtoupper(Str::limit(Str::slug($product->name, ''), 4, ''));
        $existingCount = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('master_item_id', $product->id)
            ->count();

        $nextNumber = str_pad((string) ($existingCount + 1), 3, '0', STR_PAD_LEFT);
        $candidate = "{$prefix}-{$nextNumber}";

        $counter = 1;
        while (ItemUnit::withoutGlobalScope('vendor')->where('vendor_id', $vendorId)->where('unit_code', $candidate)->exists()) {
            $nextNumber = str_pad((string) ($existingCount + 1 + $counter), 3, '0', STR_PAD_LEFT);
            $candidate = "{$prefix}-{$nextNumber}";
            $counter++;
        }

        $this->unitCode = $candidate;
    }

    public function addUnit(): void
    {
        $vendorId = (int) Auth::user()->vendor_id;

        $this->validate([
            'masterItemId' => [
                'required',
                'integer',
                Rule::exists('master_items', 'id')->where(fn ($q) => $q->where('vendor_id', $vendorId)->whereNull('deleted_at')),
            ],
            'unitCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('item_units', 'unit_code')->where(fn ($q) => $q->where('vendor_id', $vendorId)->whereNull('deleted_at')),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('item_units', 'barcode')->where(fn ($q) => $q->where('vendor_id', $vendorId)->whereNull('deleted_at')),
            ],
            'condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged', 'lost'])],
            'status' => ['required', Rule::in(['available', 'maintenance', 'retired'])],
            'purchaseDate' => ['nullable', 'date'],
            'purchasePrice' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'masterItemId.required' => 'Pilih produk induk untuk unit fisik ini.',
            'unitCode.required' => 'Kode unit wajib diisi.',
            'unitCode.unique' => 'Kode unit ini sudah digunakan pada toko Anda.',
            'barcode.unique' => 'Barcode ini sudah terdaftar pada unit lain di toko Anda.',
            'condition.required' => 'Kondisi fisik unit wajib dipilih.',
            'status.required' => 'Status operasional unit wajib dipilih.',
        ]);

        ItemUnit::create([
            'vendor_id' => $vendorId,
            'master_item_id' => (int) $this->masterItemId,
            'unit_code' => strtoupper(trim($this->unitCode)),
            'barcode' => ! empty($this->barcode) ? trim($this->barcode) : null,
            'condition' => $this->condition,
            'status' => $this->status,
            'purchase_date' => ! empty($this->purchaseDate) ? $this->purchaseDate : null,
            'purchase_price' => ! empty($this->purchasePrice) ? (float) $this->purchasePrice : null,
            'notes' => ! empty($this->notes) ? trim($this->notes) : null,
        ]);

        $this->feedbackMessage = 'Unit fisik baru berhasil ditambahkan!';
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
        $this->resetForm();
        $this->showAddModal = false;
    }

    public function updateUnit(): void
    {
        if (! $this->selectedUnitId) {
            return;
        }

        $vendorId = (int) Auth::user()->vendor_id;
        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($this->selectedUnitId);

        $this->validate([
            'masterItemId' => [
                'required',
                'integer',
                Rule::exists('master_items', 'id')->where(fn ($q) => $q->where('vendor_id', $vendorId)->whereNull('deleted_at')),
            ],
            'unitCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('item_units', 'unit_code')
                    ->where(fn ($q) => $q->where('vendor_id', $vendorId)->whereNull('deleted_at'))
                    ->ignore($unit->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('item_units', 'barcode')
                    ->where(fn ($q) => $q->where('vendor_id', $vendorId)->whereNull('deleted_at'))
                    ->ignore($unit->id),
            ],
            'condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged', 'lost'])],
            'status' => ['required', Rule::in(['available', 'rented', 'maintenance', 'retired'])],
            'purchaseDate' => ['nullable', 'date'],
            'purchasePrice' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Cek jika sedang disewa pelanggan, cegah pengubahan status sepihak ke available tanpa alur return kasir
        if ($unit->status === 'rented' && $this->status !== 'rented') {
            $isStillRented = RentalDetail::where('item_unit_id', $unit->id)
                ->where('is_returned', false)
                ->whereHas('rental', fn ($q) => $q->whereIn('status', ['confirmed', 'picked_up']))
                ->exists();

            if ($isStillRented) {
                $this->feedbackMessage = 'Unit sedang dalam masa sewa aktif pelanggan. Status harus diselesaikan melalui alur Pengembalian (Return) kasir.';
                $this->feedbackType = 'error';
                $this->dispatch('toast', [
                    'type' => 'danger',
                    'title' => 'Gagal',
                    'message' => $this->feedbackMessage,
                ]);

                return;
            }
        }

        $unit->update([
            'master_item_id' => (int) $this->masterItemId,
            'unit_code' => strtoupper(trim($this->unitCode)),
            'barcode' => ! empty($this->barcode) ? trim($this->barcode) : null,
            'condition' => $this->condition,
            'status' => $this->status,
            'purchase_date' => ! empty($this->purchaseDate) ? $this->purchaseDate : null,
            'purchase_price' => ! empty($this->purchasePrice) ? (float) $this->purchasePrice : null,
            'notes' => ! empty($this->notes) ? trim($this->notes) : null,
        ]);

        $this->feedbackMessage = 'Data unit fisik berhasil diperbarui.';
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
        $this->showEditModal = false;
    }

    public function deleteUnit(): void
    {
        if (! $this->selectedUnitId) {
            return;
        }

        $vendorId = (int) Auth::user()->vendor_id;
        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($this->selectedUnitId);

        // Validasi: jangan hapus unit jika sedang disewa
        if ($unit->status === 'rented') {
            $this->feedbackMessage = 'Unit tidak dapat dihapus karena saat ini berstatus sedang disewa (rented).';
            $this->feedbackType = 'error';
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Gagal',
                'message' => $this->feedbackMessage,
            ]);
            $this->showDeleteModal = false;

            return;
        }

        $hasActiveRental = RentalDetail::where('item_unit_id', $unit->id)
            ->where('is_returned', false)
            ->whereHas('rental', fn ($q) => $q->whereIn('status', ['confirmed', 'picked_up']))
            ->exists();

        if ($hasActiveRental) {
            $this->feedbackMessage = 'Unit tidak dapat dihapus karena terikat pada transaksi sewa aktif.';
            $this->feedbackType = 'error';
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Gagal',
                'message' => $this->feedbackMessage,
            ]);
            $this->showDeleteModal = false;

            return;
        }

        $unit->delete();

        $this->feedbackMessage = 'Unit fisik berhasil dihapus.';
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
        $this->showDeleteModal = false;
        $this->selectedUnitId = null;
    }

    public function updateStatusQuick(int $unitId, string $newStatus): void
    {
        $vendorId = (int) Auth::user()->vendor_id;
        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->findOrFail($unitId);

        if (! in_array($newStatus, ['available', 'maintenance', 'retired'], true)) {
            return;
        }

        if ($unit->status === 'rented') {
            $this->feedbackMessage = 'Status unit yang sedang disewa tidak dapat diubah secara manual di sini.';
            $this->feedbackType = 'error';

            return;
        }

        $unit->update(['status' => $newStatus]);
        $this->feedbackMessage = "Status unit {$unit->unit_code} diubah menjadi {$newStatus}.";
        $this->feedbackType = 'success';
    }

    protected function resetForm(): void
    {
        $this->selectedUnitId = null;
        $this->masterItemId = '';
        $this->unitCode = '';
        $this->barcode = '';
        $this->condition = 'excellent';
        $this->status = 'available';
        $this->purchaseDate = null;
        $this->purchasePrice = '';
        $this->notes = '';
    }

    public function render()
    {
        $vendorId = (int) Auth::user()->vendor_id;

        $products = MasterItem::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->orderBy('name')
            ->get();

        $query = ItemUnit::withoutGlobalScope('vendor')
            ->with(['masterItem'])
            ->where('vendor_id', $vendorId);

        // Filter Search
        if (! empty($this->search)) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('unit_code', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%")
                    ->orWhereHas('masterItem', fn ($itemQ) => $itemQ->where('name', 'like', "%{$s}%"));
            });
        }

        // Filter Produk
        if (! empty($this->filterProduct)) {
            $query->where('master_item_id', $this->filterProduct);
        }

        // Filter Status Tab
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Filter Kondisi
        if ($this->filterCondition !== 'all') {
            $query->where('condition', $this->filterCondition);
        }

        $units = $query->orderBy('unit_code')->paginate(12);

        // Counts per status untuk tab pills
        $baseCounts = ItemUnit::withoutGlobalScope('vendor')->where('vendor_id', $vendorId);
        $totalAll = (clone $baseCounts)->count();
        $totalAvailable = (clone $baseCounts)->where('status', 'available')->count();
        $totalRented = (clone $baseCounts)->where('status', 'rented')->count();
        $totalMaintenance = (clone $baseCounts)->where('status', 'maintenance')->count();
        $totalRetired = (clone $baseCounts)->where('status', 'retired')->count();

        return view('livewire.vendor.item-unit-manager', [
            'units' => $units,
            'products' => $products,
            'totalAll' => $totalAll,
            'totalAvailable' => $totalAvailable,
            'totalRented' => $totalRented,
            'totalMaintenance' => $totalMaintenance,
            'totalRetired' => $totalRetired,
        ]);
    }
}
