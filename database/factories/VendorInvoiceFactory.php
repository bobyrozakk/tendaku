<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorInvoice>
 */
class VendorInvoiceFactory extends Factory
{
    protected $model = VendorInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->subWeek();
        $periodEnd = $periodStart->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'vendor_id' => Vendor::factory(),
            'number' => 'INV/'.fake()->unique()->numerify('########').'/'.now()->format('Ym').'/1',
            'type' => 'settlement',
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'subtotal' => 0,
            'commission_amount' => 0,
            'fee_amount' => 0,
            'total' => 0,
            'status' => 'issued',
            'issued_at' => now(),
        ];
    }
}
