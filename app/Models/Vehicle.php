<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\OwnershipType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['plate_number', 'category', 'capacity', 'brand', 'model', 'year', 'ownership_type', 'rental_partner_id', 'rental_start_date', 'rental_end_date', 'fuel_type', 'tank_capacity', 'current_odometer', 'status', 'is_active'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
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

    /** @return BelongsTo<RentalPartner, $this> */
    public function rentalPartner()
    {
        return $this->belongsTo(RentalPartner::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<FuelLog, $this> */
    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }

    /** @return HasMany<ServiceSchedule, $this> */
    public function serviceSchedules()
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    /** @return HasMany<ServiceLog, $this> */
    public function serviceLogs()
    {
        return $this->hasMany(ServiceLog::class);
    }
}
