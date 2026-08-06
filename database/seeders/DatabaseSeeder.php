<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\FuelType;
use App\Enums\LocationType;
use App\Enums\OwnershipType;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\FuelLog;
use App\Models\Location;
use App\Models\RentalPartner;
use App\Models\ServiceLog;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedLocations();
        $org = $this->seedOrganisation();
        $fleet = $this->seedFleet();
        $this->seedBookings($org, $fleet);
        $this->seedServiceHistory($fleet);
        $this->seedAuditLogs($org);
    }

    /**
     * Wilayah operasi pertambangan nikel di Luwu Timur (Sorowako/Malili).
     */
    private function seedLocations(): void
    {
        $locations = [
            ['HQ', 'Kantor Pusat Jakarta', LocationType::HeadOffice, 'DKI Jakarta', 'Gedung Menara Bidakara, Jl. Jend. Gatot Subroto, Jakarta Selatan'],
            ['BRC', 'Kantor Cabang Sorowako', LocationType::BranchOffice, 'Sulawesi Selatan', 'Jl. Poros Malili-Sorowako, Kec. Wasuponda, Kab. Luwu Timur'],
            ['MAL', 'Tambang Malili', LocationType::Mine, 'Sulawesi Selatan', 'Site Malili, Kab. Luwu Timur'],
            ['NUH', 'Tambang Nuha', LocationType::Mine, 'Sulawesi Selatan', 'Site Nuha, Kec. Nuha, Kab. Luwu Timur'],
            ['WAS', 'Tambang Wasuponda', LocationType::Mine, 'Sulawesi Selatan', 'Site Wasuponda, Kec. Wasuponda, Kab. Luwu Timur'],
            ['MTA', 'Tambang Matano', LocationType::Mine, 'Sulawesi Selatan', 'Site Matano, Kec. Nuha, Kab. Luwu Timur'],
            ['PLB', 'Pelabuhan Sarana', LocationType::Mine, 'Sulawesi Selatan', 'Dermaga Sarana, Kec. Nuha, Kab. Luwu Timur'],
        ];

        foreach ($locations as [$code, $name, $type, $region, $address]) {
            Location::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'region' => $region,
                'address' => $address,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Struktur organisasi + akun login. Email akun berikut dipakai untuk demo
     * dan didokumentasikan di README, jangan diubah.
     *
     * @return array{employees: Collection, approvers: array, admin_user: User, drivers: Collection}
     */
    private function seedOrganisation(): array
    {
        $hz = Location::where('code', 'HQ')->first();
        $br = Location::where('code', 'BRC')->first();
        $mal = Location::where('code', 'MAL')->first();
        $nu = Location::where('code', 'NUH')->first();
        $was = Location::where('code', 'WAS')->first();
        $pla = Location::where('code', 'PLB')->first();

        $gm = Employee::create([
            'employee_number' => 'EMP-0001',
            'name' => 'Andi Wijaya',
            'position' => 'General Manager Operasi',
            'department' => 'Operasi',
            'email' => 'andi@transops.test',
            'phone' => '0812-4000-0001',
            'location_id' => $br->id,
            'manager_id' => null,
            'is_active' => true,
        ]);

        $positions = [
            // [no, nama, jabatan, departemen, lokasi, manager, email, role, punya user]
            ['EMP-0002', 'Rina Marlina', 'Manager HR & GA', 'HR & GA', $br, $gm->id, 'rina@transops.test', UserRole::Approver, true],
            ['EMP-0003', 'Budi Santoso', 'Manager Operasi Transport', 'Operasi', $br, $gm->id, 'budi@transops.test', UserRole::Approver, true],
            ['EMP-0004', 'Citra Lestari', 'Manager Keuangan', 'Finance', $hz, $gm->id, 'citra@transops.test', UserRole::Approver, true],
            ['EMP-0005', 'Dedi Kurniawan', 'Supervisor Site Malili', 'Operasi', $mal, null, 'dedi@transops.test', UserRole::Approver, true],
            ['EMP-0006', 'Eka Prasetyo', 'Supervisor Transportasi', 'Operasi', $br, null, 'eka@transops.test', UserRole::Approver, true],
            ['EMP-0007', 'Fajar Ramadhan', 'Kepala Pool Kendaraan', 'Pool', $br, null, 'fajar@transops.test', UserRole::Approver, true],
            ['EMP-0008', 'Admin Pool', 'Administrator Pool', 'Pool', $br, null, 'admin@transops.test', UserRole::Admin, true],
            ['EMP-0009', 'Dewi Anggraini', 'Staff Administrasi Operasi', 'Operasi', $br, null, 'dewi@transops.test', UserRole::Approver, false],
        ];

        $approvers = [];
        $adminUser = null;

        foreach ($positions as [$no, $name, $position, $dept, $loc, $manager, $email, $role, $hasUser]) {
            $emp = Employee::create([
                'employee_number' => $no,
                'name' => $name,
                'position' => $position,
                'department' => $dept,
                'email' => $email,
                'phone' => fake()->numerify('08##-####-####'),
                'location_id' => $loc->id,
                'manager_id' => $manager,
                'is_active' => true,
            ]);

            if ($role === UserRole::Approver) {
                $approvers[] = $emp->id;
            }

            if (! $hasUser) {
                continue;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'email_verified_at' => now(),
                'employee_id' => $emp->id,
                'role' => $role,
            ]);

            if ($role === UserRole::Admin) {
                $adminUser = $user;
            }
        }

        // Karyawan pengguna layanan (requester booking) biasa, tanpa akun login.
        $operational = [
            ['EMR', 'Kepala Satuan', 'Operasi', $mal, null],
            ['EMR', 'Kepala Satuan', 'Operasi', $nu, null],
            ['PLS', 'Koordinator Pelabuhan', 'Harbour', $pla, null],
            ['MAL', 'Surveyor Geologi', 'Geologi', $mal, null],
            ['NUH', 'Surveyor Topografi', 'Geologi', $nu, null],
            ['WAS', 'Teknisi Pemeliharaan', 'Maintenance', $was, null],
        ];

        $seq = 1000;
        foreach ($operational as [$prefix, $position, $dept, $loc, $manager]) {
            Employee::create([
                'employee_number' => 'EMP-'.($seq + 1),
                'name' => fake('id_ID')->name(),
                'position' => $position,
                'department' => $dept,
                'email' => fake('id_ID')->unique()->safeEmail(),
                'phone' => fake()->numerify('08##-####-####'),
                'location_id' => $loc->id,
                'manager_id' => $manager,
                'is_active' => true,
            ]);
            $seq += 2;
        }

        Employee::factory()->count(24)->create([
            'location_id' => fake()->randomElement(Location::pluck('id')->all()),
            'manager_id' => fake()->randomElement([$gm->id, ...$approvers]),
        ]);

        // Driver = pegawai divisi Pool + operasional lapangan yang memegang SIM.
        $driverCandidates = Employee::query()
            ->where('department', 'Pool')
            ->orWhere('position', 'like', '%Driver%')
            ->orWhere('position', 'like', '%Kepala Satuan%')
            ->get();

        if ($driverCandidates->count() < 8) {
            $driverCandidates = $driverCandidates->merge(
                Employee::query()
                    ->whereNotIn('id', $driverCandidates->pluck('id'))
                    ->inRandomOrder()
                    ->limit(8 - $driverCandidates->count())
                    ->get(),
            );
        }

        $drivers = collect();
        foreach ($driverCandidates->take(8) as $i => $emp) {
            $drivers->push(Driver::create([
                'employee_id' => $emp->id,
                'license_number' => fake()->unique()->numerify('####-####-####-####'),
                'license_type' => fake()->randomElement(['B1', 'B2', 'C']),
                'license_expiry' => now()->addYears(rand(1, 5))->toDateString(),
                'status' => DriverStatus::Available,
            ]));
        }

        return [
            'employees' => Employee::all(),
            'approvers' => $approvers,
            'admin_user' => $adminUser,
            'drivers' => $drivers,
            'manager_ids' => [$gm->id],
        ];
    }

    /**
     * Armada kendaraan + mitra rental.
     *
     * @return array{vehicles: \Illuminate\Database\Eloquent\Collection, rental_partners: \Illuminate\Database\Eloquent\Collection}
     */
    private function seedFleet(): array
    {
        $rentals = collect([
            ['PT Sarana Multi Armada', 'Hendra Gunawan', '0852-5000-1111', 'hm@smarmada.co.id', 'Jl. Poros Malili-Sorowako, Luwu Timur'],
            ['CV Mitra Andalan Transport', 'Samsul Bahri', '0853-6000-2222', 'admin@mitraandalan.co.id', 'Jl. Palopo-Makassar, Luwu Timur'],
        ])->map(fn ($r) => RentalPartner::create([
            'name' => $r[0],
            'contact_person' => $r[1],
            'phone' => $r[2],
            'email' => $r[3],
            'address' => $r[4],
            'is_active' => true,
        ]));

        $companyVehicles = [
            // [plat, kategori, kapasitas(ton), brand, model, tahun, BBM, tank(L), odometer]
            ['DD 1901 AB', VehicleCategory::Goods, 1.5, 'Toyota', 'Hilux DC', 2021, FuelType::Diesel, 80, 124500],
            ['DD 1902 AB', VehicleCategory::Goods, 1.5, 'Toyota', 'Hilux DC', 2021, FuelType::Diesel, 80, 98700],
            ['DD 1903 AC', VehicleCategory::Passenger, 1.0, 'Toyota', 'Innova Zenix', 2022, FuelType::Petrol, 55, 74100],
            ['DD 1904 AD', VehicleCategory::Passenger, 1.0, 'Toyota', 'Fortuner', 2020, FuelType::Diesel, 70, 158300],
            ['DD 1905 AE', VehicleCategory::Goods, 1.6, 'Mitsubishi', 'L200', 2019, FuelType::Diesel, 68, 182600],
            ['DD 1906 AF', VehicleCategory::Passenger, 1.2, 'Toyota', 'Hiace Commuter', 2021, FuelType::Diesel, 65, 210400],
            ['DD 1907 AG', VehicleCategory::Goods, 3.0, 'Isuzu', 'ELF NLR', 2020, FuelType::Diesel, 75, 312700],
            ['DD 1908 AH', VehicleCategory::Goods, 4.0, 'Isuzu', 'GVK 2.5 SSteps', 2018, FuelType::Diesel, 90, 425800],
            ['DD 1909 AI', VehicleCategory::Goods, 5.0, 'Hino', '500 FM 260', 2020, FuelType::Diesel, 200, 289600],
            ['DD 1910 AJ', VehicleCategory::Goods, 2.5, 'Mitsubishi', 'Canter', 2019, FuelType::Diesel, 100, 156200],
        ];

        foreach ($companyVehicles as [$plate, $cat, $cap, $brand, $model, $year, $fuel, $tank, $odo]) {
            Vehicle::create([
                'plate_number' => $plate,
                'category' => $cat,
                'capacity' => $cap,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'ownership_type' => OwnershipType::Company,
                'rental_partner_id' => null,
                'rental_start_date' => null,
                'rental_end_date' => null,
                'fuel_type' => $fuel,
                'tank_capacity' => $tank,
                'current_odometer' => $odo,
                'status' => VehicleStatus::Available,
                'is_active' => true,
            ]);
        }

        // Sewa kendaraan tambahan dari mitra.
        foreach ([
            ['DD 1911 AK', VehicleCategory::Goods, 3.0, 'Isuzu', 'ELF NMR', 2023, FuelType::Diesel, 80, 45200],
            ['DD 1912 AL', VehicleCategory::Passenger, 1.0, 'Toyota', 'Hilux DC', 2023, FuelType::Diesel, 72, 38800],
        ] as [$plate, $cat, $cap, $brand, $model, $year, $fuel, $tank, $odo]) {
            Vehicle::create([
                'plate_number' => $plate,
                'category' => $cat,
                'capacity' => $cap,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'ownership_type' => OwnershipType::Rental,
                'rental_partner_id' => $rentals->random()->id,
                'rental_start_date' => now()->subMonths(3)->toDateString(),
                'rental_end_date' => now()->addMonths(9)->toDateString(),
                'fuel_type' => $fuel,
                'tank_capacity' => $tank,
                'current_odometer' => $odo,
                'status' => VehicleStatus::Available,
                'is_active' => true,
            ]);
        }

        return [
            'vehicles' => Vehicle::all(),
            'rental_partners' => $rentals,
        ];
    }

    /**
     * @param  array{approvers: array, admin_user: User, drivers: Collection, manager_ids: array, employees:Collection}  $org
     * @param  array{vehicles: \Illuminate\Database\Eloquent\Collection}  $fleet
     */
    private function seedBookings(array $org, array $fleet): void
    {
        $vehicles = $fleet['vehicles'];
        $drivers = $org['drivers'];
        $approvers = $org['approvers'];
        $adminUser = $org['admin_user'];

        // Requester alternatif = karyawan yang tidak punya driver/Akun.
        $requesterIds = $org['employees']
            ->whereNotIn('id', $drivers->pluck('employee_id'))
            ->whereNotIn('id', $approvers)
            ->pluck('id')
            ->concat(array_values($org['manager_ids']))
            ->reject(fn ($id) => is_null($id))
            ->all();

        $destinations = [
            'Tambang Malili' => [14, 28],
            'Tambang Nuha' => [18, 35],
            'Tambang Wasuponda' => [10, 22],
            'Tambang Matano' => [20, 40],
            'Pelabuhan Sarana' => [25, 45],
            'Kantor Cabang Sorowako' => [5, 12],
        ];

        $purposes = [
            'Pengangkutan peralatan geologi ke site',
            'Penjemputan rombongan karyawan shift sore',
            'Pengiriman material logistik ke gudang site',
            'Kunjungan inspeksi keselamatan kerja (SMK3)',
            'Pengiriman sampel bijih ke laboratorium',
            'Mobilitas tim sar dan pengawasan',
            'Operasional pindah tugas antar site',
            'Pengambilan dokumen administrasi di kantor cabang',
            'Pengangkutan alat pemeliharaan menuju pelabuhan',
            'Dispensasi pengantaran tamu vendor ke area site',
        ];

        // 6 bulan terakhir, intensitas naik mendekati bulan berjalan.
        $seq = 1;
        foreach (range(5, 0) as $offset) {
            $month = now()->startOfMonth()->subMonths($offset);
            $isCurrent = $offset === 0;
            $count = $isCurrent ? rand(6, 11) : rand(9, 16);

            for ($i = 0; $i < $count; $i++) {
                $dest = fake()->randomElement(array_keys($destinations));

                $vehicle = $vehicles->random();
                $driver = $drivers->random();
                $requester = Employee::find($requesterIds[array_rand($requesterIds)]);

                $day = rand(1, max(1, $month->daysInMonth - 1));
                $hour = rand(7, 9);
                $start = $month->copy()->addDays($day)->setTime($hour, 0);

                $status = $this->pickStatus($isCurrent);

                // Pastikan ada booking yang masih menunggu/diajukan di bulan berjalan.
                if ($isCurrent && ($i === 0 || $i === 1)) {
                    $status = BookingStatus::Pending;
                }

                $duration = rand(3, 9);

                // Jarak tempuh & odometer aktual hanya untuk yang sudah selesai.
                $departure = in_array($status, [BookingStatus::InUse, BookingStatus::Completed]) ? $start->copy()->addMinutes(rand(0, 30)) : null;
                $distance = rand(18, 120);
                $odoStart = $vehicle->current_odometer;
                $odoEnd = $odoStart + $distance;

                $booking = Booking::create([
                    'booking_number' => 'BK-'.$start->format('Ym').'-'.str_pad((string) ($seq++), 4, '0', STR_PAD_LEFT),
                    'vehicle_id' => $vehicle->id,
                    'requester_id' => $requester->id,
                    'driver_id' => $driver->id,
                    'purpose' => $purposes[array_rand($purposes)],
                    'destination' => $dest,
                    'start_at' => $start,
                    'end_at' => $start->copy()->addHours($duration),
                    'status' => $status,
                    'created_by' => $adminUser->id,
                    'departure_at' => $departure,
                    'return_at' => $status === BookingStatus::Completed ? $start->copy()->addHours($duration)->addMinutes(rand(5, 40)) : null,
                    'odometer_start' => $status === BookingStatus::Completed ? $odoStart : null,
                    'odometer_end' => $status === BookingStatus::Completed ? $odoEnd : null,
                    'distance_km' => $status === BookingStatus::Completed ? $distance : null,
                ]);

                $this->seedApprovals($booking, $approvers, $status);
                $this->advanceVehicleState($vehicle, $driver, $status, $odoEnd);
                $this->seedFuelLog($booking, $vehicle, $org, $status);
            }
        }
    }

    private function pickStatus(bool $isCurrent): BookingStatus
    {
        $pool = [BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Completed];

        // Bulan berjalan: ada yang masih aktif/menunggu, bukan semua selesai.
        if ($isCurrent) {
            $pool = array_merge($pool, [
                BookingStatus::InUse,
                BookingStatus::Approved,
                BookingStatus::Pending,
                BookingStatus::Pending,
            ]);
        } else {
            $pool = array_merge($pool, [
                BookingStatus::Approved,
                BookingStatus::Rejected,
                BookingStatus::Cancelled,
            ]);
        }

        return $pool[array_rand($pool)];
    }

    private function seedApprovals(Booking $booking, array $approverIds, BookingStatus $status): void
    {
        $chain = fake()->randomElements($approverIds, min(2, count($approverIds)), false);

        foreach ($chain as $level => $approverId) {
            $approvalStatus = match (true) {
                $status === BookingStatus::Rejected && $level === 0 => ApprovalStatus::Rejected,
                in_array($status, [BookingStatus::Approved, BookingStatus::InUse, BookingStatus::Completed], true) => ApprovalStatus::Approved,
                $status === BookingStatus::Cancelled && $level > 0 => ApprovalStatus::Approved,
                default => ApprovalStatus::Pending,
            };

            $booking->approvals()->create([
                'approver_id' => $approverId,
                'level' => $level + 1,
                'status' => $approvalStatus,
                'note' => $approvalStatus === ApprovalStatus::Rejected ? 'Ketersediaan armada tidak mencukupi untuk jadwal ini.' : null,
                'decided_at' => $approvalStatus === ApprovalStatus::Pending ? null : $booking->start_at?->subDays(rand(1, 3)),
            ]);
        }
    }

    private function advanceVehicleState(Vehicle $vehicle, Driver $driver, BookingStatus $status, ?float $odoEnd = null): void
    {
        if ($status === BookingStatus::Completed) {
            $vehicle->update([
                'current_odometer' => (float) $odoEnd,
                'status' => VehicleStatus::Available,
            ]);
            $driver->update(['status' => DriverStatus::Available]);
        } elseif ($status === BookingStatus::InUse) {
            $vehicle->update(['status' => VehicleStatus::InUse]);
            $driver->update(['status' => DriverStatus::Assigned]);
        }
    }

    /**
     * @param  array{approvers: array, admin_user: User, drivers: Collection, employees:Collection}  $org
     */
    private function seedFuelLog(Booking $booking, Vehicle $vehicle, array $org, BookingStatus $status): void
    {
        $recordedBy = $org['admin_user']?->employee_id;

        // Hanya booking yang benar-benar berjalan yang punya catatan BBM.
        if (! in_array($status, [BookingStatus::InUse, BookingStatus::Completed], true) || ! $booking->start_at || ! $booking->end_at) {
            return;
        }

        $price = $vehicle->fuel_type === FuelType::Diesel ? 6800.0 : 10000.0;
        $liters = rand(10, 45);

        FuelLog::create([
            'vehicle_id' => $vehicle->id,
            'booking_id' => $booking->id,
            'recorded_by' => $recordedBy,
            'refueled_at' => $booking->start_at->copy()
                ->addHours(min(rand(2, 6), max(1, (int) $booking->start_at->diffInHours($booking->end_at))))
                ->addMinutes(rand(0, 59)),
            'odometer' => max((float) $vehicle->current_odometer + rand(-30, 20), 0),
            'liters' => $liters,
            'price_per_liter' => $price,
            'total_cost' => round($liters * $price, 2),
            'station' => fake()->randomElement(['SPBU Sorowako', 'SPBU Malili', 'SPBU Nuha', 'Fuel Station Site']),
            'is_full_tank' => true,
            'note' => null,
        ]);
    }

    /**
     * @param  array{vehicles: \Illuminate\Database\Eloquent\Collection}  $fleet
     */
    private function seedServiceHistory(array $fleet): void
    {
        $serviceTypes = [
            ['Ganti oli mesin', 6500],
            ['Servis berkala ringan', 10000],
            ['Servis berkala besar', 15000],
            ['Rotasi ban', 50000],
            ['Perawatan rem & kampas', 25000],
        ];

        foreach ($fleet['vehicles']->take(8) as $vehicle) {
            $schedule = ServiceSchedule::create([
                'vehicle_id' => $vehicle->id,
                'service_type' => fake()->randomElement(array_column($serviceTypes, 0)),
                'scheduled_date' => fake()->randomElement([
                    now()->subDays(rand(10, 25))->toDateString(),
                    now()->addDays(rand(1, 5))->toDateString(),
                    now()->addWeeks(rand(2, 10))->toDateString(),
                ]),
                'scheduled_odometer' => (float) $vehicle->current_odometer + rand(3000, 9000),
                'recurrence_km' => 5000,
                'recurrence_months' => 6,
                'status' => fake()->randomElement([ServiceStatus::Scheduled, ServiceStatus::Due, ServiceStatus::Overdue]),
            ]);

            // Jadwal di masa lalu yang sudah terlayani → catat riwayat.
            if ($schedule->scheduled_date?->lt(today())) {
                ServiceLog::create([
                    'vehicle_id' => $vehicle->id,
                    'service_schedule_id' => $schedule->id,
                    'service_type' => $schedule->service_type,
                    'serviced_date' => $schedule->scheduled_date,
                    'odometer' => (float) $schedule->scheduled_odometer - rand(0, 500),
                    'cost' => rand(350000, 1800000),
                    'vendor' => fake()->randomElement(['Bengkel Mitra Sorowako', 'AHASS Sorowako', 'Workshop Internal Pool']),
                    'mechanic' => fake('id_ID')->name(),
                    'note' => null,
                ]);
            }
        }

        // Sebagian jadwal untuk kendaraan lain sebagai riwayat lama.
        foreach ($fleet['vehicles']->take(4) as $vehicle) {
            ServiceLog::create([
                'vehicle_id' => $vehicle->id,
                'service_schedule_id' => null,
                'service_type' => 'Servis berkala ringan',
                'serviced_date' => now()->subMonths(rand(2, 4))->toDateString(),
                'odometer' => max((float) $vehicle->current_odometer - rand(8000, 20000), 0),
                'cost' => rand(400000, 1500000),
                'vendor' => 'Workshop Internal Pool',
                'mechanic' => fake('id_ID')->name(),
                'note' => null,
            ]);
        }
    }

    /**
     * @param  array{approvers: array, admin_user: User, employees: Collection}  $org
     */
    private function seedAuditLogs(array $org): void
    {
        $user = $org['admin_user'];

        foreach (Booking::latest()->limit(6)->get() as $booking) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'created',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'before' => null,
                'after' => [
                    'booking_number' => $booking->booking_number,
                    'destination' => $booking->destination,
                    'status' => BookingStatus::Pending->value,
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder/1.0',
            ]);
        }
    }
}
