<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Database\Factories\BookingApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'approver_id', 'level', 'status', 'note', 'decided_at'])]
class BookingApproval extends Model
{
    /** @use HasFactory<BookingApprovalFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}
