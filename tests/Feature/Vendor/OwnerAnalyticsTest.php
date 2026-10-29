<?php

namespace Tests\Feature\Vendor;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\VendorAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected User $owner1;

    protected VendorAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new VendorAnalyticsService;

        $this->vendor1 = Vendor::create([
            'name' => 'Tendaku Vendor 1',
            'slug' => 'tendaku-vendor-1',
            'phone' => '081234567891',
            'address' => 'Jl. Vendor 1',
            'status' => 'active',
        ]);

        $this->vendor2 = Vendor::create([
            'name' => 'Mountaineer Vendor 2',
            'slug' => 'mountaineer-vendor-2',
            'phone' => '081234567892',
            'address' => 'Jl. Vendor 2',
            'status' => 'active',
        ]);

        $this->owner1 = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor1->id,
        ]);
    }

    public function test_empty_database_returns_zero_metrics_without_errors(): void
    {
        $metrics = $this->service->getDashboardMetrics($this->vendor1->id);

        $this->assertEquals(0, $metrics['daily_revenue']);
        $this->assertEquals(0, $metrics['monthly_revenue']);
        $this->assertEquals(0, $metrics['period_revenue']);
        $this->assertEquals(0, $metrics['damaged_assets_count']);
        $this->assertEquals(0, $metrics['lost_assets_count']);
        $this->assertEquals(0, $metrics['overdue_count']);
        $this->assertCount(0, $metrics['top_products']);
    }

    public function test_revenue_excludes_cancelled_and_expired_rentals(): void
    {
        // Rental Sah (Confirmed) -> Rp 100.000
        Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-OK-01',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'final_total' => 100000,
            'status' => 'confirmed',
            'created_at' => Carbon::today(),
        ]);

        // Rental Cancelled -> Rp 200.000 (Tidak boleh dihitung)
        Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-CANCEL-01',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 200000,
            'grand_total' => 200000,
            'final_total' => 200000,
            'status' => 'cancelled',
            'created_at' => Carbon::today(),
        ]);

        // Rental Expired -> Rp 150.000 (Tidak boleh dihitung)
        Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'TND-EXPIRE-01',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'final_total' => 150000,
            'status' => 'expired',
            'created_at' => Carbon::today(),
        ]);

        $dailyRevenue = $this->service->getDailyRevenue($this->vendor1->id);
        $this->assertEquals(100000, $dailyRevenue);
    }

    public function test_damaged_and_lost_assets_are_counted_correctly(): void
    {
        $cat = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $item = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $cat->id,
            'name' => 'Tenda 4P',
            'slug' => 'tenda-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 20000,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $item->id,
            'unit_code' => 'DAMAGED-1',
            'status' => 'maintenance',
            'condition' => 'damaged',
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $item->id,
            'unit_code' => 'DAMAGED-2',
            'status' => 'available',
            'condition' => 'damaged',
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $item->id,
            'unit_code' => 'LOST-1',
            'status' => 'retired',
            'condition' => 'lost',
        ]);

        // Unit Vendor 2 tidak boleh dihitung
        ItemUnit::create([
            'vendor_id' => $this->vendor2->id,
            'master_item_id' => $item->id,
            'unit_code' => 'V2-DAMAGED',
            'status' => 'maintenance',
            'condition' => 'damaged',
        ]);

        $this->assertEquals(2, $this->service->getDamagedAssetsCount($this->vendor1->id));
        $this->assertEquals(1, $this->service->getLostAssetsCount($this->vendor1->id));
    }

    public function test_overdue_rentals_detected(): void
    {
        // Rental yang masih berstatus picked_up dan return_date-nya kemarin (telat)
        Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'LATE-01',
            'pickup_date' => Carbon::today()->subDays(3),
            'return_date' => Carbon::today()->subDays(1),
            'rental_days' => 2,
            'subtotal' => 90000,
            'grand_total' => 90000,
            'status' => 'picked_up',
        ]);

        // Rental yang return_date-nya besok (belum telat)
        Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'ONTIME-01',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(2),
            'rental_days' => 2,
            'subtotal' => 90000,
            'grand_total' => 90000,
            'status' => 'picked_up',
        ]);

        $overdueRentals = $this->service->getOverdueRentals($this->vendor1->id);
        $this->assertCount(1, $overdueRentals);
        $this->assertEquals('LATE-01', $overdueRentals->first()->booking_code);
    }

    public function test_multi_tenant_isolation_in_analytics(): void
    {
        // Omzet Vendor 1
        Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => 'V1-RENT',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(1),
            'rental_days' => 1,
            'subtotal' => 50000,
            'grand_total' => 50000,
            'status' => 'completed',
            'created_at' => Carbon::today(),
        ]);

        // Omzet Vendor 2
        Rental::create([
            'vendor_id' => $this->vendor2->id,
            'booking_code' => 'V2-RENT',
            'pickup_date' => Carbon::today(),
            'return_date' => Carbon::today()->addDays(1),
            'rental_days' => 1,
            'subtotal' => 999000,
            'grand_total' => 999000,
            'status' => 'completed',
            'created_at' => Carbon::today(),
        ]);

        // Vendor 1 hanya menghitung 50.000, bukan 1.049.000
        $this->assertEquals(50000, $this->service->getDailyRevenue($this->vendor1->id));
        $this->assertEquals(999000, $this->service->getDailyRevenue($this->vendor2->id));
    }

    public function test_owner_dashboard_controller_renders_view_with_metrics(): void
    {
        $this->actingAs($this->owner1);

        $response = $this->get(route('vendor.owner.dashboard', ['period' => 'today']));

        $response->assertOk();
        $response->assertViewIs('vendor.owner.dashboard');
        $response->assertViewHas(['metrics', 'vendor', 'selectedPeriod']);
        $response->assertSee('Dashboard Owner');
        $response->assertSee('Omzet Harian');
    }

    public function test_daily_revenue_histogram_renders_scaled_bars(): void
    {
        // Hari terbesar: Rp 300.000 (hari ini), hari sebelumnya: Rp 100.000
        $this->createRentalOnDate('TND-CHART-PEAK', 300000, Carbon::today());
        $this->createRentalOnDate('TND-CHART-SMALL', 100000, Carbon::today()->subDay());

        $this->actingAs($this->owner1);

        $response = $this->get(route('vendor.owner.dashboard', ['period' => '7_days']));

        $response->assertOk();
        $response->assertSee('Tren Pendapatan Harian');
        // Bar tertinggi diskalakan ke 100% sumbu Y, bar Rp 100.000 menjadi ~33,33%.
        $response->assertSee('height: 100%;', false);
        $response->assertSee('height: 33.33%;', false);
        $response->assertSee('Rp 300 rb');
        $response->assertSee('Export Excel');
        // Label nilai tampil permanen di atas bar (bukan hanya saat hover).
        $response->assertSeeInOrder(['Rp 300 rb', 'Rp 100 rb'], false);
    }

    public function test_y_axis_uses_rounded_hundred_thousand_steps(): void
    {
        // Nilai tertinggi Rp 210.000 -> skala dibulatkan ke kelipatan 100 rb (0/100/200/300 rb),
        // bukan pecahan seperti 52,5 rb atau 157,5 rb.
        $this->createRentalOnDate('TND-AXIS-210', 210000, Carbon::today());

        $this->actingAs($this->owner1);

        $response = $this->get(route('vendor.owner.dashboard', ['period' => '7_days']));

        $response->assertOk();
        $response->assertSeeInOrder(['Rp 300 rb', 'Rp 200 rb', 'Rp 100 rb', '0'], false);
        $response->assertDontSee('Rp 52,5 rb');
        $response->assertDontSee('Rp 157,5 rb');
        // Bar Rp 210.000 terhadap skala 300.000 = 70%.
        $response->assertSee('height: 70%;', false);
    }

    private function createRentalOnDate(string $bookingCode, float $grandTotal, Carbon $date): Rental
    {
        $rental = Rental::create([
            'vendor_id' => $this->vendor1->id,
            'booking_code' => $bookingCode,
            'pickup_date' => $date->copy(),
            'return_date' => $date->copy()->addDay(),
            'rental_days' => 1,
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'final_total' => $grandTotal,
            'status' => 'completed',
        ]);

        $rental->forceFill(['created_at' => $date->copy()])->save();

        return $rental;
    }
}
