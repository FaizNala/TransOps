<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_exposes_stats_and_monthly_usage()
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $vehicle = Vehicle::factory()->create();
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Pending,
            'start_at' => now()->startOfMonth()->addDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 4)
            ->has('monthly_usage', 6)
            ->where('stats.vehicles_available', 1)
            ->where('stats.pending_approvals', 1));
    }

    public function test_approver_dashboard_exposes_own_approval_stats()
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => $employee->id,
        ]);
        $approver2 = Employee::factory()->create();
        User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => $approver2->id,
        ]);

        BookingApproval::factory()->create([
            'approver_id' => $employee->id,
            'status' => ApprovalStatus::Pending,
        ]);
        BookingApproval::factory()->create([
            'approver_id' => $employee->id,
            'status' => ApprovalStatus::Approved,
        ]);
        BookingApproval::factory()->create([
            'approver_id' => $employee->id,
            'status' => ApprovalStatus::Rejected,
        ]);
        BookingApproval::factory()->create([
            'approver_id' => $approver2->id,
            'status' => ApprovalStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('is_approver', true)
            ->where('stats.my_pending', 1)
            ->where('stats.my_approved', 1)
            ->where('stats.my_rejected', 1)
            ->where('stats.overall_pending', 4)
            ->has('pending_approvals', 1));
    }
}
