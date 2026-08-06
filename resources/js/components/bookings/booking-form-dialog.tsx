import { Plus, X } from 'lucide-react';
import { EntityFormDialog } from '@/components/entity-form-dialog';
import type {
    MasterFormField,
    MasterFormValue,
} from '@/components/entity-form-dialog';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store, update } from '@/routes/bookings';

type Option = {
    id: number;
    label: string;
};

export type BookingFormPayload = {
    id?: number;
    vehicle_id: number | '';
    driver_id: number | '';
    purpose: string;
    destination: string;
    start_at: string;
    end_at: string;
    approvers: (number | string)[];
};

type BookingFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    vehicles: Option[];
    drivers: Option[];
    approvers: Option[];
    suggestedApprovers?: number[];
    initial?: BookingFormPayload;
};

export function BookingFormDialog({
    open,
    onOpenChange,
    vehicles,
    drivers,
    approvers,
    suggestedApprovers,
    initial,
}: BookingFormDialogProps) {
    const isEdit = Boolean(initial);

    const defaultApprovers =
        initial && initial.approvers.length
            ? initial.approvers.map(String)
            : suggestedApprovers && suggestedApprovers.length
              ? suggestedApprovers.map(String)
              : ['', ''];

    while (defaultApprovers.length < 2) {
        defaultApprovers.push('');
    }

    const approverOptions = approvers.map((approver) => ({
        value: String(approver.id),
        label: approver.label,
    }));

    const fields: MasterFormField[] = [
        {
            name: 'vehicle_id',
            label: 'Kendaraan',
            type: 'select',
            required: true,
            options: vehicles.map((vehicle) => ({
                value: String(vehicle.id),
                label: vehicle.label,
            })),
            placeholder: 'Pilih kendaraan',
        },
        {
            name: 'driver_id',
            label: 'Driver',
            type: 'select',
            required: true,
            options: drivers.map((driver) => ({
                value: String(driver.id),
                label: driver.label,
            })),
            placeholder: 'Pilih driver',
        },
        {
            name: 'purpose',
            label: 'Tujuan Pemesanan',
            type: 'textarea',
            required: true,
            placeholder: 'Misal: pengantaran logistik ke area tambang',
        },
        {
            name: 'destination',
            label: 'Destinasi',
            type: 'text',
            required: true,
            placeholder: 'Contoh: Tambang Malili',
        },
        {
            name: 'start_at',
            label: 'Mulai',
            type: 'datetime',
            half: true,
            placeholder: 'Tanggal & jam mulai',
        },
        {
            name: 'end_at',
            label: 'Selesai',
            type: 'datetime',
            half: true,
            placeholder: 'Tanggal & jam selesai',
        },
        {
            name: 'approvers',
            label: 'Approver (minimal 2 level)',
            type: 'custom',
            render: (values, setValue) => {
                const current = (values.approvers as
                    (string | number)[] | undefined) ?? ['', ''];

                const setApprover = (index: number, value: string) => {
                    const next = [...current];
                    next[index] = value;
                    setValue('approvers', next);
                };

                return (
                    <div className="flex flex-col gap-2">
                        {current.map((value, index) => (
                            <div
                                key={index}
                                className="flex items-center gap-2"
                            >
                                <Select
                                    value={String(value)}
                                    onValueChange={(updated) =>
                                        setApprover(index, updated)
                                    }
                                >
                                    <SelectTrigger className="w-full flex-1">
                                        <SelectValue
                                            placeholder={`Pilih approver level ${index + 1}`}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {approverOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {current.length > 2 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Hapus approver level ${index + 1}`}
                                        onClick={() =>
                                            setValue(
                                                'approvers',
                                                current.filter(
                                                    (_, j) => j !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <X />
                                    </Button>
                                )}
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="w-fit"
                            onClick={() =>
                                setValue('approvers', [
                                    ...current,
                                    '',
                                ] as MasterFormValue)
                            }
                        >
                            <Plus /> Tambah level
                        </Button>
                    </div>
                );
            },
        },
    ];

    return (
        <EntityFormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Pemesanan' : 'Tambah Pemesanan'}
            fields={fields}
            initial={{
                vehicle_id: String(initial?.vehicle_id ?? ''),
                driver_id: String(initial?.driver_id ?? ''),
                purpose: initial?.purpose ?? '',
                destination: initial?.destination ?? '',
                start_at: initial?.start_at ?? '',
                end_at: initial?.end_at ?? '',
                approvers: defaultApprovers,
            }}
            id={initial?.id}
            store={store}
            update={update}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Simpan Pemesanan'}
        />
    );
}
