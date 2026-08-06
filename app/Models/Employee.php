<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_number', 'name', 'position', 'department', 'email', 'phone', 'location_id', 'manager_id', 'is_active'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Location, $this> */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /** @return HasMany<Employee, $this> */
    public function subordinates()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /** @return HasOne<Driver, $this> */
    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    /** @return HasMany<User, $this> */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Booking, $this> */
    public function requests()
    {
        return $this->hasMany(Booking::class, 'requester_id');
    }

    /** @return HasMany<BookingApproval, $this> */
    public function approvals()
    {
        return $this->hasMany(BookingApproval::class, 'approver_id');
    }
}
