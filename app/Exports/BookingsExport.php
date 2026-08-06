<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingsExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(private $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['No', 'No. Pemesanan', 'Pemohon', 'Departemen', 'Kendaraan', 'Pengemudi', 'Mulai', 'Selesai', 'Status', 'Tujuan'];
    }

    public function map($booking): array
    {
        return [
            ++$this->rowNumber,
            $booking->booking_number,
            $booking->requester?->name,
            $booking->requester?->department,
            trim("{$booking->vehicle?->plate_number} {$booking->vehicle?->brand} {$booking->vehicle?->model}"),
            $booking->driver?->employee?->name,
            $booking->start_at?->format('Y-m-d H:i'),
            $booking->end_at?->format('Y-m-d H:i'),
            $booking->status->value,
            $booking->destination,
        ];
    }
}
