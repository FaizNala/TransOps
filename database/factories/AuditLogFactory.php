<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'action' => fake()->randomElement(['created', 'updated', 'approved', 'rejected', 'cancelled']),
            'entity_type' => fake()->randomElement(['booking', 'vehicle', 'fuel_log']),
            'entity_id' => fake()->numberBetween(1, 9999),
            'before' => null,
            'after' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
