<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_id', 'requester_id', 'driver_id', 'purpose', 'destination', 'start_at', 'end_at', 'status', 'created_by', 'departure_at', 'return_at', 'odometer_start', 'odometer_end', 'distance_km', 'note'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (! $booking->booking_number) {
                $month = date('Ym');
                $sequence = Booking::query()
                    ->whereYear('created_at', date('Y'))
                    ->whereMonth('created_at', date('m'))
                    ->count() + 1;
                $booking->booking_number = "BK-{$month}-".str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => BookingStatus::class,
            'departure_at' => 'datetime',
            'return_at' => 'datetime',
            'odometer_start' => 'decimal:2',
            'odometer_end' => 'decimal:2',
            'distance_km' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function requester()
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<BookingApproval, $this> */
    public function approvals()
    {
        return $this->hasMany(BookingApproval::class);
    }

    /** @return HasMany<FuelLog, $this> */
    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }
}
