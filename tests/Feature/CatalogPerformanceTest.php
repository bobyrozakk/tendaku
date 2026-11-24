<?php

namespace Tests\Feature;

use App\Livewire\Customer\CatalogIndex;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogPerformanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalog_query_count(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');

        $vendor = Vendor::create([
            'name' => 'Active Vendor',
            'slug' => 'active-vendor',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 1',
            'status' => 'active',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        for ($i = 1; $i <= 12; $i++) {
            $item = MasterItem::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => "Item {$i}",
                'slug' => "item-{$i}",
                'daily_rate' => 50000,
                'is_active' => true,
            ]);

            ItemUnit::create([
                'vendor_id' => $vendor->id,
                'master_item_id' => $item->id,
                'unit_code' => "U-{$i}-1",
                'condition' => 'excellent',
                'status' => 'available',
                'purchase_date' => Carbon::now()->subMonths(3),
            ]);

            ItemUnit::create([
                'vendor_id' => $vendor->id,
                'master_item_id' => $item->id,
                'unit_code' => "U-{$i}-2",
                'condition' => 'good',
                'status' => 'available',
                'purchase_date' => Carbon::now()->subMonths(1),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component = Livewire::test(CatalogIndex::class);
        $html = $component->html();

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertLessThanOrEqual(10, $queryCount);

        // Test with onlyAvailable = true
        DB::flushQueryLog();

        $componentOnlyAvailable = Livewire::test(CatalogIndex::class)
            ->set('onlyAvailable', true);
        $componentOnlyAvailable->html();

        $queriesOnly = DB::getQueryLog();
        $queryCountOnly = count($queriesOnly);

        $this->assertLessThanOrEqual(20, $queryCountOnly);
    }
}
