import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DriverFormDialog } from '@/components/drivers/driver-form-dialog';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import { destroy, index } from '@/routes/drivers';

type DriverRow = {
    id: number;
    employee_id: number;
    employee: { id: number; name: string; employee_number: string } | null;
    license_number: string;
    license_type: string | null;
    license_expiry: string | null;
    status: string;
};

type PageData = {
    data: DriverRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Employee = {
    id: number;
    name: string;
    employee_number: string;
};

type Props = {
    drivers: PageData;
    filters: { search?: string };
    employees: Employee[];
};

const statuses = [
    { value: 'available', label: 'Tersedia' },
    { value: 'assigned', label: 'Bertugas' },
    { value: 'inactive', label: 'Nonaktif' },
];

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    available: 'default',
    assigned: 'secondary',
    inactive: 'outline',
};

export default function DriversIndex({ drivers, filters, employees }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchRef = useRef(search);
    const timerRef = useRef<number | undefined>(undefined);
    const [formTarget, setFormTarget] = useState<{
        key: string;
        row?: DriverRow;
    } | null>(null);
    const [deleting, setDeleting] = useState<DriverRow | null>(null);

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

    const openEdit = (row: DriverRow) => {
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

    const columns: DataTableColumn<DriverRow>[] = [
        {
            key: 'employee',
            header: 'Nama Driver',
            render: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">
                        {row.employee?.name ?? '-'}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        {row.employee?.employee_number}
                    </span>
                </div>
            ),
        },
        { key: 'license_number', header: 'No. SIM' },
        { key: 'license_type', header: 'Jenis SIM' },
        {
            key: 'license_expiry',
            header: 'Berlaku Hingga',
            render: (row) => formatDate(row.license_expiry),
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
            <Head title="Master Driver" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Master Driver
                    </h1>
                    <Button onClick={() => setFormTarget({ key: 'create' })}>
                        <Plus /> Tambah Driver
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="search"
                        placeholder="Cari nama driver, no. SIM..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="max-w-xs"
                    />
                </div>

                <DataTable
                    columns={columns}
                    data={drivers.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada driver."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {drivers.from ?? 0}-{drivers.to ?? 0} dari{' '}
                        {drivers.total}
                    </p>
                    <Pagination links={drivers.links} />
                </div>
            </div>

            {formTarget && (
                <DriverFormDialog
                    key={formTarget.key}
                    open
                    onOpenChange={(open) => !open && setFormTarget(null)}
                    initial={formTarget.row}
                    employees={employees}
                />
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus driver?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus driver ${deleting.employee?.name ?? deleting.license_number}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

DriversIndex.layout = {
    breadcrumbs: [
        {
            title: 'Master Driver',
            href: index(),
        },
    ],
};
