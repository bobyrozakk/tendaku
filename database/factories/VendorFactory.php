<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::limit(Str::slug($name), 140, '').'-'.fake()->unique()->numerify('####'),
            'description' => fake()->sentence(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'logo_url' => null,
            'status' => 'active',
            'commission_rate' => null,
            'approved_at' => now(),
            'approved_by' => null,
        ];
    }
}
