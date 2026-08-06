<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Models\AuditLog;
use App\Models\ServiceLog;
use App\Models\ServiceSchedule;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function schedules(Request $request)
    {
        $filters = $request->query('filter', []);

        $schedules = ServiceSchedule::query()
            ->with(['vehicle:id,plate_number,brand,model'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ServiceSchedule $schedule) => [
                'id' => $schedule->id,
                'vehicle' => trim("{$schedule->vehicle?->plate_number} {$schedule->vehicle?->brand} {$schedule->vehicle?->model}"),
                'vehicle_id' => $schedule->vehicle_id,
                'service_type' => $schedule->service_type,
                'scheduled_date' => $schedule->scheduled_date?->format('Y-m-d'),
                'scheduled_odometer' => $schedule->scheduled_odometer,
                'recurrence' => $this->recurrenceText($schedule),
                'status' => $this->scheduleStatus($schedule)->value,
                'status_label' => ucfirst($this->scheduleStatus($schedule)->value),
                'note' => $schedule->note,
            ]);

        return inertia('service/schedules', [
            'schedules' => $schedules,
            'filters' => $request->query('filter', []),
            'summary' => $this->scheduleSummary(),
            'vehicles' => $this->vehicleOptions(),
        ]);
    }

    private function scheduleSummary(): array
    {
        $today = today();

        $overdue = ServiceSchedule::query()
            ->where('status', ServiceStatus::Scheduled->value)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<', $today)
            ->count();

        $due = ServiceSchedule::query()
            ->where('status', ServiceStatus::Scheduled->value)
            ->whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', [$today, $today->addDays(7)])
            ->count();

        $scheduled = ServiceSchedule::query()
            ->where('status', ServiceStatus::Scheduled->value)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>', $today->addDays(7))
            ->count();

        $completed = ServiceSchedule::query()
            ->where('status', ServiceStatus::Scheduled->value)
            ->where('scheduled_date', '>', $today->addDays(7))
            ->count();

        return [
            ['label' => 'Terlambat', 'value' => $overdue],
            ['label' => 'Segera (7 hari)', 'value' => $due],
            ['label' => 'Terjadwal', 'value' => $completed],
            ['label' => 'Total Jadwal', 'value' => ServiceSchedule::query()->count()],
        ];
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'service_type' => ['required', 'string', 'max:50'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_odometer' => ['nullable', 'numeric', 'min:0'],
            'recurrence_km' => ['nullable', 'integer', 'min:0'],
            'recurrence_months' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $schedule = ServiceSchedule::create($validated + ['status' => ServiceStatus::Scheduled->value]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'entity_type' => 'service_schedule',
            'entity_id' => $schedule->id,
            'after' => ['vehicle_id' => $schedule->vehicle_id, 'service_type' => $schedule->service_type, 'scheduled_date' => $schedule->scheduled_date],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Jadwal service ditambahkan.',
        ]);

        return back();
    }

    public function destroySchedule(Request $request, ServiceSchedule $serviceSchedule)
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'entity_type' => 'service_schedule',
            'entity_id' => $serviceSchedule->id,
            'after' => ['vehicle_id' => $serviceSchedule->vehicle_id, 'service_type' => $serviceSchedule->service_type],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $serviceSchedule->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Jadwal service dihapus.',
        ]);

        return back();
    }

    public function logs(Request $request)
    {
        $filters = $request->query('filter', []);

        $logs = ServiceLog::query()
            ->with(['vehicle:id,plate_number,brand,model'])
            ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
            ->latest('serviced_date')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ServiceLog $log) => [
                'id' => $log->id,
                'vehicle' => trim("{$log->vehicle?->plate_number} {$log->vehicle?->brand} {$log->vehicle?->model}"),
                'vehicle_id' => $log->vehicle_id,
                'service_type' => $log->service_type,
                'serviced_date' => $log->serviced_date?->format('Y-m-d'),
                'odometer' => $log->odometer,
                'cost' => $log->cost,
                'vendor' => $log->vendor,
                'mechanic' => $log->mechanic,
                'note' => $log->note,
            ]);

        return inertia('service/logs', [
            'logs' => $logs,
            'filters' => $request->query('filter', []),
            'summary' => [
                ['label' => 'Total Servis', 'value' => ServiceLog::query()->count()],
                ['label' => 'Total Biaya', 'value' => 'Rp '.number_format((float) ServiceLog::query()->sum('cost'), 0, ',', '.')],
                ['label' => 'Kendaraan Diservis', 'value' => ServiceLog::query()->distinct('vehicle_id')->count('vehicle_id')],
                ['label' => 'Servis Bulan Ini', 'value' => ServiceLog::query()->where('serviced_date', '>=', now()->startOfMonth())->count()],
            ],
            'vehicles' => $this->vehicleOptions(),
            'schedules' => ServiceSchedule::query()
                ->whereIn('status', [ServiceStatus::Scheduled->value, ServiceStatus::Due->value])
                ->with('vehicle:id,plate_number,brand,model')
                ->orderByDesc('scheduled_date')
                ->limit(100)
                ->get()
                ->map(fn (ServiceSchedule $schedule) => [
                    'id' => $schedule->id,
                    'label' => "{$schedule->vehicle?->plate_number} · {$schedule->service_type}",
                ])
                ->values(),
        ]);
    }

    public function storeLog(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'service_schedule_id' => ['nullable', 'integer', Rule::exists('service_schedules', 'id')],
            'service_type' => ['required', 'string', 'max:50'],
            'serviced_date' => ['required', 'date'],
            'odometer' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'mechanic' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $log = ServiceLog::create($validated);

            if (($validated['service_schedule_id'] ?? null) && ($schedule = ServiceSchedule::find($validated['service_schedule_id']))) {
                if ($schedule->recurrence_months || $schedule->recurrence_km) {
                    $schedule->update([
                        'scheduled_date' => $schedule->recurrence_months
                            ? now()->addMonths($schedule->recurrence_months)->toDateString()
                            : null,
                        'scheduled_odometer' => $schedule->recurrence_km
                            ? (float) $validated['odometer'] + $schedule->recurrence_km
                            : null,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'created',
                'entity_type' => 'service_log',
                'entity_id' => $log->id,
                'after' => ['vehicle_id' => $log->vehicle_id, 'service_type' => $log->service_type, 'serviced_date' => $log->serviced_date, 'odometer' => $log->odometer],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Riwayat service dicatat.',
        ]);

        return back();
    }

    public function destroyLog(Request $request, ServiceLog $serviceLog)
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'entity_type' => 'service_log',
            'entity_id' => $serviceLog->id,
            'after' => ['vehicle_id' => $serviceLog->vehicle_id, 'service_type' => $serviceLog->service_type],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $serviceLog->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Riwayat service dihapus.',
        ]);

        return back();
    }

    private function scheduleStatus(ServiceSchedule $schedule): ServiceStatus
    {
        if ($schedule->status === ServiceStatus::Cancelled) {
            return ServiceStatus::Cancelled;
        }

        $today = today();

        if ($schedule->scheduled_date && $schedule->scheduled_date->lt($today)) {
            return ServiceStatus::Overdue;
        }

        if ($schedule->scheduled_date && $schedule->scheduled_date->lte($today->addDays(7))) {
            return ServiceStatus::Due;
        }

        return ServiceStatus::Scheduled;
    }

    private function recurrenceText(ServiceSchedule $schedule): ?string
    {
        $parts = [];
        if ($schedule->recurrence_km) {
            $parts[] = "{$schedule->recurrence_km} km";
        }
        if ($schedule->recurrence_months) {
            $parts[] = "{$schedule->recurrence_months} bln";
        }

        return $parts ? implode(' / ', $parts) : null;
    }

    private function vehicleOptions(): array
    {
        return Vehicle::query()
            ->where('is_active', true)
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'brand', 'model'])
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'label' => "{$vehicle->plate_number} · {$vehicle->brand} {$vehicle->model}",
            ])
            ->values()
            ->all();
    }
}
