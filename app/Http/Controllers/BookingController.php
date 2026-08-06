<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Exports\BookingsExport;
use App\Http\Requests\StoreBookingRequest;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->query('filter', []);

        $bookings = Booking::query()
            ->with([
                'vehicle:id,plate_number,brand,model',
                'driver:id,employee_id',
                'driver.employee:id,name',
                'requester:id,name',
                'approvals:booking_id,approver_id,level',
            ])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhereHas('requester', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn ($query) => $query->where('plate_number', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'requester' => $booking->requester?->name,
                'vehicle' => trim("{$booking->vehicle?->plate_number} {$booking->vehicle?->brand} {$booking->vehicle?->model}"),
                'vehicle_id' => $booking->vehicle_id,
                'driver' => $booking->driver?->employee?->name,
                'driver_id' => $booking->driver_id,
                'purpose' => $booking->purpose,
                'destination' => $booking->destination,
                'start_at' => $booking->start_at?->format('Y-m-d\TH:i'),
                'end_at' => $booking->end_at?->format('Y-m-d\TH:i'),
                'status' => $booking->status->value,
                'status_label' => ucfirst($booking->status->value),
                'approver_ids' => $booking->approvals->pluck('approver_id')->all(),
            ]);

        return inertia('bookings/index', [
            'bookings' => $bookings,
            'filters' => $request->query('filter', []),
            'summary' => [
                ['label' => 'Total Pemesanan', 'value' => Booking::query()->count()],
                ['label' => 'Menunggu Persetujuan', 'value' => Booking::query()->where('status', BookingStatus::Pending)->count()],
                ['label' => 'Aktif / Disetujui', 'value' => Booking::query()->whereIn('status', [BookingStatus::Approved, BookingStatus::InUse])->count()],
                ['label' => 'Selesai Bulan Ini', 'value' => Booking::query()->where('status', BookingStatus::Completed)->where('start_at', '>=', now()->startOfMonth())->count()],
            ],
            'suggested_approvers' => $this->suggestedApprovers($request),
            'statuses' => collect(BookingStatus::cases())
                ->map(fn (BookingStatus $status) => ['value' => $status->value, 'label' => ucfirst($status->value)])
                ->values(),
            ...$this->selectOptions(),
        ]);
    }

    private function suggestedApprovers(Request $request): array
    {
        $employee = $request->user()?->employee;

        if (! $employee) {
            return [];
        }

        $ids = [];
        $seen = [];

        while ($employee && ! isset($seen[$employee->id])) {
            $seen[$employee->id] = true;
            $employee = $employee->manager;
            if ($employee && $employee->users()->where('role', UserRole::Approver)->exists()) {
                $ids[] = $employee->id;
            }
        }

        return $ids;
    }

    private function selectOptions(): array
    {
        return [
            'vehicles' => Vehicle::query()
                ->where('is_active', true)
                ->where('status', VehicleStatus::Available)
                ->orderBy('plate_number')
                ->get(['id', 'plate_number', 'brand', 'model'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => "{$vehicle->plate_number} · {$vehicle->brand} {$vehicle->model}",
                ])
                ->values(),
            'drivers' => Driver::query()
                ->where('status', DriverStatus::Available)
                ->with('employee:id,name')
                ->get(['id', 'employee_id'])
                ->map(fn (Driver $driver) => [
                    'id' => $driver->id,
                    'label' => $driver->employee?->name ?? "Driver #{$driver->id}",
                ])
                ->values(),
            'approvers' => Employee::query()
                ->where('is_active', true)
                ->whereHas('users', fn ($query) => $query->where('role', UserRole::Approver))
                ->orderBy('name')
                ->get(['id', 'name', 'position'])
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'label' => $employee->position ? "{$employee->name} · {$employee->position}" : $employee->name,
                ])
                ->values(),
        ];
    }

    public function store(StoreBookingRequest $request)
    {
        $user = $request->user();

        if (! $user->employee_id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun Anda belum terhubung ke data pegawai.',
            ]);

            return back();
        }

        $data = $request->validated();

        $booking = DB::transaction(function () use ($user, $data) {
            $booking = Booking::create([
                'vehicle_id' => $data['vehicle_id'],
                'requester_id' => $user->employee_id,
                'driver_id' => $data['driver_id'],
                'purpose' => $data['purpose'],
                'destination' => $data['destination'],
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
                'status' => BookingStatus::Pending,
                'created_by' => $user->id,
            ]);

            foreach (array_values($data['approvers']) as $level => $approverId) {
                $booking->approvals()->create([
                    'approver_id' => $approverId,
                    'level' => $level + 1,
                    'status' => ApprovalStatus::Pending,
                ]);
            }

            return $booking;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pemesanan {$booking->booking_number} berhasil dibuat.",
        ]);

        return redirect()->route('bookings.index');
    }

    public function update(StoreBookingRequest $request, Booking $booking)
    {
        if ($booking->status !== BookingStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Hanya pemesanan berstatus Pending yang dapat diubah.',
            ]);

            return back();
        }

        $data = $request->validated();

        DB::transaction(function () use ($booking, $data, $request) {
            $booking->update([
                'vehicle_id' => $data['vehicle_id'],
                'driver_id' => $data['driver_id'],
                'purpose' => $data['purpose'],
                'destination' => $data['destination'],
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
            ]);

            $booking->approvals()->delete();
            foreach (array_values($data['approvers']) as $level => $approverId) {
                $booking->approvals()->create([
                    'approver_id' => $approverId,
                    'level' => $level + 1,
                    'status' => ApprovalStatus::Pending,
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'before' => ['workflow' => 'reset_approvals'],
                'after' => ['booking_number' => $booking->booking_number, 'status' => $booking->status->value],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pemesanan {$booking->booking_number} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Booking $booking)
    {
        if ($booking->status !== BookingStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Hanya pemesanan berstatus Pending yang dapat dihapus.',
            ]);

            return back();
        }

        DB::transaction(function () use ($booking, $request) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'deleted',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'after' => ['booking_number' => $booking->booking_number],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $booking->delete();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pemesanan {$booking->booking_number} dihapus.",
        ]);

        return back();
    }

    public function start(Request $request, Booking $booking)
    {
        if ($booking->status !== BookingStatus::Approved) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Hanya pemesanan yang disetujui yang dapat dimulai.',
            ]);

            return back();
        }

        $validated = $request->validate([
            'odometer_start' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $booking, $validated) {
            $booking->update([
                'departure_at' => now(),
                'odometer_start' => $validated['odometer_start'],
                'status' => BookingStatus::InUse,
            ]);
            $booking->vehicle?->update(['status' => VehicleStatus::InUse]);
            $booking->driver?->update(['status' => DriverStatus::Assigned]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'started',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'before' => ['status' => BookingStatus::Approved->value],
                'after' => ['status' => BookingStatus::InUse->value, 'odometer_start' => $validated['odometer_start']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pemesanan {$booking->booking_number} dimulai.",
        ]);

        return back();
    }

    public function complete(Request $request, Booking $booking)
    {
        if ($booking->status !== BookingStatus::InUse) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Hanya pemesanan yang sedang berjalan yang dapat diselesaikan.',
            ]);

            return back();
        }

        $validated = $request->validate([
            'odometer_end' => ['required', 'numeric', 'min:'.max(0, (float) $booking->odometer_start)],
        ]);

        $distance = round((float) $validated['odometer_end'] - (float) $booking->odometer_start, 2);

        DB::transaction(function () use ($request, $booking, $validated, $distance) {
            $booking->update([
                'return_at' => now(),
                'odometer_end' => $validated['odometer_end'],
                'distance_km' => $distance,
                'status' => BookingStatus::Completed,
            ]);
            $booking->vehicle?->update([
                'current_odometer' => $validated['odometer_end'],
                'status' => VehicleStatus::Available,
            ]);
            $booking->driver?->update(['status' => DriverStatus::Available]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'completed',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'before' => ['status' => BookingStatus::InUse->value],
                'after' => ['status' => BookingStatus::Completed->value, 'odometer_end' => $validated['odometer_end'], 'distance_km' => $distance],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pemesanan {$booking->booking_number} selesai. Jarak {$distance} km.",
        ]);

        return back();
    }

    public function cancel(Request $request, Booking $booking)
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Approved], true)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Hanya pemesanan berstatus Pending atau Approved yang dapat dibatalkan.',
            ]);

            return back();
        }

        DB::transaction(function () use ($request, $booking) {
            $previousStatus = $booking->status;
            $booking->update(['status' => BookingStatus::Cancelled]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'cancelled',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'before' => ['status' => $previousStatus->value],
                'after' => ['status' => BookingStatus::Cancelled->value, 'booking_number' => $booking->booking_number],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pemesanan {$booking->booking_number} dibatalkan.",
        ]);

        return back();
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Booking::query()
            ->with([
                'vehicle:id,plate_number,brand,model',
                'requester:id,name,department',
                'driver:id,employee_id',
                'driver.employee:id,name',
            ])
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('start_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('start_at', '<=', $to))
            ->orderBy('start_at');

        $filename = 'laporan-pemesanan'
            .($validated['from'] ?? '' ? '-'.$validated['from'] : '')
            .($validated['to'] ?? '' ? '-'.$validated['to'] : '')
            .'.xlsx';

        return Excel::download(new BookingsExport($query), $filename);
    }

    public function approvals(Request $request)
    {
        $user = $request->user();

        if (! $user->employee_id) {
            return inertia('approvals/index', ['approvals' => []]);
        }

        $approvals = BookingApproval::query()
            ->where('approver_id', $user->employee_id)
            ->where('status', ApprovalStatus::Pending->value)
            ->with(['booking' => fn ($query) => $query->with([
                'vehicle:id,plate_number,brand,model',
                'driver:id,employee_id',
                'driver.employee:id,name',
                'requester:id,name,department',
                'approvals' => fn ($query) => $query->orderBy('level')->with('approver:id,name,position'),
            ])])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BookingApproval $item) {
                $booking = $item->booking;
                $pendingLevels = $booking->approvals
                    ->where('status', ApprovalStatus::Pending)
                    ->pluck('level');

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'purpose' => $booking->purpose,
                    'destination' => $booking->destination,
                    'start_at' => $booking->start_at?->format('Y-m-d H:i'),
                    'end_at' => $booking->end_at?->format('Y-m-d H:i'),
                    'requester' => $booking->requester?->name,
                    'requester_department' => $booking->requester?->department,
                    'vehicle' => trim("{$booking->vehicle?->plate_number} {$booking->vehicle?->brand} {$booking->vehicle?->model}"),
                    'driver' => $booking->driver?->employee?->name,
                    'my_level' => $item->level,
                    'can_decide' => $item->level === $pendingLevels->min(),
                    'chain' => $booking->approvals->map(fn (BookingApproval $approval) => [
                        'level' => $approval->level,
                        'name' => $approval->approver?->name,
                        'status' => $approval->status->value,
                        'decided_at' => $approval->decided_at?->format('d M Y H:i'),
                    ])->values(),
                ];
            })
            ->values();

        return inertia('approvals/index', ['approvals' => $approvals]);
    }

    public function decide(Request $request, Booking $booking)
    {
        $user = $request->user();

        if (! $user->employee_id) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::enum(ApprovalStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $action = ApprovalStatus::from($validated['action']);

        if ($booking->status !== BookingStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Pemesanan {$booking->booking_number} sudah diproses.",
            ]);

            return back();
        }

        $approval = $booking->approvals()
            ->where('approver_id', $user->employee_id)
            ->where('status', ApprovalStatus::Pending->value)
            ->first();

        if (! $approval) {
            abort(403, 'Anda tidak memiliki persetujuan tertunda pada pemesanan ini.');
        }

        $lowestPendingLevel = $booking->approvals()
            ->where('status', ApprovalStatus::Pending->value)
            ->min('level');

        if ($approval->level !== $lowestPendingLevel) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Menunggu persetujuan level sebelumnya.',
            ]);

            return back();
        }

        $note = $validated['note'] ?? null;

        DB::transaction(function () use ($user, $booking, $approval, $action, $note, $request) {
            $approval->update([
                'status' => $action,
                'note' => $note,
                'decided_at' => now(),
            ]);

            if ($action === ApprovalStatus::Rejected) {
                $booking->update(['status' => BookingStatus::Rejected]);
            } elseif (! $booking->approvals()->where('status', ApprovalStatus::Pending->value)->exists()) {
                $booking->update(['status' => BookingStatus::Approved]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action === ApprovalStatus::Approved ? 'approved' : 'rejected',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'before' => ['status' => BookingStatus::Pending->value, 'approval_level' => $approval->level],
                'after' => ['status' => $booking->status->value, 'approval_level' => $approval->level, 'note' => $note],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => $action === ApprovalStatus::Approved ? 'success' : 'error',
            'message' => $action === ApprovalStatus::Approved
                ? ($booking->status === BookingStatus::Approved
                    ? "Pemesanan {$booking->booking_number} disetujui semua level."
                    : "Level {$approval->level} disetujui.")
                : "Pemesanan {$booking->booking_number} ditolak.",
        ]);

        return back();
    }
}
