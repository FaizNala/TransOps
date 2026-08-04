<?php

namespace Database\Factories;

use App\Models\FuelLog;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelLog>
 */
class FuelLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'booking_id' => null,
            'recorded_by' => null,
            'refueled_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'odometer' => fake()->randomFloat(2, 1000, 200000),
            'liters' => fake()->randomFloat(2, 10, 200),
            'price_per_liter' => fake()->randomFloat(2, 10000, 15000),
            'total_cost' => fn (array $attributes) => $attributes['liters'] * $attributes['price_per_liter'],
            'station' => fake()->company(),
            'is_full_tank' => true,
            'note' => null,
        ];
    }
}
