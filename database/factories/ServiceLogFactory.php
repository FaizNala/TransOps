<?php

namespace Database\Factories;

use App\Models\ServiceLog;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceLog>
 */
class ServiceLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'service_schedule_id' => null,
            'service_type' => fake()->randomElement(['oil_change', 'routine', 'brake', 'tire', 'engine']),
            'serviced_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'odometer' => fake()->randomFloat(2, 1000, 200000),
            'cost' => fake()->randomFloat(2, 50000, 5000000),
            'vendor' => fake()->company(),
            'mechanic' => fake()->name(),
            'note' => null,
        ];
    }
}
