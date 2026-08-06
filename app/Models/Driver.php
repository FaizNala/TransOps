<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'license_number', 'license_type', 'license_expiry', 'status'])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'status' => DriverStatus::class,
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
