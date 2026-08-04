<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_id', 'requester_id', 'driver_id', 'purpose', 'destination', 'start_at', 'end_at', 'status', 'created_by', 'departure_at', 'return_at', 'odometer_start', 'odometer_end', 'distance_km', 'note'])]
class Booking extends Model
{
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
                // ponytail: count-based sequence; rawan race hanya pada konkurensi ketat,
                // cukup untuk demo — pakai tabel sequence bila produksi.
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }
}
