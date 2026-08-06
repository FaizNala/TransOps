<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')
                    ->where('is_active', true)
                    ->where('status', VehicleStatus::Available->value),
            ],
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id')
                    ->where('status', DriverStatus::Available->value),
            ],
            'purpose' => ['required', 'string'],
            'destination' => ['required', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'approvers' => ['required', 'array', 'min:2', 'distinct'],
            'approvers.*' => ['required', 'integer', function ($attribute, $value, $fail) {
                $isApprover = Employee::query()
                    ->whereKey($value)
                    ->where('is_active', true)
                    ->whereHas('users', fn ($query) => $query->where('role', UserRole::Approver))
                    ->exists();

                if (! $isApprover) {
                    $fail('Approver tidak valid.');
                }
            }],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = $this->validated();
                $startAt = $data['start_at'] ?? null;
                $endAt = $data['end_at'] ?? null;

                if (! $startAt || ! $endAt) {
                    return;
                }

                $vehicle = Vehicle::find($data['vehicle_id'] ?? 0);
                if ($vehicle && $this->hasOverlap($vehicle->bookings(), $startAt, $endAt)) {
                    $validator->errors()->add('vehicle_id', 'Kendaraan sudah dipesan pada rentang waktu tersebut.');
                }

                $driver = Driver::find($data['driver_id'] ?? 0);
                if ($driver && $this->hasOverlap($driver->bookings(), $startAt, $endAt)) {
                    $validator->errors()->add('driver_id', 'Driver sudah memiliki pemesanan pada rentang waktu tersebut.');
                }
            },
        ];
    }

    private function hasOverlap($query, string $startAt, string $endAt): bool
    {
        return $query
            ->whereNotIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::Rejected->value,
                BookingStatus::Completed->value,
            ])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->when($this->route('booking'), fn ($query, $booking) => $query->whereKeyNot($booking->id))
            ->exists();
    }
}
