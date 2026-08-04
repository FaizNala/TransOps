# ERD — TransOps (Aplikasi Pemesanan & Monitoring Kendaraan)

Perusahaan tambang nikel (1 kantor pusat, 1 cabang, 6 tambang). Aplikasi web untuk pemesanan kendaraan, persetujuan berjenjang, serta monitoring konsumsi BBM, jadwal service, dan riwayat pemakaian.

## Prinsip Desain

1. **Pisahkan master, transaksi, monitoring, dan audit** agar siklus hidup data jelas.
2. **Normalisasi secukupnya** — hindari FK redundan, gunakan index untuk efisiensi query.
3. **Satu sumber kebenaran** — `bookings.status` adalah agregat; detail per level ada di `booking_approvals`.
4. **Integritas dijaga di database** (unique constraint, FK, enum) bukan hanya di aplikasi.
5. **Denormalisasi hanya bila perlu** (contoh: `current_odometer` di `vehicles`).
6. **Efisiensi** — 12 tabel final (pemangkasan dari 14) tanpa mengorbankan requirement; `drivers` dipertahankan sebagai tabel subtype (spesialisasi dari `employees`).

## Diagram Relasi

```
MASTER
locations 1─N employees 1─N users (auth: admin/approver)
employees 1─1 drivers (subtype; FK employee_id UNIQUE)
employees 1─N employees (manager_id, hierarki atasan)
rental_partners N─1 vehicles 1─N bookings

TRANSAKSI
bookings 1─N booking_approvals N─1 employees (approver, level 1..N)

MONITORING
vehicles 1─N fuel_logs
vehicles 1─N service_schedules
vehicles 1─N service_logs N─1 service_schedules (nullable)

SISTEM
users 1─N audit_logs
```

## Physical Data Model (12 Tabel)

Konvensi: PK/FK `BIGINT UNSIGNED`, timestamps standar, `deleted_at` (soft delete) hanya di master data agar histori tidak putus.

### 1. `locations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| code | VARCHAR(20) UNIQUE | |
| name | VARCHAR(150) NN | |
| type | ENUM('head_office','branch_office','mine') | |
| region | VARCHAR(100) NULL | daerah/region |
| address | VARCHAR(255) NULL | |
| is_active | BOOLEAN default 1 | |
| timestamps | | + deleted_at |

### 2. `employees`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| employee_number | VARCHAR(30) UNIQUE | |
| name | VARCHAR(150) NN | |
| position | VARCHAR(100) | jabatan |
| department | VARCHAR(100) | |
| email | VARCHAR(150) NULL | |
| phone | VARCHAR(30) NULL | |
| location_id | FK → locations NULL | |
| manager_id | FK → employees NULL | self-ref, hierarki atasan untuk auto-suggest approver |
| is_active | BOOLEAN default 1 | |
| timestamps | | + deleted_at |

### 3. `drivers` (subtype dari `employees`)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| employee_id | FK → employees UNIQUE NN | 1:1 — satu employee maksimal satu record driver |
| license_number | VARCHAR(30) UNIQUE | |
| license_type | VARCHAR(50) NULL | |
| license_expiry | DATE NULL | |
| status | ENUM('available','assigned','inactive') default 'available' | |
| timestamps | | + deleted_at |

### 4. `users` (akun login — hanya role `admin` & `approver`)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| employee_id | FK → employees UNIQUE NULL | |
| username | VARCHAR(50) UNIQUE NN | |
| password | VARCHAR(255) NN | bcrypt |
| role | ENUM('admin','approver') | |
| is_active | BOOLEAN default 1 | |
| last_login_at | DATETIME NULL | |
| timestamps | | |

### 5. `rental_partners`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| name | VARCHAR(150) NN | |
| contact_person | VARCHAR(100) NULL | |
| phone | VARCHAR(30) NULL | |
| email | VARCHAR(150) NULL | |
| address | VARCHAR(255) NULL | |
| is_active | BOOLEAN default 1 | |
| timestamps | | + deleted_at |

### 6. `vehicles`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| plate_number | VARCHAR(20) UNIQUE | |
| category | ENUM('passenger','goods') | angkut orang / angkut barang |
| capacity | DECIMAL(8,2) NULL | kursi atau tonase |
| brand | VARCHAR(50) | |
| model | VARCHAR(50) | |
| year | SMALLINT NULL | |
| ownership_type | ENUM('company','rental') | |
| rental_partner_id | FK → rental_partners NULL | hanya jika rental |
| rental_start_date | DATE NULL | |
| rental_end_date | DATE NULL | |
| fuel_type | ENUM('diesel','petrol','electric') | |
| tank_capacity | DECIMAL(6,2) NULL | |
| current_odometer | DECIMAL(10,2) default 0 | denormalisasi, sinkron saat return |
| status | ENUM('available','in_use','maintenance','retired') default 'available' | |
| is_active | BOOLEAN default 1 | |
| timestamps | | + deleted_at |

