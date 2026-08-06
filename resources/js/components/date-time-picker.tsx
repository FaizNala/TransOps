import { format, parse } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { id as idDayPicker } from 'react-day-picker/locale';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type DateTimePickerProps = {
    value: string;
    onChange: (value: string) => void;
    withTime?: boolean;
    placeholder?: string;
    className?: string;
};

export function DateTimePicker({
    value,
    onChange,
    withTime = true,
    placeholder,
    className,
}: DateTimePickerProps) {
    const [timeValue, setTimeValue] = useState(value ? value.slice(11) : '');
    const date = value
        ? parse(
              value,
              withTime ? "yyyy-MM-dd'T'HH:mm" : 'yyyy-MM-dd',
              new Date(),
          )
        : undefined;
    const displayPlaceholder =
        placeholder ?? (withTime ? 'Pilih tanggal & jam' : 'Pilih tanggal');

    const handleDaySelect = (day: Date | undefined) => {
        if (!day) {
            onChange('');

            return;
        }

        onChange(
            withTime
                ? `${format(day, 'yyyy-MM-dd')}T${timeValue || '00:00'}`
                : format(day, 'yyyy-MM-dd'),
        );
    };

    const handleTimeChange = (time: string) => {
        setTimeValue(time);

        if (value) {
            onChange(`${value.slice(0, 10)}T${time}`);
        }
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    data-empty={!value}
                    className={cn(
                        'w-full justify-start font-normal data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon />
                    {value
                        ? format(date!, withTime ? 'PPP p' : 'PPP', {
                              locale: idLocale,
                          })
                        : displayPlaceholder}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={date}
                    onSelect={handleDaySelect}
                    defaultMonth={date}
                    locale={idDayPicker}
                />
                {withTime && (
                    <div className="border-t p-3">
                        <Input
                            type="time"
                            value={timeValue}
                            onChange={(event) =>
                                handleTimeChange(event.target.value)
                            }
                            aria-label="Jam"
                        />
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}
