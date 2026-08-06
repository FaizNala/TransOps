import { useState } from 'react';
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

type BookingUsageDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    fieldLabel: string;
    submitLabel: string;
    onSubmit: (odometer: number) => void;
};

export function BookingUsageDialog({
    open,
    onOpenChange,
    title,
    description,
    fieldLabel,
    submitLabel,
    onSubmit,
}: BookingUsageDialogProps) {
    const [odometer, setOdometer] = useState('');

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const value = Number(odometer);

        if (!odometer || Number.isNaN(value) || value < 0) {
            return;
        }

        onSubmit(value);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    setOdometer('');
                }

                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>

                    <div className="grid gap-2">
                        <Label htmlFor="odometer">{fieldLabel}</Label>
                        <Input
                            id="odometer"
                            type="number"
                            min="0"
                            step="any"
                            inputMode="decimal"
                            placeholder="0"
                            value={odometer}
                            onChange={(event) =>
                                setOdometer(event.target.value)
                            }
                            autoFocus
                        />
                    </div>

                    <DialogFooter className="sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                !odometer || Number.isNaN(Number(odometer))
                            }
                        >
                            {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
