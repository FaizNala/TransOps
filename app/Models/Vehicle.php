<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['plate_number', 'category', 'capacity', 'brand', 'model', 'year', 'ownership_type', 'rental_partner_id', 'rental_start_date', 'rental_end_date', 'fuel_type', 'tank_capacity', 'current_odometer', 'status', 'is_active'])]
class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'category' => VehicleCategory::class,
            'capacity' => 'decimal:2',
            'year' => 'integer',
            'ownership_type' => OwnershipType::class,
            'rental_start_date' => 'date',
            'rental_end_date' => 'date',
            'fuel_type' => FuelType::class,
            'tank_capacity' => 'decimal:2',
            'current_odometer' => 'decimal:2',
            'status' => VehicleStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function rentalPartner(): BelongsTo
    {
        return $this->belongsTo(RentalPartner::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function serviceSchedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }
}
