<?php

namespace App\Http\Controllers;

use App\Enums\LocationType;
use App\Models\AuditLog;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $locations = Location::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('master/locations', [
            'locations' => $locations,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $location = Location::create($data);

        AuditLog::create($this->audit($request, 'created', null, $location->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Lokasi {$location->name} berhasil dibuat.",
        ]);

        return redirect()->route('locations.index');
    }

    public function update(Request $request, Location $location)
    {
        $data = $this->validated($request);

        $location->update($data);

        AuditLog::create($this->audit($request, 'updated', null, $location->toArray()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Lokasi {$location->name} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Location $location)
    {
        AuditLog::create($this->audit($request, 'deleted', $location->toArray(), null));

        $location->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Lokasi {$location->name} dihapus.",
        ]);

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('locations', 'code')->ignore($request->route('location'))],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(LocationType::class)],
            'region' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function audit(Request $request, string $action, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "location.{$action}",
            'entity_type' => 'location',
            'entity_id' => $request->route('location')?->id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
