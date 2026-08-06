import { EntityFormDialog } from '@/components/entity-form-dialog';
import type { MasterFormField } from '@/components/entity-form-dialog';
import { store, update } from '@/routes/drivers';

type DriverRow = {
    id: number;
    employee_id: number;
    license_number: string;
    license_type: string | null;
    license_expiry: string | null;
    status: string;
};

type Employee = {
    id: number;
    name: string;
    employee_number: string;
};

type DriverFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: DriverRow;
    employees: Employee[];
};

const statuses = [
    { value: 'available', label: 'Tersedia' },
    { value: 'assigned', label: 'Bertugas' },
    { value: 'inactive', label: 'Nonaktif' },
];

export function DriverFormDialog({
    open,
    onOpenChange,
    initial,
    employees,
}: DriverFormDialogProps) {
    const isEdit = Boolean(initial);

    const fields: MasterFormField[] = [
        {
            name: 'employee_id',
            label: 'Pegawai',
            type: 'select',
            required: true,
            options: employees.map((emp) => ({
                value: String(emp.id),
                label: `${emp.name} · ${emp.employee_number}`,
            })),
            placeholder: 'Pilih pegawai',
        },
        {
            name: 'license_number',
            label: 'Nomor SIM',
            type: 'text',
            required: true,
            placeholder: 'Contoh: 1234567890123456',
        },
        {
            name: 'license_type',
            label: 'Jenis SIM',
            type: 'text',
            placeholder: 'Contoh: A, B1, B2',
        },
        { name: 'license_expiry', label: 'Berlaku Hingga', type: 'date' },
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            required: true,
            options: statuses,
            placeholder: 'Pilih status',
        },
    ];

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Driver' : 'Tambah Driver'}
            fields={fields}
            initial={{
                employee_id: initial?.employee_id?.toString() ?? '',
                license_number: initial?.license_number ?? '',
                license_type: initial?.license_type ?? '',
                license_expiry: initial?.license_expiry ?? '',
                status: initial?.status ?? '',
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan Driver'}
        />
    );
}
