<?php

namespace App\Models;

use Database\Factories\RentalPartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'contact_person', 'phone', 'email', 'address', 'is_active'])]
class RentalPartner extends Model
{
    /** @use HasFactory<RentalPartnerFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Vehicle, $this> */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
