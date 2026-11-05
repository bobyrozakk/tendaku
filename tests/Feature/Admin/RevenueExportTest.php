<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class RevenueExportTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    private User $superAdmin;

    private User $vendorOwner;

    private User $vendorAdmin;

    private User $customer;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = $this->createRevenueVendor('Vendor Export');

        $this->superAdmin = $this->createRevenueUser('super_admin');
        $this->vendorOwner = $this->createRevenueUser('vendor_owner', $this->vendor);
        $this->vendorAdmin = $this->createRevenueUser('vendor_admin', $this->vendor);
        $this->customer = $this->createRevenueUser('customer');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_superadmin_login(): void
    {
        $this->get(route('admin.pendapatan.export'))
            ->assertRedirect(route('superadmin.login'));
    }

    public function test_non_superadmin_roles_cannot_export_csv(): void
    {
        foreach ([$this->vendorOwner, $this->vendorAdmin, $this->customer] as $user) {
            $this->actingAs($user)
                ->get(route('admin.pendapatan.export'))
                ->assertForbidden();
        }
    }

    public function test_superadmin_can_export_filtered_csv(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        $otherVendor = $this->createRevenueVendor('Vendor Lain');

        [, $qrisPayment] = $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            [
                'gateway_fee_amount' => 8400,
                'booking_code' => 'TND-CSV-001',
            ],
        );
        $this->createRevenueCommission($qrisPayment, 60000, ['base_amount' => 1200000]);

        [, $cashPayment] = $this->createPaidRevenuePayment(
            $otherVendor,
            500000,
            [
                'payment_type' => 'dp',
                'payment_method' => 'cash',
                'payment_channel' => null,
                'gateway_fee_amount' => 0,
                'escrow_status' => 'not_applicable',
                'booking_code' => 'TND-CSV-002',
            ],
        );
        $this->createRevenueCommission($cashPayment, 25000, ['base_amount' => 500000]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.pendapatan.export', [
            'period' => 'this_month',
            'vendor_id' => $this->vendor->id,
        ]));

        $response->assertOk()->assertDownload();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();

        $this->assertStringContainsString('Kode Booking', $content);
        $this->assertStringContainsString('TND-CSV-001', $content);
        $this->assertStringNotContainsString('TND-CSV-002', $content);
    }

    public function test_csv_respects_payment_method_and_payment_type_filters(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        $this->createPaidRevenuePayment(
            $this->vendor,
            1200000,
            [
                'gateway_fee_amount' => 8400,
                'booking_code' => 'TND-CSV-QRIS',
            ],
        );

        $this->createPaidRevenuePayment(
            $this->vendor,
            500000,
            [
                'payment_type' => 'dp',
                'payment_method' => 'cash',
                'payment_channel' => null,
                'escrow_status' => 'not_applicable',
                'booking_code' => 'TND-CSV-CASH',
            ],
        );

        $this->createPaidRevenuePayment(
            $this->vendor,
            200000,
            [
                'payment_type' => 'fine',
                'gateway_fee_amount' => 1400,
                'booking_code' => 'TND-CSV-FINE',
            ],
        );

        $cashResponse = $this->actingAs($this->superAdmin)->get(route('admin.pendapatan.export', [
            'period' => 'this_month',
            'payment_method' => 'cash',
        ]));

        $cashContent = $cashResponse->streamedContent();

        $this->assertStringContainsString('TND-CSV-CASH', $cashContent);
        $this->assertStringNotContainsString('TND-CSV-QRIS', $cashContent);
        $this->assertStringNotContainsString('TND-CSV-FINE', $cashContent);

        $fineResponse = $this->actingAs($this->superAdmin)->get(route('admin.pendapatan.export', [
            'period' => 'this_month',
            'payment_type' => 'fine',
        ]));

        $fineContent = $fineResponse->streamedContent();

        $this->assertStringContainsString('TND-CSV-FINE', $fineContent);
        $this->assertStringNotContainsString('TND-CSV-QRIS', $fineContent);
        $this->assertStringNotContainsString('TND-CSV-CASH', $fineContent);
    }
}
