# Design System — TransOps

TransOps adalah sistem pemesanan & monitoring kendaraan (fleet) untuk perusahaan tambang nikel: admin pool menginput pemesanan, persetujuan berjenjang minimal 2 level oleh approver, serta monitoring konsumsi BBM, jadwal service, dan riwayat pemakaian — dilengkapi dashboard grafik dan laporan ekspor Excel.

Dokumen ini adalah spesifikasi UI/UX: prinsip, warna, tipografi, layout, komponen, dan perilaku responsive. Berfungsi sebagai acuan implementasi (Laravel + Inertia React + Tailwind).

---

## 1. Prinsip Desain

1. **Data-first** — aplikasi ini adalah alat operasional. Tabel, grafik, dan status adalah bintangnya; chrome (navigasi, tombol, kartu) hanya pengiring dan tidak boleh mengalahkan informasi.
2. **Status selalu "warna + label"** — tidak pernah warna saja. Setiap status (pending, approved, rejected, in_use, completed, due, overdue, maintenance) memiliki warna + teks sehingga tetap terbaca untuk pengguna buta warna (WCAG AA).
3. **Operasi efisien** — aksi yang sering (approve/reject, catat odometer, input BBM) maksimal 2 klik. Form pemesanan meminimalkan field wajib.
4. **Satu akun, satu peran** — UI admin dan approver berbeda secara nyata (navigasi & aksi berbeda), bukan sekadar tombol yang disembunyikan.
5. **Responsive dari awal** — bukan "desktop lalu dipaksa mobile". Layout dirancang mobile-first untuk breakpoint.

---

## 2. Warna

Nada visual: industri, tegas, tenang — terinspirasi operasi tambang, bukan laman marketing. Blue-slate sebagai basis, warna semantik hanya untuk status & aksi.

### Brand & Akun
| Token | Nilai | Penggunaan |
|---|---|---|
| `{colors.primary}` | `#1e40af` | Aksi primer, link, nav aktif, fokus |
| `{colors.primary-hover}` | `#1e3a8a` | Hover tombol primer |
| `{colors.primary-soft}` | `#dbeafe` | Baris terpilih, latar badge info |
| `{colors.canvas}` | `#ffffff` | Dasar halaman, kartu |
| `{colors.surface}` | `#f8fafc` | Wash body, sidebar |
| `{colors.surface-card}` | `#ffffff` | Kartu & kontainer |
| `{colors.hairline}` | `#e2e8f0` | Border tabel, pembatas |
| `{colors.ink}` | `#0f172a` | Judul, teks utama |
| `{colors.body}` | `#334155` | Teks paragraf |
| `{colors.mute}` | `#64748b` | Metadata, caption |
| `{colors.ash}` | `#94a3b8` | Placeholder, disabled |

### Semantik (status)
| Token | Nilai | Status |
|---|---|---|
| `{colors.success}` / `{colors.success-pale}` | `#16a34a` / `#dcfce7` | completed, approved (level), available, done |
| `{colors.warning}` / `{colors.warning-pale}` | `#d97706` / `#fef3c7` | pending, due, overdue, maintenance |
| `{colors.error}` / `{colors.error-pale}` | `#dc2626` / `#fee2e2` | rejected, error, cancelled |
| `{colors.info}` / `{colors.info-pale}` | `#0284c7` / `#e0f2fe` | in_use, scheduled |
| `{colors.neutral}` / `{colors.neutral-pale}` | `#64748b` / `#f1f5f9` | retired, cancelled (sekunder) |

**Pemetaan status aplikasi:**
- Pemesanan: `pending`=warning · `approved`=success · `in_use`=info · `completed`=success · `rejected`=error · `cancelled`=neutral
- Approval per level: `pending`=warning · `approved`=success · `rejected`=error
- Kendaraan: `available`=success · `in_use`=info · `maintenance`=warning · `retired`=neutral
- Service: `scheduled`=info · `due`=warning · `overdue`=error · `cancelled`=neutral

---

## 3. Tipografi

### Font Family
**Inter** (open-source, weights 400/500/600/700) dengan fallback `-apple-system → system-ui → Segoe UI → Roboto`. Inter dipilih karena geometri bersih dan kepadatan tinggi di ukuran kecil — ideal untuk tabel data.

