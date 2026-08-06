import { useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DateTimePicker } from '@/components/date-time-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export type MasterFormValue = string | boolean | (string | number)[];

export type MasterFormValues = Record<string, MasterFormValue>;

export type MasterFormFieldOption = { value: string; label: string };

export type MasterFormField =
    | {
          name: string;
          label: string;
          type: 'text' | 'email' | 'number' | 'password' | 'date' | 'datetime';
          placeholder?: string;
          required?: boolean;
          half?: boolean;
          shownWhen?: (values: MasterFormValues) => boolean;
      }
    | {
          name: string;
          label: string;
          type: 'select';
          options: MasterFormFieldOption[];
          optionalText?: string;
          placeholder?: string;
          required?: boolean;
          nullable?: boolean;
          half?: boolean;
          shownWhen?: (values: MasterFormValues) => boolean;
      }
    | {
          name: string;
          label: string;
          type: 'textarea';
          placeholder?: string;
          required?: boolean;
          half?: boolean;
          shownWhen?: (values: MasterFormValues) => boolean;
      }
    | {
          name: string;
          label: string;
          type: 'checkbox';
          required?: boolean;
          half?: boolean;
          shownWhen?: (values: MasterFormValues) => boolean;
      }
    | {
          name: string;
          label: string;
          type: 'custom';
          render: (
              values: MasterFormValues,
              setValue: (name: string, value: MasterFormValue) => void,
          ) => ReactNode;
          required?: boolean;
          half?: boolean;
          shownWhen?: (values: MasterFormValues) => boolean;
      };

type EntityFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    fields: MasterFormField[];
    initial: MasterFormValues;
    store: { (): { url: string } };
    update?: { (id: number): { url: string } };
    id?: number;
    submitLabel?: string;
    dialogClassName?: string;
};

export function EntityFormDialog({
    open,
    onOpenChange,
    title,
    fields,
    initial,
    store,
    update,
    id,
    submitLabel = 'Simpan',
    dialogClassName = 'sm:max-w-lg',
}: EntityFormDialogProps) {
    const isEdit = Boolean(id);
    const form = useForm<MasterFormValues>(initial);

    const submit = () => {
        const options = { onSuccess: () => onOpenChange(false) };

        if (isEdit && id && update) {
            form.put(update(id).url, options);
        } else {
            form.post(store().url, options);
        }
    };

    const visibleFields = fields.filter(
        (field) => !field.shownWhen || field.shownWhen(form.data),
    );

    const renderControl = (field: MasterFormField) => {
        if (field.type === 'select') {
            const options = field.nullable
                ? [
                      { value: '', label: field.optionalText ?? 'Tidak ada' },
                      ...field.options,
                  ]
                : field.options;

            return (
                <Select
                    value={String(form.data[field.name] ?? '')}
                    onValueChange={(value) => form.setData(field.name, value)}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder={field.placeholder} />
                    </SelectTrigger>
                    <SelectContent>
                        {options.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            );
        }

        if (field.type === 'textarea') {
            return (
                <Textarea
                    id={`field-${field.name}`}
                    className="min-h-20"
                    value={String(form.data[field.name] ?? '')}
                    onChange={(event) =>
                        form.setData(field.name, event.target.value)
                    }
                    placeholder={field.placeholder}
                />
            );
        }

        if (field.type === 'custom') {
            return field.render(form.data, (name, value) =>
                form.setData(name, value),
            );
        }

        if (field.type === 'date') {
            return (
                <DateTimePicker
                    withTime={false}
                    value={String(form.data[field.name] ?? '')}
                    onChange={(value) => form.setData(field.name, value)}
                    placeholder={field.placeholder}
                />
            );
        }

        if (field.type === 'datetime') {
            return (
                <DateTimePicker
                    value={String(form.data[field.name] ?? '')}
                    onChange={(value) => form.setData(field.name, value)}
                    placeholder={field.placeholder}
                />
            );
        }

        if (field.type === 'checkbox') {
            return (
                <div className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id={`field-${field.name}`}
                        checked={Boolean(form.data[field.name])}
                        onChange={(event) =>
                            form.setData(field.name, event.target.checked)
                        }
                        className="h-4 w-4"
                    />
                    <Label
                        htmlFor={`field-${field.name}`}
                        className="cursor-pointer"
                    >
                        {field.label}
                        {field.required && (
                            <span
                                className="text-destructive"
                                aria-hidden="true"
                            >
                                *
                            </span>
                        )}
                    </Label>
                </div>
            );
        }

        return (
            <Input
                id={`field-${field.name}`}
                type={field.type}
                value={String(form.data[field.name] ?? '')}
                onChange={(event) =>
                    form.setData(field.name, event.target.value)
                }
                placeholder={field.placeholder}
                required={field.required}
            />
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className={`max-h-[90vh] [scrollbar-width:none] overflow-y-auto [&::-webkit-scrollbar]:hidden ${dialogClassName}`}
            >
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                    className="space-y-4"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        {visibleFields.map((field) => (
                            <div
                                key={field.name}
                                className={field.half ? '' : 'sm:col-span-2'}
                            >
                                <div className="grid gap-2">
                                    {field.type !== 'checkbox' && (
                                        <Label htmlFor={`field-${field.name}`}>
                                            {field.label}
                                            {field.required && (
                                                <span
                                                    className="text-destructive"
                                                    aria-hidden="true"
                                                >
                                                    *
                                                </span>
                                            )}
                                        </Label>
                                    )}
                                    {renderControl(field)}
                                    <InputError
                                        message={form.errors[field.name]}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="grid gap-4 pt-2">
                        <DialogFooter className="sm:justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                                disabled={form.processing}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Menyimpan...' : submitLabel}
                            </Button>
                        </DialogFooter>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
