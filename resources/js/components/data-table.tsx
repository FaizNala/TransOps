import type { Key, ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type DataTableColumn<T> = {
    key: string;
    header: string;
    className?: string;
    render?: (row: T) => ReactNode;
};

type DataTableProps<T> = {
    columns: DataTableColumn<T>[];
    data: T[];
    rowKey?: (row: T) => Key;
    emptyMessage?: string;
    showIndex?: boolean;
};

export function DataTable<T>({
    columns,
    data,
    rowKey,
    emptyMessage = 'Tidak ada data.',
    showIndex = true,
}: DataTableProps<T>) {
    const indexClass =
        'w-10 px-4 py-2 text-right tabular-nums text-muted-foreground';

    return (
        <div className="overflow-x-auto rounded-lg border border-border bg-card">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-border">
                        {showIndex && <th className={indexClass}>No</th>}
                        {columns.map((column) => (
                            <th
                                key={column.key}
                                className={cn(
                                    'px-4 py-2 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase',
                                    column.className,
                                )}
                            >
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {data.map((row, index) => (
                        <tr
                            key={rowKey?.(row) ?? index}
                            className="border-b border-border last:border-0"
                        >
                            {showIndex && (
                                <td className="px-4 py-2 text-right text-sm text-muted-foreground tabular-nums">
                                    {index + 1}
                                </td>
                            )}
                            {columns.map((column) => (
                                <td
                                    key={column.key}
                                    className={cn(
                                        'px-4 py-2 align-middle',
                                        column.className,
                                    )}
                                >
                                    {column.render
                                        ? column.render(row)
                                        : String(
                                              (row as Record<string, unknown>)[
                                                  column.key
                                              ] ?? '',
                                          )}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            {data.length === 0 && (
                <div className="p-6 text-center text-sm text-muted-foreground">
                    {emptyMessage}
                </div>
            )}
        </div>
    );
}
