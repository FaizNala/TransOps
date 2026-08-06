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
import { formatDateTime } from '@/lib/format';
import { destroy, index, store } from '@/routes/fuel-logs';

type FuelLogRow = {
    id: number;
    vehicle: string;
    vehicle_id: number;
    refueled_at: string;
    odometer: number;
    liters: number;
    price_per_liter: number | null;
    total_cost: number | null;
    is_full_tank: boolean;
    station: string | null;
    consumption: number | null;
};

type PageData = {
    data: FuelLogRow[];
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
        name: 'refueled_at',
        label: 'Waktu Pengisian',
        type: 'datetime',
        required: true,
    },
    {
        name: 'odometer',
        label: 'Odometer (km)',
        type: 'number',
        required: true,
        placeholder: 'Contoh: 12500',
        half: true,
    },
    {
        name: 'liters',
        label: 'Volume (liter)',
        type: 'number',
        required: true,
        placeholder: 'Contoh: 45.5',
        half: true,
    },
    {
        name: 'price_per_liter',
        label: 'Harga per Liter',
        type: 'number',
        required: true,
        placeholder: 'Contoh: 10000',
        half: true,
    },
    {
        name: 'station',
        label: 'SPBU / Tempat',
        type: 'text',
        placeholder: 'Contoh: SPBU KM 12',
        half: true,
    },
    { name: 'is_full_tank', label: 'Full tank', type: 'checkbox' },
    {
        name: 'note',
        label: 'Catatan',
        type: 'textarea',
        placeholder: 'Catatan tambahan',
    },
];

export default function FuelLogsIndex({
    logs,
    filters,
    summary,
    vehicles,
}: Props) {
    const vehicleOptions = vehicles.map((vehicle) => ({
        value: String(vehicle.id),
        label: vehicle.label,
    }));
    const [vehicleId, setVehicleId] = useState(filters.vehicle_id ?? '');
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<FuelLogRow | null>(null);

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

    const columns: DataTableColumn<FuelLogRow>[] = [
        { key: 'vehicle', header: 'Kendaraan' },
        {
            key: 'refueled_at',
            header: 'Waktu Pengisian',
            render: (row) => (
                <span className="whitespace-nowrap">
                    {formatDateTime(row.refueled_at)}
                </span>
            ),
        },
        {
            key: 'odometer',
            header: 'Odometer',
            render: (row) =>
                `${new Intl.NumberFormat('id-ID').format(row.odometer)} km`,
        },
        {
            key: 'liters',
            header: 'Liter',
            render: (row) => row.liters.toLocaleString('id-ID'),
        },
        {
            key: 'total_cost',
            header: 'Biaya',
            render: (row) => rupiah(row.total_cost),
        },
        {
            key: 'consumption',
            header: 'Konsumsi',
            render: (row) =>
                row.consumption === null
                    ? '-'
                    : `${row.consumption.toLocaleString('id-ID')} km/l`,
        },
        {
            key: 'is_full_tank',
            header: 'Full Tank',
            render: (row) => (
                <Badge variant={row.is_full_tank ? 'default' : 'secondary'}>
                    {row.is_full_tank ? 'Ya' : 'Tidak'}
                </Badge>
            ),
        },
        { key: 'station', header: 'SPBU / Tempat' },
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
            <Head title="Pemakaian BBM" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Pemakaian BBM
                    </h1>
                    <Button onClick={() => setCreating(true)}>
                        <Plus /> Catat Pengisian
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
                    emptyMessage="Tidak ada catatan BBM."
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
                title="Catat Pengisian BBM"
                fields={fields(vehicleOptions)}
                initial={{
                    vehicle_id: '',
                    refueled_at: '',
                    odometer: '',
                    liters: '',
                    price_per_liter: '',
                    station: '',
                    is_full_tank: false,
                    note: '',
                }}
                store={store}
                submitLabel="Simpan Catatan"
                dialogClassName="sm:max-w-xl"
            />

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus catatan BBM?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus catatan ${deleting.station ?? 'pengisian'} untuk ${deleting.vehicle}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

FuelLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pemakaian BBM',
            href: index(),
        },
    ],
};
