# PRD — TransOps: Fleet Booking & Monitoring System

| | |
|---|---|
| **Nama Produk** | TransOps — Fleet Booking & Monitoring System |
| **Versi Dokumen** | 1.0 |
| **Tanggal** | 2026-08-04 |
| **Status** | Draft Final — Siap Implementasi |
| **Disusun oleh** | Product Owner & Engineer Lead |
| **Referensi** | Technical Test Engineer Lead (`docs/EngineerLeadTest .md`) |

---

## 1. Executive Summary

Perusahaan tambang nikel dengan 1 kantor pusat, 1 kantor cabang, dan 6 tambang memiliki armada kendaraan (angkut orang & barang; milik sendiri dan sewa) yang saat ini penggunaannya dikelola secara manual melalui pool/pengelola kendaraan. Proses manual tidak terdokumentasi: tidak ada catatan konsumsi BBM, jadwal service tidak terpantau, riwayat pemakaian tidak tersedia, dan tidak ada jejak persetujuan yang jelas.

Produk ini adalah aplikasi web untuk **memonitor dan mengelola pemesanan kendaraan** secara terstruktur: admin menginput pemesanan, persetujuan dilakukan berjenjang minimal 2 level oleh atasan melalui aplikasi, serta menyediakan monitoring konsumsi BBM, jadwal service, riwayat pemakaian, dashboard grafik, dan laporan periodik yang dapat diekspor ke Excel.

---

## 2. Problem Statement

1. **Tidak ada jejak pemesanan & persetujuan** — pemesanan dilakukan verbal/manual ke pool; persetujuan atasan tidak terdokumentasi, sehingga rentan terhadap pemakaian tanpa izin dan tidak ada akuntabilitas.
2. **Konsumsi BBM tidak terukur** — tidak ada data liter, odometer, dan biaya per kendaraan, sehingga biaya operasional tidak dapat dikendalikan.
3. **Jadwal service tidak terpantau** — servis berkala terlewat, berisiko downtime kendaraan dan kerusakan lebih besar.
4. **Riwayat pemakaian tidak tersedia** — manajemen tidak dapat melihat kendaraan/driver mana yang paling banyak dipakai untuk pengambilan keputusan (rotasi armada, efisiensi).
5. **Tidak ada laporan periodik** — pelaporan ke manajemen manual, lambat, dan rawan kesalahan.

---

## 3. Goals

### Business Goals (Tujuan Bisnis)
- **BG-1**: Menjamin setiap pemakaian kendaraan tercatat dan disetujui oleh atasan yang berwenang (akuntabilitas & tata kelola).
- **BG-2**: Mengendalikan biaya operasional armada melalui data konsumsi BBM yang akurat.
- **BG-3**: Meminimalkan downtime kendaraan melalui monitoring jadwal service.
- **BG-4**: Menyediakan basis data pemakaian untuk keputusan efisiensi armada.

### Product Goals (Tujuan Produk)
- **PG-1**: Digitalisasi alur pemesanan kendaraan dari input admin → persetujuan berjenjang → penggunaan → penyelesaian.
- **PG-2**: Persetujuan berjenjang minimal 2 level yang transparan dan dapat dilacak.
- **PG-3**: Monitoring menyeluruh: konsumsi BBM, jadwal service, riwayat pemakaian.
- **PG-4**: Dashboard visual pemakaian kendaraan + laporan periodik yang dapat diekspor.

### Success Metrics (Indikator Keberhasilan)
- 100% pemesanan kendaraan tercatat di sistem (tanpa pemakaian di luar sistem).
- 100% pemesanan melewati minimal 2 level persetujuan sebelum kendaraan digunakan.
- Seluruh transisi status pemesanan tercatat di audit log.
- Laporan pemesanan dapat diekspor ke Excel dalam satu periode klik.

---

## 4. Target Users

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Admin (Pool/Pengelola Kendaraan)** | Petugas pool yang menerima permintaan pegawai dan mengelola operasional armada. | Input pemesanan, pilih kendaraan/driver, tentukan approver, catat pemakaian/BBM/service, lihat dashboard & export laporan. |
| **Approver (Atasan/Pihak Menyetujui)** | Atasan pegawai pemesan (minimal 2 level per pemesanan). | Menyetujui/menolak pemesanan yang menunggu keputusan dirinya melalui aplikasi. |

> Pegawai pemesan tidak memiliki akun aplikasi; permintaan mereka diinputkan oleh admin pool atas nama mereka (sesuai requirement soal: hanya 2 tipe user).

---

## 5. User Stories

