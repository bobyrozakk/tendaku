<?php

namespace Tests\Unit;

use App\Services\OwnerDashboardPresenter;
use PHPUnit\Framework\TestCase;

class OwnerDashboardPresenterTest extends TestCase
{
    private OwnerDashboardPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->presenter = new OwnerDashboardPresenter;
    }

    public function test_it_exposes_all_supported_period_keys_and_labels(): void
    {
        $this->assertSame(
            ['today', '7_days', '30_days', 'this_month', 'this_year'],
            $this->presenter->validPeriods()
        );
        $this->assertSame('Hari Ini', $this->presenter->periodLabel('today'));
        $this->assertSame('30 Hari', $this->presenter->periodLabel('unknown_period'));
    }

    public function test_it_formats_rupiah_and_compact_rupiah(): void
    {
        $this->assertSame('Rp 0', $this->presenter->formatRupiah(0));
        $this->assertSame('Rp 1.250.000', $this->presenter->formatRupiah(1250000));
        $this->assertSame('Rp 500', $this->presenter->formatCompactRupiah(500));
        $this->assertSame('Rp 200 rb', $this->presenter->formatCompactRupiah(200000));
        $this->assertSame('Rp 1,5 jt', $this->presenter->formatCompactRupiah(1500000));
    }

    public function test_it_builds_nice_y_axis_scale_and_bar_percentages(): void
    {
        $metrics = [
            'start_date' => '2026-11-16',
            'end_date' => '2026-11-22',
            'daily_revenue' => 210000,
            'monthly_revenue' => 500000,
            'period_revenue' => 310000,
            'damaged_assets_count' => 1,
            'lost_assets_count' => 0,
            'overdue_count' => 2,
            'total_units_count' => 14,
            'revenue_trend' => collect([
                ['date' => '2026-11-21', 'label' => '21 Nov', 'total' => 100000],
                ['date' => '2026-11-22', 'label' => '22 Nov', 'total' => 210000],
            ]),
            'top_products' => collect([
                (object) ['name' => 'Tenda 4P', 'category_name' => 'Tenda', 'times_rented' => 8, 'total_earnings' => 400000],
                (object) ['name' => 'Carrier 60L', 'category_name' => null, 'times_rented' => 4, 'total_earnings' => 120000],
            ]),
            'overdue_rentals' => collect([
                (object) [
                    'booking_code' => 'TND-LATE-01',
                    'customer' => (object) ['name' => 'Budi Santoso', 'phone' => '08123456789'],
                    'guest_name' => null,
                    'guest_phone' => null,
                    'return_date' => '2026-11-20',
                    'days_overdue' => 2,
                    'total_late_fee' => 40000,
                    'status' => 'picked_up',
                ],
            ]),
        ];

        $viewData = $this->presenter->build($metrics, '7_days');

        $this->assertSame('7 Hari', $viewData['selected_period_label']);
        $this->assertCount(2, $viewData['financial_cards']);
        $this->assertSame(['Omzet Harian', 'Omzet (7 Hari)'], array_column($viewData['financial_cards'], 'title'));
        $this->assertTrue($viewData['financial_cards'][1]['emphasis']);
        $this->assertTrue($viewData['issue_summary']['has_issues']);

        $chart = $viewData['revenue_chart'];
        $this->assertTrue($chart['has_data']);
        $this->assertSame(['Rp 300 rb', 'Rp 200 rb', 'Rp 100 rb', '0'], array_column($chart['ticks'], 'label'));
        $this->assertSame(33.33, $chart['bars'][0]['height_percent']);
        $this->assertSame(70.0, $chart['bars'][1]['height_percent']);
        $this->assertTrue($chart['bars'][1]['is_peak']);

        $this->assertSame(100, $viewData['top_products'][0]['share_percent']);
        $this->assertSame(50, $viewData['top_products'][1]['share_percent']);
        $this->assertSame('Umum', $viewData['top_products'][1]['category']);

        $this->assertSame('Belum Kembali', $viewData['overdue_rows'][0]['status_label']);
        $this->assertSame('goldenrod', $viewData['overdue_rows'][0]['status_tone']);
        $this->assertTrue($viewData['overdue_rows'][0]['has_late_fee']);
        $this->assertSame('Rp 40.000', $viewData['overdue_rows'][0]['late_fee_formatted']);
    }

    public function test_it_handles_empty_metrics_gracefully(): void
    {
        $viewData = $this->presenter->build([
            'start_date' => '2026-11-22',
            'end_date' => '2026-11-22',
            'daily_revenue' => 0,
            'monthly_revenue' => 0,
            'period_revenue' => 0,
            'damaged_assets_count' => 0,
            'lost_assets_count' => 0,
            'overdue_count' => 0,
            'total_units_count' => 0,
            'revenue_trend' => collect(),
            'top_products' => collect(),
            'overdue_rentals' => collect(),
        ], 'today');

        $this->assertFalse($viewData['revenue_chart']['has_data']);
        $this->assertFalse($viewData['issue_summary']['has_issues']);
        $this->assertSame([], $viewData['top_products']);
        $this->assertSame([], $viewData['overdue_rows']);
    }

    public function test_it_limits_displayed_top_products_to_three_items(): void
    {
        $products = collect([
            (object) ['name' => 'Produk 1', 'category_name' => 'Tenda', 'times_rented' => 10, 'total_earnings' => 500000],
            (object) ['name' => 'Produk 2', 'category_name' => 'Carrier', 'times_rented' => 8, 'total_earnings' => 400000],
            (object) ['name' => 'Produk 3', 'category_name' => 'Kompor', 'times_rented' => 6, 'total_earnings' => 300000],
            (object) ['name' => 'Produk 4', 'category_name' => 'Lampu', 'times_rented' => 4, 'total_earnings' => 200000],
            (object) ['name' => 'Produk 5', 'category_name' => 'Matras', 'times_rented' => 2, 'total_earnings' => 100000],
        ]);

        $viewData = $this->presenter->build([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-22',
            'daily_revenue' => 0,
            'monthly_revenue' => 0,
            'period_revenue' => 0,
            'damaged_assets_count' => 0,
            'lost_assets_count' => 0,
            'overdue_count' => 0,
            'total_units_count' => 0,
            'revenue_trend' => collect(),
            'top_products' => $products,
            'overdue_rentals' => collect(),
        ], '30_days');

        $this->assertCount(3, $viewData['top_products']);
        $this->assertSame(['Produk 1', 'Produk 2', 'Produk 3'], array_column($viewData['top_products'], 'name'));
    }
}
