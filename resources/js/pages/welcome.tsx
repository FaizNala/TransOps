import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Car,
    CheckCircle2,
    ClipboardList,
    Droplets,
    FileSpreadsheet,
    FileSearch,
    Gauge,
    GitBranch,
    LayoutDashboard,
    LogIn,
    ShieldCheck,
    Users,
    Wrench,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

const steps = [
    {
        no: '01',
        title: 'Diajukan',
        desc: 'Admin mencatat keperluan, kendaraan, driver, dan tujuan dalam satu pengajuan.',
    },
    {
        no: '02',
        title: 'Disetujui dua level',
        desc: 'Atasan menyetujui atau menolak lewat aplikasi. Tidak ada pemakaian tanpa jejak.',
    },
    {
        no: '03',
        title: 'Berangkat',
        desc: 'Odometer berangkat tercatat. Konflik jadwal kendaraan atau driver dicegah sejak awal.',
    },
    {
        no: '04',
        title: 'Tercatat',
        desc: 'Kembali, BBM, dan odometer diisi. Seluruh transisi mengalir ke audit log.',
    },
];

const pains = [
    'Pemesanan verbal ke pool — tanpa jejak, rentan pemakaian tanpa izin',
    'Konsumsi BBM tidak terukur — liter, odometer, dan biaya per kendaraan tidak ada',
    'Jadwal service terlewat — berisiko downtime dan kerusakan lebih besar',
    'Riwayat pemakaian tidak tersedia — sulit mengambil keputusan rotasi dan efisiensi',
];

