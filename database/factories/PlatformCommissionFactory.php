<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PlatformCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformCommission>
 */
class PlatformCommissionFactory extends Factory
{
    protected $model = PlatformCommission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'rate' => 0.05,
            'status' => 'accrued',
            'recognized_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PlatformCommission $commission): void {
            if ($commission->payment_id === null) {
                return;
            }

            $payment = Payment::withoutGlobalScopes()->find($commission->payment_id);

            if ($payment === null) {
                return;
            }

            $commission->rental_id ??= $payment->rental_id;
            $commission->vendor_id ??= $payment->vendor_id;
            $commission->base_amount ??= $payment->amount;
            $commission->amount ??= max(
                2500,
                (int) round((float) $commission->base_amount * (float) $commission->rate),
            );
        });
    }
}
