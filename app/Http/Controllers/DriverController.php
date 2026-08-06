<?php

namespace App\Http\Controllers;

use App\Enums\DriverStatus;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $drivers = Driver::query()
            ->with('employee:id,name,employee_number')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('license_number', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('master/drivers', [
            'drivers' => $drivers,
            'filters' => $filters,
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_number']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $driver = Driver::create($data);

        AuditLog::create($this->audit($request, 'created', $driver->id, null, $driver->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Driver {$driver->license_number} berhasil dibuat.",
        ]);

        return redirect()->route('drivers.index');
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $this->validated($request);

        $driver->update($data);

        AuditLog::create($this->audit($request, 'updated', $driver->id, $driver->getOriginal(), $driver->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Driver {$driver->license_number} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Driver $driver)
    {
        AuditLog::create($this->audit($request, 'deleted', $driver->id, $driver->toArray(), null));

        $driver->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Driver dihapus.',
        ]);

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
                Rule::unique('drivers', 'employee_id')->ignore($request->route('driver')),
            ],
            'license_number' => ['required', 'string', 'max:30', Rule::unique('drivers', 'license_number')->ignore($request->route('driver'))],
            'license_type' => ['nullable', 'string', 'max:50'],
            'license_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(DriverStatus::class)],
        ]);
    }

    private function audit(Request $request, string $action, int $id, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "driver.{$action}",
            'entity_type' => 'driver',
            'entity_id' => $id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
