<?php

namespace Tests\Feature\Vendor;

use App\Livewire\Vendor\ItemUnitManager;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerItemUnitTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected User $owner1;

    protected MasterItem $item1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor1 = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        $this->owner1 = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor1->id,
        ]);

        $cat = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->item1 = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $cat->id,
            'name' => 'Tenda Consina Magnum 4',
            'slug' => 'tenda-consina-magnum-4',
            'daily_rate' => 45000,
            'late_fee_per_day' => 20000,
        ]);
    }

    public function test_item_unit_manager_renders_database_units(): void
    {
        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'TND-001',
            'barcode' => 'BC-111111',
            'status' => 'available',
            'condition' => 'excellent',
        ]);

        $this->actingAs($this->owner1);

        Livewire::test(ItemUnitManager::class)
            ->assertSee('TND-001')
            ->assertSee('BC-111111')
            ->assertSee('Tersedia')
            ->assertSee('Sangat Baik');
    }

    public function test_owner_can_add_physical_unit(): void
    {
        $this->actingAs($this->owner1);

        Livewire::test(ItemUnitManager::class)
            ->set('masterItemId', (string) $this->item1->id)
            ->set('unitCode', 'TND-999')
            ->set('barcode', 'BC-999999')
            ->set('status', 'available')
            ->set('condition', 'good')
            ->set('purchaseDate', '2026-01-15')
            ->set('purchasePrice', '500000')
            ->call('addUnit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('item_units', [
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'TND-999',
            'barcode' => 'BC-999999',
            'status' => 'available',
            'condition' => 'good',
            'purchase_date' => '2026-01-15 00:00:00',
        ]);
    }

    public function test_barcode_and_unit_code_must_be_unique_per_vendor(): void
    {
        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'TND-EXISTS',
            'barcode' => 'BC-EXISTS',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $this->actingAs($this->owner1);

        Livewire::test(ItemUnitManager::class)
            ->set('masterItemId', (string) $this->item1->id)
            ->set('unitCode', 'TND-EXISTS')
            ->set('barcode', 'BC-EXISTS')
            ->set('status', 'available')
            ->set('condition', 'good')
            ->call('addUnit')
            ->assertHasErrors(['unitCode', 'barcode']);
    }

    public function test_cannot_delete_unit_when_status_is_rented(): void
    {
        $unit = ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'TND-RENTED',
            'status' => 'rented',
            'condition' => 'good',
        ]);

        $this->actingAs($this->owner1);

        Livewire::test(ItemUnitManager::class)
            ->set('selectedUnitId', $unit->id)
            ->call('deleteUnit')
            ->assertSet('feedbackType', 'error');

        $this->assertNotSoftDeleted('item_units', ['id' => $unit->id]);
    }

    public function test_can_delete_available_unit(): void
    {
        $unit = ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'TND-DELETE',
            'status' => 'available',
            'condition' => 'good',
        ]);

        $this->actingAs($this->owner1);

        Livewire::test(ItemUnitManager::class)
            ->set('selectedUnitId', $unit->id)
            ->call('deleteUnit')
            ->assertSet('feedbackType', 'success');

        $this->assertSoftDeleted('item_units', ['id' => $unit->id]);
    }

    public function test_search_and_tabs_filter_units(): void
    {
        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'ALPHA-01',
            'status' => 'available',
            'condition' => 'good',
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->item1->id,
            'unit_code' => 'BETA-02',
            'status' => 'maintenance',
            'condition' => 'damaged',
        ]);

        $this->actingAs($this->owner1);

        Livewire::test(ItemUnitManager::class)
            ->set('search', 'ALPHA')
            ->assertSee('ALPHA-01')
            ->assertDontSee('BETA-02')
            ->set('search', '')
            ->set('filterStatus', 'maintenance')
            ->assertSee('BETA-02')
            ->assertDontSee('ALPHA-01');
    }
}
