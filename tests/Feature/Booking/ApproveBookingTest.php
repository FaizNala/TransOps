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
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApproveBookingTest extends TestCase
{
    use RefreshDatabase;

    private function approverUser(): array
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => $employee->id,
        ]);

        return [$user, $employee];
    }

    private function bookingWithChain(array $approverEmployees): Booking
    {
        $booking = Booking::factory()->create([
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'requester_id' => Employee::factory(),
            'created_by' => User::factory()->create(['role' => UserRole::Admin]),
            'status' => BookingStatus::Pending,
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

    private function approve(Booking $booking, User $user, array $payload = ['action' => 'approved']): TestResponse
    {
        return $this->actingAs($user)
            ->from(route('approvals.index'))
            ->post(route('approvals.decide', $booking), $payload);
    }

    public function test_approver_can_approve_first_level_while_lower_levels_remain_pending(): void
    {
        [$firstUser, $firstEmployee] = $this->approverUser();
        [, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->approve($booking, $firstUser, ['action' => 'approved', 'note' => 'Oke'])->assertRedirect();

        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'level' => 1,
            'status' => ApprovalStatus::Approved->value,
            'note' => 'Oke',
        ]);
        $this->assertDatabaseMissing('booking_approvals', [
            'booking_id' => $booking->id,
            'level' => 1,
            'decided_at' => null,
        ]);
        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'level' => 2,
            'status' => ApprovalStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Pending->value,
        ]);
    }

    public function test_last_approval_sets_booking_to_approved(): void
    {
        [$firstUser, $firstEmployee] = $this->approverUser();
        [$secondUser, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->approve($booking, $firstUser);
        $this->approve($booking, $secondUser);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Approved->value,
        ]);
    }

    public function test_rejection_marks_booking_as_rejected(): void
    {
        [$firstUser, $firstEmployee] = $this->approverUser();
        [, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->approve($booking, $firstUser, ['action' => 'rejected', 'note' => 'Tidak layak']);

        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'level' => 1,
            'status' => ApprovalStatus::Rejected->value,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Rejected->value,
        ]);
    }

    public function test_higher_level_cannot_decide_before_lower_level(): void
    {
        [$firstUser, $firstEmployee] = $this->approverUser();
        [$secondUser, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->approve($booking, $secondUser)->assertRedirect();

        $this->assertDatabaseHas('booking_approvals', [
            'booking_id' => $booking->id,
            'level' => 2,
            'status' => ApprovalStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Pending->value,
        ]);
    }

    public function test_unrelated_user_cannot_decide(): void
    {
        [$outsiderUser, $outsiderEmployee] = $this->approverUser();
        [, $firstEmployee] = $this->approverUser();
        [, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->approve($booking, $outsiderUser)->assertForbidden();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }

    public function test_approval_writes_audit_log(): void
    {
        [$firstUser, $firstEmployee] = $this->approverUser();
        [, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->approve($booking, $firstUser);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $firstUser->id,
            'action' => 'approved',
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
        ]);
    }

    public function test_waiting_list_shows_only_pending_items_and_turn_flag(): void
    {
        [$firstUser, $firstEmployee] = $this->approverUser();
        [$secondUser, $secondEmployee] = $this->approverUser();
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);

        $this->actingAs($firstUser)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approvals/index')
                ->has('approvals', 1)
                ->where('approvals.0.id', $booking->id)
                ->where('approvals.0.can_decide', true)
                ->where('approvals.0.chain.0.status', 'pending')
                ->where('approvals.0.chain.1.status', 'pending'));

        $this->actingAs($secondUser)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('approvals', 1)
                ->where('approvals.0.my_level', 2)
                ->where('approvals.0.can_decide', false));
    }

    public function test_admin_cannot_access_approval_routes(): void
    {
        [$firstEmployee, $secondEmployee] = [Employee::factory()->create(), Employee::factory()->create()];
        $booking = $this->bookingWithChain([$firstEmployee, $secondEmployee]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('approvals.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('approvals.decide', $booking), ['action' => 'approved'])
            ->assertForbidden();
    }
}
