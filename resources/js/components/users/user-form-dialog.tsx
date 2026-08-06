import { EntityFormDialog } from '@/components/entity-form-dialog';
import type { MasterFormField } from '@/components/entity-form-dialog';
import { store, update } from '@/routes/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    employee_id: number | null;
};

type Employee = {
    id: number;
    name: string;
};

type UserFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: UserRow;
    createDefaults?: {
        name: string;
        email: string;
        employee_id: number | null;
    };
    employees: Employee[];
};

const roles = [
    { value: 'admin', label: 'Admin' },
    { value: 'approver', label: 'Approver' },
];

export function UserFormDialog({
    open,
    onOpenChange,
    initial,
    createDefaults,
    employees,
}: UserFormDialogProps) {
    const isEdit = Boolean(initial);

    const fields: MasterFormField[] = [
        {
            name: 'name',
            label: 'Nama Lengkap',
            type: 'text',
            required: true,
            placeholder: 'Contoh: Ahmad Santoso',
        },
        {
            name: 'email',
            label: 'Email',
            type: 'email',
            required: true,
            placeholder: 'Contoh: ahmad@example.com',
        },
        {
            name: 'role',
            label: 'Peran',
            type: 'select',
            required: true,
            options: roles,
            placeholder: 'Pilih peran',
        },
        {
            name: 'employee_id',
            label: 'Pegawai Terkait (Opsional)',
            type: 'select',
            nullable: true,
            options: employees.map((emp) => ({
                value: String(emp.id),
                label: emp.name,
            })),
            placeholder: 'Pilih pegawai',
        },
        {
            name: 'password',
            label: isEdit
                ? 'Password (kosongkan jika tidak ingin mengubah)'
                : 'Password',
            type: 'password',
            required: !isEdit,
            placeholder: 'Minimal 8 karakter',
        },
    ];

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit User' : 'Tambah User'}
            fields={fields}
            initial={{
                name: initial?.name ?? createDefaults?.name ?? '',
                email: initial?.email ?? createDefaults?.email ?? '',
                role: initial?.role ?? '',
                employee_id:
                    initial?.employee_id?.toString() ??
                    createDefaults?.employee_id?.toString() ??
                    '',
                password: '',
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan User'}
        />
    );
}
