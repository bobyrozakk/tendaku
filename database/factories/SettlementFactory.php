<?php

namespace Database\Factories;

use App\Models\Settlement;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

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
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'status' => 'draft',
            'gross_qris' => 0,
            'gross_fine_qris' => 0,
            'commission_total' => 0,
            'gateway_fee_total' => 0,
            'fine_fee_total' => 0,
            'cash_commission_offset' => 0,
            'transfer_fee' => 0,
            'adjustment_amount' => 0,
            'net_payout' => 0,
            'bank_name' => 'BCA',
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_name' => fake()->name(),
        ];
    }
}
