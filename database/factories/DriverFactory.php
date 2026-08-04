<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'license_number' => fake()->unique()->numerify('SIM-########'),
            'license_type' => fake()->randomElement(['A', 'B1', 'B2', 'C']),
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years'),
            'status' => DriverStatus::Available,
        ];
    }
}
