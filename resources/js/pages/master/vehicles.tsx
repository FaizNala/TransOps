import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { VehicleFormDialog } from '@/components/vehicles/vehicle-form-dialog';
import { destroy, index } from '@/routes/vehicles';

type VehicleRow = {
    id: number;
    plate_number: string;
    category: string;
    capacity: number | null;
    brand: string | null;
    model: string | null;
    year: number | null;
    ownership_type: string;
    rental_partner_id: number | null;
    rental_partner: { id: number; name: string } | null;
    rental_start_date: string | null;
    rental_end_date: string | null;
    fuel_type: string;
    tank_capacity: number | null;
    current_odometer: number | null;
    status: string;
    is_active: boolean;
};

type PageData = {
    data: VehicleRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type RentalPartner = {
    id: number;
    name: string;
};

type Props = {
    vehicles: PageData;
    filters: { search?: string };
    rental_partners: RentalPartner[];
};

const categories = [
    { value: 'passenger', label: 'Penumpang' },
    { value: 'goods', label: 'Barang' },
];

const statuses = [
    { value: 'available', label: 'Tersedia' },
    { value: 'in_use', label: 'Sedang Dipakai' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'retired', label: 'Retired' },
];

const ownershipTypes = [
    { value: 'company', label: 'Milik Perusahaan' },
    { value: 'rental', label: 'Sewa' },
];

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    available: 'default',
    in_use: 'secondary',
    maintenance: 'outline',
    retired: 'destructive',
};

export default function VehiclesIndex({
    vehicles,
    filters,
    rental_partners,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchRef = useRef(search);
    const timerRef = useRef<number | undefined>(undefined);
    const [formTarget, setFormTarget] = useState<{
        key: string;
        row?: VehicleRow;
    } | null>(null);
    const [deleting, setDeleting] = useState<VehicleRow | null>(null);

    useEffect(() => {
        searchRef.current = search;
    }, [search]);

    const applyFilters = (searchValue: string) => {
        router.get(
            index({
                query: {
                    filter: { search: searchValue || null },
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
        timerRef.current = window.setTimeout(() => applyFilters(value), 300);
    };

    const openEdit = (row: VehicleRow) => {
        setFormTarget({ key: `edit-${row.id}`, row });
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

    const columns: DataTableColumn<VehicleRow>[] = [
        { key: 'plate_number', header: 'Plat Nomor' },
        {
            key: 'vehicle',
            header: 'Kendaraan',
            render: (row) => (
                <span className="whitespace-nowrap">
                    {row.brand} {row.model} {row.year ? `(${row.year})` : ''}
                </span>
            ),
        },
        {
            key: 'category',
            header: 'Kategori',
            render: (row) => (
                <Badge variant="outline">
                    {categories.find((c) => c.value === row.category)?.label ??
                        row.category}
                </Badge>
            ),
        },
        {
            key: 'ownership',
            header: 'Kepemilikan',
            render: (row) => (
                <span className="whitespace-nowrap">
                    {ownershipTypes.find((o) => o.value === row.ownership_type)
                        ?.label ?? row.ownership_type}
                    {row.rental_partner ? ` · ${row.rental_partner.name}` : ''}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <Badge variant={statusVariant[row.status]}>
                    {statuses.find((s) => s.value === row.status)?.label ??
                        row.status}
                </Badge>
            ),
        },
        {
            key: 'is_active',
            header: 'Aktif',
            render: (row) => (
                <Badge variant={row.is_active ? 'default' : 'secondary'}>
                    {row.is_active ? 'Ya' : 'Tidak'}
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
                        aria-label="Edit"
                        onClick={() => openEdit(row)}
                    >
                        <Pencil />
                    </Button>
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
            <Head title="Master Kendaraan" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Master Kendaraan
                    </h1>
                    <Button onClick={() => setFormTarget({ key: 'create' })}>
                        <Plus /> Tambah Kendaraan
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="search"
                        placeholder="Cari plat nomor, merek, model..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="max-w-xs"
                    />
                </div>

                <DataTable
                    columns={columns}
                    data={vehicles.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada kendaraan."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {vehicles.from ?? 0}-{vehicles.to ?? 0} dari{' '}
                        {vehicles.total}
                    </p>
                    <Pagination links={vehicles.links} />
                </div>
            </div>

            {formTarget && (
                <VehicleFormDialog
                    key={formTarget.key}
                    open
                    onOpenChange={(open) => !open && setFormTarget(null)}
                    initial={formTarget.row}
                    rentalPartners={rental_partners}
                />
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus kendaraan?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus kendaraan ${deleting.plate_number}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

VehiclesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Master Kendaraan',
            href: index(),
        },
    ],
};
