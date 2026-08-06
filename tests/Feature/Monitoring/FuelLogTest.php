<?php

namespace Tests\Feature\Monitoring;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FuelLogTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'employee_id' => Employee::factory(),
        ]);
    }

    public function test_admin_can_store_fuel_log_with_total_cost_and_audit(): void
    {
        $admin = $this->adminUser();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)
            ->from(route('fuel-logs.index'))
            ->post(route('fuel-logs.store'), [
                'vehicle_id' => $vehicle->id,
                'refueled_at' => now()->format('Y-m-d H:i:s'),
                'odometer' => 12000,
                'liters' => 40,
                'price_per_liter' => 10000,
                'station' => 'SPBU KM 12',
                'is_full_tank' => 1,
            ])
            ->assertRedirect(route('fuel-logs.index'));

        $log = FuelLog::query()->firstOrFail();

        $this->assertSame(12000, (int) $log->odometer);
        $this->assertSame(400000.0, (float) $log->total_cost);
        $this->assertTrue((bool) $log->is_full_tank);
        $this->assertSame($admin->employee_id, $log->recorded_by);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'fuel_log', 'entity_id' => $log->id, 'action' => 'created']);
    }

    public function test_fuel_log_store_requires_vehicle(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->from(route('fuel-logs.index'))
            ->post(route('fuel-logs.store'), [
                'refueled_at' => now()->format('Y-m-d H:i:s'),
                'odometer' => 12000,
                'liters' => 40,
                'price_per_liter' => 10000,
            ])
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_admin_can_destroy_fuel_log(): void
    {
        $admin = $this->adminUser();
        $log = FuelLog::factory()->create();

        $this->actingAs($admin)
            ->from(route('fuel-logs.index'))
            ->delete(route('fuel-logs.destroy', $log))
            ->assertRedirect(route('fuel-logs.index'));

        $this->assertDatabaseMissing('fuel_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'fuel_log', 'entity_id' => $log->id, 'action' => 'deleted']);
    }

    public function test_approver_cannot_access_fuel_log_routes(): void
    {
        $approver = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => Employee::factory(),
        ]);

        $this->actingAs($approver)
            ->get(route('fuel-logs.index'))
            ->assertForbidden();
    }

    public function test_index_returns_inertia(): void
    {
        $admin = $this->adminUser();
        FuelLog::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('fuel-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('fuel-logs/index')
                ->has('logs.data', 3)
                ->has('vehicles'));
    }
}
