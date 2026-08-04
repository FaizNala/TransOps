<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_id', 'service_type', 'scheduled_date', 'scheduled_odometer', 'recurrence_km', 'recurrence_months', 'status', 'note'])]
class ServiceSchedule extends Model
{
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class, 'service_schedule_id');
    }
}
