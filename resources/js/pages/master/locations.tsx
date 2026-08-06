import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { LocationFormDialog } from '@/components/locations/location-form-dialog';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, index } from '@/routes/locations';

type LocationRow = {
    id: number;
    code: string;
    name: string;
    type: string;
    region: string | null;
    address: string | null;
    is_active: boolean;
};

type PageData = {
    data: LocationRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Props = {
    locations: PageData;
    filters: { search?: string };
};

const locationTypes = [
    { value: 'head_office', label: 'Kantor Pusat' },
    { value: 'branch_office', label: 'Kantor Cabang' },
    { value: 'mine', label: 'Tambang' },
];

export default function LocationsIndex({ locations, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchRef = useRef(search);
    const timerRef = useRef<number | undefined>(undefined);
    const [formTarget, setFormTarget] = useState<{
        key: string;
        row?: LocationRow;
    } | null>(null);
    const [deleting, setDeleting] = useState<LocationRow | null>(null);

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

    const openEdit = (row: LocationRow) => {
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

    const columns: DataTableColumn<LocationRow>[] = [
        { key: 'code', header: 'Kode' },
        { key: 'name', header: 'Nama Lokasi' },
        {
            key: 'type',
            header: 'Tipe',
            render: (row) => (
                <Badge variant="outline">
                    {locationTypes.find((t) => t.value === row.type)?.label ??
                        row.type}
                </Badge>
            ),
        },
        { key: 'region', header: 'Wilayah' },
        {
            key: 'is_active',
            header: 'Status',
            render: (row) => (
                <Badge variant={row.is_active ? 'default' : 'secondary'}>
                    {row.is_active ? 'Aktif' : 'Nonaktif'}
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
            <Head title="Master Lokasi" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Master Lokasi
                    </h1>
                    <Button onClick={() => setFormTarget({ key: 'create' })}>
                        <Plus /> Tambah Lokasi
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="search"
                        placeholder="Cari kode, nama, wilayah..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="max-w-xs"
                    />
                </div>

                <DataTable
                    columns={columns}
                    data={locations.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada lokasi."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {locations.from ?? 0}-{locations.to ?? 0}{' '}
                        dari {locations.total}
                    </p>
                    <Pagination links={locations.links} />
                </div>
            </div>

            {formTarget && (
                <LocationFormDialog
                    key={formTarget.key}
                    open
                    onOpenChange={(open) => !open && setFormTarget(null)}
                    initial={formTarget.row}
                />
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus lokasi?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus lokasi ${deleting.name}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

LocationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Master Lokasi',
            href: index(),
        },
    ],
};
