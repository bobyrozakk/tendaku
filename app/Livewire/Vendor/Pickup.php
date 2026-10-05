<?php

namespace App\Livewire\Vendor;

use Livewire\Component;
use Livewire\WithFileUploads;

class Pickup extends Component
{
    use WithFileUploads;

    public $searchPickup = '';
    public $selectedPickupId = null;

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

    // Mock Orders with Status "Siap Pickup"
    public $pickupOrders = [
        [
            'id' => 'TND-2026-004',
            'customer_name' => 'Budi Santoso',
            'phone' => '0812-3456-7890',
            'rental_period' => '03 Okt 2026 - 05 Okt 2026 (2 Hari)',
            'payment_status' => 'Lunas (QRIS)',
            'status' => 'Siap Pickup',
            'allocated_units' => [
                ['item_id' => 101, 'name' => 'Tenda Dome 4P', 'barcode' => 'A-001', 'status' => 'Reserved'],
                ['item_id' => 102, 'name' => 'Tenda Dome 4P', 'barcode' => 'A-002', 'status' => 'Reserved'],
                ['item_id' => 103, 'name' => 'Carrier 60L', 'barcode' => 'CR-011', 'status' => 'Reserved'],
            ],
        ],
        [
            'id' => 'TND-2026-008',
            'customer_name' => 'Siti Rahmawati',
            'phone' => '0857-1122-3344',
            'rental_period' => '03 Okt 2026 - 06 Okt 2026 (3 Hari)',
            'payment_status' => 'Lunas (Transfer BCA)',
            'status' => 'Siap Pickup',
            'allocated_units' => [
                ['item_id' => 201, 'name' => 'Sleeping Bag Thermal', 'barcode' => 'SB-005', 'status' => 'Reserved'],
                ['item_id' => 202, 'name' => 'Matras Foil Camping', 'barcode' => 'MT-009', 'status' => 'Reserved'],
            ],
        ],
        [
            'id' => 'TND-2026-012',
            'customer_name' => 'Dedi Pratama',
            'phone' => '0813-9988-7766',
            'rental_period' => '03 Okt 2026 - 04 Okt 2026 (1 Hari)',
            'payment_status' => 'Lunas (E-Wallet)',
            'status' => 'Siap Pickup',
            'allocated_units' => [
                ['item_id' => 301, 'name' => 'Kompor Portable Camping', 'barcode' => 'KP-003', 'status' => 'Reserved'],
                ['item_id' => 302, 'name' => 'Nesting Set Rantang', 'barcode' => 'NST-014', 'status' => 'Reserved'],
            ],
        ],
    ];

    public function mount()
    {
        if (!empty($this->pickupOrders)) {
            $this->selectPickup($this->pickupOrders[0]['id']);
        }
    }

    public function selectPickup($id)
    {
        $this->selectedPickupId = $id;
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

    public function getSelectedOrderProperty()
    {
        return collect($this->pickupOrders)->firstWhere('id', $this->selectedPickupId);
    }

    public function getIsPhotoVerifiedProperty()
    {
        return $this->tenantPhotoUploaded || !empty($this->tenantPhoto);
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
        if (!$this->isPickupReady) {
            return;
        }

        $order = $this->getSelectedOrderProperty();
        if (!$order) {
            return;
        }

        $completedOrderId = $order['id'];
        $customerName = $order['customer_name'];

        // Update status order & remove from active pickup queue
        $this->pickupOrders = collect($this->pickupOrders)->reject(function ($o) use ($completedOrderId) {
            return $o['id'] === $completedOrderId;
        })->values()->toArray();

        $this->pickupSuccessMessage = "Serah Terima Berhasil! Transaksi {$completedOrderId} ({$customerName}) telah diperbarui menjadi 'Sedang Disewa'. Status unit fisik berubah menjadi 'Sedang Disewa'.";

        if (!empty($this->pickupOrders)) {
            $this->selectPickup($this->pickupOrders[0]['id']);
            $this->pickupSuccessMessage = "Serah Terima Berhasil! Transaksi {$completedOrderId} ({$customerName}) telah diperbarui menjadi 'Sedang Disewa'. Status unit fisik berubah menjadi 'Sedang Disewa'.";
        } else {
            $this->selectedPickupId = null;
        }
    }

    public function render()
    {
        $filteredOrders = collect($this->pickupOrders)->filter(function ($order) {
            if (empty($this->searchPickup)) {
                return true;
            }
            return stripos($order['id'], $this->searchPickup) !== false ||
                   stripos($order['customer_name'], $this->searchPickup) !== false;
        });

        return view('livewire.vendor.pickup', [
            'filteredOrders' => $filteredOrders,
        ]);
    }
}
