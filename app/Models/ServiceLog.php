<?php

namespace App\Models;

use Database\Factories\ServiceLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'service_schedule_id', 'service_type', 'serviced_date', 'odometer', 'cost', 'vendor', 'mechanic', 'note'])]
class ServiceLog extends Model
{
    /** @use HasFactory<ServiceLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'serviced_date' => 'date',
            'odometer' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<ServiceSchedule, $this> */
    public function schedule()
    {
        return $this->belongsTo(ServiceSchedule::class, 'service_schedule_id');
    }
}
