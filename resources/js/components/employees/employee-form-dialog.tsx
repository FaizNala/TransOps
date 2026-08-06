import { EntityFormDialog } from '@/components/entity-form-dialog';
import type { MasterFormField } from '@/components/entity-form-dialog';
import { store, update } from '@/routes/employees';
type EmployeeRow = {
    id: number;
    employee_number: string;
    name: string;
    position: string | null;
    department: string | null;
    email: string | null;
    phone: string | null;
    location_id: number | null;
    manager_id: number | null;
    is_active: boolean;
};

type Location = {
    id: number;
    name: string;
};

type Manager = {
    id: number;
    name: string;
};

type EmployeeFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: EmployeeRow;
    locations: Location[];
    managers: Manager[];
};

export function EmployeeFormDialog({
    open,
    onOpenChange,
    initial,
    locations,
    managers,
}: EmployeeFormDialogProps) {
    const isEdit = Boolean(initial);

    const availableManagers =
        isEdit && initial
            ? managers.filter((manager) => manager.id !== initial.id)
            : managers;

    const fields: MasterFormField[] = [
        {
            name: 'employee_number',
            label: 'NIP',
            type: 'text',
            required: true,
            placeholder: 'Contoh: EMP001',
        },
        {
            name: 'name',
            label: 'Nama Lengkap',
            type: 'text',
            required: true,
            placeholder: 'Contoh: Ahmad Santoso',
        },
        {
            name: 'position',
            label: 'Jabatan',
            type: 'text',
            half: true,
            placeholder: 'Contoh: Manager',
        },
        {
            name: 'department',
            label: 'Departemen',
            type: 'text',
            half: true,
            placeholder: 'Contoh: Operasional',
        },
        {
            name: 'email',
            label: 'Email',
            type: 'email',
            required: true,
            half: true,
            placeholder: 'Contoh: ahmad@example.com',
        },
        {
            name: 'phone',
            label: 'Telepon',
            type: 'text',
            half: true,
            placeholder: 'Contoh: 081234567890',
        },
        {
            name: 'location_id',
            label: 'Lokasi',
            type: 'select',
            nullable: true,
            options: locations.map((loc) => ({
                value: String(loc.id),
                label: loc.name,
            })),
            placeholder: 'Pilih lokasi',
        },
        {
            name: 'manager_id',
            label: 'Atasan',
            type: 'select',
            nullable: true,
            options: availableManagers.map((manager) => ({
                value: String(manager.id),
                label: manager.name,
            })),
            placeholder: 'Pilih atasan',
        },
        { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        {
            name: 'user_role',
            label: 'Peran Akun Login',
            type: 'select',
            required: true,
            options: [
                { value: 'admin', label: 'Admin' },
                { value: 'approver', label: 'Approver' },
            ],
            placeholder: 'Pilih peran',
            shownWhen: () => !isEdit,
        },
        {
            name: 'user_password',
            label: 'Password Akun Login',
            type: 'password',
            required: true,
            placeholder: 'Minimal 8 karakter',
            shownWhen: () => !isEdit,
        },
    ];

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Pegawai' : 'Tambah Pegawai'}
            fields={fields}
            initial={{
                employee_number: initial?.employee_number ?? '',
                name: initial?.name ?? '',
                position: initial?.position ?? '',
                department: initial?.department ?? '',
                email: initial?.email ?? '',
                phone: initial?.phone ?? '',
                location_id: initial?.location_id?.toString() ?? '',
                manager_id: initial?.manager_id?.toString() ?? '',
                is_active: initial?.is_active ?? true,
                user_role: '',
                user_password: '',
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan Pegawai'}
        />
    );
}