| ID | Sebagai | Saya ingin | Sehingga | Prioritas |
|---|---|---|---|---|
| US-01 | Admin | membuat pemesanan kendaraan (pilih kendaraan, driver, pegawai pemesan, keperluan, waktu) | permintaan pegawai terdokumentasi | Must |
| US-02 | Admin | menentukan approver berjenjang (minimal 2 level) untuk setiap pemesanan | persetujuan sesuai aturan tata kelola | Must |
| US-03 | Admin | mendapatkan peringatan bila kendaraan/driver dipilih ganda pada rentang waktu sama | tidak terjadi double-booking | Must |
| US-04 | Admin | melihat daftar pemesanan beserta status persetujuannya | memantau progres tiap permintaan | Must |
| US-05 | Admin | membatalkan pemesanan yang belum digunakan | mengelola pembatalan | Must |
| US-06 | Approver | melihat daftar pemesanan yang menunggu persetujuan saya | tidak ketinggalan permintaan | Must |
| US-07 | Approver | menyetujui/menolak pemesanan disertai catatan | keputusan terdokumentasi | Must |
| US-08 | Approver | hanya dapat menyetujui setelah level sebelumnya disetujui | persetujuan berjenjang konsisten | Must |
| US-09 | Admin | mencatat odometer berangkat & kembali saat kendaraan dipakai | riwayat pemakaian & jarak akurat | Must |
| US-10 | Admin | mencatat pengisian BBM (liter, harga, odometer) | konsumsi km/liter & biaya terukur | Must |
| US-11 | Admin | membuat jadwal service dan mencatat riwayat service kendaraan | servis terjadwal & riwayat tersedia | Must |
| US-12 | Admin | melihat dashboard grafik pemakaian kendaraan | analisis penggunaan armada cepat | Must |
| US-13 | Admin | mengekspor laporan periodik pemesanan ke Excel | pelaporan ke manajemen | Must |
| US-14 | Sistem | mencatat log untuk setiap proses (create, update, approve, reject, cancel) | audit trail lengkap | Must |
| US-15 | Admin | mengelola data master (lokasi, pegawai, user, driver, kendaraan, partner sewa) | data dasar terpelihara | Should |

---

## 6. Functional Requirements

### FR-1 — Autentikasi & Otorisasi
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-1.1 | Sistem menyediakan 2 peran: `admin` dan `approver`. | Must |
| FR-1.2 | Admin dapat login dan mengakses seluruh modul. | Must |
| FR-1.3 | Approver hanya dapat mengakses modul persetujuan (daftar menunggu, riwayat yang ia proses) dan dashboard. | Must |
| FR-1.4 | Sistem memblokir akses lintas peran (RBAC). | Must |

### FR-2 — Pemesanan Kendaraan (Inti)
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-2.1 | Admin membuat pemesanan: kendaraan, driver, pegawai pemesan, keperluan, tujuan, rencana berangkat/kembali. | Must |
| FR-2.2 | Admin menentukan daftar approver berjenjang **minimal 2 level**; sistem menolak jika kurang dari 2. | Must |
| FR-2.3 | Sistem menyarankan approver default dari hierarki atasan (`manager_id`); admin dapat mengubahnya. | Should |
| FR-2.4 | Sistem memblokir double-booking: kendaraan tidak boleh punya pemesanan aktif yang waktunya tumpang tindih. | Must |
| FR-2.5 | Sistem memblokir driver yang memiliki aktivitas aktif bersamaan. | Must |
| FR-2.6 | Admin dapat membatalkan pemesanan berstatus `pending`/`approved`. | Must |

### FR-3 — Persetujuan Berjenjang
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-3.1 | Approver melihat daftar pemesanan yang menunggu persetujuannya. | Must |
| FR-3.2 | Approver dapat menyetujui/menolak disertai catatan (wajib saat menolak). | Must |
| FR-3.3 | Level N hanya dapat diproses setelah level N-1 disetujui. | Must |
| FR-3.4 | Semua level disetujui → status pemesanan `approved`; ada yang menolak → `rejected`. | Must |
| FR-3.5 | Setiap keputusan tercatat: siapa, kapan, catatan. | Must |

### FR-4 — Penggunaan & Penyelesaian
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-4.1 | Pemesanan `approved` dapat dijalankan: catat odometer berangkat → status `in_use`. | Must |
| FR-4.2 | Saat kembali: catat odometer akhir, sistem menghitung jarak (distance_km) → status `completed`. | Must |
| FR-4.3 | `current_odometer` kendaraan otomatis diperbarui saat kembali. | Must |

### FR-5 — Monitoring BBM
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-5.1 | Admin mencatat pengisian BBM: kendaraan, tanggal, odometer, liter, harga/liter, total biaya, status full-tank. | Must |
| FR-5.2 | Sistem menghitung konsumsi (km/liter) antar pengisian full-tank. | Should |
| FR-5.3 | Catatan BBM dapat dikaitkan ke pemesanan tertentu (opsional). | Should |

### FR-6 — Monitoring Service
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-6.1 | Admin membuat jadwal service (tanggal dan/atau odometer, tipe, pengulangan). | Must |
| FR-6.2 | Sistem menandai jadwal `due`/`overdue`. | Must |
| FR-6.3 | Admin mencatat riwayat service (tanggal, odometer, biaya, vendor) — termasuk perbaikan tanpa jadwal (ad-hoc). | Must |

