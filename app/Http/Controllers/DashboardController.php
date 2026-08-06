<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === UserRole::Approver) {
            return $this->approverDashboard($user);
        }

        $stats = [
            'vehicles_available' => Vehicle::query()
                ->where('is_active', true)
                ->where('status', VehicleStatus::Available)
                ->count(),
            'active_bookings' => Booking::query()
                ->where('status', BookingStatus::Approved)
                ->count(),
            'pending_approvals' => Booking::query()
                ->where('status', BookingStatus::Pending)
                ->count(),
            'overdue_services' => ServiceSchedule::query()
                ->whereIn('status', [ServiceStatus::Scheduled, ServiceStatus::Due])
                ->where('scheduled_date', '<', now())
                ->count(),
        ];

        $months = collect(range(5, 0))->map(
            fn (int $offset) => now()->startOfMonth()->subMonths($offset)
        );

        $rows = Booking::query()
            ->selectRaw("strftime('%Y-%m', start_at) as ym, status, count(*) as total")
            ->whereBetween('start_at', [
                $months->first()->startOfDay(),
                $months->last()->copy()->endOfMonth()->endOfDay(),
            ])
            ->groupBy('ym', 'status')
            ->get();

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $monthlyUsage = $months->map(function ($month) use ($rows, $monthNames) {
            $ym = $month->format('Y-m');
            $monthRows = $rows->where('ym', $ym);

            return [
                'month' => $monthNames[$month->month - 1],
                'total' => $monthRows->sum('total'),
                'completed' => $monthRows
                    ->where('status', BookingStatus::Completed->value)
                    ->sum('total'),
            ];
        })->values();

        return inertia('dashboard', [
            'stats' => $stats,
            'monthly_usage' => $monthlyUsage,
        ]);
    }

    private function approverDashboard(User $user)
    {
        $myApprovals = BookingApproval::query()->where('approver_id', $user->employee_id);

        $stats = [
            'my_pending' => (clone $myApprovals)->where('status', ApprovalStatus::Pending)->count(),
            'my_approved' => (clone $myApprovals)->where('status', ApprovalStatus::Approved)->count(),
            'my_rejected' => (clone $myApprovals)->where('status', ApprovalStatus::Rejected)->count(),
            'overall_pending' => Booking::query()->where('status', BookingStatus::Pending)->count(),
        ];

        $pendingApprovals = BookingApproval::query()
            ->where('approver_id', $user->employee_id)
            ->where('status', ApprovalStatus::Pending)
            ->with(['booking' => fn ($query) => $query->with([
                'vehicle:id,plate_number,brand,model',
                'requester:id,name',
            ])])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (BookingApproval $approval) => [
                'id' => $approval->booking->id,
                'booking_number' => $approval->booking->booking_number,
                'requester' => $approval->booking->requester?->name,
                'vehicle' => trim("{$approval->booking->vehicle?->plate_number} {$approval->booking->vehicle?->brand} {$approval->booking->vehicle?->model}"),
                'my_level' => $approval->level,
            ]);

        return inertia('dashboard', [
            'is_approver' => true,
            'stats' => $stats,
            'pending_approvals' => $pendingApprovals,
        ]);
    }
}
