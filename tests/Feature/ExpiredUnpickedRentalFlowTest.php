<?php

namespace Tests\Feature;

use App\Livewire\Kasir\Pickup;
use App\Livewire\Kasir\ReturnFlow;
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

class ExpiredUnpickedRentalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_unpicked_rental_moves_from_pickup_to_return_flow(): void
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
            'status' => 'rented',
            'condition' => 'good',
        ]);

        // Pesanan kedaluwarsa: Disewa tgl 7, kembali tgl 9, sekarang sudah tgl 10 (return_date < today)
        $expiredRental = Rental::create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-EXPIRED-999',
            'pickup_date' => Carbon::today()->subDays(4),
            'return_date' => Carbon::today()->subDays(1),
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'final_total' => 150000,
            'status' => 'confirmed',
        ]);

        RentalDetail::create([
            'rental_id' => $expiredRental->id,
            'master_item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'item_name_snapshot' => $item->name,
            'daily_rate_snapshot' => 50000,
            'quantity' => 1,
            'subtotal' => 150000,
            'is_returned' => false,
        ]);

        $this->actingAs($cashier);

        // 1. Verifikasi di Kasir Pickup: Tidak boleh muncul di 'siap' maupun 'belum_diambil' karena sudah kedaluwarsa
        Livewire::test(Pickup::class)
            ->assertDontSee('TDK-EXPIRED-999')
            ->call('setTab', 'belum_diambil')
            ->assertDontSee('TDK-EXPIRED-999');

        // 2. Verifikasi di Kasir ReturnFlow: Harus muncul di antrean retur dengan label Kedaluwarsa
        Livewire::test(ReturnFlow::class)
            ->assertSee('TDK-EXPIRED-999')
            ->assertSee('Kedaluwarsa (Tidak Diambil)')
            ->call('selectRental', $expiredRental->id)
            ->assertSee('Pesanan tidak pernah diambil oleh penyewa')
            // Coba panggil setCondition ke 'damaged' atau 'lost' harus diabaikan / tetap good
            ->call('setCondition', $expiredRental->details->first()->id, 'damaged')
            ->call('markItemReturned', $expiredRental->details->first()->id)
            // Selesaikan transaksi kedaluwarsa
            ->call('completeReturn');

        // 3. Verifikasi status database
        $expiredRental->refresh();
        $this->assertEquals('completed', $expiredRental->status);
        $this->assertEquals(0, (float) $expiredRental->total_late_fee);
        $this->assertEquals(0, (float) $expiredRental->total_damage_fee);

        $unit->refresh();
        $this->assertEquals('available', $unit->status);
        $this->assertEquals('good', $unit->condition);
    }
}
