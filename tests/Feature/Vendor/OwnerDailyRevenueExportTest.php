<?php

namespace Tests\Feature\Vendor;

use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

class OwnerDailyRevenueExportTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Vendor $otherVendor;

    protected User $owner;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor 1',
            'slug' => 'tendaku-vendor-1',
            'phone' => '081234567891',
            'address' => 'Jl. Vendor 1',
            'status' => 'active',
        ]);

        $this->otherVendor = Vendor::create([
            'name' => 'Mountaineer Vendor 2',
            'slug' => 'mountaineer-vendor-2',
            'phone' => '081234567892',
            'address' => 'Jl. Vendor 2',
            'status' => 'active',
        ]);

        $this->owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'vendor_admin',
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_owner_can_download_daily_revenue_excel(): void
    {
        $this->createRental($this->vendor, 150000, Carbon::today());

        $response = $this->actingAs($this->owner)
            ->get(route('vendor.owner.dashboard.export', ['period' => '7_days']));

        $response->assertOk();
        $response->assertDownload($this->expectedFileName('7_days'));
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheet = $this->readSheetXml($response);

        $this->assertStringContainsString('Laporan Omzet Harian', $sheet);
        $this->assertStringContainsString('Tendaku Vendor 1', $sheet);
        $this->assertStringContainsString(Carbon::today()->format('Y-m-d'), $sheet);
        $this->assertStringContainsString('<v>150000</v>', $sheet);
    }

    public function test_export_only_contains_revenue_of_own_vendor(): void
    {
        $this->createRental($this->vendor, 150000, Carbon::today());
        $this->createRental($this->otherVendor, 999000, Carbon::today());

        $response = $this->actingAs($this->owner)
            ->get(route('vendor.owner.dashboard.export', ['period' => 'today']));

        $sheet = $this->readSheetXml($response);

        $this->assertStringContainsString('<v>150000</v>', $sheet);
        $this->assertStringNotContainsString('999000', $sheet);
    }

    public function test_invalid_period_falls_back_to_thirty_days(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('vendor.owner.dashboard.export', ['period' => 'not_a_period']));

        $response->assertOk();
        $response->assertDownload($this->expectedFileName('30_days'));
    }

    public function test_export_without_transactions_still_produces_valid_file(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('vendor.owner.dashboard.export', ['period' => 'today']));

        $response->assertOk();

        $sheet = $this->readSheetXml($response);

        $this->assertStringContainsString('TOTAL', $sheet);
        $this->assertStringContainsString(Carbon::today()->format('Y-m-d'), $sheet);
    }

    public function test_vendor_admin_cannot_export_daily_revenue(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('vendor.owner.dashboard.export'));

        $response->assertForbidden();
    }

    private function createRental(Vendor $vendor, float $grandTotal, Carbon $createdAt): Rental
    {
        return Rental::create([
            'vendor_id' => $vendor->id,
            'booking_code' => 'TND-EXPORT-'.$vendor->id.'-'.uniqid(),
            'pickup_date' => $createdAt,
            'return_date' => $createdAt->copy()->addDays(2),
            'rental_days' => 2,
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'final_total' => $grandTotal,
            'status' => 'completed',
            'created_at' => $createdAt,
        ]);
    }

    private function expectedFileName(string $period): string
    {
        [$startDate, $endDate] = match ($period) {
            'today' => [Carbon::today(), Carbon::today()],
            '7_days' => [Carbon::today()->subDays(6), Carbon::today()],
            default => [Carbon::today()->subDays(29), Carbon::today()],
        };

        return sprintf(
            'omzet-harian-tendaku-vendor-1-%s-sd-%s.xlsx',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    private function readSheetXml(TestResponse $response): string
    {
        $path = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'File Excel tidak dapat dibuka sebagai arsip XLSX.');

        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertNotFalse(
            @simplexml_load_string($sheet),
            'Worksheet Excel bukan XML yang valid.'
        );

        return $sheet;
    }
}
