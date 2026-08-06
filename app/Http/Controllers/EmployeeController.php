<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $employees = Employee::query()
            ->with(['location:id,name', 'manager:id,name', 'users:id,name,email,role,employee_id'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('master/employees', [
            'employees' => $employees,
            'filters' => $filters,
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'managers' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $userData = $request->validate([
            'user_role' => ['required', Rule::enum(UserRole::class)],
            'user_password' => ['required', 'string', 'min:8'],
        ]);

        $employee = DB::transaction(function () use ($request, $data, $userData) {
            $employee = Employee::create($data);

            $user = User::create([
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => $userData['user_password'],
                'role' => UserRole::from($userData['user_role']),
                'employee_id' => $employee->id,
                'email_verified_at' => now(),
            ]);

            AuditLog::create($this->audit($request, 'created', $employee->id, null, $employee->toArray()));
            AuditLog::create($this->userAudit($request, 'created', $user->id, null, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ]));

            return $employee;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pegawai {$employee->name} dan akun loginnya berhasil dibuat.",
        ]);

        return redirect()->route('employees.index');
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validated($request);

        $employee->update($data);

        AuditLog::create($this->audit($request, 'updated', $employee->id, $employee->getOriginal(), $employee->toArray()));

        $user = $employee->users()->first();

        if ($user) {
            $user->update([
                'name' => $employee->name,
                'email' => $employee->email,
            ]);

            AuditLog::create($this->userAudit($request, 'updated', $user->id, $user->getOriginal(), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ]));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pegawai {$employee->name} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Employee $employee)
    {
        AuditLog::create($this->audit($request, 'deleted', $employee->id, $employee->toArray(), null));

        $employee->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pegawai {$employee->name} dihapus.",
        ]);

        return back();
    }

    private function validated(Request $request): array
    {
        $employee = $request->route('employee');

        return $request->validate([
            'employee_number' => ['required', 'string', 'max:30', 'unique:employees,employee_number,'.$employee?->id],
            'name' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->users()->value('id'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id', Rule::notIn([$employee?->id])],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function audit(Request $request, string $action, int $id, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "employee.{$action}",
            'entity_type' => 'employee',
            'entity_id' => $id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    private function userAudit(Request $request, string $action, int $id, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "user.{$action}",
            'entity_type' => 'user',
            'entity_id' => $id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
