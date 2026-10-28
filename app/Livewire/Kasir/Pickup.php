<?php

namespace App\Livewire\Kasir;

use App\Models\ItemUnit;
use App\Models\Rental;
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
                'payments' => fn ($p) => $p->where('status', 'paid')->select(['id', 'rental_id', 'payment_method', 'status']),
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
        return $this->isPhotoVerified
            && $this->tenantKtpUploaded
            && $this->checkIdentity
            && $this->checkUnits
            && $this->checkHandover;
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
                'payments' => fn ($p) => $p->where('status', 'paid')->select(['id', 'rental_id', 'payment_method', 'status']),
            ])
            ->orderBy('pickup_date', 'asc')
            ->orderBy('id', 'asc');
    }

    private function formatRentalToArray(Rental $rental): array
    {
        $customerName = $rental->customer?->name ?? $rental->guest_name;
        $phone = $rental->customer?->phone ?? $rental->guest_phone ?? '-';
        $period = $rental->pickup_date->format('d M Y').' - '.$rental->return_date->format('d M Y').' ('.$rental->rental_days.' Hari)';

        $paymentStatus = 'Lunas';
        $paidPayment = $rental->payments->first();
        if ($paidPayment) {
            $methodName = match ($paidPayment->payment_method) {
                'midtrans_snap' => 'Midtrans/Online',
                'qris' => 'QRIS',
                'cash' => 'Tunai/Cash',
                'transfer' => 'Transfer Bank',
                default => strtoupper($paidPayment->payment_method),
            };
            $paymentStatus = "Lunas ({$methodName})";
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
