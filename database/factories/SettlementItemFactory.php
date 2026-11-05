<?php

namespace Database\Factories;

use App\Models\Settlement;
use App\Models\SettlementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettlementItem>
 */
class SettlementItemFactory extends Factory
{
    protected $model = SettlementItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'settlement_id' => Settlement::factory(),
            'type' => 'payment',
            'gross_amount' => 100000,
            'commission_amount' => 5000,
            'fee_amount' => 0,
            'net_amount' => 95000,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (SettlementItem $item): void {
            if ($item->vendor_id !== null || $item->settlement_id === null) {
                return;
            }

            $settlement = Settlement::withoutGlobalScopes()->find($item->settlement_id);

            if ($settlement !== null) {
                $item->vendor_id = $settlement->vendor_id;
            }
        });
    }
}