### Hierarki
| Token | Size | Weight | Line Height | Penggunaan |
|---|---|---|---|---|
| `{typography.heading-lg}` | 24px | 700 | 1.25 | Judul halaman |
| `{typography.heading-md}` | 18px | 600 | 1.3 | Judul kartu / seksi |
| `{typography.body-md}` | 14px | 400 | 1.5 | Teks default |
| `{typography.body-strong}` | 14px | 600 | 1.5 | Label form, nav aktif |
| `{typography.body-sm}` | 13px | 400 | 1.45 | Isi tabel (dense) |
| `{typography.table-header}` | 12px | 700 | 1.4 | Header tabel, uppercase |
| `{typography.caption}` | 12px | 400 | 1.4 | Caption, metadata |
| `{typography.button}` | 13px | 600 | 1 | Label tombol |

**Catatan:** aplikasi operasional tidak memakai display headline besar (70px ala marketing). Ukuran terbesar cukup 24px agar kepadatan informasi tetap tinggi.

---

## 4. Spacing & Layout

### Spacing System
- Base unit **8px**. Token: `{spacing.xxs}` 4 · `{spacing.xs}` 6 · `{spacing.sm}` 8 · `{spacing.md}` 12 · `{spacing.lg}` 16 · `{spacing.xl}` 24 · `{spacing.xxl}` 32.
- Jarak antar kartu: `{spacing.lg}` (16px). Padding kartu: `{spacing.lg}`. Jarak seksi besar: `{spacing.xxl}` (32px).

### App Shell
- **Sidebar** (240px, kiri): navigasi modul + nama/role pengguna. Collapse ke ikon-only (72px) di tablet, menjadi drawer di mobile (<1024px).
- **Topbar** (56px): judul halaman, role badge, tombol logout.
- **Konten**: fluid, max-width 1440px, `{spacing.xl}` padding di desktop → `{spacing.lg}` di mobile.

### Grid
- **Dashboard**: grid 4 kolom stat-card (desktop) → 2 kolom (tablet) → 1 kolom (mobile). Grafik full-width, tingginya menyesuaikan kontainer.
- **Tabel**: full-width dengan sticky header; di mobile menjadi horizontal-scroll (bukan stack kartu) untuk data padat.
- **Form**: label di atas input, 1 kolom pada <768px.

---

## 5. Bentuk & Elevasi

### Border Radius
| Token | Nilai | Penggunaan |
|---|---|---|
| `{rounded.none}` | 0px | Struktur (sidebar, topbar) |
| `{rounded.md}` | 8px | Tombol, input, kartu, badge, tabel-row |
| `{rounded.lg}` | 12px | Modal, kartu besar |
| `{rounded.full}` | 9999px | Badge status (dot-pill), avatar |

Radius **8px** dipilih (bukan 16px) agar terasa presisi dan profesional untuk alat operasional.

### Elevasi
- Level 0: flat (default kartu/tabel).
- Level 1: 1px `{colors.hairline}` border (kartu, tabel, input).
- Level 2: modal dengan scrim 50% + soft shadow 16px ambient.
- Tidak ada drop shadow pada kartu konten (kecuali modal).

---

## 6. Komponen

### Tombol
- **`button-primary`** — bg `{colors.primary}`, teks putih, `{rounded.md}`, height 38px, padding 0 16px. Untuk aksi utama (Simpan, Buat Pemesanan, Setujui, Kembalikan).
- **`button-secondary`** — bg `{colors.surface-card}` + border `{colors.hairline}`. Aksi sekunder (Batal, Edit).
- **`button-danger`** — bg `{colors.error}`, teks putih. Aksi destruktif (Tolak, Batalkan Pemesanan) — dipakai saat konfirmasi modal.
- **`button-ghost`** — transparan, teks `{colors.body}`. Aksi tersier (Lihat, Ekspor).
- **`button-icon`** — 40×40, `{rounded.full}`, untuk aksi baris tabel (detail, edit, hapus).
- Disabled: bg `{colors.surface}` + teks `{colors.ash}`.

### Badge Status
- Bentuk **pill** (`{rounded.full}`) dengan **dot + label teks** — dot berwarna status, label `{typography.caption}` semibold. Contoh: `● pending`, `● approved`, `● overdue`.
- Pemetaan warna mengikuti tabel semantik di atas.

