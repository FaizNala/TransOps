import { Head, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { EntityFormDialog } from '@/components/entity-form-dialog';
import type {
    MasterFormField,
    MasterFormFieldOption,
} from '@/components/entity-form-dialog';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { SummaryCards } from '@/components/summary-cards';
import type { SummaryCardItem } from '@/components/summary-cards';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/lib/format';
import { destroy, index, store } from '@/routes/service-schedules';

type ServiceScheduleRow = {
    id: number;
    vehicle: string;
    vehicle_id: number;
    service_type: string;
    scheduled_date: string | null;
    scheduled_odometer: number | null;
    recurrence: string | null;
    status: 'scheduled' | 'due' | 'overdue' | 'cancelled';
    status_label: string;
    note: string | null;
};

type PageData = {
    data: ServiceScheduleRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Props = {
    schedules: PageData;
    filters: { status?: string };
    summary: SummaryCardItem[];
    vehicles: { id: number; label: string }[];
};

const statusOptions = [
    { value: 'scheduled', label: 'Terjadwal' },
    { value: 'due', label: 'Segera' },
    { value: 'overdue', label: 'Terlambat' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

const statusVariant: Record<
    ServiceScheduleRow['status'],
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    scheduled: 'default',
    due: 'outline',
    overdue: 'destructive',
    cancelled: 'secondary',
};

const fields: (vehicleOptions: MasterFormFieldOption[]) => MasterFormField[] = (
    vehicleOptions,
) => [
    {
        name: 'vehicle_id',
        label: 'Kendaraan',
        type: 'select',
        required: true,
        options: vehicleOptions,
        placeholder: 'Pilih kendaraan',
    },
    {
        name: 'service_type',
        label: 'Jenis Service',
        type: 'text',
        required: true,
        placeholder: 'Contoh: Ganti oli',
        half: true,
    },
    {
        name: 'scheduled_date',
        label: 'Tanggal Jadwal',
        type: 'date',
        placeholder: 'Pilih tanggal',
        half: true,
    },
    {
        name: 'scheduled_odometer',
        label: 'Jadwal Odometer',
        type: 'number',
        placeholder: 'Contoh: 15000',
        half: true,
    },
    {
        name: 'recurrence_km',
        label: 'Ulang tiap (km)',
        type: 'number',
        placeholder: 'Contoh: 10000',
        half: true,
    },
    {
        name: 'recurrence_months',
        label: 'Ulang tiap (bulan)',
        type: 'number',
        placeholder: 'Contoh: 3',
        half: true,
    },
    {
        name: 'note',
        label: 'Catatan',
        type: 'textarea',
        placeholder: 'Catatan tambahan',
    },
];

export default function ServiceSchedulesIndex({
    schedules,
    filters,
    summary,
    vehicles,
}: Props) {
    const vehicleOptions = vehicles.map((vehicle) => ({
        value: String(vehicle.id),
        label: vehicle.label,
    }));
    const [status, setStatus] = useState(filters.status ?? '');
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<ServiceScheduleRow | null>(null);

    const applyFilter = (value: string) => {
        setStatus(value);
        router.get(
            index({
                query: {
                    filter: { status: value === '__all' ? null : value },
                    page: 1,
                },
            }).url,
            {},
            { preserveScroll: true },
        );
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

    const columns: DataTableColumn<ServiceScheduleRow>[] = [
        { key: 'vehicle', header: 'Kendaraan' },
        { key: 'service_type', header: 'Jenis Service' },
        {
            key: 'scheduled_date',
            header: 'Tgl Jadwal',
            render: (row) => formatDate(row.scheduled_date),
        },
        {
            key: 'scheduled_odometer',
            header: 'Odometer',
            render: (row) =>
                row.scheduled_odometer === null
                    ? '-'
                    : `${new Intl.NumberFormat('id-ID').format(row.scheduled_odometer)} km`,
        },
        {
            key: 'recurrence',
            header: 'Pengulangan',
            render: (row) => row.recurrence ?? '-',
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <Badge variant={statusVariant[row.status]}>
                    {statusOptions.find((s) => s.value === row.status)?.label ??
                        row.status_label}
                </Badge>
            ),
        },
        {
            key: 'actions',
            header: 'Aksi',
            render: (row) => (
                <div className="flex gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Hapus"
                        onClick={() => setDeleting(row)}
                    >
                        <Trash2 />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Jadwal Service" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Jadwal Service
                    </h1>
                    <Button onClick={() => setCreating(true)}>
                        <Plus /> Tambah Jadwal
                    </Button>
                </div>

                <SummaryCards items={summary} />

                <div className="flex flex-wrap items-center gap-2">
                    <Select value={status} onValueChange={applyFilter}>
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="Semua status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">Semua status</SelectItem>
                            {statusOptions.map((option) => (
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

                <DataTable
                    columns={columns}
                    data={schedules.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada jadwal service."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {schedules.from ?? 0}-{schedules.to ?? 0}{' '}
                        dari {schedules.total}
                    </p>
                    <Pagination links={schedules.links} />
                </div>
            </div>

            <EntityFormDialog
                open={creating}
                onOpenChange={setCreating}
                title="Tambah Jadwal Service"
                fields={fields(vehicleOptions)}
                initial={{
                    vehicle_id: '',
                    service_type: '',
                    scheduled_date: '',
                    scheduled_odometer: '',
                    recurrence_km: '',
                    recurrence_months: '',
                    note: '',
                }}
                store={store}
                submitLabel="Simpan Jadwal"
                dialogClassName="sm:max-w-xl"
            />

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus jadwal service?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus jadwal ${deleting.service_type} untuk ${deleting.vehicle}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

ServiceSchedulesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Jadwal Service',
            href: index(),
        },
    ],
};
