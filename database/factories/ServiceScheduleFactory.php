<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\ServiceSchedule;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceSchedule>
 */
class ServiceScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'service_type' => fake()->randomElement(['oil_change', 'routine', 'brake', 'tire', 'engine']),
            'scheduled_date' => fake()->dateTimeBetween('now', '+3 months'),
            'scheduled_odometer' => null,
            'recurrence_km' => fake()->randomElement([5000, 10000, 15000, null]),
            'recurrence_months' => fake()->randomElement([3, 6, 12, null]),
            'status' => ServiceStatus::Scheduled,
            'note' => null,
        ];
    }
}
