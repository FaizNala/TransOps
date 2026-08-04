<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'requester_id' => Employee::factory(),
            'driver_id' => Driver::factory(),
            'purpose' => fake()->sentence(),
            'destination' => fake()->city(),
            'start_at' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'end_at' => fn (array $attributes) => Carbon::parse($attributes['start_at'])->addHours(fake()->numberBetween(3, 12)),
            'status' => BookingStatus::Pending,
            'created_by' => User::factory(),
            'departure_at' => null,
            'return_at' => null,
            'odometer_start' => null,
            'odometer_end' => null,
            'distance_km' => null,
            'note' => null,
        ];
    }
}
