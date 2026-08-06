<?php

namespace App\Models;

use Database\Factories\FuelLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'booking_id', 'recorded_by', 'refueled_at', 'odometer', 'liters', 'price_per_liter', 'total_cost', 'station', 'is_full_tank', 'note'])]
class FuelLog extends Model
{
    /** @use HasFactory<FuelLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'refueled_at' => 'datetime',
            'odometer' => 'decimal:2',
            'liters' => 'decimal:2',
            'price_per_liter' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'is_full_tank' => 'boolean',
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function recordedBy()
    {
        return $this->belongsTo(Employee::class, 'recorded_by');
    }
}
