<?php

namespace Tests\Feature;

use App\Livewire\Kasir\ItemUnitList;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KasirItemUnitListLostTest extends TestCase
{
    use RefreshDatabase;

    public function test_lost_unit_is_displayed_with_hilang_badge_and_sorted_at_the_bottom(): void
    {
        $vendor = Vendor::create([
            'name' => 'Toko Kasir Test',
            'slug' => 'toko-kasir-test',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1',
            'city' => 'Malang',
            'status' => 'approved',
        ]);

        $cashier = User::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => 'vendor_admin',
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

        // Buat 1 unit lost dengan kode berawalan A (jika sort alfabet biasa akan di atas)
        $lostUnit = ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'AAA-LOST',
            'barcode' => 'BC-0001',
            'status' => 'lost',
            'condition' => 'lost',
        ]);

        // Buat 1 unit available dengan kode berawalan Z
        $availableUnit = ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'ZZZ-AVAILABLE',
            'barcode' => 'BC-0002',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $this->actingAs($cashier);

        $test = Livewire::test(ItemUnitList::class);

        // Pastikan badge "Hilang" muncul
        $test->assertSee('Hilang')
            ->assertSee('AAA-LOST')
            ->assertSee('ZZZ-AVAILABLE');

        // Pastikan unit available (ZZZ-AVAILABLE) muncul sebelum unit lost (AAA-LOST)
        $html = $test->html();
        $posAvailable = strpos($html, 'ZZZ-AVAILABLE');
        $posLost = strpos($html, 'AAA-LOST');

        $this->assertNotFalse($posAvailable);
        $this->assertNotFalse($posLost);
        $this->assertTrue($posAvailable < $posLost, 'Unit normal harus muncul lebih dulu, unit hilang harus berada di paling bawah.');
    }
}
