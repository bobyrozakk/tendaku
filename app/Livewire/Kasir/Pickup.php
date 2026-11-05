<?php

namespace App\Livewire\Kasir;

use App\Models\ItemUnit;
use App\Models\Rental;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Pickup extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $searchPickup = '';

    public $selectedPickupId = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearchPickup(): void
    {
        $this->resetPage();
    }

    // Tenant Photo & KTP Verification
    public $tenantPhoto = null;

    public $tenantPhotoUploaded = false;

    public $tenantKtpUploaded = false;

    // Checklist Flags
    public $checkIdentity = false;

    public $checkUnits = false;

    public $checkHandover = false;

    // Success Banner
    public $pickupSuccessMessage = null;

    public function mount()
    {
        $this->loadDefaultPickup();
    }

    private function loadDefaultPickup(): void
    {
        $first = $this->pickupOrdersQuery()->first();
        if ($first && ! $this->selectedPickupId) {
            $this->selectPickup((string) $first->id);
        }
    }

    public function selectPickup($id)
    {
        $this->selectedPickupId = (string) $id;
        $this->tenantPhoto = null;
        $this->tenantPhotoUploaded = false;
        $this->tenantKtpUploaded = false;
        $this->checkIdentity = false;
        $this->checkUnits = false;
        $this->checkHandover = false;
        $this->pickupSuccessMessage = null;
    }

    public function updatedTenantPhoto()
    {
        $this->validate([
            'tenantPhoto' => 'nullable|image|max:5120', // max 5MB
        ]);
        $this->tenantPhotoUploaded = true;
    }

    public function markTenantKtpUploaded()
    {
        $this->tenantKtpUploaded = true;
    }

    public function getSelectedOrderProperty(): ?array
    {
        if (! $this->selectedPickupId) {
            return null;
        }

        $vendorId = Auth::user()?->vendor_id;

        $rental = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'confirmed')
            ->where(function ($q) {
                if (is_numeric($this->selectedPickupId)) {
                    $q->where('id', $this->selectedPickupId);
                } else {
                    $q->where('booking_code', $this->selectedPickupId);
                }
            })
            ->with([
                'customer:id,name,phone',
                'details:id,rental_id,master_item_id,item_unit_id',
                'details.masterItem:id,name',
                'details.itemUnit:id,unit_code,barcode',
                'payments' => fn ($p) => $p->where('status', 'paid'),
            ])
            ->first();

        if (! $rental) {
            return null;
        }

        return $this->formatRentalToArray($rental);
    }

    public function getIsPhotoVerifiedProperty()
    {
        return $this->tenantPhotoUploaded || ! empty($this->tenantPhoto);
    }

    public function getIsPickupReadyProperty()
    {
        $selected = $this->selectedOrder;
        $isFullyPaid = $selected ? ($selected['is_fully_paid'] ?? false) : false;

        return $this->isPhotoVerified
            && $this->tenantKtpUploaded
            && $this->checkIdentity
            && $this->checkUnits
            && $this->checkHandover
            && $isFullyPaid;
    }

    public function acceptCashPayment(PaymentService $paymentService): void
    {
        if (! $this->selectedPickupId) {
            return;
        }

        $vendorId = Auth::user()?->vendor_id;

        $rental = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'confirmed')
            ->where(function ($q) {
                if (is_numeric($this->selectedPickupId)) {
                    $q->where('id', $this->selectedPickupId);
                } else {
                    $q->where('booking_code', $this->selectedPickupId);
                }
            })
            ->first();

        if (! $rental) {
            return;
        }

        $remaining = $paymentService->calculateRemainingAmount($rental);
        if ($remaining <= 0) {
            return;
        }

        $paymentService->createCashRemainingPayment($rental, Auth::id());

        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Pelunasan Berhasil',
            'message' => 'Pelunasan tunai sebesar Rp '.number_format($remaining, 0, ',', '.').' berhasil dicatat.',
        ]);
    }

    public function confirmPickup()
    {
        if (! $this->isPickupReady || ! $this->selectedPickupId) {
            return;
        }

        $vendorId = Auth::user()?->vendor_id;
        $user = Auth::user();

        $rental = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'confirmed')
            ->where(function ($q) {
                if (is_numeric($this->selectedPickupId)) {
                    $q->where('id', $this->selectedPickupId);
                } else {
                    $q->where('booking_code', $this->selectedPickupId);
                }
            })
            ->with(['details.itemUnit', 'customer'])
            ->first();

        if (! $rental) {
            return;
        }

        $paymentService = app(PaymentService::class);
        if ($paymentService->calculateRemainingAmount($rental) > 0) {
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Tagihan Belum Lunas',
                'message' => 'Tagihan sewa belum lunas. Terima pelunasan tunai di kasir terlebih dahulu.',
            ]);

            return;
        }

        $bookingCode = $rental->booking_code;
        $customerName = $rental->customer?->name ?? $rental->guest_name;

        // Simpan foto jaminan penyewa bila diupload
        $photoPath = null;
        if ($this->tenantPhoto) {
            $photoPath = $this->tenantPhoto->store('ktp_collateral', 'public');
        }

        DB::transaction(function () use ($rental, $user, $photoPath) {
            // 1. Update status Rental menjadi picked_up (Sedang Disewa)
            $rental->update([
                'status' => 'picked_up',
                'picked_up_at' => Carbon::now(),
                'picked_up_by' => $user->id,
                'ktp_collateral_status' => 'held',
                'ktp_received_at' => Carbon::now(),
                'ktp_collateral_photo_url' => $photoPath ?? $rental->ktp_collateral_photo_url,
            ]);

            // 2. Update status fisik item_units menjadi rented
            $unitIds = $rental->details->pluck('item_unit_id')->filter()->all();
            if (! empty($unitIds)) {
                ItemUnit::withoutGlobalScope('vendor')
                    ->whereIn('id', $unitIds)
                    ->update([
                        'status' => 'rented',
                    ]);
            }
        });

        $successText = "Serah Terima Berhasil! Transaksi {$bookingCode} ({$customerName}) telah diperbarui menjadi 'Sedang Disewa'. Unit fisik telah diserahkan.";

        // Reset state & pilih order antrean berikutnya bila ada
        $this->selectedPickupId = null;
        $nextOrder = $this->pickupOrdersQuery()->first();
        if ($nextOrder) {
            $this->selectPickup((string) $nextOrder->id);
        }

        // Simpan pesan berhasil SETELAH selectPickup agar tidak ter-reset menjadi null
        $this->pickupSuccessMessage = $successText;
        $this->dispatch('close-pickup-modal');
    }

    private function pickupOrdersQuery()
    {
        $vendorId = Auth::user()?->vendor_id;

        return Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'confirmed')
            ->when($this->searchPickup, function ($q) {
                $s = trim($this->searchPickup);
                $q->where(function ($sub) use ($s) {
                    $sub->where('booking_code', 'like', "%{$s}%")
                        ->orWhere('guest_name', 'like', "%{$s}%")
                        ->orWhere('guest_phone', 'like', "%{$s}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
                });
            })
            ->with([
                'customer:id,name,phone',
                'details:id,rental_id,master_item_id,item_unit_id',
                'details.masterItem:id,name',
                'details.itemUnit:id,unit_code,barcode',
                'payments' => fn ($p) => $p->where('status', 'paid'),
            ])
            ->orderBy('pickup_date', 'asc')
            ->orderBy('id', 'asc');
    }

    private function formatRentalToArray(Rental $rental): array
    {
        $customerName = $rental->customer?->name ?? $rental->guest_name;
        $phone = $rental->customer?->phone ?? $rental->guest_phone ?? '-';
        $period = $rental->pickup_date->format('d M Y').' - '.$rental->return_date->format('d M Y').' ('.$rental->rental_days.' Hari)';

        $grandTotal = (int) $rental->grand_total;
        $paidAmount = (int) $rental->payments
            ->where('status', 'paid')
            ->filter(fn ($p) => in_array($p->payment_type, ['full', 'dp', 'remaining'], true))
            ->sum('amount');

        $remainingAmount = max(0, $grandTotal - $paidAmount);
        $isFullyPaid = $remainingAmount === 0;

        $paidPayment = $rental->payments->where('status', 'paid')->first();
        $methodName = 'Online';
        if ($paidPayment) {
            $rawMethod = $paidPayment->payment_method ?? $paidPayment->channel ?? 'online';
            $methodName = match ($rawMethod) {
                'midtrans_snap' => 'Midtrans/Online',
                'qris' => 'QRIS',
                'cash' => 'Tunai/Cash',
                'transfer' => 'Transfer Bank',
                default => strtoupper((string) $rawMethod),
            };
        }

        if ($isFullyPaid) {
            $paymentStatus = "Lunas ({$methodName})";
        } else {
            $paymentStatus = 'DP (Sisa Rp '.number_format($remainingAmount, 0, ',', '.').')';
        }

        $allocatedUnits = [];
        foreach ($rental->details as $d) {
            $unit = $d->itemUnit;
            $allocatedUnits[] = [
                'item_id' => $d->id,
                'name' => $d->masterItem?->name ?? 'Barang',
                'barcode' => $unit?->barcode ?? $unit?->unit_code ?? 'Belum dialokasikan',
                'status' => $unit ? 'Ready' : 'Pending',
            ];
        }

        return [
            'id' => $rental->booking_code,
            'real_id' => $rental->id,
            'customer_name' => $customerName,
            'phone' => $phone,
            'rental_period' => $period,
            'grand_total' => $grandTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'is_fully_paid' => $isFullyPaid,
            'payment_status' => $paymentStatus,
            'status' => 'Siap Pickup',
            'allocated_units' => $allocatedUnits,
        ];
    }

    public function render()
    {
        $paginated = $this->pickupOrdersQuery()->paginate(10);
        $totalCount = $paginated->total();

        // Transform pagination collection agar tetap kompatibel dengan blade yang ada
        $transformed = $paginated->through(fn ($rental) => $this->formatRentalToArray($rental));

        return view('livewire.kasir.pickup', [
            'filteredOrders' => $transformed,
            'totalCount' => $totalCount,
        ]);
    }
}
