import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { RentalPartnerFormDialog } from '@/components/rental-partners/rental-partner-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, index } from '@/routes/rental-partners';

type RentalPartnerRow = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
};

type PageData = {
    data: RentalPartnerRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Props = {
    rental_partners: PageData;
    filters: { search?: string };
};

export default function RentalPartnersIndex({
    rental_partners,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchRef = useRef(search);
    const timerRef = useRef<number | undefined>(undefined);
    const [formTarget, setFormTarget] = useState<{
        key: string;
        row?: RentalPartnerRow;
    } | null>(null);
    const [deleting, setDeleting] = useState<RentalPartnerRow | null>(null);

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

    const openEdit = (row: RentalPartnerRow) => {
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

    const columns: DataTableColumn<RentalPartnerRow>[] = [
        { key: 'name', header: 'Nama Partner' },
        { key: 'contact_person', header: 'Kontak Person' },
        { key: 'phone', header: 'Telepon' },
        { key: 'email', header: 'Email' },
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
            <Head title="Master Partner Sewa" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Master Partner Sewa
                    </h1>
                    <Button onClick={() => setFormTarget({ key: 'create' })}>
                        <Plus /> Tambah Partner
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="search"
                        placeholder="Cari nama, kontak person, telepon..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="max-w-xs"
                    />
                </div>

                <DataTable
                    columns={columns}
                    data={rental_partners.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada partner sewa."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {rental_partners.from ?? 0}-
                        {rental_partners.to ?? 0} dari {rental_partners.total}
                    </p>
                    <Pagination links={rental_partners.links} />
                </div>
            </div>

            {formTarget && (
                <RentalPartnerFormDialog
                    key={formTarget.key}
                    open
                    onOpenChange={(open) => !open && setFormTarget(null)}
                    initial={formTarget.row}
                />
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus partner sewa?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus partner ${deleting.name}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

RentalPartnersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Master Partner Sewa',
            href: index(),
        },
    ],
};
