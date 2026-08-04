<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('LOC-????'),
            'name' => fake()->company(),
            'type' => fake()->randomElement(LocationType::cases()),
            'region' => fake()->city(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
