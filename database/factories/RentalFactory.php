<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    protected $model = Rental::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pickupDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $rentalDays = fake()->numberBetween(1, 7);
        $subtotal = fake()->numberBetween(1, 20) * 50000;

        return [
            'vendor_id' => Vendor::factory(),
            'user_id' => User::factory(),
            'booking_code' => 'TDK-'.fake()->unique()->numerify('########'),
            'pickup_date' => $pickupDate,
            'return_date' => (clone $pickupDate)->modify("+{$rentalDays} days"),
            'rental_days' => $rentalDays,
            'subtotal' => $subtotal,
            'grand_total' => $subtotal,
            'status' => 'pending',
        ];
    }
}
