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
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/lib/format';
import { destroy, index, store } from '@/routes/service-logs';

type ServiceLogRow = {
    id: number;
    vehicle: string;
    vehicle_id: number;
    service_type: string;
    serviced_date: string;
    odometer: number;
    cost: number | null;
    vendor: string | null;
    mechanic: string | null;
    note: string | null;
};

type PageData = {
    data: ServiceLogRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Props = {
    logs: PageData;
    filters: { vehicle_id?: string };
    summary: SummaryCardItem[];
    vehicles: { id: number; label: string }[];
    schedules: { id: number; label: string }[];
};

const fields: (
    vehicleOptions: MasterFormFieldOption[],
    scheduleOptions: MasterFormFieldOption[],
) => MasterFormField[] = (vehicleOptions, scheduleOptions) => [
    {
        name: 'vehicle_id',
        label: 'Kendaraan',
        type: 'select',
        required: true,
        options: vehicleOptions,
        placeholder: 'Pilih kendaraan',
    },
    {
        name: 'service_schedule_id',
        label: 'Jadwal Service',
        type: 'select',
        nullable: true,
        optionalText: 'Tanpa jadwal',
        options: scheduleOptions,
        placeholder: 'Pilih jadwal',
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
        name: 'serviced_date',
        label: 'Tanggal Servis',
        type: 'date',
        required: true,
        placeholder: 'Pilih tanggal',
        half: true,
    },
    {
        name: 'odometer',
        label: 'Odometer (km)',
        type: 'number',
        required: true,
        placeholder: 'Contoh: 15200',
        half: true,
    },
    {
        name: 'cost',
        label: 'Biaya',
        type: 'number',
        placeholder: 'Contoh: 750000',
        half: true,
    },
    {
        name: 'vendor',
        label: 'Bengkel / Vendor',
        type: 'text',
        placeholder: 'Contoh: Bengkel Jaya',
        half: true,
    },
    {
        name: 'mechanic',
        label: 'Mekanik',
        type: 'text',
        placeholder: 'Nama mekanik',
        half: true,
    },
    {
        name: 'note',
        label: 'Catatan',
        type: 'textarea',
        placeholder: 'Detail pekerjaan service',
    },
];

export default function ServiceLogsIndex({
    logs,
    filters,
    summary,
    vehicles,
    schedules,
}: Props) {
    const vehicleOptions = vehicles.map((vehicle) => ({
        value: String(vehicle.id),
        label: vehicle.label,
    }));
    const scheduleOptions = schedules.map((schedule) => ({
        value: String(schedule.id),
        label: schedule.label,
    }));
    const [vehicleId, setVehicleId] = useState(filters.vehicle_id ?? '');
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<ServiceLogRow | null>(null);

    const applyFilter = (value: string) => {
        setVehicleId(value);
        router.get(
            index({
                query: {
                    filter: { vehicle_id: value === '__all' ? null : value },
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

    const rupiah = (value: number | null) =>
        value === null ? '-' : new Intl.NumberFormat('id-ID').format(value);

    const columns: DataTableColumn<ServiceLogRow>[] = [
        { key: 'vehicle', header: 'Kendaraan' },
        { key: 'service_type', header: 'Jenis Service' },
        {
            key: 'serviced_date',
            header: 'Tgl Servis',
            render: (row) => formatDate(row.serviced_date),
        },
        {
            key: 'odometer',
            header: 'Odometer',
            render: (row) =>
                `${new Intl.NumberFormat('id-ID').format(row.odometer)} km`,
        },
        { key: 'cost', header: 'Biaya', render: (row) => rupiah(row.cost) },
        { key: 'vendor', header: 'Vendor', render: (row) => row.vendor ?? '-' },
        {
            key: 'mechanic',
            header: 'Mekanik',
            render: (row) => row.mechanic ?? '-',
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
            <Head title="Riwayat Service" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Riwayat Service
                    </h1>
                    <Button onClick={() => setCreating(true)}>
                        <Plus /> Catat Service
                    </Button>
                </div>

                <SummaryCards items={summary} />

                <div className="flex flex-wrap items-center gap-2">
                    <Select value={vehicleId} onValueChange={applyFilter}>
                        <SelectTrigger className="w-64">
                            <SelectValue placeholder="Semua kendaraan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">
                                Semua kendaraan
                            </SelectItem>
                            {vehicles.map((vehicle) => (
                                <SelectItem
                                    key={vehicle.id}
                                    value={String(vehicle.id)}
                                >
                                    {vehicle.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <DataTable
                    columns={columns}
                    data={logs.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada riwayat service."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {logs.from ?? 0}-{logs.to ?? 0} dari{' '}
                        {logs.total}
                    </p>
                    <Pagination links={logs.links} />
                </div>
            </div>

            <EntityFormDialog
                open={creating}
                onOpenChange={setCreating}
                title="Catat Riwayat Service"
                fields={fields(vehicleOptions, scheduleOptions)}
                initial={{
                    vehicle_id: '',
                    service_schedule_id: '',
                    service_type: '',
                    serviced_date: '',
                    odometer: '',
                    cost: '',
                    vendor: '',
                    mechanic: '',
                    note: '',
                }}
                store={store}
                submitLabel="Simpan Riwayat"
                dialogClassName="sm:max-w-xl"
            />

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus riwayat service?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus riwayat ${deleting.service_type} untuk ${deleting.vehicle}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

ServiceLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Riwayat Service',
            href: index(),
        },
    ],
};
