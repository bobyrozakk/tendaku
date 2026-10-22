<?php

namespace App\Livewire\Kasir;

use App\Models\ItemUnit;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnFlow extends Component
{
    // Tab Management ('return' or 'history')
    public $activeTab = 'return';

    public $activeOrderId = null;

    public $searchOrder = '';

    // History Search & Filter
    public $searchHistory = '';

    public $statusFilter = 'all';

    // Form Inputs for Inspection
    public $inspectionNotes = [];

    public $damageFees = [];

    public $itemNotes = [];

    public $returnedItems = [];

    // Modal Pelunasan Denda & Modal Struk
    public $showFineModal = false;

    public $finePaymentMethod = 'cash'; // 'cash' or 'qris'

    public $cashTendered = 0;

    public $fineQrisActive = false;

    public $finePaidSuccess = false;

    // Struk Print Modal
    public $showReceiptModal = false;

    public $receiptData = null;

    public function mount()
    {
        $this->loadDefaultActive();
    }

    public function updatedSearchOrder()
    {
        $this->loadDefaultActive();
    }

    private function loadDefaultActive(): void
    {
        $vendorId = Auth::user()?->vendor_id;
        $first = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'picked_up')
            ->when($this->searchOrder, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchOrder}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchOrder}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchOrder}%"));
                });
            })
            ->first();

        if ($first && ! $this->activeOrderId) {
            $this->selectRental($first->id);
        }
    }

    public function selectRental($rentalId)
    {
        $this->activeOrderId = $rentalId;
        $this->inspectionNotes = [];
        $this->damageFees = [];
        $this->itemNotes = [];
        $this->returnedItems = [];
        $this->showFineModal = false;
        $this->finePaidSuccess = false;

        $order = $this->getActiveOrderProperty();
        if ($order) {
            foreach ($order->details as $detail) {
                $this->inspectionNotes[$detail->id] = $detail->condition_after ?? 'good';
                $this->damageFees[$detail->id] = (float) ($detail->damage_fee ?? 0);
                $this->itemNotes[$detail->id] = $detail->checklist_notes ?? '';
                if ($detail->is_returned) {
                    $this->returnedItems[$detail->id] = true;
                }
            }
        }
    }

    public function getActiveOrderProperty()
    {
        if (! $this->activeOrderId) {
            return null;
        }

        $vendorId = Auth::user()?->vendor_id;

        return Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->with(['details.masterItem', 'details.itemUnit', 'customer'])
            ->find($this->activeOrderId);
    }

    public function setCondition($detailId, $condition)
    {
        $this->inspectionNotes[$detailId] = $condition;
        $this->resetErrorBag("damageFees.{$detailId}");

        if ($condition === 'good') {
            $this->damageFees[$detailId] = 0;
        }
    }

    public function markItemReturned($detailId)
    {
        $condition = $this->inspectionNotes[$detailId] ?? 'good';
        $fee = (float) ($this->damageFees[$detailId] ?? 0);

        // Validasi: jika kondisi rusak atau hilang, denda wajib diisi > 0 agar tidak kecolongan
        if (in_array($condition, ['damaged', 'lost']) && $fee <= 0) {
            $this->addError("damageFees.{$detailId}", 'Denda wajib diisi minimal Rp 1 bila kondisi barang '.($condition === 'damaged' ? 'Rusak' : 'Hilang').'.');

            return;
        }

        $this->resetErrorBag("damageFees.{$detailId}");
        $this->returnedItems[$detailId] = true;

        // Auto update individual detail in DB
        $detail = RentalDetail::find($detailId);
        if ($detail) {
            $detail->update([
                'condition_after' => $condition,
                'damage_fee' => $fee,
                'checklist_notes' => $this->itemNotes[$detailId] ?? null,
                'is_returned' => true,
                'returned_at' => Carbon::now(),
            ]);

            // Jika barang rusak/hilang atau dikembalikan, update item_unit juga
            if ($detail->item_unit_id) {
                $unitStatus = in_array($condition, ['damaged', 'lost']) ? 'maintenance' : 'available';
                ItemUnit::withoutGlobalScope('vendor')
                    ->where('id', $detail->item_unit_id)
                    ->update([
                        'status' => $unitStatus,
                        'condition' => in_array($condition, ['damaged', 'lost']) ? 'damaged' : 'good',
                    ]);
            }
        }
    }

    public function completeReturn()
    {
        $order = $this->getActiveOrderProperty();
        if (! $order) {
            return;
        }

        $allReturned = $order->details->every(fn ($detail) => ! empty($this->returnedItems[$detail->id]));
        if (! $allReturned) {
            $this->addError('general', 'Semua barang harus diinspeksi dan dikonfirmasi sebelum menyelesaikan return.');

            return;
        }

        // Hitung total denda
        $totalLateFee = (float) $this->calculatedLateFee;
        $totalDamageFee = (float) $this->totalDamageFee;
        $totalFines = $totalLateFee + $totalDamageFee;

        // Jika ada denda dan belum dilunasi, buka modal pelunasan denda
        if ($totalFines > 0 && ! $this->finePaidSuccess) {
            $this->showFineModal = true;

            return;
        }

        // Eksekusi penyelesaian return di Database
        DB::transaction(function () use ($order, $totalLateFee, $totalDamageFee, $totalFines) {
            $user = Auth::user();

            // 1. Simpan denda & status return pada detail
            foreach ($order->details as $detail) {
                $cond = $this->inspectionNotes[$detail->id] ?? 'good';
                $damage = (float) ($this->damageFees[$detail->id] ?? 0);

                $detail->update([
                    'condition_after' => $cond,
                    'damage_fee' => $damage,
                    'is_returned' => true,
                    'returned_at' => $detail->returned_at ?? Carbon::now(),
                ]);

                if ($detail->item_unit_id) {
                    $unitStatus = in_array($cond, ['damaged', 'lost']) ? 'maintenance' : 'available';
                    ItemUnit::withoutGlobalScope('vendor')
                        ->where('id', $detail->item_unit_id)
                        ->update([
                            'status' => $unitStatus,
                            'condition' => in_array($cond, ['damaged', 'lost']) ? 'damaged' : 'good',
                        ]);
                }
            }

            // 2. Jika ada pembayaran denda, catat ke tabel payments
            if ($totalFines > 0) {
                Payment::withoutGlobalScope('vendor')->create([
                    'rental_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                    'received_by' => $user->id,
                    'payment_type' => 'fine',
                    'payment_method' => $this->finePaymentMethod === 'cash' ? 'cash' : 'midtrans_snap',
                    'amount' => $totalFines,
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                ]);
            }

            // 3. Update master Rental: KTP returned & status completed
            $order->update([
                'status' => 'completed',
                'actual_return_date' => Carbon::today(),
                'returned_at' => Carbon::now(),
                'returned_by' => $user->id,
                'ktp_collateral_status' => 'returned',
                'ktp_returned_at' => Carbon::now(),
                'total_late_fee' => $totalLateFee,
                'total_damage_fee' => $totalDamageFee,
                'final_total' => $order->grand_total + $totalFines,
            ]);
        });

        // Siapkan struk untuk dicetak
        $this->prepareReceipt($order);

        session()->flash('message', "Pengembalian {$order->booking_code} selesai! KTP telah diserahkan kembali & status transaksi 'completed'.");

        $this->activeOrderId = null;
        $this->showFineModal = false;
        $this->loadDefaultActive();
    }

    public function processFinePayment()
    {
        $this->finePaidSuccess = true;
        $this->completeReturn();
    }

    public function prepareReceipt($order)
    {
        $order->refresh();
        $this->receiptData = [
            'booking_code' => $order->booking_code,
            'customer_name' => $order->customer?->name ?? $order->guest_name,
            'phone' => $order->customer?->phone ?? $order->guest_phone,
            'date' => Carbon::now()->format('d M Y, H:i'),
            'vendor_name' => $order->vendor?->name ?? 'Tendaku Outdoor',
            'vendor_address' => $order->vendor?->address ?? 'Malang, Jawa Timur',
            'items' => $order->details->map(fn ($d) => [
                'name' => $d->masterItem?->name,
                'unit_code' => $d->itemUnit?->unit_code ?? '-',
                'barcode' => $d->itemUnit?->barcode ?? '-',
                'condition' => $d->condition_after ?? 'good',
                'damage_fee' => (float) $d->damage_fee,
            ])->toArray(),
            'grand_total' => (float) $order->grand_total,
            'late_fee' => (float) $order->total_late_fee,
            'damage_fee' => (float) $order->total_damage_fee,
            'final_total' => (float) $order->final_total,
            'ktp_status' => 'Sudah Dikembalikan ke Penyewa',
            'cashier_name' => Auth::user()?->name ?? 'Kasir Tendaku',
        ];

        $this->showReceiptModal = true;
    }

    public function getCalculatedLateFeeProperty(): float
    {
        $order = $this->getActiveOrderProperty();
        if (! $order) {
            return 0;
        }

        if (Carbon::today()->gt($order->return_date)) {
            $lateDays = Carbon::today()->diffInDays($order->return_date);
            // Jumlah late fee per day dari snapshot detail
            $dailyLateRate = $order->details->sum('late_fee_per_day_snapshot') ?: 25000;

            return (float) ($lateDays * $dailyLateRate);
        }

        return (float) ($order->total_late_fee ?? 0);
    }

    public function getTotalDamageFeeProperty(): float
    {
        return array_sum(array_map('floatval', $this->damageFees));
    }

    public function getTotalChargeProperty(): float
    {
        return $this->calculatedLateFee + $this->totalDamageFee;
    }

    public function render()
    {
        $vendorId = Auth::user()?->vendor_id;

        // 1. Transaksi aktif yang siap diretur (status: picked_up)
        $activeRentals = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'picked_up')
            ->when($this->searchOrder, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchOrder}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchOrder}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchOrder}%"));
                });
            })
            ->with(['details.masterItem', 'details.itemUnit', 'customer'])
            ->latest('id')
            ->get();

        // 2. Riwayat transaksi yang sudah selesai (status: completed / returned)
        $historyQuery = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['completed', 'returned'])
            ->when($this->searchHistory, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchHistory}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchHistory}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchHistory}%"));
                });
            })
            ->when($this->statusFilter === 'Denda', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('total_late_fee', '>', 0)
                        ->orWhere('total_damage_fee', '>', 0);
                });
            })
            ->when($this->statusFilter === 'Selesai', function ($q) {
                $q->where('total_late_fee', '<=', 0)
                    ->where('total_damage_fee', '<=', 0);
            })
            ->with(['details.masterItem', 'customer', 'payments'])
            ->latest('returned_at');

        $filteredHistory = $historyQuery->get();

        return view('livewire.kasir.return-flow', [
            'activeRentalsList' => $activeRentals,
            'filteredHistory' => $filteredHistory,
        ]);
    }
}
