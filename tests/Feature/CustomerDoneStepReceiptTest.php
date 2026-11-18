<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDoneStepReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_return_receipt_on_done_step(): void
    {
        $vendor = Vendor::create([
            'name' => 'Tendaku Outdoor Malang',
            'slug' => 'tendaku-outdoor-malang',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10 Malang',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $cashier = User::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kasir Budi',
            'role' => 'vendor_admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Pelanggan Setia',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome Arpenaz 4.1',
            'slug' => 'tenda-dome-arpenaz-41',
            'daily_rate' => 60000,
        ]);

        $unit = ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TND-01',
            'barcode' => 'BC-889900',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'returned_by' => $cashier->id,
            'booking_code' => 'TDK-DONE-RCPT',
            'pickup_date' => Carbon::yesterday(),
            'return_date' => Carbon::today(),
            'rental_days' => 1,
            'subtotal' => 60000,
            'grand_total' => 60000,
            'final_total' => 60000,
            'status' => 'completed',
            'returned_at' => Carbon::now(),
            'completed_at' => Carbon::now(),
            'ktp_collateral_status' => 'returned',
            'ktp_returned_at' => Carbon::now(),
        ]);

        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'item_name_snapshot' => $item->name,
            'daily_rate_snapshot' => 60000,
            'quantity' => 1,
            'subtotal' => 60000,
            'condition_after' => 'good',
            'damage_fee' => 0,
            'is_returned' => true,
            'returned_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($customer)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertOk();
        $response->assertSee('Struk Pengembalian');
        $response->assertSee('Inspeksi');
        $response->assertSee('Tendaku Outdoor Malang');
        $response->assertSee('TDK-DONE-RCPT');
        $response->assertSee('Kasir Budi');
        $response->assertSee('Tenda Dome Arpenaz 4.1');
        $response->assertSee('BC-889900');
        $response->assertSee('Jaminan KTP: Sudah Dikembalikan ke Penyewa');
        $response->assertSee('Cetak Struk');
    }
}