### Tabel (komponen paling penting)
- Header: `{colors.surface}` bg, teks `{typography.table-header}` uppercase, border bawah `{colors.hairline}`.
- Baris: 40px, zebra ringan (even = `{colors.surface}`), border bawah 1px hairline.
- Aksi baris (icon button) muncul permanen di kolom kanan.
- **Pagination** di bawah tabel (10/25/50 per halaman).
- Responsive: horizontal-scroll pada <768px dengan sticky kolom aksi di kanan.

### Kartu & Stat
- **`stat-card`** — bg `{colors.surface-card}` + border hairline + radius md. Berisi: label (`{typography.caption}` mute), nilai besar (`{typography.heading-lg}`), delta/trend kecil. Dipakai di dashboard (kendaraan tersedia, pemesanan aktif, pending approval, service overdue).
- **`card`** — kontainer umum untuk grafik & form section.

### Form
- **`text-input` / `select` / `date-input`** — bg `{colors.canvas}`, border 1px `{colors.ash}`, radius md, height 40px, label `{typography.body-strong}`.
- Fokus: 2px `{colors.primary}` border + 4px ring `{colors.primary-soft}`.
- **Error state**: border `{colors.error}` + pesan `{colors.error}` di bawah field.
- **Required indicator**: asterisk `*` di label.

### Alur Persetujuan (komponen khas)
- **`approval-stepper`** — indikator berjenjang: `Level 1 → Level 2 → …` sebagai pill-row; pill yang sudah approve berwarna success (✓), pending warning, rejected error. Menampilkan nama approver + waktu keputusan pada tooltip/kartu detail.
- Tombol aksi approver: **Setujui** (`button-primary`) / **Tolak** (`button-danger`) — Tolak mewajibkan isi catatan di modal konfirmasi.

### Lainnya
- **`modal`** — radius lg, padding 24px, scrim 50%, dipakai untuk konfirmasi tolak/cancel dan form input odometer.
- **`toast`** — notifikasi sukses/error di pojok kanan atas, auto-dismiss 4 detik.
- **`empty-state`** — ikon + pesan + CTA saat daftar kosong.
- **`search-input` + `filter-chip`** — filter daftar pemesanan (status, kendaraan, rentang tanggal).

---

## 7. Perilaku Responsive

### Breakpoints
| Nama | Lebar | Perubahan Kunci |
|---|---|---|
| desktop | 1280px+ | Sidebar penuh, grid 4 kolom, tabel normal |
| laptop | 1024px | Sidebar collapse ikon-only, grid 3 kolom |
| tablet | 768px | Sidebar drawer, grid 2 kolom, tabel horizontal-scroll |
| mobile | 480px | Single column, tombol aksi baris tabel jadi menu, padding 16px |

### Touch Target
Semua elemen interaktif ≥ 44×44px efektif (WCAG AA). Tombol 38px tinggi + padding inline untuk memenuhi.

### Collapsing Strategy
- **Sidebar**: penuh → ikon-only (1024px) → drawer hamburger (768px).
- **Grid stat**: 4 → 3 → 2 → 1 kolom.
- **Tabel**: normal → horizontal-scroll (768px) → aksi baris masuk menu "⋯" (480px).
- **Form pemesanan**: multi-kolom → single kolom (768px).
- **Approval stepper**: horizontal → stack vertikal (480px).

---

## 8. Do's & Don'ts

### Do
- Selalu gunakan **warna + label** untuk status; jangan warna saja.
- Reserve warna primer untuk aksi primer & link; jangan dekoratif.
- Gunakan tabel dense (`{typography.body-sm}`) untuk data operasional.
- Konsistenkan radius 8px untuk semua elemen interaktif.
- Fokus ring (`{colors.primary}` + `{colors.primary-soft}`) pada semua input.

### Don't
- Jangan gunakan display headline besar (marketing style) di dalam modul operasional.
- Jangan tambah shadow pada kartu konten; hanya modal yang boleh.
- Jangan menaruh aksi destruktif tanpa modal konfirmasi.
- Jangan sembunyikan aksi penting hanya pada hover (tidak mobile-friendly).

---

## 9. Catatan Implementasi

- Diterapkan dengan **Tailwind CSS** (token dipetakan ke variabel Tailwind via `tailwind.config`).
- Grafik dashboard memakai **Rechart.js** (konsisten dengan keputusan stack Inertia React).
- Komponen React dibangun sebagai pustaka komponen lokal (`resources/js/components/ui`) agar konsisten lintas halaman.
- Validasi form inline mengikuti pola error state di atas.
