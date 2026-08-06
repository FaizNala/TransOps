import { EntityFormDialog } from '@/components/entity-form-dialog';
import type { MasterFormField } from '@/components/entity-form-dialog';
import { store, update } from '@/routes/rental-partners';

type RentalPartnerRow = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    is_active: boolean;
};

type RentalPartnerFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: RentalPartnerRow;
};

const fields: MasterFormField[] = [
    {
        name: 'name',
        label: 'Nama Partner',
        type: 'text',
        required: true,
        placeholder: 'Contoh: PT Rental Jaya',
    },
    {
        name: 'contact_person',
        label: 'Kontak Person',
        type: 'text',
        placeholder: 'Contoh: Budi Santoso',
    },
    {
        name: 'phone',
        label: 'Telepon',
        type: 'text',
        placeholder: 'Contoh: 021-5551234',
    },
    {
        name: 'email',
        label: 'Email',
        type: 'email',
        placeholder: 'Contoh: info@rentaljaya.com',
    },
    {
        name: 'address',
        label: 'Alamat',
        type: 'textarea',
        placeholder: 'Alamat lengkap partner',
    },
    { name: 'is_active', label: 'Aktif', type: 'checkbox' },
];

export function RentalPartnerFormDialog({
    open,
    onOpenChange,
    initial,
}: RentalPartnerFormDialogProps) {
    const isEdit = Boolean(initial);

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Partner Sewa' : 'Tambah Partner Sewa'}
            fields={fields}
            initial={{
                name: initial?.name ?? '',
                contact_person: initial?.contact_person ?? '',
                phone: initial?.phone ?? '',
                email: initial?.email ?? '',
                address: initial?.address ?? '',
                is_active: initial?.is_active ?? true,
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan Partner'}
        />
    );
}
