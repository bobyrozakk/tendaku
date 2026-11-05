<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'payment_type' => 'full',
            'payment_method' => 'midtrans_snap',
            'amount' => 100000,
            'status' => 'pending',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment): void {
            if ($payment->vendor_id !== null || $payment->rental_id === null) {
                return;
            }

            $rental = Rental::withoutGlobalScopes()->find($payment->rental_id);

            if ($rental !== null) {
                $payment->vendor_id = $rental->vendor_id;
            }
        });
    }
}
