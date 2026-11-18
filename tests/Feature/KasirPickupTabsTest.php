<?php

namespace Tests\Feature;

use App\Livewire\Kasir\Pickup;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KasirPickupTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_tabs_filter_ready_and_overdue_orders(): void
    {
        $vendor = Vendor::create([
            'name' => 'Toko Outdoor Malang',
            'slug' => 'toko-outdoor-malang',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10 Malang',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $cashier = User::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => 'vendor_admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
            'daily_rate' => 50000,
        ]);

        $unit = ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TND-01',
            'barcode' => 'BC-001',
            'status' => 'available',
            'condition' => 'good',
        ]);

        // Pesanan 1: Dijadwalkan tanggal kemarin (Belum Diambil / Terlambat)
        $overdueRental = Rental::create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-BELUM-AMBIL',
            'pickup_date' => Carbon::yesterday(),
            'return_date' => Carbon::tomorrow(),
            'rental_days' => 2,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'final_total' => 100000,
            'status' => 'confirmed',
        ]);

        RentalDetail::create([
            'rental_id' => $overdueRental->id,
            'master_item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'item_name_snapshot' => $item->name,
            'daily_rate_snapshot' => 50000,
            'quantity' => 1,
            'subtotal' => 100000,
            'is_returned' => false,
        ]);

        // Pesanan 2: Dijadwalkan tanggal hari ini (Siap Diambil)
        $readyRental = Rental::create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-SIAP-AMBIL',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::tomorrow(),
            'rental_days' => 1,
            'subtotal' => 50000,
            'grand_total' => 50000,
            'final_total' => 50000,
            'status' => 'confirmed',
        ]);

        RentalDetail::create([
            'rental_id' => $readyRental->id,
            'master_item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'item_name_snapshot' => $item->name,
            'daily_rate_snapshot' => 50000,
            'quantity' => 1,
            'subtotal' => 50000,
            'is_returned' => false,
        ]);

        $this->actingAs($cashier);

        // Uji default tab 'siap'
        Livewire::test(Pickup::class)
            ->assertSee('Siap Diambil')
            ->assertSee('Belum Diambil')
            ->assertSee('TDK-SIAP-AMBIL')
            ->assertDontSee('TDK-BELUM-AMBIL')
            // Beralih ke tab 'belum_diambil'
            ->call('setTab', 'belum_diambil')
            ->assertSee('TDK-BELUM-AMBIL')
            ->assertDontSee('TDK-SIAP-AMBIL');
    }
}
