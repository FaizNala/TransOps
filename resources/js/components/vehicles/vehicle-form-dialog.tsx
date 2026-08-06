import { EntityFormDialog } from '@/components/entity-form-dialog';
import type {
    MasterFormField,
    MasterFormValues,
} from '@/components/entity-form-dialog';
import { store, update } from '@/routes/vehicles';

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
    rental_start_date: string | null;
    rental_end_date: string | null;
    fuel_type: string;
    tank_capacity: number | null;
    current_odometer: number | null;
    status: string;
    is_active: boolean;
};

type RentalPartner = {
    id: number;
    name: string;
};

type VehicleFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: VehicleRow;
    rentalPartners: RentalPartner[];
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

const fuelTypes = [
    { value: 'diesel', label: 'Diesel' },
    { value: 'petrol', label: 'Bensin' },
    { value: 'electric', label: 'Listrik' },
];

const ownershipTypes = [
    { value: 'company', label: 'Milik Perusahaan' },
    { value: 'rental', label: 'Sewa' },
];

export function VehicleFormDialog({
    open,
    onOpenChange,
    initial,
    rentalPartners,
}: VehicleFormDialogProps) {
    const isEdit = Boolean(initial);
    const isRental = (values: MasterFormValues) =>
        values.ownership_type === 'rental';

    const fields: MasterFormField[] = [
        {
            name: 'plate_number',
            label: 'Plat Nomor',
            type: 'text',
            required: true,
            half: true,
            placeholder: 'Contoh: B 1234 XYZ',
        },
        {
            name: 'category',
            label: 'Kategori',
            type: 'select',
            required: true,
            half: true,
            options: categories,
            placeholder: 'Pilih kategori',
        },
        {
            name: 'brand',
            label: 'Merek',
            type: 'text',
            half: true,
            placeholder: 'Contoh: Toyota',
        },
        {
            name: 'model',
            label: 'Model',
            type: 'text',
            half: true,
            placeholder: 'Contoh: Avanza',
        },
        {
            name: 'year',
            label: 'Tahun',
            type: 'number',
            half: true,
            placeholder: 'Contoh: 2020',
        },
        {
            name: 'capacity',
            label: 'Kapasitas',
            type: 'number',
            half: true,
            placeholder: 'Contoh: 7',
        },
        {
            name: 'ownership_type',
            label: 'Kepemilikan',
            type: 'select',
            required: true,
            options: ownershipTypes,
            placeholder: 'Pilih kepemilikan',
        },
        {
            name: 'rental_partner_id',
            label: 'Partner Sewa',
            type: 'select',
            required: true,
            options: rentalPartners.map((partner) => ({
                value: String(partner.id),
                label: partner.name,
            })),
            placeholder: 'Pilih partner sewa',
            shownWhen: isRental,
        },
        {
            name: 'rental_start_date',
            label: 'Tanggal Mulai Sewa',
            type: 'date',
            half: true,
            shownWhen: isRental,
        },
        {
            name: 'rental_end_date',
            label: 'Tanggal Selesai Sewa',
            type: 'date',
            half: true,
            shownWhen: isRental,
        },
        {
            name: 'fuel_type',
            label: 'Jenis BBM',
            type: 'select',
            required: true,
            half: true,
            options: fuelTypes,
            placeholder: 'Pilih jenis BBM',
        },
        {
            name: 'tank_capacity',
            label: 'Kapasitas Tangki (L)',
            type: 'number',
            half: true,
            placeholder: 'Contoh: 50',
        },
        {
            name: 'current_odometer',
            label: 'Odometer Saat Ini (km)',
            type: 'number',
            half: true,
            placeholder: 'Contoh: 12000',
        },
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            required: true,
            half: true,
            options: statuses,
            placeholder: 'Pilih status',
        },
        { name: 'is_active', label: 'Aktif', type: 'checkbox' },
    ];

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Kendaraan' : 'Tambah Kendaraan'}
            fields={fields}
            initial={{
                plate_number: initial?.plate_number ?? '',
                category: initial?.category ?? '',
                capacity: initial?.capacity?.toString() ?? '',
                brand: initial?.brand ?? '',
                model: initial?.model ?? '',
                year: initial?.year?.toString() ?? '',
                ownership_type: initial?.ownership_type ?? '',
                rental_partner_id: initial?.rental_partner_id?.toString() ?? '',
                rental_start_date: initial?.rental_start_date ?? '',
                rental_end_date: initial?.rental_end_date ?? '',
                fuel_type: initial?.fuel_type ?? '',
                tank_capacity: initial?.tank_capacity?.toString() ?? '',
                current_odometer: initial?.current_odometer?.toString() ?? '',
                status: initial?.status ?? '',
                is_active: initial?.is_active ?? true,
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan Kendaraan'}
            dialogClassName="sm:max-w-2xl"
        />
    );
}
