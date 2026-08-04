<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Enums\LocationType;
use App\Enums\OwnershipType;
use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Location;
use App\Models\RentalPartner;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $locations = Location::create([
            'code' => 'HQ',
            'name' => 'Kantor Pusat',
            'type' => LocationType::HeadOffice,
            'region' => 'Jakarta',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
            'is_active' => true,
        ]);
        $branch = Location::create([
            'code' => 'BRC',
            'name' => 'Kantor Cabang Sorowako',
            'type' => LocationType::BranchOffice,
            'region' => 'Sulawesi Selatan',
            'address' => 'Jl. Poros Malili-Sorowako, Luwu Timur',
            'is_active' => true,
        ]);
        foreach ([
            ['M01', 'Tambang Malili', 'Sulawesi Selatan'],
            ['M02', 'Tambang Nuha', 'Sulawesi Selatan'],
            ['M03', 'Tambang Wasuponda', 'Sulawesi Selatan'],
            ['M04', 'Tambang Matano', 'Sulawesi Selatan'],
            ['M05', 'Tambang Towuti', 'Sulawesi Selatan'],
            ['M06', 'Tambang Larona', 'Sulawesi Selatan'],
        ] as [$code, $name, $region]) {
            Location::create([
                'code' => $code,
                'name' => $name,
                'type' => LocationType::Mine,
                'region' => $region,
                'address' => "Area Operasi $name",
                'is_active' => true,
            ]);
        }

        $gm = Employee::create([
            'employee_number' => 'EMP-0001',
            'name' => 'Andi Wijaya',
            'position' => 'General Manager',
            'department' => 'Operasi',
            'email' => 'andi@transops.test',
            'phone' => '081200000001',
            'location_id' => $locations->id,
            'manager_id' => null,
            'is_active' => true,
        ]);
        $admin = Employee::create([
            'employee_number' => 'EMP-0002',
            'name' => 'Admin Pool',
            'position' => 'Admin Pool',
            'department' => 'Pool',
            'email' => 'admin@transops.test',
            'phone' => '081200000002',
            'location_id' => $locations->id,
            'manager_id' => $gm->id,
            'is_active' => true,
        ]);
        User::create([
            'name' => 'Admin Pool',
            'email' => 'admin@transops.test',
            'password' => 'password',
            'employee_id' => $admin->id,
            'role' => UserRole::Admin,
        ]);

        $approvers = collect();
        foreach ([
            ['EMP-0003', 'Budi Santoso', 'Manager Operasi', 'Operasi', $locations->id, $gm->id, 'budi@transops.test'],
            ['EMP-0004', 'Citra Lestari', 'Manager HR', 'HR', $branch->id, $gm->id, 'citra@transops.test'],
            ['EMP-0005', 'Dedi Kurniawan', 'Supervisor Tambang Malili', 'Operasi', null, null, 'dedi@transops.test'],
            ['EMP-0006', 'Eka Prasetyo', 'Supervisor Transportasi', 'Transportasi', $branch->id, null, 'eka@transops.test'],
        ] as [$no, $name, $position, $dept, $location, $manager, $email]) {
            $emp = Employee::create([
                'employee_number' => $no,
                'name' => $name,
                'position' => $position,
                'department' => $dept,
                'email' => $email,
                'phone' => fake()->phoneNumber(),
                'location_id' => $location,
                'manager_id' => $manager,
                'is_active' => true,
            ]);
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'employee_id' => $emp->id,
                'role' => UserRole::Approver,
            ]);
            $approvers->push($emp->id);
        }

        Employee::factory()->count(20)->create([
            'location_id' => fake()->randomElement(Location::pluck('id')->all()),
            'manager_id' => fake()->randomElement($approvers->push($gm->id)->all()),
        ]);

        foreach (Employee::inRandomOrder()->limit(8)->get() as $emp) {
            Driver::create([
                'employee_id' => $emp->id,
                'license_number' => fake()->unique()->numerify('SIM-########'),
                'license_type' => fake()->randomElement(['A', 'B1', 'B2', 'C']),
                'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years'),
                'status' => DriverStatus::Available,
            ]);
        }

        RentalPartner::factory()->count(2)->create();
        Vehicle::factory()->count(9)->create();
        Vehicle::factory()->count(3)->create([
            'ownership_type' => OwnershipType::Rental,
            'rental_partner_id' => RentalPartner::inRandomOrder()->first()->id,
            'rental_start_date' => now()->subMonth(),
            'rental_end_date' => now()->addMonths(11),
        ]);
    }
}
