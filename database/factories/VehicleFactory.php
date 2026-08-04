<?php

namespace Database\Factories;

use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plate_number' => fake()->unique()->bothify('???? ####'),
            'category' => fake()->randomElement(VehicleCategory::cases()),
            'capacity' => fake()->randomFloat(2, 2, 40),
            'brand' => fake()->randomElement(['Toyota', 'Mitsubishi', 'Isuzu', 'Hino', 'Daihatsu']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2015, 2026),
            'ownership_type' => OwnershipType::Company,
            'rental_partner_id' => null,
            'rental_start_date' => null,
            'rental_end_date' => null,
            'fuel_type' => fake()->randomElement(FuelType::cases()),
            'tank_capacity' => fake()->randomFloat(2, 40, 300),
            'current_odometer' => fake()->randomFloat(2, 5000, 200000),
            'status' => VehicleStatus::Available,
            'is_active' => true,
        ];
    }
}
