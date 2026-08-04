<?php

namespace Database\Factories;

use App\Models\RentalPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPartner>
 */
class RentalPartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
