import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Settings, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { EmployeeFormDialog } from '@/components/employees/employee-form-dialog';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { UserFormDialog } from '@/components/users/user-form-dialog';
import { destroy, index } from '@/routes/employees';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    employee_id: number | null;
};

type EmployeeRow = {
    id: number;
    employee_number: string;
    name: string;
    position: string | null;
    department: string | null;
    email: string | null;
    phone: string | null;
    location_id: number | null;
    location: { id: number; name: string } | null;
    manager_id: number | null;
    manager: { id: number; name: string } | null;
    is_active: boolean;
    users: UserRow[];
};

type PageData = {
    data: EmployeeRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Location = {
    id: number;
    name: string;
};

type Manager = {
    id: number;
    name: string;
};

type Props = {
    employees: PageData;
    filters: { search?: string };
    locations: Location[];
    managers: Manager[];
};

export default function EmployeesIndex({
    employees,
    filters,
    locations,
    managers,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchRef = useRef(search);
    const timerRef = useRef<number | undefined>(undefined);
    const [formTarget, setFormTarget] = useState<{
        key: string;
        row?: EmployeeRow;
    } | null>(null);
    const [deleting, setDeleting] = useState<EmployeeRow | null>(null);
    const [accountTarget, setAccountTarget] = useState<{ row: UserRow } | null>(
        null,
    );

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

    const openEdit = (row: EmployeeRow) => {
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

    const columns: DataTableColumn<EmployeeRow>[] = [
        {
            key: 'employee',
            header: 'Pegawai',
            render: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.name}</span>
                    <span className="text-sm text-muted-foreground">
                        {row.employee_number}
                    </span>
                </div>
            ),
        },
        { key: 'position', header: 'Jabatan' },
        { key: 'department', header: 'Departemen' },
        {
            key: 'location',
            header: 'Lokasi',
            render: (row) => row.location?.name ?? '-',
        },
        {
            key: 'manager',
            header: 'Atasan',
            render: (row) => row.manager?.name ?? '-',
        },
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
            key: 'role',
            header: 'Peran',
            render: (row) => {
                const role = row.users[0]?.role;

                return role ? (
                    <Badge variant={role === 'admin' ? 'default' : 'secondary'}>
                        {role === 'admin' ? 'Admin' : 'Approver'}
                    </Badge>
                ) : (
                    <Badge variant="outline">Tanpa Akun</Badge>
                );
            },
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
                        aria-label="Kelola akun"
                        onClick={() =>
                            setAccountTarget({
                                row: row.users[0] ?? {
                                    id: 0,
                                    name: row.name,
                                    email: row.email ?? '',
                                    role: '',
                                    employee_id: row.id,
                                },
                            })
                        }
                    >
                        <Settings />
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
            <Head title="Master Pegawai" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Master Pegawai
                    </h1>
                    <Button onClick={() => setFormTarget({ key: 'create' })}>
                        <Plus /> Tambah Pegawai
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="search"
                        placeholder="Cari nama, NIP, departemen..."
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        className="max-w-xs"
                    />
                </div>

                <DataTable
                    columns={columns}
                    data={employees.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada pegawai."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {employees.from ?? 0}-{employees.to ?? 0}{' '}
                        dari {employees.total}
                    </p>
                    <Pagination links={employees.links} />
                </div>
            </div>

            {formTarget && (
                <EmployeeFormDialog
                    key={formTarget.key}
                    open
                    onOpenChange={(open) => !open && setFormTarget(null)}
                    initial={formTarget.row}
                    locations={locations}
                    managers={managers}
                />
            )}

            {accountTarget && (
                <UserFormDialog
                    key={`account-${accountTarget.row.id}`}
                    open
                    onOpenChange={(open) => !open && setAccountTarget(null)}
                    initial={
                        accountTarget.row.id ? accountTarget.row : undefined
                    }
                    createDefaults={
                        accountTarget.row.id
                            ? undefined
                            : {
                                  name: accountTarget.row.name,
                                  email: accountTarget.row.email,
                                  employee_id: accountTarget.row.employee_id,
                              }
                    }
                    employees={managers}
                />
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus pegawai?"
                description={
                    deleting
                        ? `Apakah Anda yakin ingin menghapus pegawai ${deleting.name}?`
                        : ''
                }
                confirmLabel="Hapus"
                onConfirm={confirmsDelete}
            />
        </>
    );
}

EmployeesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Master Pegawai',
            href: index(),
        },
    ],
};
