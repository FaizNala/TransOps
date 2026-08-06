<?php

namespace Tests\Feature\Monitoring;

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\ServiceLog;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'employee_id' => Employee::factory(),
        ]);
    }

    public function test_admin_can_store_service_schedule_with_audit(): void
    {
        $admin = $this->adminUser();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)
            ->from(route('service-schedules.index'))
            ->post(route('service-schedules.store'), [
                'vehicle_id' => $vehicle->id,
                'service_type' => 'oil_change',
                'scheduled_date' => now()->addMonth()->toDateString(),
                'scheduled_odometer' => 15000,
                'recurrence_km' => 10000,
            ])
            ->assertRedirect(route('service-schedules.index'));

        $schedule = ServiceSchedule::query()->firstOrFail();

        $this->assertSame(ServiceStatus::Scheduled, $schedule->status);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'service_schedule', 'entity_id' => $schedule->id, 'action' => 'created']);
    }

    public function test_admin_can_destroy_service_schedule(): void
    {
        $admin = $this->adminUser();
        $schedule = ServiceSchedule::factory()->create();

        $this->actingAs($admin)
            ->from(route('service-schedules.index'))
            ->delete(route('service-schedules.destroy', $schedule))
            ->assertRedirect(route('service-schedules.index'));

        $this->assertDatabaseMissing('service_schedules', ['id' => $schedule->id]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'service_schedule', 'entity_id' => $schedule->id, 'action' => 'deleted']);
    }

    public function test_admin_can_store_service_log_and_reschedule_recurring(): void
    {
        $admin = $this->adminUser();
        $vehicle = Vehicle::factory()->create();
        $schedule = ServiceSchedule::factory()->create([
            'vehicle_id' => $vehicle->id,
            'recurrence_months' => 3,
            'recurrence_km' => null,
            'scheduled_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->from(route('service-logs.index'))
            ->post(route('service-logs.store'), [
                'vehicle_id' => $vehicle->id,
                'service_schedule_id' => $schedule->id,
                'service_type' => 'oil_change',
                'serviced_date' => now()->toDateString(),
                'odometer' => 15000,
                'cost' => 750000,
                'vendor' => 'Bengkel Jaya',
            ])
            ->assertRedirect(route('service-logs.index'));

        $log = ServiceLog::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'service_log', 'entity_id' => $log->id, 'action' => 'created']);

        $this->assertSame(
            now()->addMonths(3)->toDateString(),
            $schedule->refresh()->scheduled_date?->toDateString(),
        );
    }

    public function test_admin_can_destroy_service_log(): void
    {
        $admin = $this->adminUser();
        $log = ServiceLog::factory()->create();

        $this->actingAs($admin)
            ->from(route('service-logs.index'))
            ->delete(route('service-logs.destroy', $log))
            ->assertRedirect(route('service-logs.index'));

        $this->assertDatabaseMissing('service_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'service_log', 'entity_id' => $log->id, 'action' => 'deleted']);
    }

    public function test_index_pages_return_inertia(): void
    {
        $admin = $this->adminUser();
        $vehicle = Vehicle::factory()->create();
        ServiceSchedule::factory()->create(['vehicle_id' => $vehicle->id]);
        ServiceLog::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->actingAs($admin)
            ->get(route('service-schedules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('service/schedules')->has('schedules.data', 1));

        $this->actingAs($admin)
            ->get(route('service-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('service/logs')
                ->has('logs.data', 1)
                ->has('vehicles')
                ->has('schedules'));
    }

    public function test_approver_cannot_access_service_routes(): void
    {
        $approver = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => Employee::factory(),
        ]);

        $this->actingAs($approver)
            ->get(route('service-schedules.index'))
            ->assertForbidden();

        $this->actingAs($approver)
            ->get(route('service-logs.index'))
            ->assertForbidden();
    }
}