### 7. `bookings` (merangkum riwayat pemakaian)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| booking_number | VARCHAR(30) UNIQUE NN | format `BK-YYYYMM-NNNN` |
| vehicle_id | FK → vehicles | |
| requester_id | FK → employees | pegawai pemesan |
| driver_id | FK → drivers | driver yang ditugaskan admin |
| purpose | TEXT | keperluan |
| destination | VARCHAR(200) | |
| start_at | DATETIME | rencana berangkat |
| end_at | DATETIME | rencana kembali |
| status | ENUM('pending','approved','rejected','cancelled','in_use','completed') default 'pending' | |
| created_by | FK → users | admin yang menginput |
| departure_at | DATETIME NULL | aktual berangkat |
| return_at | DATETIME NULL | aktual kembali |
| odometer_start | DECIMAL(10,2) NULL | saat berangkat |
| odometer_end | DECIMAL(10,2) NULL | saat kembali |
| distance_km | DECIMAL(10,2) NULL | dihitung saat return |
| note | TEXT NULL | |
| timestamps | | |

### 8. `booking_approvals` (persetujuan berjenjang)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| booking_id | FK → bookings (CASCADE) | |
| approver_id | FK → employees | wajib punya akun role approver |
| level | TINYINT UNSIGNED | 1, 2, 3, ... |
| status | ENUM('pending','approved','rejected') default 'pending' | |
| note | TEXT NULL | |
| decided_at | DATETIME NULL | |
| timestamps | | |
| **UNIQUE** | (booking_id, level) | |

### 9. `fuel_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| vehicle_id | FK → vehicles | |
| booking_id | FK → bookings NULL | refuel saat perjalanan tertentu |
| recorded_by | FK → employees NULL | |
| refueled_at | DATETIME | |
| odometer | DECIMAL(10,2) | |
| liters | DECIMAL(10,2) | |
| price_per_liter | DECIMAL(12,2) | |
| total_cost | DECIMAL(12,2) | hitung: liters × price_per_liter |
| station | VARCHAR(100) NULL | |
| is_full_tank | BOOLEAN default 1 | dasar perhitungan km/liter |
| note | TEXT NULL | |
| timestamps | | |

### 10. `service_schedules` (rencana servis — mutabel)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| vehicle_id | FK → vehicles | |
| service_type | VARCHAR(50) | oil_change, routine, brake, dll |
| scheduled_date | DATE NULL | rencana pelaksanaan |
| scheduled_odometer | DECIMAL(10,2) NULL | |
| recurrence_km | INT NULL | servis berikutnya tiap X km |
| recurrence_months | INT NULL | servis berikutnya tiap X bulan |
| status | ENUM('scheduled','due','overdue','cancelled') default 'scheduled' | |
| note | TEXT NULL | |
| timestamps | | |

### 11. `service_logs` (riwayat servis — immutable)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| vehicle_id | FK → vehicles | |
| service_schedule_id | FK → service_schedules NULL | null untuk perbaikan ad-hoc/breakdown |
| service_type | VARCHAR(50) | |
| serviced_date | DATE | |
| odometer | DECIMAL(10,2) | |
| cost | DECIMAL(12,2) NULL | |
| vendor | VARCHAR(150) NULL | |
| mechanic | VARCHAR(100) NULL | |
| note | TEXT NULL | |
| timestamps | | |

### 12. `audit_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| user_id | FK → users NULL (SET NULL) | |
| action | VARCHAR(50) NN | booking.created, booking.approved, dll |
| entity_type | VARCHAR(50) | |
| entity_id | BIGINT UNSIGNED NULL | |
| before | JSON NULL | data sebelum |
| after | JSON NULL | data sesudah |
| ip_address | VARCHAR(45) NULL | |
| user_agent | VARCHAR(255) NULL | |
| created_at | | tanpa updated_at (log immutable) |

## State Machine `bookings`

```
pending ──(semua level approved)──▶ approved ──(usage start)──▶ in_use ──(return)──▶ completed
   │                                    │
   ├──(ada level rejected)──▶ rejected  └──(admin)──▶ cancelled
   └──(admin)──▶ cancelled
```

## Validasi Bisnis

