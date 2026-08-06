<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => UserRole::Admin,
            'employee_id' => Employee::factory(),
        ]);
        $this->vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Available]);
        $this->driver = Driver::factory()->create(['status' => DriverStatus::Available]);
    }

    private function approvedBooking(): Booking
    {
        return Booking::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'requester_id' => Employee::factory(),
            'created_by' => $this->user->id,
            'status' => BookingStatus::Approved,
        ]);
    }

    private function inUseBooking(): Booking
    {
        return Booking::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'requester_id' => Employee::factory(),
            'created_by' => $this->user->id,
            'status' => BookingStatus::InUse,
            'departure_at' => now(),
            'odometer_start' => 100,
        ]);
    }

    public function test_admin_can_start_approved_booking(): void
    {
        $booking = $this->approvedBooking();

        $this->actingAs($this->user)
            ->from(route('bookings.index'))
            ->post(route('bookings.start', $booking), ['odometer_start' => 100])
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::InUse->value,
            'odometer_start' => 100,
        ]);
        $this->assertNotNull($booking->refresh()->departure_at);
        $this->assertDatabaseHas('vehicles', ['id' => $this->vehicle->id, 'status' => VehicleStatus::InUse->value]);
        $this->assertDatabaseHas('drivers', ['id' => $this->driver->id, 'status' => DriverStatus::Assigned->value]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'booking', 'entity_id' => $booking->id, 'action' => 'started']);
    }

    public function test_non_approved_booking_cannot_start(): void
    {
        $booking = Booking::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'requester_id' => Employee::factory(),
            'created_by' => $this->user->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($this->user)
            ->from(route('bookings.index'))
            ->post(route('bookings.start', $booking), ['odometer_start' => 100]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::Pending->value]);
        $this->assertDatabaseMissing('audit_logs', ['entity_type' => 'booking', 'entity_id' => $booking->id, 'action' => 'started']);
    }

    public function test_admin_can_complete_in_use_booking_and_sync_odometer(): void
    {
        $booking = $this->inUseBooking();

        $this->actingAs($this->user)
            ->from(route('bookings.index'))
            ->post(route('bookings.complete', $booking), ['odometer_end' => 165.5])
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Completed->value,
            'odometer_end' => 165.5,
            'distance_km' => 65.5,
        ]);
        $this->assertNotNull($booking->refresh()->return_at);
        $this->assertDatabaseHas('vehicles', [
            'id' => $this->vehicle->id,
            'status' => VehicleStatus::Available->value,
            'current_odometer' => 165.5,
        ]);
        $this->assertDatabaseHas('drivers', ['id' => $this->driver->id, 'status' => DriverStatus::Available->value]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'booking', 'entity_id' => $booking->id, 'action' => 'completed']);
    }

    public function test_complete_rejects_odometer_end_below_start(): void
    {
        $booking = $this->inUseBooking();

        $this->actingAs($this->user)
            ->from(route('bookings.index'))
            ->post(route('bookings.complete', $booking), ['odometer_end' => 50])
            ->assertSessionHasErrors('odometer_end');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::InUse->value]);
    }

    public function test_non_in_use_booking_cannot_complete(): void
    {
        $booking = $this->approvedBooking();

        $this->actingAs($this->user)
            ->from(route('bookings.index'))
            ->post(route('bookings.complete', $booking), ['odometer_end' => 150]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::Approved->value]);
    }

    public function test_approver_cannot_access_usage_routes(): void
    {
        $approver = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => Employee::factory(),
        ]);
        $booking = $this->approvedBooking();

        $this->actingAs($approver)
            ->post(route('bookings.start', $booking), ['odometer_start' => 100])
            ->assertForbidden();
    }
}
