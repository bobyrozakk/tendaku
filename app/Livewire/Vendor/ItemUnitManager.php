<?php

namespace App\Livewire\Vendor;

use Livewire\Component;

class ItemUnitManager extends Component
{
    public $search = '';
    public $showAddModal = false;

    // Form inputs
    public $newItemCode = '';
    public $newStatus = 'available';
    public $newPurchasePrice = '';
    
    // Mock Data
    public $units = [
        ['id' => 1, 'item_name' => 'Tenda Dome 4 Orang', 'unit_code' => 'TND-001', 'status' => 'available', 'purchase_price' => 500000],
        ['id' => 2, 'item_name' => 'Tenda Dome 4 Orang', 'unit_code' => 'TND-002', 'status' => 'rented', 'purchase_price' => 500000],
        ['id' => 3, 'item_name' => 'Sleeping Bag Polar', 'unit_code' => 'SB-101', 'status' => 'maintenance', 'purchase_price' => 150000],
        ['id' => 4, 'item_name' => 'Kompor Portable', 'unit_code' => 'KMP-055', 'status' => 'available', 'purchase_price' => 120000],
        ['id' => 5, 'item_name' => 'Matras Foil', 'unit_code' => 'MTR-202', 'status' => 'lost', 'purchase_price' => 35000],
    ];

    public function addUnit()
    {
        $this->validate([
            'newItemCode' => 'required|string',
            'newStatus' => 'required|string',
            'newPurchasePrice' => 'required|numeric'
        ]);

        $this->units[] = [
            'id' => max(array_column($this->units, 'id')) + 1,
            'item_name' => 'Barang Baru (Mock)',
            'unit_code' => $this->newItemCode,
            'status' => $this->newStatus,
            'purchase_price' => $this->newPurchasePrice,
        ];

        $this->reset(['newItemCode', 'newStatus', 'newPurchasePrice', 'showAddModal']);
    }

    public function deleteUnit($id)
    {
        $this->units = collect($this->units)->reject(fn($unit) => $unit['id'] == $id)->toArray();
    }

    public function updateStatus($unitId, $newStatus)
    {
        // Pastikan status yang dipilih tidak kosong
        if (!empty($newStatus)) {
            foreach ($this->units as &$unit) {
                if ($unit['id'] == $unitId) {
                    $unit['status'] = $newStatus;
                    break;
                }
            }
        }
    }
    public function render()
    {
        return view('livewire.vendor.item-unit-manager');
    }
}
