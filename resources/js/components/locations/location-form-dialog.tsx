import { EntityFormDialog } from '@/components/entity-form-dialog';
import type { MasterFormField } from '@/components/entity-form-dialog';
import { store, update } from '@/routes/locations';

type LocationRow = {
    id: number;
    code: string;
    name: string;
    type: string;
    region: string | null;
    address: string | null;
    is_active: boolean;
};

type LocationFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: LocationRow;
};

const locationTypes = [
    { value: 'head_office', label: 'Kantor Pusat' },
    { value: 'branch_office', label: 'Kantor Cabang' },
    { value: 'mine', label: 'Tambang' },
];

const fields: MasterFormField[] = [
    {
        name: 'code',
        label: 'Kode Lokasi',
        type: 'text',
        required: true,
        placeholder: 'Contoh: HQ, MINE01',
    },
    {
        name: 'name',
        label: 'Nama Lokasi',
        type: 'text',
        required: true,
        placeholder: 'Contoh: Kantor Pusat Jakarta',
    },
    {
        name: 'type',
        label: 'Tipe Lokasi',
        type: 'select',
        required: true,
        options: locationTypes,
        placeholder: 'Pilih tipe lokasi',
    },
    {
        name: 'region',
        label: 'Wilayah',
        type: 'text',
        placeholder: 'Contoh: Sulawesi Selatan',
    },
    {
        name: 'address',
        label: 'Alamat',
        type: 'textarea',
        placeholder: 'Alamat lengkap lokasi',
    },
    { name: 'is_active', label: 'Aktif', type: 'checkbox' },
];

export function LocationFormDialog({
    open,
    onOpenChange,
    initial,
}: LocationFormDialogProps) {
    const isEdit = Boolean(initial);

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Lokasi' : 'Tambah Lokasi'}
            fields={fields}
            initial={{
                code: initial?.code ?? '',
                name: initial?.name ?? '',
                type: initial?.type ?? '',
                region: initial?.region ?? '',
                address: initial?.address ?? '',
                is_active: initial?.is_active ?? true,
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan Lokasi'}
        />
    );
}
