<?php

namespace Tests\Feature\Booking;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManageBookingTest extends TestCase
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

    private function validPayload(Vehicle $vehicle, Driver $driver, array $approverIds): array
    {
        return [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'purpose' => 'Pengantaran logistik ke tambang',
            'destination' => 'Tambang Malili',
            'start_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(1)->addHours(6)->format('Y-m-d H:i:s'),
            'approvers' => $approverIds,
        ];
    }

    private function bookingWithChain(array $approverEmployees, ?BookingStatus $status = BookingStatus::Pending): Booking
    {
        $booking = Booking::factory()->create([
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'requester_id' => Employee::factory(),
            'created_by' => User::factory()->create(['role' => UserRole::Admin]),
            'status' => $status,
        ]);

        foreach ($approverEmployees as $index => $approverEmployee) {
            BookingApproval::factory()->create([
                'booking_id' => $booking->id,
                'approver_id' => $approverEmployee->id,
                'level' => $index + 1,
                'status' => ApprovalStatus::Pending,
            ]);
        }

        return $booking;
    }

    public function test_index_paginates_bookings(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        foreach (range(1, 12) as $_) {
            $this->bookingWithChain([$firstApprover, $secondApprover]);
        }

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('bookings/index')
                ->has('bookings.data', 10)
                ->where('bookings.total', 12));

        $this->actingAs($user)
            ->get(route('bookings.index', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->has('bookings.data', 2));
    }

    public function test_index_filters_by_status(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $this->bookingWithChain([$firstApprover, $secondApprover]);
        $approved = $this->bookingWithChain([$firstApprover, $secondApprover], BookingStatus::Approved);

        $this->actingAs($user)
            ->get(route('bookings.index', ['filter' => ['status' => 'approved']]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.id', $approved->id));
    }

    public function test_index_filters_by_search(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $this->bookingWithChain([$firstApprover, $secondApprover]);
        $target = $this->bookingWithChain([$firstApprover, $secondApprover]);

        $this->actingAs($user)
            ->get(route('bookings.index', ['filter' => ['search' => $target->booking_number]]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.id', $target->id));
    }

    public function test_admin_can_update_pending_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        [$newFirst, $newSecond] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover]);
        $newVehicle = Vehicle::factory()->create();
        $newDriver = Driver::factory()->create();

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->put(route('bookings.update', $booking), $this->validPayload($newVehicle, $newDriver, [$newFirst->id, $newSecond->id]))
            ->assertRedirect(route('bookings.index'));

        $booking->refresh();

        $this->assertSame($newVehicle->id, $booking->vehicle_id);
        $this->assertSame($newDriver->id, $booking->driver_id);
        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'approver_id' => $newFirst->id,
            'level' => 1,
            'status' => ApprovalStatus::Pending->value,
        ]);
        $this->assertDatabaseMissing('booking_approvals', [
            'booking_id' => $booking->id,
            'approver_id' => $firstApprover->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
            'action' => 'updated',
        ]);
    }

    public function test_admin_cannot_update_decided_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover], BookingStatus::Approved);
        $newVehicle = Vehicle::factory()->create();
        $newDriver = Driver::factory()->create();

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->put(route('bookings.update', $booking), $this->validPayload($newVehicle, $newDriver, [$firstApprover->id, $secondApprover->id]))
            ->assertRedirect(route('bookings.index'));

        $this->assertNotSame($newVehicle->id, $booking->refresh()->vehicle_id);
    }

    public function test_admin_can_delete_pending_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover]);

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->delete(route('bookings.destroy', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('booking_approvals', ['booking_id' => $booking->id]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
            'action' => 'deleted',
        ]);
    }

    public function test_admin_cannot_delete_decided_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover], BookingStatus::Approved);

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->delete(route('bookings.destroy', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_index_suggests_approvers_from_manager_chain(): void
    {
        [$user, $employee] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();

        $employee->update(['manager_id' => $firstApprover->id]);
        $firstApprover->update(['manager_id' => $secondApprover->id]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('suggested_approvers', [$firstApprover->id, $secondApprover->id]));
    }

    public function test_admin_can_cancel_pending_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover]);

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->post(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertSame(
            BookingStatus::Cancelled,
            $booking->fresh()->status,
        );
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
            'action' => 'cancelled',
        ]);
    }

    public function test_admin_can_cancel_approved_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover], BookingStatus::Approved);

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->post(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertSame(
            BookingStatus::Cancelled,
            $booking->fresh()->status,
        );
    }

    public function test_admin_cannot_cancel_completed_booking(): void
    {
        [$user] = $this->adminUser();
        [$firstApprover, $secondApprover] = $this->approverEmployees();
        $booking = $this->bookingWithChain([$firstApprover, $secondApprover], BookingStatus::Completed);

        $this->actingAs($user)
            ->from(route('bookings.index'))
            ->post(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertNotSame(
            BookingStatus::Cancelled,
            $booking->fresh()->status,
        );
    }
}
