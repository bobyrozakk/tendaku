<?php

namespace Tests\Feature;

use App\Livewire\Kasir\NotificationBell;
use App\Livewire\Kasir\ReturnFlow;
use App\Livewire\Vendor\ItemUnitManager;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\NotificationLog;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LostItemFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $owner;

    protected User $cashier;

    protected User $customer;

    protected MasterItem $item;

    protected ItemUnit $unit;

    protected Rental $rental;

    protected RentalDetail $detail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Toko Outdoor Barokah',
            'slug' => 'toko-outdoor-barokah',
            'phone' => '081234567890',
            'address' => 'Jl. Soekarno Hatta No. 9 Malang',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $this->owner = User::factory()->create([
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_owner',
            'phone' => '081234567891',
        ]);

        $this->cashier = User::factory()->create([
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_admin',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $this->item = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome Arpenaz 4.1',
            'slug' => 'tenda-dome-arpenaz-41',
            'daily_rate' => 50000,
        ]);

        $this->unit = ItemUnit::create([
            'vendor_id' => $this->vendor->id,
            'master_item_id' => $this->item->id,
            'unit_code' => 'TND-001',
            'barcode' => 'BC-111111',
            'status' => 'rented',
            'condition' => 'good',
        ]);

        $this->rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TDK-LOST-TEST',
            'pickup_date' => Carbon::yesterday(),
            'return_date' => Carbon::today(),
            'rental_days' => 1,
            'subtotal' => 50000,
            'grand_total' => 50000,
            'final_total' => 50000,
            'status' => 'picked_up',
            'ktp_collateral_status' => 'held',
        ]);

        $this->detail = RentalDetail::create([
            'rental_id' => $this->rental->id,
            'master_item_id' => $this->item->id,
            'item_unit_id' => $this->unit->id,
            'item_name_snapshot' => $this->item->name,
            'daily_rate_snapshot' => 50000,
            'quantity' => 1,
            'subtotal' => 50000,
            'is_returned' => false,
        ]);
    }

    public function test_cashier_marks_item_lost_sends_notification_to_owner(): void
    {
        $this->actingAs($this->cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $this->rental->id)
            ->set("inspectionNotes.{$this->detail->id}", 'lost')
            ->set("damageFees.{$this->detail->id}", 250000)
            ->set("returnedItems.{$this->detail->id}", true)
            ->set('finePaymentMethod', 'cash')
            ->set('finePaidSuccess', true)
            ->call('completeReturn');

        // 1. Status unit berubah jadi lost
        $this->unit->refresh();
        $this->assertEquals('lost', $this->unit->status);
        $this->assertEquals('lost', $this->unit->condition);

        // 2. Denda tersimpan pada rental, detail barang, dan pembayaran
        $this->assertDatabaseHas('rentals', [
            'id' => $this->rental->id,
            'total_damage_fee' => 250000,
            'final_total' => 300000,
        ]);
        $this->assertDatabaseHas('rental_details', [
            'id' => $this->detail->id,
            'condition_after' => 'lost',
            'damage_fee' => 250000,
        ]);
        $this->assertDatabaseHas('payments', [
            'rental_id' => $this->rental->id,
            'payment_type' => 'fine',
            'payment_method' => 'cash',
            'amount' => 250000,
            'status' => 'paid',
        ]);

        // 3. Notifikasi masuk ke database untuk vendor_owner
        $this->assertDatabaseHas('notifications', [
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->owner->id,
            'rental_id' => $this->rental->id,
            'type' => 'lost_unit',
            'status' => 'sent',
        ]);

        // 4. Notification bell menampilkan notifikasi barang hilang untuk owner
        $this->actingAs($this->owner);
        Livewire::test(NotificationBell::class)
            ->assertSee('Barang Hilang')
            ->assertSee('TDK-LOST-TEST');
    }

    public function test_return_cannot_complete_with_zero_fine_for_a_lost_item(): void
    {
        $this->actingAs($this->cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $this->rental->id)
            ->set("inspectionNotes.{$this->detail->id}", 'lost')
            ->set("damageFees.{$this->detail->id}", 0)
            ->set("returnedItems.{$this->detail->id}", true)
            ->set('finePaidSuccess', true)
            ->call('completeReturn')
            ->assertHasErrors(["damageFees.{$this->detail->id}"]);

        $this->assertDatabaseHas('rentals', [
            'id' => $this->rental->id,
            'status' => 'picked_up',
            'total_damage_fee' => 0,
        ]);
        $this->assertDatabaseHas('rental_details', [
            'id' => $this->detail->id,
            'condition_after' => null,
            'damage_fee' => 0,
        ]);
        $this->assertDatabaseMissing('payments', [
            'rental_id' => $this->rental->id,
            'payment_type' => 'fine',
        ]);
    }

    public function test_owner_can_view_and_delete_lost_item_unit(): void
    {
        // Set unit status jadi lost dan rental sudah selesai (is_returned = true)
        $this->rental->update(['status' => 'completed']);
        $this->detail->update(['is_returned' => true]);

        $this->unit->update([
            'status' => 'lost',
            'condition' => 'lost',
        ]);

        NotificationLog::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->owner->id,
            'rental_id' => $this->rental->id,
            'channel' => 'in_app',
            'type' => 'lost_unit',
            'message' => "Laporan Barang Hilang: {$this->item->name} ({$this->unit->unit_code})",
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);

        $this->actingAs($this->owner);

        // Owner buka ItemUnitManager dan hapus unit hilang
        Livewire::test(ItemUnitManager::class)
            ->assertSee('Barang Hilang')
            ->set('selectedUnitId', $this->unit->id)
            ->call('deleteUnit')
            ->assertDispatched('toast');

        // Unit sudah terhapus (soft delete)
        $this->assertSoftDeleted('item_units', [
            'id' => $this->unit->id,
        ]);

        // Notifikasi ditutup
        $this->assertDatabaseHas('notifications', [
            'vendor_id' => $this->vendor->id,
            'type' => 'lost_unit',
            'status' => 'failed',
        ]);
    }
}
