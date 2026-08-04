<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingApproval>
 */
class BookingApprovalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'approver_id' => Employee::factory(),
            'level' => 1,
            'status' => ApprovalStatus::Pending,
            'note' => null,
            'decided_at' => null,
        ];
    }
}
