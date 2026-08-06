<?php

namespace App\Http\Controllers;

use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\AuditLog;
use App\Models\RentalPartner;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $vehicles = Vehicle::query()
            ->with('rentalPartner:id,name')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('master/vehicles', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'rental_partners' => RentalPartner::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $vehicle = Vehicle::create($data);

        AuditLog::create($this->audit($request, 'created', $vehicle->id, null, $vehicle->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Kendaraan {$vehicle->plate_number} berhasil dibuat.",
        ]);

        return redirect()->route('vehicles.index');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $this->validated($request);

        $vehicle->update($data);

        AuditLog::create($this->audit($request, 'updated', $vehicle->id, $vehicle->getOriginal(), $vehicle->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Kendaraan {$vehicle->plate_number} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        AuditLog::create($this->audit($request, 'deleted', $vehicle->id, $vehicle->toArray(), null));

        $vehicle->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Kendaraan {$vehicle->plate_number} dihapus.",
        ]);

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($request->route('vehicle'))],
            'category' => ['required', Rule::enum(VehicleCategory::class)],
            'capacity' => ['nullable', 'numeric'],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'ownership_type' => ['required', Rule::enum(OwnershipType::class)],
            'rental_partner_id' => ['nullable', 'integer', 'exists:rental_partners,id'],
            'rental_start_date' => ['nullable', 'date'],
            'rental_end_date' => ['nullable', 'date', 'after_or_equal:rental_start_date'],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],
            'tank_capacity' => ['nullable', 'numeric'],
            'current_odometer' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function audit(Request $request, string $action, int $id, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "vehicle.{$action}",
            'entity_type' => 'vehicle',
            'entity_id' => $id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
