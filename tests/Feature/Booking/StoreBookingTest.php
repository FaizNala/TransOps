<?php

namespace Tests\Feature\Booking;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreBookingTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): array
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'employee_id' => $employee->id,
        ]);

        return [$user, $employee];
    }

    private function approverEmployees(int $count = 2): array
    {
        $employees = Employee::factory()->count($count)->create();
        foreach ($employees as $employee) {
            User::factory()->create([
                'role' => UserRole::Approver,
                'employee_id' => $employee->id,
            ]);
        }

        return $employees->all();
    }

    private function validPayload(Vehicle $vehicle, Driver $driver, array $approverIds, ?string $startAt = null): array
    {
        $startAt = $startAt ?? now()->addDays(1)->format('Y-m-d H:i:s');

        return [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'purpose' => 'Pengantaran logistik ke tambang',
            'destination' => 'Tambang Malili',
            'start_at' => $startAt,
            'end_at' => Carbon::parse($startAt)->addHours(6)->format('Y-m-d H:i:s'),
            'approvers' => $approverIds,
        ];
    }

    private function existingBooking(Vehicle $vehicle, Driver $driver): Booking
    {
        $startAt = now()->addDays(1)->format('Y-m-d H:i:s');

        return Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'status' => BookingStatus::Pending,
            'start_at' => $startAt,
            'end_at' => Carbon::parse($startAt)->addHours(6)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_admin_can_create_booking_with_approvals_in_transaction(): void
    {
        [$user, $employee] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $vehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();

        $response = $this->actingAs($user)->post(route('bookings.store'), $this->validPayload($vehicle, $driver, [$firstApprover->id, $secondApprover->id]));

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/^BK-\d{6}-\d{4}$/', $booking->booking_number);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame($employee->id, $booking->requester_id);
        $this->assertSame($user->id, $booking->created_by);
        $this->assertSame($vehicle->id, $booking->vehicle_id);
        $this->assertSame($driver->id, $booking->driver_id);

        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'approver_id' => $firstApprover->id,
            'level' => 1,
            'status' => ApprovalStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'approver_id' => $secondApprover->id,
            'level' => 2,
            'status' => ApprovalStatus::Pending->value,
        ]);
    }

    public function test_booking_requires_at_least_two_approvers(): void
    {
        [$user] = $this->adminUser();
        [$approver] = $this->approverEmployees(1);
        $vehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();

        $response = $this->actingAs($user)->post(route('bookings.store'), $this->validPayload($vehicle, $driver, [$approver->id]));

        $response->assertSessionHasErrors('approvers');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_vehicle_double_booking_is_rejected(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $vehicle = Vehicle::factory()->create();
        $existingDriver = Driver::factory()->create();
        $requestedDriver = Driver::factory()->create();
        $this->existingBooking($vehicle, $existingDriver);

        $response = $this->actingAs($user)->post(
            route('bookings.store'),
            $this->validPayload($vehicle, $requestedDriver, [$firstApprover->id, $secondApprover->id])
        );

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_driver_double_booking_is_rejected(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $vehicle = Vehicle::factory()->create();
        $existingVehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();
        $this->existingBooking($existingVehicle, $driver);

        $response = $this->actingAs($user)->post(
            route('bookings.store'),
            $this->validPayload($vehicle, $driver, [$firstApprover->id, $secondApprover->id])
        );

        $response->assertSessionHasErrors('driver_id');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_outside_existing_schedule_is_allowed(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $vehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();
        $this->existingBooking($vehicle, $driver);

        $response = $this->actingAs($user)->post(
            route('bookings.store'),
            $this->validPayload($vehicle, $driver, [$firstApprover->id, $secondApprover->id], now()->addDays(3)->format('Y-m-d H:i:s'))
        );

        $response->assertRedirect(route('bookings.index'));
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_non_admin_cannot_access_booking_routes(): void
    {
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $user = $firstApprover->users()->firstOrFail();
        $vehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('bookings.store'), $this->validPayload($vehicle, $driver, [$firstApprover->id, $secondApprover->id]))
            ->assertForbidden();
    }

    public function test_admin_can_visit_index_page(): void
    {
        [$user] = $this->adminUser();
        $this->approverEmployees();

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('bookings/index')
                ->has('bookings')
                ->has('vehicles')
                ->has('drivers')
                ->has('approvers'));
    }
}
