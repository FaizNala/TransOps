import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import type { PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDateTime } from '@/lib/format';
import { index } from '@/routes/audit-logs';

type AuditLogRow = {
    id: number;
    user: string;
    action: string;
    entity_type: string;
    entity_id: number | null;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};

type PageData = {
    data: AuditLogRow[];
    links: PaginationLink[];
    total: number;
    from: number;
    to: number;
};

type Props = {
    logs: PageData;
    filters: { entity_type?: string; entity_id?: string };
    entities: string[];
};

const actionVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    created: 'default',
    approved: 'default',
    updated: 'outline',
    rejected: 'destructive',
    cancelled: 'destructive',
    deleted: 'destructive',
    started: 'outline',
    completed: 'default',
};

const entryText = (value: unknown): string =>
    value === null || value === undefined
        ? '—'
        : typeof value === 'object'
          ? JSON.stringify(value)
          : String(value);

function PayloadChips({ value }: { value: Record<string, unknown> | null }) {
    if (!value || Object.keys(value).length === 0) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <div className="flex max-w-72 flex-wrap gap-x-4 gap-y-1">
            {Object.entries(value).map(([key, val]) => (
                <span key={key} className="text-xs">
                    <span className="text-muted-foreground">{key}:</span>{' '}
                    <span className="font-medium">{entryText(val)}</span>
                </span>
            ))}
        </div>
    );
}

export default function AuditLogsIndex({ logs, filters, entities }: Props) {
    const [entityType, setEntityType] = useState(filters.entity_type ?? '');
    const [entityId, setEntityId] = useState(filters.entity_id ?? '');

    const applyFilter = (type: string, id: string) => {
        router.get(
            index({
                query: {
                    filter: {
                        entity_type: type === '__all' ? null : type || null,
                        entity_id: id || null,
                    },
                    page: 1,
                },
            }).url,
            {},
            { preserveScroll: true },
        );
    };

    const columns: DataTableColumn<AuditLogRow>[] = [
        {
            key: 'created_at',
            header: 'Waktu',
            render: (row) => (
                <span className="whitespace-nowrap text-muted-foreground">
                    {formatDateTime(row.created_at)}
                </span>
            ),
        },
        { key: 'user', header: 'Pengguna' },
        {
            key: 'action',
            header: 'Aksi',
            render: (row) => (
                <Badge variant={actionVariant[row.action] ?? 'secondary'}>
                    {row.action}
                </Badge>
            ),
        },
        {
            key: 'entity_type',
            header: 'Entitas',
            render: (row) => (
                <span className="font-medium">{row.entity_type}</span>
            ),
        },
        {
            key: 'entity_id',
            header: 'ID',
            render: (row) =>
                row.entity_id === null ? '-' : String(row.entity_id),
        },
        {
            key: 'before',
            header: 'Data Sebelum',
            render: (row) => <PayloadChips value={row.before} />,
        },
        {
            key: 'after',
            header: 'Data Sesudah',
            render: (row) => <PayloadChips value={row.after} />,
        },
    ];

    return (
        <>
            <Head title="Audit Log" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-foreground">
                        Audit Log
                    </h1>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Select
                        value={entityType}
                        onValueChange={(value) => applyFilter(value, entityId)}
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="Semua entitas" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">Semua entitas</SelectItem>
                            {entities.map((entity) => (
                                <SelectItem key={entity} value={entity}>
                                    {entity}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Input
                        type="number"
                        placeholder="ID entitas"
                        value={entityId}
                        onChange={(event) => setEntityId(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                applyFilter(
                                    entityType,
                                    event.currentTarget.value,
                                );
                            }
                        }}
                        className="max-w-40"
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            setEntityType('');
                            setEntityId('');
                            applyFilter('', '');
                        }}
                    >
                        Reset
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={logs.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Tidak ada audit log."
                />

                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {logs.from ?? 0}-{logs.to ?? 0} dari{' '}
                        {logs.total}
                    </p>
                    <Pagination links={logs.links} />
                </div>
            </div>
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Audit Log',
            href: index(),
        },
    ],
};
