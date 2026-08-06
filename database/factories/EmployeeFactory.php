<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => fake()->unique()->numerify('EMP-F-####'),
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Operasi', 'HR', 'Finance', 'Logistik']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'location_id' => Location::factory(),
            'manager_id' => null,
            'is_active' => true,
        ];
    }
}
