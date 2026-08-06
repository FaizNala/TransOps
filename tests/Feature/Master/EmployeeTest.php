<?php

namespace Tests\Feature\Master;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'employee_id' => Employee::factory(),
        ]);
    }

    public function test_admin_store_employee_creates_linked_user_with_audits(): void
    {
        $admin = $this->adminUser();
        $location = Location::factory()->create();
        $manager = Employee::factory()->create();

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), [
                'employee_number' => 'EMP-0100',
                'name' => 'Fajar Nugroho',
                'position' => 'Staff Operasi',
                'department' => 'Operasi',
                'email' => 'fajar@example.com',
                'phone' => '081299998888',
                'location_id' => $location->id,
                'manager_id' => $manager->id,
                'is_active' => true,
                'user_role' => 'approver',
                'user_password' => 'rahasia123',
            ])
            ->assertRedirect(route('employees.index'));

        $employee = Employee::query()->where('employee_number', 'EMP-0100')->firstOrFail();
        $user = User::query()->where('email', 'fajar@example.com')->firstOrFail();

        $this->assertSame($employee->id, $user->employee_id);
        $this->assertSame(UserRole::Approver, $user->role);
        $this->assertTrue(password_verify('rahasia123', $user->password));
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'employee', 'entity_id' => $employee->id, 'action' => 'employee.created']);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'user', 'entity_id' => $user->id, 'action' => 'user.created']);
    }

    public function test_store_requires_email(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), [
                'employee_number' => 'EMP-0101',
                'name' => 'Gita Permata',
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_requires_role_and_password_for_account(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), [
                'employee_number' => 'EMP-0101',
                'name' => 'Gita Permata',
                'email' => 'gita@example.com',
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['user_role', 'user_password']);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_rejects_duplicate_email_on_existing_user(): void
    {
        $admin = $this->adminUser();
        User::factory()->create(['email' => 'dipakai@example.com']);

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), [
                'employee_number' => 'EMP-0102',
                'name' => 'Hadi Setiawan',
                'email' => 'dipakai@example.com',
                'is_active' => true,
                'user_role' => 'approver',
                'user_password' => 'rahasia123',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_update_employee_syncs_linked_user_name_and_email(): void
    {
        $admin = $this->adminUser();
        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'name' => $employee->name,
            'email' => $employee->email,
            'employee_id' => $employee->id,
            'role' => UserRole::Approver,
        ]);

        $this->actingAs($admin)
            ->from(route('employees.index'))
            ->put(route('employees.update', $employee), [
                'employee_number' => $employee->employee_number,
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
                'position' => $employee->position,
                'department' => $employee->department,
                'phone' => $employee->phone,
                'location_id' => $employee->location_id,
                'manager_id' => $employee->manager_id,
                'is_active' => true,
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertSame('Nama Baru', $user->refresh()->name);
        $this->assertSame('baru@example.com', $user->refresh()->email);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'user', 'entity_id' => $user->id, 'action' => 'user.updated']);
    }

    public function test_index_shows_linked_user_role(): void
    {
        $admin = $this->adminUser();
        $employee = Employee::factory()->create([
            'employee_number' => 'EMP-0200',
            'name' => 'Ria Anggraini',
            'email' => 'ria@example.com',
        ]);
        User::factory()->create([
            'name' => $employee->name,
            'email' => $employee->email,
            'employee_id' => $employee->id,
            'role' => UserRole::Approver,
        ]);

        $this->actingAs($admin)
            ->get(route('employees.index', ['filter' => ['search' => 'Ria']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('master/employees')
                ->has('employees.data', 1)
                ->where('employees.data.0.users.0.role', 'approver'));
    }

    public function test_approver_cannot_access_employee_routes(): void
    {
        $approver = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => Employee::factory(),
        ]);

        $this->actingAs($approver)
            ->get(route('employees.index'))
            ->assertForbidden();
    }
}