### FR-7 — Dashboard & Laporan
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-7.1 | Dashboard menampilkan grafik pemakaian kendaraan (mis. frekuensi per kendaraan per bulan). | Must |
| FR-7.2 | Dashboard menampilkan ringkasan: kendaraan tersedia, pemesanan aktif, overdue service. | Should |
| FR-7.3 | Admin dapat mengekspor laporan periodik pemesanan (filter rentang tanggal) ke file Excel. | Must |

### FR-8 — Audit Log
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-8.1 | Sistem mencatat setiap proses: pengguna, aksi, entitas, data sebelum/sesudah, IP, waktu. | Must |
| FR-8.2 | Admin dapat melihat riwayat audit suatu entitas. | Should |

### FR-9 — Master Data
| ID | Deskripsi | Prioritas |
|---|---|---|
| FR-9.1 | CRUD lokasi (pusat/cabang/tambang, region). | Should |
| FR-9.2 | CRUD pegawai (termasuk hierarki atasan) & user (admin/approver). | Should |
| FR-9.3 | CRUD driver (pegawai + data SIM). | Should |
| FR-9.4 | CRUD kendaraan (tipe angkut, kepemilikan, data sewa) & partner persewaan. | Should |

---

## 7. Non-Functional Requirements

### NFR-1 — Performa
- Waktu muat halaman utama/dashboard ≤ 3 detik pada jaringan normal.
- Query dashboard agregasi bulanan selesai ≤ 2 detik untuk volume data uji (ribuan pemesanan).
- Pagination wajib pada daftar pemesanan.

### NFR-2 — Keamanan
- Autentikasi berbasis session dengan password ter-hash (bcrypt).
- Otorisasi berbasis peran (RBAC) di sisi server — bukan hanya penyembunyian UI.
- Proteksi CSRF pada seluruh form; output di-escape untuk mencegah XSS.
- Validasi input di server-side.

### NFR-3 — Keandalan & Integritas
- Transaksi database (DB transaction) untuk operasi multi-tabel (create booking + approvals; return kendaraan).
- Konstrain database (FK, UNIQUE, ENUM) menjamin integritas di lapisan data.
- Audit log tidak dapat dimodifikasi setelah tercatat.

### NFR-4 — Usability & UI/UX
- Desain responsive (desktop & mobile).
- Alur approval sederhana (lihat → keputusan) dalam ≤ 2 klik.
- Notifikasi validasi yang jelas pada form.

### NFR-5 — Maintainability
- Kode mengikuti standar framework (PHP-FIG, Laravel conventions).
- Migrasi database versioned; konfigurasi via environment variables.

### NFR-6 — Kompatibilitas
- Mendukung browser modern (Chrome, Edge, Firefox, Safari versi terakhir).
- Backend: PHP 8.3+, framework Laravel 13, database MySQL 8.

### NFR-7 — Dokumentasi
- README berisi: daftar username-password, versi database, versi PHP, framework, dan panduan penggunaan.

---

## 8. Scope

### In Scope (dibangun pada rilis ini)
1. Modul autentikasi & RBAC (admin, approver).
2. Modul pemesanan kendaraan + persetujuan berjenjang minimal 2 level.
3. Pencatatan penggunaan kendaraan (odometer, jarak).
4. Modul monitoring: konsumsi BBM, jadwal & riwayat service.
5. Dashboard grafik pemakaian kendaraan.
6. Laporan periodik pemesanan — ekspor Excel.
7. Audit log pada setiap proses.
8. Master data pendukung (lokasi, pegawai, user, driver, kendaraan, partner sewa).
9. Physical data model (`docs/ERD.md`) + activity diagram + README.

### Out of Scope (tidak termasuk rilis ini)
- Akun login untuk pegawai pemesan (tetap melalui admin pool).
- Mobile native app (web responsive cukup).
- Notifikasi real-time (email/SMS/WhatsApp) ke approver.
- Integrasi IoT/telemetri kendaraan (odometer manual).
- Manajemen inventori suku cadang & perhitungan depresiasi kendaraan.
- Multi-bahasa (i18n).
- Pembayaran/penagihan biaya sewa.

### Asumsi & Batasan
- Data driver adalah karyawan perusahaan (bukan vendor eksternal).
- Jumlah approver bervariasi per pemesanan (minimal 2), ditentukan admin.
- Kendaraan sewa dicatat sebagai master dengan masa kontrak.
- Satu pemesanan = satu perjalanan kendaraan (odometer berangkat/kembali).

---

## 9. Deliverables Terkait

| Dokumen | Tautan |
|---|---|
| Physical Data Model (12 tabel) | `docs/ERD.md` |
| Activity Diagram Pemesanan | Akan menyusul |
| README (kredensial, versi, panduan) | Akan menyusul |
