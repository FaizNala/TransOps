<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FuelLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $logs = FuelLog::query()
            ->with(['vehicle:id,plate_number,brand,model'])
            ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
            ->latest('refueled_at')
            ->paginate(10)
            ->withQueryString();

        // Konsumsi km/liter: bandwidth antar pengisian full-tank.
        $vehicleIds = $logs->getCollection()->pluck('vehicle_id')->unique()->all();
        $history = FuelLog::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->orderBy('vehicle_id')
            ->orderBy('refueled_at')
            ->orderBy('id')
            ->get(['id', 'vehicle_id', 'refueled_at', 'odometer', 'liters', 'is_full_tank']);

        $prevFullOdometer = [];
        $consumption = [];
        foreach ($history as $log) {
            if ($log->is_full_tank) {
                $prev = $prevFullOdometer[$log->vehicle_id] ?? null;
                if ($prev !== null && $log->odometer > $prev && (float) $log->liters > 0) {
                    $consumption[$log->id] = round(((float) $log->odometer - $prev) / (float) $log->liters, 2);
                }
                $prevFullOdometer[$log->vehicle_id] = (float) $log->odometer;
            }
        }

        $rows = $logs->getCollection()
            ->map(fn (FuelLog $log) => [
                'id' => $log->id,
                'vehicle' => trim("{$log->vehicle?->plate_number} {$log->vehicle?->brand} {$log->vehicle?->model}"),
                'vehicle_id' => $log->vehicle_id,
                'refueled_at' => $log->refueled_at?->format('Y-m-d H:i'),
                'odometer' => $log->odometer,
                'liters' => $log->liters,
                'price_per_liter' => $log->price_per_liter,
                'total_cost' => $log->total_cost,
                'is_full_tank' => $log->is_full_tank,
                'station' => $log->station,
                'consumption' => $log->is_full_tank ? ($consumption[$log->id] ?? null) : null,
            ])
            ->values();
        $logs->setCollection($rows);

        return inertia('fuel-logs/index', [
            'logs' => $logs,
            'filters' => $request->query('filter', []),
            'summary' => [
                ['label' => 'Total Pengisian', 'value' => FuelLog::query()->count()],
                ['label' => 'Total Liter', 'value' => number_format((float) FuelLog::query()->sum('liters'), 1)],
                ['label' => 'Total Biaya', 'value' => 'Rp '.number_format((float) FuelLog::query()->sum('total_cost'), 0, ',', '.')],
                ['label' => 'Konsumsi Rata-rata', 'value' => $this->averageConsumption().' km/l'],
            ],
            'vehicles' => Vehicle::query()
                ->where('is_active', true)
                ->orderBy('plate_number')
                ->get(['id', 'plate_number', 'brand', 'model'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => "{$vehicle->plate_number} · {$vehicle->brand} {$vehicle->model}",
                ])
                ->values(),
        ]);
    }

    private function averageConsumption(): ?string
    {
        $rows = FuelLog::query()
            ->orderBy('vehicle_id')
            ->orderBy('refueled_at')
            ->orderBy('id')
            ->get(['vehicle_id', 'odometer', 'liters', 'is_full_tank']);

        $prevFullOdometer = [];
        $values = [];
        foreach ($rows as $log) {
            if ($log->is_full_tank) {
                $prev = $prevFullOdometer[$log->vehicle_id] ?? null;
                if ($prev !== null && $log->odometer > $prev && (float) $log->liters > 0) {
                    $values[] = ((float) $log->odometer - $prev) / (float) $log->liters;
                }
                $prevFullOdometer[$log->vehicle_id] = (float) $log->odometer;
            }
        }

        return $values ? number_format(array_sum($values) / count($values), 2) : null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'refueled_at' => ['required', 'date'],
            'odometer' => ['required', 'numeric', 'min:0'],
            'liters' => ['required', 'numeric', 'min:0'],
            'price_per_liter' => ['required', 'numeric', 'min:0'],
            'station' => ['nullable', 'string', 'max:100'],
            'is_full_tank' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['is_full_tank'] = $request->boolean('is_full_tank');
        $validated['total_cost'] = round((float) $validated['liters'] * (float) $validated['price_per_liter'], 2);
        $validated['recorded_by'] = $request->user()?->employee_id;

        $log = FuelLog::create($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'entity_type' => 'fuel_log',
            'entity_id' => $log->id,
            'after' => ['vehicle_id' => $log->vehicle_id, 'refueled_at' => $log->refueled_at, 'odometer' => $log->odometer, 'total_cost' => $log->total_cost],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Catatan BBM berhasil disimpan.',
        ]);

        return back();
    }

    public function destroy(Request $request, FuelLog $fuelLog)
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'entity_type' => 'fuel_log',
            'entity_id' => $fuelLog->id,
            'after' => ['vehicle_id' => $fuelLog->vehicle_id, 'odometer' => $fuelLog->odometer],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $fuelLog->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Catatan BBM dihapus.',
        ]);

        return back();
    }
}