- Minimal 2 level approval (dipaksa saat create booking).
- Approver terpilih harus memiliki akun role `approver`.
- Approval bersifat berurutan: level N hanya bisa approve bila level N-1 sudah approved.
- Blokir double-booking: kendaraan tidak boleh memiliki booking berstatus `pending`/`approved`/`in_use` yang waktunya tumpang tindih.
- Driver terpilih harus terdaftar di tabel `drivers` (status aktif) dan tidak memiliki aktivitas aktif bersamaan.
- Odometer start/end wajib diisi saat usage dibuka/ditutup agar perhitungan BBM (km/liter) valid.
- `audit_logs` tercatat pada setiap proses (create, update, approve, reject, cancel, dsb).

## Aturan Cascade

- `bookings` → `booking_approvals`, `bookings` → `fuel_logs.booking_id`: **CASCADE**
- `service_logs` → `service_schedules.service_schedule_id`: **SET NULL**
- `vehicles` / `employees` / `users` / `rental_partners` / `drivers`: **RESTRICT** (tak bisa dihapus bila masih dipakai)
- `audit_logs.user_id`: **SET NULL**

## Strategi Index

- `bookings`: `(vehicle_id, status, start_at)`, `(requester_id)`, `(created_by)` — cek konflik & list admin
- `booking_approvals`: `(approver_id, status)` — daftar pending approval per approver
- `bookings`: `(status, return_at)` — agregasi grafik pemakaian bulanan
- `fuel_logs`: `(vehicle_id, refueled_at)`
- `service_schedules`: `(vehicle_id, status, scheduled_date)` — reminder overdue
- `service_logs`: `(vehicle_id, serviced_date)`
- `audit_logs`: `(entity_type, entity_id)`, `(created_at)`

## Catatan Pemangkasan (dari 14 → 12 tabel)

| Tabel awal | Aksi |
|---|---|
| `vehicle_types` | digabung ke `vehicles` (kolom `category` + `capacity`) |
| `drivers` | **dipertahankan terpisah** sebagai tabel subtype dari `employees` (pola spesialisasi; hindari kolom sparse di `employees`, siap untuk atribut driver tambahan) |
| `service_schedules` + `service_logs` | **dipertahankan terpisah** — rencana (mutabel, recurring) vs riwayat (immutable); menangani perbaikan ad-hoc, reschedule, dan satu kunjungan menyelesaikan banyak item jadwal |
| `vehicle_usages` | digabung ke `bookings` (kolom aktual + status lifecycle) |

`users` tetap terpisah dari `employees` (tabel auth bawaan Laravel); `rental_partners` tetap tabel terpisah (hindari duplikasi data partner di banyak kendaraan).

## Keterkaitan dengan Soal (Traceability)

| Requirement | Dukungan di ERD |
|---|---|
| Multi-lokasi: 1 pusat, 1 cabang, 6 tambang, beberapa region | `locations.type` ('head_office'/'branch_office'/'mine') + `locations.region` |
| Kendaraan angkut orang & angkutan barang | `vehicles.category` ('passenger'/'goods') |
| Kendaraan milik perusahaan & sewa | `vehicles.ownership_type` + `rental_partners` + `rental_start_date`/`rental_end_date` |
| Monitoring konsumsi BBM | `fuel_logs` (odometer, liters, is_full_tank → km/liter) |
| Monitoring jadwal service | `service_schedules` (recurring) + `service_logs` (riwayat) |
| Monitoring riwayat pemakaian kendaraan | `bookings` kolom aktual: departure_at, return_at, odometer_start/end, distance_km |
| Soal 1 — 2 user (admin & pihak menyetujui) | `users.role` ENUM('admin','approver') |
| Soal 2 — Admin input booking, tentukan driver & approver | `bookings.created_by` (admin), `bookings.driver_id`, `booking_approvals.approver_id` |
| Soal 3 — Persetujuan berjenjang minimal 2 level | `booking_approvals.level` + UNIQUE(booking_id, level) + validasi min 2 level |
| Soal 4 — Approver setujui melalui aplikasi | `users` role 'approver', `booking_approvals.status` + `decided_at`, index (approver_id, status) |
| Soal 5 — Dashboard grafik pemakaian | `bookings` status 'completed' (return_at, distance_km), index (status, return_at) untuk agregasi bulanan |
| Soal 6 — Laporan periodik export Excel | Data mentah dari `bookings` + `booking_approvals` |
| Bonus 1 — Physical data model | Seluruh dokumen ini |
| Bonus 3 — Log aplikasi tiap proses | `audit_logs` (before/after JSON, user, ip) |
