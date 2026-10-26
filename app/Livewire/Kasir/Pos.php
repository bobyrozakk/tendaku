<?php

namespace App\Livewire\Kasir;

use App\Models\ItemUnit;
use App\Models\Rental;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Pos extends Component
{
    public $activeTab = 'pending'; // 'pending' or 'completed'

    public $searchOrder = '';

    public $selectedOrderId = null;

    // Modal state for barcode scanning
    public $showScanModal = false;

    public $modalBarcode = '';

    public $scanError = '';

    public $scanSuccessMessage = '';

    // Array of scanned barcode IDs/codes per rental_detail: [detail_id => [scanned_barcode]]
    public $scannedUnits = [];

    public function mount()
    {
        $this->loadDefaultOrder();
    }

    public function updatedSearchOrder()
    {
        $this->loadDefaultOrder();
    }

    public function updatedActiveTab()
    {
        $this->selectedOrderId = null;
        $this->loadDefaultOrder();
    }

    private function loadDefaultOrder(): void
    {
        $order = $this->filteredOrdersQuery()->first();
        if ($order && ! $this->selectedOrderId) {
            $this->selectOrder($order->id);
        }
    }

    public function selectOrder($orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->scanError = '';
        $this->scanSuccessMessage = '';
        $this->showScanModal = false;
        $this->modalBarcode = '';

        $order = $this->getSelectedOrderProperty();
        if ($order && ! isset($this->scannedUnits[$order->id])) {
            $this->scannedUnits[$order->id] = [];
            // Jika pesanan sudah confirmed / completed / returned, otomatis semua unit sudah terverifikasi
            if (in_array($order->status, ['confirmed', 'picked_up', 'completed', 'returned'])) {
                foreach ($order->details as $d) {
                    if ($d->item_unit_id && $d->itemUnit?->barcode) {
                        $this->scannedUnits[$order->id][$d->id] = true;
                    }
                }
            }
        }
    }

    public function getSelectedOrderProperty()
    {
        if (! $this->selectedOrderId) {
            return null;
        }

        $vendorId = Auth::user()?->vendor_id;

        return Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->with(['details.masterItem', 'details.itemUnit', 'customer'])
            ->find($this->selectedOrderId);
    }

    public function openScanModal()
    {
        $this->showScanModal = true;
        $this->modalBarcode = '';
        $this->scanError = '';
        $this->scanSuccessMessage = '';
    }

    public function quickFillBarcode($barcode)
    {
        $this->modalBarcode = $barcode;
        $this->processScan();
    }

    public function getEligibleUnitsProperty()
    {
        $order = $this->getSelectedOrderProperty();
        if (! $order) {
            return [];
        }

        $vendorId = Auth::user()?->vendor_id;
        $masterItemIds = $order->details->pluck('master_item_id')->unique();

        return ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('master_item_id', $masterItemIds)
            ->where(function ($q) {
                $q->where('status', 'available')
                    ->orWhereIn('id', $this->getSelectedOrderProperty()->details->pluck('item_unit_id')->filter());
            })
            ->with('masterItem')
            ->get();
    }

    public function processScan()
    {
        $order = $this->getSelectedOrderProperty();
        if (! $order) {
            return;
        }

        $barcodeInput = strtoupper(trim($this->modalBarcode));
        if (empty($barcodeInput)) {
            return;
        }

        $vendorId = Auth::user()?->vendor_id;

        // Cari ItemUnit berdasarkan barcode ATAU unit_code toko
        $unit = ItemUnit::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where(function ($q) use ($barcodeInput) {
                $q->where('barcode', $barcodeInput)
                    ->orWhere('unit_code', $barcodeInput);
            })
            ->first();

        if (! $unit) {
            $this->scanError = "⚠ Barcode / Kode Unit '{$barcodeInput}' tidak ditemukan di sistem toko Anda.";
            $this->scanSuccessMessage = '';

            return;
        }

        // Cek apakah ada rental_detail pesanan ini yang unitnya adalah unit ini, ATAU cocok dengan master_item jika belum terisi
        $matchedDetail = null;
        foreach ($order->details as $detail) {
            // 1. Jika detail sudah dialokasikan ke unit ini
            if ($detail->item_unit_id === $unit->id) {
                $matchedDetail = $detail;
                break;
            }
        }

        // 2. Jika belum cocok langsung tapi jenis barang sama dan detail belum memiliki unit yang tervalidasi
        if (! $matchedDetail) {
            foreach ($order->details as $detail) {
                if ($detail->master_item_id === $unit->master_item_id) {
                    $isAlreadyScanned = ! empty($this->scannedUnits[$order->id][$detail->id]);
                    if (! $isAlreadyScanned) {
                        $matchedDetail = $detail;
                        // Pasangkan unit fisik ini ke detail pesanan
                        $matchedDetail->update([
                            'item_unit_id' => $unit->id,
                            'condition_before' => $unit->condition ?: 'good',
                        ]);
                        break;
                    }
                }
            }
        }

        if (! $matchedDetail) {
            $this->scanError = "⚠ Unit {$unit->unit_code} ({$unit->barcode}) tidak cocok dengan item pesanan atau sudah dicocokkan.";
            $this->scanSuccessMessage = '';

            return;
        }

        // Tandai detail ini sudah dicocokkan
        if (! isset($this->scannedUnits[$order->id])) {
            $this->scannedUnits[$order->id] = [];
        }
        $this->scannedUnits[$order->id][$matchedDetail->id] = true;

        $this->scanSuccessMessage = "✓ Cocok: Unit {$unit->unit_code} ({$unit->barcode}) berhasil dicocokkan!";
        $this->scanError = '';
        $this->modalBarcode = '';
    }

    public function approveOrder($orderId)
    {
        $vendorId = Auth::user()?->vendor_id;
        $order = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->with('details')
            ->find($orderId);

        if (! $order) {
            return;
        }

        // Validasi: pastikan semua item di detail pesanan telah discan / dicocokkan fisiknya
        $allScanned = $order->details->every(function ($d) use ($order) {
            return ! empty($d->item_unit_id) && ! empty($this->scannedUnits[$order->id][$d->id]);
        });

        if (! $allScanned) {
            $this->scanError = '⚠ Harap scan dan cocokkan barcode untuk semua unit fisik sebelum menyetujui pesanan!';

            return;
        }

        // Update status order menjadi confirmed (Siap Pickup/Disewa)
        $order->update([
            'status' => 'confirmed',
        ]);

        $msg = "Pesanan {$order->booking_code} telah tervalidasi via barcode & siap diserahkan (Siap Pickup)!";
        session()->flash('pos_message', $msg);
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $msg,
        ]);
    }

    private function filteredOrdersQuery()
    {
        $vendorId = Auth::user()?->vendor_id;

        return Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->when($this->activeTab === 'pending', function ($q) {
                $q->where('status', 'pending');
            })
            ->when($this->activeTab === 'completed', function ($q) {
                $q->whereIn('status', ['confirmed', 'picked_up', 'completed', 'returned']);
            })
            ->when($this->searchOrder, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchOrder}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchOrder}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchOrder}%"));
                });
            })
            ->with(['details.masterItem', 'details.itemUnit', 'customer'])
            ->latest('id');
    }

    public function getKpiProperty(): array
    {
        $vendorId = Auth::user()?->vendor_id;

        $pending = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'pending')
            ->count();

        $completed = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['confirmed', 'picked_up', 'completed', 'returned'])
            ->count();

        return [
            'pending' => $pending,
            'completed' => $completed,
        ];
    }

    public function render()
    {
        $orders = $this->filteredOrdersQuery()->get();

        return view('livewire.kasir.pos', [
            'orders' => $orders,
            'kpi' => $this->kpi,
            'eligibleUnits' => $this->eligibleUnits,
        ]);
    }
}
