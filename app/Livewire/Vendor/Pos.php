<?php

namespace App\Livewire\Vendor;

use Livewire\Component;

class Pos extends Component
{
    public $activeTab = 'pending'; // all, pending, ready
    public $selectedOrder = null;
    
    // Modal state for scanning
    public $showScanModal = false;
    public $modalBarcode = '';
    public $scanError = '';
    public $scanSuccessMessage = '';

    // Mock KPI Data (Removed Stock Cepat)
    public $kpi = [
        'pending' => 4,
        'pickup_today' => 14,
        'return_today' => 9,
    ];

    // Mock Orders Data
    public $orders = [
        [
            'id' => 'ORD-1001',
            'customer' => 'Budi Santoso',
            'tier' => 'Member Gold',
            'duration' => '2 Hari (12-14 Okt)',
            'status' => 'pending',
            'payment_status' => 'Lunas',
            'items' => [
                ['name' => 'Tenda Dome 4P', 'qty' => 2, 'allocated_units' => ['A-001', 'A-002'], 'scanned_units' => [], 'status' => 'Reserved'],
                ['name' => 'Carrier 60L', 'qty' => 1, 'allocated_units' => ['CR-011'], 'scanned_units' => [], 'status' => 'Reserved']
            ]
        ],
        [
            'id' => 'ORD-1002',
            'customer' => 'Siti Aminah',
            'tier' => 'Reguler',
            'duration' => '1 Hari (12-13 Okt)',
            'status' => 'pending',
            'payment_status' => 'Lunas',
            'items' => [
                ['name' => 'Kompor Portable', 'qty' => 2, 'allocated_units' => ['KP-001', 'KP-002'], 'scanned_units' => [], 'status' => 'Reserved']
            ]
        ],
        [
            'id' => 'ORD-1003',
            'customer' => 'Andi Wijaya',
            'tier' => 'Member Silver',
            'duration' => '3 Hari (12-15 Okt)',
            'status' => 'ready', // Siap Pickup
            'payment_status' => 'Lunas',
            'items' => [
                ['name' => 'Sleeping Bag', 'qty' => 3, 'allocated_units' => ['SB-100', 'SB-101', 'SB-102'], 'scanned_units' => ['SB-100', 'SB-101', 'SB-102'], 'status' => 'Siap Pickup']
            ]
        ]
    ];

    public function selectOrder($orderId)
    {
        $this->selectedOrder = collect($this->orders)->firstWhere('id', $orderId);
        $this->scanError = '';
        $this->scanSuccessMessage = '';
        $this->showScanModal = false;
        $this->modalBarcode = '';
    }

    public function openScanModal()
    {
        $this->showScanModal = true;
        $this->modalBarcode = '';
        $this->scanError = '';
        $this->scanSuccessMessage = '';
    }

    public function processScan()
    {
        if (!$this->selectedOrder) return;
        
        $barcode = trim($this->modalBarcode);
        if (empty($barcode)) return;

        $items = $this->selectedOrder['items'];
        $foundAndValid = false;
        
        foreach ($items as &$item) {
            // Check if this barcode is part of the allocated units and not yet scanned
            if (in_array($barcode, $item['allocated_units']) && !in_array($barcode, $item['scanned_units'])) {
                $item['scanned_units'][] = $barcode;
                $foundAndValid = true;
                break;
            }
        }
        
        if ($foundAndValid) {
            $this->selectedOrder['items'] = $items;
            $this->scanSuccessMessage = '✓ Sesuai';
            $this->scanError = '';
            $this->modalBarcode = '';
        } else {
            $this->scanError = '⚠ Barcode tidak sesuai atau sudah discan.';
            $this->scanSuccessMessage = '';
        }
    }

    public function approveOrder($orderId)
    {
        // This is "Konfirmasi Persiapan"
        $this->orders = collect($this->orders)->map(function ($order) use ($orderId) {
            if ($order['id'] === $orderId) {
                $order['status'] = 'ready';
                foreach ($order['items'] as &$item) {
                    $item['status'] = 'Siap Pickup';
                }
            }
            return $order;
        })->toArray();
        
        $this->kpi['pending']--;
        if ($this->selectedOrder && $this->selectedOrder['id'] === $orderId) {
            $this->selectedOrder['status'] = 'ready';
            foreach ($this->selectedOrder['items'] as &$item) {
                $item['status'] = 'Siap Pickup';
            }
        }
    }

    public function getFilteredOrdersProperty()
    {
        return collect($this->orders)->filter(function ($order) {
            if ($this->activeTab === 'all') return true;
            return $order['status'] === $this->activeTab;
        })->values()->toArray();
    }

    public function render()
    {
        return view('livewire.vendor.pos');
    }
}

