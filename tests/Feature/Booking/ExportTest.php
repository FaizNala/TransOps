<?php

namespace Tests\Feature\Booking;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): array
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'employee_id' => $employee->id,
        ]);

        return [$user, $employee];
    }

    public function test_admin_can_export_bookings_as_excel(): void
    {
        Excel::fake();
        [$user] = $this->adminUser();

        $this->actingAs($user)->get(route('bookings.export'));

        Excel::assertDownloaded('laporan-pemesanan.xlsx');
    }

    public function test_export_filename_includes_date_range_filter(): void
    {
        Excel::fake();
        [$user] = $this->adminUser();

        $from = now()->subDays(10)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $this->actingAs($user)->get(route('bookings.export', ['from' => $from, 'to' => $to]));

        Excel::assertDownloaded("laporan-pemesanan-{$from}-{$to}.xlsx");
    }

    public function test_non_admin_cannot_export(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Approver,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($user)
            ->get(route('bookings.export'))
            ->assertForbidden();
    }
}
