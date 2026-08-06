const monthsShort = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
];

function parseParts(value: string): { date: string; time: string | null } {
    const [datePart, timePart = ''] = value.replace('T', ' ').split(' ');

    return { date: datePart, time: timePart || null };
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const { date } = parseParts(value);
    const [year, month, day] = date.slice(0, 10).split('-');

    if (!year || !month || !day || Number.isNaN(+month) || Number.isNaN(+day)) {
        return value;
    }

    return `${day} ${monthsShort[+month - 1]} ${year}`;
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const { date, time } = parseParts(value);

    return time ? `${formatDate(date)} ${time.slice(0, 5)}` : formatDate(date);
}
