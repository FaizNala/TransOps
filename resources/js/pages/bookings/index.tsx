import { Head, router, Link } from '@inertiajs/react';
import {
    Download,
    Flag,
    History,
    Pencil,
    Play,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { BookingFormDialog } from '@/components/bookings/booking-form-dialog';
import type { BookingFormPayload } from '@/components/bookings/booking-form-dialog';
import { BookingUsageDialog } from '@/components/bookings/booking-usage-dialog';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { SummaryCards } from '@/components/summary-cards';
import type { SummaryCardItem } from '@/components/summary-cards';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDateTime } from '@/lib/format';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { cancel, destroy, index } from '@/routes/bookings';

type StatusOption = { value: string; label: string };
type Option = { id: number; label: string };

type BookingRow = {
    id: number;
    booking_number: string;
    requester: string | null;
    vehicle: string;
    vehicle_id: number;
    driver: string | null;
    driver_id: number;
    purpose: string;
    destination: string;
    start_at: string;
    end_at: string;
    status: string;
    status_label: string;
    approver_ids: number[];
};

type BookingPageData = {
    data: BookingRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Props = {
    bookings: BookingPageData;
    filters: { search?: string; status?: string };
    summary: SummaryCardItem[];
    statuses: StatusOption[];
    vehicles: Option[];
    drivers: Option[];
    approvers: Option[];
    suggestedApprovers: number[];
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    approved: 'default',
    in_use: 'default',
    pending: 'secondary',
    completed: 'outline',
    rejected: 'destructive',
    cancelled: 'outline',
};

export default function BookingsIndex({
    bookings,
    filters,
    summary,
    statuses,
    vehicles,
    drivers,
    approvers,
    suggestedApprovers,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const searchRef = useRef(search);
    const statusRef = useRef(status);
    const timerRef = useRef<number | undefined>(undefined);
    const [formTarget, setFormTarget] = useState<{
        key: string;
        payload?: BookingFormPayload;
    } | null>(null);
    const [deleting, setDeleting] = useState<BookingRow | null>(null);
    const [cancelling, setCancelling] = useState<BookingRow | null>(null);
    const [exportFrom, setExportFrom] = useState('');
    const [exportTo, setExportTo] = useState('');
    const [usageTarget, setUsageTarget] = useState<{
        row: BookingRow;
        mode: 'start' | 'return';
    } | null>(null);

    const submitUsage = (odometer: number) => {
        if (!usageTarget) {
            return;
        }

        router.post(
            `/bookings/${usageTarget.row.id}/${usageTarget.mode === 'start' ? 'start' : 'return'}`,
            usageTarget.mode === 'start'
                ? { odometer_start: odometer }
                : { odometer_end: odometer },
            { preserveScroll: true, onSuccess: () => setUsageTarget(null) },
        );
    };

    const downloadExcel = () => {
        const params = new URLSearchParams();

        if (exportFrom) {
            params.set('from', exportFrom);
        }

        if (exportTo) {
            params.set('to', exportTo);
        }

        const qs = params.toString();

        window.location.href = qs
            ? `/bookings/export?${qs}`
            : '/bookings/export';
    };

    useEffect(() => {
        searchRef.current = search;
        statusRef.current = status;
    }, [search, status]);

    const applyFilters = (searchValue: string, statusValue: string) => {
        router.get(
            index({
                query: {
                    filter: {
                        search: searchValue || null,
                        status: statusValue || null,
                    },
                    page: 1,
                },
            }).url,
            {},
            { preserveScroll: true },
        );
    };

    const onSearchChange = (value: string) => {
        setSearch(value);
        window.clearTimeout(timerRef.current);
        timerRef.current = window.setTimeout(
            () => applyFilters(value, statusRef.current),
            300,
        );
    };

    const onStatusChange = (value: string) => {
        setStatus(value);
        applyFilters(searchRef.current, value);
    };

    const openEdit = (row: BookingRow) => {
        setFormTarget({
            key: `edit-${row.id}`,
            payload: {
                id: row.id,
                vehicle_id: row.vehicle_id,
                driver_id: row.driver_id,
                purpose: row.purpose,
                destination: row.destination,
                start_at: row.start_at,
                end_at: row.end_at,
                approvers: row.approver_ids,
            },
        });
    };

    const confirmsDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(destroy(deleting.id).url, {
            preserveScroll: true,
            onSuccess: () => setDeleting(null),
        });
    };

    const confirmsCancel = () => {
        if (!cancelling) {
            return;
        }

        router.post(
            cancel(cancelling.id).url,
            {},
            { preserveScroll: true, onSuccess: () => setCancelling(null) },
        );
    };

    const columns: DataTableColumn<BookingRow>[] = [
        { key: 'booking_number', header: 'No. Pemesanan' },
        { key: 'requester', header: 'Pemohon' },
        { key: 'vehicle', header: 'Kendaraan' },
        { key: 'driver', header: 'Driver' },
        {
            key: 'purpose',
            header: 'Tujuan',
            render: (row) => (
                <span className="line-clamp-1 max-w-56">{row.purpose}</span>
            ),
        },
        {
            key: 'schedule',
            header: 'Jadwal',
            render: (row) => (
                <span className="whitespace-nowrap text-muted-foreground">
                    {formatDateTime(row.start_at)} -{' '}
                    {formatDateTime(row.end_at)}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <Badge variant={statusVariant[row.status]}>
                    {row.status_label}
                </Badge>
            ),
        },
        {
            key: 'actions',
            header: 'Aksi',
            render: (row) => {
                const canManage = row.status === 'pending';

                return (
                    <div className="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Audit"
                            asChild
                            className="h-8 w-8"
                        >
                            <Link
                                href={
                                    auditLogsIndex({
                                        query: {
                                            filter: {
                                                entity_type: 'booking',
                                                entity_id: String(row.id),
                                            },
                                        },
                                    }).url
                                }
                            >
                                <History />
                            </Link>
                        </Button>
                        {row.status === 'approved' && (
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label="Mulai"
                                onClick={() =>
                                    setUsageTarget({ row, mode: 'start' })
                                }
                                className="h-7 gap-1 text-xs"
                            >
                                <Play /> Mulai
                            </Button>
                        )}
                        {row.status === 'in_use' && (
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label="Selesaikan"
                                onClick={() =>
                                    setUsageTarget({ row, mode: 'return' })
                                }
                                className="h-7 gap-1 text-xs"
                            >
                                <Flag /> Selesai
                            </Button>
                        )}
                        {(row.status === 'pending' ||
                            row.status === 'approved') && (
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label="Batalkan"
                                onClick={() => setCancelling(row)}
                                className="h-7 gap-1 text-xs text-destructive"
                            >
                                <X /> Batalkan
                            </Button>
                        )}
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Edit"
                            disabled={!canManage}
                            onClick={() => openEdit(row)}
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Hapus"
                            disabled={!canManage}
                            onClick={() => setDeleting(row)}
                        >
                            <Trash2 />
                        </Button>
                    </div>
                );
            },
        },
    ];

    return (
        <>
            <Head title="Manajemen Pemesanan" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Manajemen Pemesanan
                    </h1>
                    <Button onClick={() => setFormTarget({ key: 'create' })}>
                        <Plus /> Tambah Pemesanan
                    </Button>
                </div>

                <SummaryCards items={summary} />

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="search"
                        placeholder="Cari no. pemesanan, pemohon, kendaraan..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="max-w-xs"
                    />
                    <Select
                        value={status || 'all'}
                        onValueChange={(value) =>
                            onStatusChange(value === 'all' ? '' : value)
                        }
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Semua status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            {statuses.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex flex-wrap items-center gap-2 rounded-md border p-2">
                    <span className="text-sm text-muted-foreground">
                        Ekspor laporan:
                    </span>
                    <Input
                        type="date"
                        aria-label="Tanggal mulai"
                        value={exportFrom}
                        onChange={(event) => setExportFrom(event.target.value)}
                        className="w-auto"
                    />
                    <Input
                        type="date"
                        aria-label="Tanggal selesai"
                        value={exportTo}
                        onChange={(event) => setExportTo(event.target.value)}
                        className="w-auto"
                    />
                    <Button variant="outline" onClick={downloadExcel}>
                        <Download /> Ekspor Excel
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={bookings.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada pemesanan."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {bookings.from ?? 0}-{bookings.to ?? 0} dari{' '}
                        {bookings.total}
                    </p>
                    <Pagination links={bookings.links} />
                </div>
            </div>

            {formTarget && (
                <BookingFormDialog
                    key={formTarget.key}
                    open
                    onOpenChange={(open) => !open && setFormTarget(null)}
                    vehicles={vehicles}
                    drivers={drivers}
                    approvers={approvers}
                    suggestedApprovers={suggestedApprovers}
                    initial={formTarget.payload}
                />
            )}

            <BookingUsageDialog
                open={Boolean(usageTarget)}
                onOpenChange={(open) => !open && setUsageTarget(null)}
                title={
                    usageTarget?.mode === 'start'
                        ? 'Mulai Pemesanan'
                        : 'Selesaikan Pemesanan'
                }
                description={
                    usageTarget
                        ? `Pemesanan ${usageTarget.row.booking_number}`
                        : ''
                }
                fieldLabel={
                    usageTarget?.mode === 'start'
                        ? 'Odometer berangkat (km)'
                        : 'Odometer kembali (km)'
                }
                submitLabel={
                    usageTarget?.mode === 'start' ? 'Mulai' : 'Selesaikan'
                }
                onSubmit={submitUsage}
            />

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus pemesanan?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus ${deleting.booking_number}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />

            <ConfirmDialog
                open={Boolean(cancelling)}
                onOpenChange={(open) => !open && setCancelling(null)}
                title="Batalkan pemesanan?"
                description={
                    cancelling
                        ? `Apakah Anda yakin ingin membatalkan ${cancelling.booking_number}? Tindakan ini tidak dapat dibatalkan.`
                        : ''
                }
                confirmLabel="Batalkan"
                onConfirm={confirmsCancel}
            />
        </>
    );
}

BookingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Manajemen Pemesanan',
            href: index(),
        },
    ],
};
