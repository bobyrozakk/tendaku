<?php

namespace App\Livewire\Vendor;

use Livewire\Component;

class ReturnFlow extends Component
{
    // Tab Management ('return' or 'history')
    public $activeTab = 'return';

    public $activeOrder = null;
    public $searchOrder = '';
    
    // History Search & Filter
    public $searchHistory = '';
    public $statusFilter = 'all';

    // Form Inputs for Inspection
    public $inspectionNotes = [];
    public $damageFees = [];
    
    // Mock Active Rentals (Orders currently rented out)
    public $activeRentals = [
        [
            'id' => 'RNT-9001',
            'customer' => 'Budi Santoso',
            'due_date' => '2026-10-02',
            'is_late' => true,
            'late_fee' => 50000,
            'ktp_held' => true,
            'items' => [
                ['id' => 1, 'name' => 'Tenda Dome 4 Orang', 'barcode' => 'TD-04-002', 'returned' => false, 'condition' => 'good'],
                ['id' => 2, 'name' => 'Carrier 60L', 'barcode' => 'CR-60-011', 'returned' => false, 'condition' => 'good']
            ]
        ],
        [
            'id' => 'RNT-9002',
            'customer' => 'Siti Aminah',
            'due_date' => '2026-10-03',
            'is_late' => false,
            'late_fee' => 0,
            'ktp_held' => true,
            'items' => [
                ['id' => 3, 'name' => 'Kompor Portable', 'barcode' => 'KP-02-001', 'returned' => false, 'condition' => 'good']
            ]
        ]
    ];

    // Mock Completed History
    public $historyTransactions = [
        [
            'id' => 'RNT-8890',
            'customer' => 'Ahmad Fauzi',
            'rental_period' => '28 Sep - 30 Sep 2026',
            'total' => 350000,
            'status' => 'Selesai & Dikembalikan',
            'date' => '30 Sep 2026',
            'items' => '1x Tenda Dome 4P, 2x Sleeping Bag'
        ],
        [
            'id' => 'RNT-8885',
            'customer' => 'Dewi Lestari',
            'rental_period' => '25 Sep - 27 Sep 2026',
            'total' => 180000,
            'status' => 'Selesai & Dikembalikan',
            'date' => '27 Sep 2026',
            'items' => '1x Kompor Portable, 1x Nesting'
        ],
        [
            'id' => 'RNT-8872',
            'customer' => 'Reza Rahadian',
            'rental_period' => '20 Sep - 23 Sep 2026',
            'total' => 520000,
            'status' => 'Selesai (Ada Denda Rusak)',
            'date' => '23 Sep 2026',
            'items' => '1x Carrier Eiger 60L'
        ]
    ];

    public function selectRental($rentalId)
    {
        $this->activeOrder = collect($this->activeRentals)->firstWhere('id', $rentalId);
        $this->inspectionNotes = [];
        $this->damageFees = [];
        
        if ($this->activeOrder) {
            foreach ($this->activeOrder['items'] as $item) {
                $this->inspectionNotes[$item['id']] = 'good';
                $this->damageFees[$item['id']] = 0;
            }
        }
    }

    public function markItemReturned($itemId)
    {
        if (!$this->activeOrder) return;
        
        $items = $this->activeOrder['items'];
        foreach ($items as &$item) {
            if ($item['id'] === $itemId) {
                $item['returned'] = true;
                $item['condition'] = $this->inspectionNotes[$itemId] ?? 'good';
            }
        }
        $this->activeOrder['items'] = $items;
    }

    public function completeReturn()
    {
        if (!$this->activeOrder) return;

        $allReturned = collect($this->activeOrder['items'])->every(fn($item) => $item['returned']);
        if (!$allReturned) {
            $this->addError('general', 'Semua barang harus diinspeksi dan dikembalikan.');
            return;
        }

        // Pindahkan ke riwayat secara otomatis saat selesai
        array_unshift($this->historyTransactions, [
            'id' => $this->activeOrder['id'],
            'customer' => $this->activeOrder['customer'],
            'rental_period' => 'Hari ini',
            'total' => 250000 + $this->totalCharge,
            'status' => 'Selesai & Dikembalikan',
            'date' => date('d M Y'),
            'items' => count($this->activeOrder['items']) . ' Item Alat Camping'
        ]);

        $this->activeRentals = collect($this->activeRentals)->reject(function ($rental) {
            return $rental['id'] === $this->activeOrder['id'];
        })->toArray();
        
        $this->activeOrder = null;
        session()->flash('message', 'Pengembalian berhasil diselesaikan! KTP pelanggan telah diserahkan kembali.');
    }

    public function getTotalDamageFeeProperty()
    {
        return array_sum(array_map('floatval', $this->damageFees));
    }
    
    public function getTotalChargeProperty()
    {
        $lateFee = $this->activeOrder ? $this->activeOrder['late_fee'] : 0;
        return $lateFee + $this->totalDamageFee;
    }

    public function render()
    {
        // Filter history based on search and status
        $filteredHistory = collect($this->historyTransactions)->filter(function ($item) {
            $matchSearch = empty($this->searchHistory) || 
                stripos($item['id'], $this->searchHistory) !== false || 
                stripos($item['customer'], $this->searchHistory) !== false;
            
            $matchStatus = $this->statusFilter === 'all' || stripos($item['status'], $this->statusFilter) !== false;

            return $matchSearch && $matchStatus;
        });

        return view('livewire.vendor.return-flow', [
            'filteredHistory' => $filteredHistory
        ]);
    }
}