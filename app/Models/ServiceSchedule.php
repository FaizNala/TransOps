<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\ServiceScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_id', 'service_type', 'scheduled_date', 'scheduled_odometer', 'recurrence_km', 'recurrence_months', 'status', 'note'])]
class ServiceSchedule extends Model
{
    /** @use HasFactory<ServiceScheduleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'scheduled_odometer' => 'decimal:2',
            'recurrence_km' => 'integer',
            'recurrence_months' => 'integer',
            'status' => ServiceStatus::class,
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return HasMany<ServiceLog, $this> */
    public function serviceLogs()
    {
        return $this->hasMany(ServiceLog::class, 'service_schedule_id');
    }
}