const capabilities = [
    {
        icon: ClipboardList,
        title: 'Pemesanan kendaraan',
        desc: 'Pengajuan lengkap dengan kendaraan, driver, keperluan, dan waktu.',
    },
    {
        icon: GitBranch,
        title: 'Persetujuan berjenjang',
        desc: 'Minimal dua level, keputusan terdokumentasi, langsung dari aplikasi.',
    },
    {
        icon: Droplets,
        title: 'Monitoring BBM',
        desc: 'Liter, odometer, dan biaya tiap pengisian tercatat per kendaraan.',
    },
    {
        icon: Wrench,
        title: 'Jadwal & riwayat service',
        desc: 'Servis berkala terpantau, riwayat tersedia, downtime bisa dicegah.',
    },
    {
        icon: LayoutDashboard,
        title: 'Dashboard grafik',
        desc: 'Pola pemakaian armada terlihat sekilas untuk keputusan cepat.',
    },
    {
        icon: FileSpreadsheet,
        title: 'Laporan periodik',
        desc: 'Ekspor pemesanan ke Excel dalam satu klik per periode.',
    },
    {
        icon: FileSearch,
        title: 'Audit log penuh',
        desc: 'Setiap transisi status tercatat. Siapa menyetujui apa, kapan, selalu bisa ditelusuri.',
    },
    {
        icon: Users,
        title: 'Data master terkelola',
        desc: 'Lokasi, pegawai, driver, kendaraan, hingga partner sewa dalam satu tempat.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <div
                dangerouslySetInnerHTML={{
                    __html: `<!--
THESIS: Setiap perjalanan armada tercatat dari pengajuan hingga penyelesaian, tanpa jalur lain. Menolak hero-kartu SaaS standar dengan menampilkan alur persetujuan berjenjang sebagai garis tegangan yang jadi inti komposisi.
OWN-WORLD: Ground neutral (bg-background) dengan struktur rangka garis 1px (border-border); aksen merah --primary dipakai hanya pada tali tegangan status, titik aktif, dan aksi primer. Tipografi Inter; label kecil mono untuk nomor langkah.
STORY: Pengunjung (karyawan/atasan internal) memahami bahwa setiap pemakaian kendaraan kini berdokumen: diajukan, disetujui dua level, dipakai, lalu tercatat lengkap dengan BBM, odometer, service, dan audit.
FIRST VIEWPORT: Header tipis dengan TRANSSOPS kiri dan aksi di kanan; pernyataan besar satu baris, subcopy singkat, lalu garis tegangan merah yang merangkai status Diajukan → Persetujuan → Berangkat → Selesai, dengan titik aktif. Aksi primer di bawah.
FORM: surface seed 8999756f.
FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, and DESIGN.md.
-->`,
                }}
                hidden
            />
            <Head title="TransOps — Fleet Booking & Monitoring" />

            <div className="min-h-screen bg-background text-foreground">
                {/* Nav */}
                <header className="border-b border-border">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                        <Link
                            href={dashboard()}
                            prefetch
                            className="flex items-center gap-2"
                        >
                            <AppLogo />
                        </Link>
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Dashboard
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button asChild>
                                    <Link href={login()}>Masuk</Link>
                                </Button>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    {/* Hero */}
                    <section className="mx-auto max-w-6xl px-6 pt-20 pb-16 md:pt-28">
                        <h1 className="max-w-3xl text-balance text-4xl leading-[1.08] font-semibold tracking-tight md:text-6xl">
                            Satu jalur tercatat untuk setiap perjalanan armada
                        </h1>
                        <p className="mt-6 max-w-xl text-pretty text-base text-muted-foreground md:text-lg">
                            TransOps mendigitalkan alur pemesanan kendaraan —
                            dari pengajuan, persetujuan berjenjang, hingga
                            penyelesaian — lengkap dengan monitoring BBM,
                            jadwal service, dan audit yang bisa ditelusuri.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-3">
                            {auth.user ? (
                                <Button size="lg" asChild>
                                    <Link href={dashboard()}>
                                        Buka Dashboard
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="lg" asChild>
                                    <Link href={login()}>
                                        Masuk ke Aplikasi
                                        <LogIn className="size-4" />
                                    </Link>
                                </Button>
                            )}
                            <Button size="lg" variant="outline" asChild>
                                <a href="#cara-kerja">
                                    Lihat cara kerjanya
                                    <ArrowRight className="size-4" />
                                </a>
                            </Button>
                        </div>
                    </section>

                    {/* Status pipeline — the tension line */}
                    <section
                        aria-label="Alur pemesanan"
                        className="border-y border-border bg-muted/50"
                    >
                        <div className="mx-auto max-w-6xl px-6 py-14">
                            <ol className="flex flex-col gap-5 sm:grid sm:grid-cols-2 sm:gap-x-4 md:grid-cols-5 md:gap-0 md:items-start">
                                {[
                                    'Diajukan',
                                    'Persetujuan 1',
                                    'Persetujuan 2',
                                    'Berangkat',
                                    'Selesai',
                                ].map((label, index) => (
                                    <li
                                        key={label}
                                        className="relative flex items-center gap-3 md:flex-col md:items-center md:gap-2"
                                    >
                                        <span className="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-primary bg-background">
                                            <span className="h-2.5 w-2.5 rounded-full bg-primary" />
                                        </span>
                                        <div className="flex flex-col gap-0.5 md:items-center">
                                            <span className="font-mono text-[11px] tracking-wide text-muted-foreground">
                                                {String(index + 1).padStart(2, '0')}
                                            </span>
                                            <span className="text-sm font-medium md:text-center">
                                                {label}
                                            </span>
                                        </div>
                                        {/* connecting line: from this dot's centre to the next */}
                                        {index < 4 && (
                                            <span
                                                aria-hidden
                                                className="absolute top-4 left-1/2 hidden h-px w-full bg-primary/40 md:block"
                                            />
                                        )}
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    {/* Problem */}
                    <section className="mx-auto max-w-6xl px-6 py-20 md:py-24">
                        <div className="grid gap-12 md:grid-cols-2">
                            <h2 className="max-w-md text-balance text-3xl leading-tight font-semibold tracking-tight md:text-4xl">
                                Operasional armada yang tidak terdokumentasi
                                mahal dan tak terkendali
                            </h2>
                            <ul className="divide-y divide-border border-y border-border">
                                {pains.map((pain) => (
                                    <li
                                        key={pain}
                                        className="flex items-start gap-3 py-4 text-[15px] leading-relaxed text-muted-foreground"
                                    >
                                        <ShieldCheck className="mt-0.5 size-5 shrink-0 text-primary" />
                                        {pain}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </section>

                    {/* How it works */}
                    <section
                        id="cara-kerja"
                        className="border-y border-border bg-muted/50"
                    >
                        <div className="mx-auto max-w-6xl px-6 py-20 md:py-24">
                            <h2 className="max-w-xl text-balance text-3xl leading-tight font-semibold tracking-tight md:text-4xl">
                                Dari permintaan lisan menjadi alur yang bisa
                                diaudit
                            </h2>
                            <ol className="mt-12 grid gap-px border border-border bg-border md:grid-cols-4">
                                {steps.map((step) => (
                                    <li
                                        key={step.no}
                                        className="bg-background p-6"
                                    >
                                        <span className="font-mono text-sm text-primary">
                                            {step.no}
                                        </span>
                                        <h3 className="mt-4 font-semibold">
                                            {step.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                            {step.desc}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    {/* Capabilities */}
                    <section className="mx-auto max-w-6xl px-6 py-20 md:py-24">
                        <h2 className="max-w-xl text-balance text-3xl leading-tight font-semibold tracking-tight md:text-4xl">
                            Satu sistem untuk seluruh siklus armada
                        </h2>
                        <ul className="mt-12 grid gap-px border border-border bg-border sm:grid-cols-2 lg:grid-cols-4">
                            {capabilities.map(({ icon: Icon, title, desc }) => (
                                <li key={title} className="bg-background p-6">
                                    <Icon className="size-5 text-primary" />
                                    <h3 className="mt-4 font-semibold">
                                        {title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {desc}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {/* Closing CTA */}
                    <section className="border-t border-border">
                        <div className="mx-auto flex max-w-6xl flex-col items-center px-6 py-20 text-center md:py-28">
                            <Gauge className="size-8 text-primary" />
                            <h2 className="mt-6 max-w-2xl text-balance text-3xl leading-tight font-semibold tracking-tight md:text-4xl">
                                Setiap kendaraan terpantau. Setiap keputusan
                                tercatat.
                            </h2>
                            <p className="mt-4 max-w-xl text-pretty text-muted-foreground">
                                Mulai menertibkan pemakaian armada hari ini.
                            </p>
                            <div className="mt-8">
                                {auth.user ? (
                                    <Button size="lg" asChild>
                                        <Link href={dashboard()}>
                                            Buka Dashboard
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button size="lg" asChild>
                                        <Link href={login()}>
                                            Masuk ke Aplikasi
                                            <LogIn className="size-4" />
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="border-t border-border">
                    <div className="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-10 md:flex-row md:items-center md:justify-between">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Car className="size-4" />
                            <span>TransOps — Fleet Booking & Monitoring</span>
                        </div>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <CheckCircle2 className="size-4 text-primary" />
                            <span>Setiap perjalanan tercatat</span>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
