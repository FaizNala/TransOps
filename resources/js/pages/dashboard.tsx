import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { dashboard } from '@/routes';
import { index as approvalsIndex } from '@/routes/approvals';

type Stats = {
    vehicles_available: number;
    active_bookings: number;
    pending_approvals: number;
    overdue_services: number;
};

type ApproverStats = {
    my_pending: number;
    my_approved: number;
    my_rejected: number;
    overall_pending: number;
};

type MonthlyUsage = {
    month: string;
    total: number;
    completed: number;
};

type PendingApproval = {
    id: number;
    booking_number: string;
    requester: string | null;
    vehicle: string;
    my_level: number;
};

type Props = {
    stats: Stats | ApproverStats;
    monthly_usage?: MonthlyUsage[];
    is_approver?: boolean;
    pending_approvals?: PendingApproval[];
};

const statCards: { key: keyof Stats; label: string; hint: string }[] = [
    {
        key: 'vehicles_available',
        label: 'Kendaraan Tersedia',
        hint: 'Siap pakai',
    },
    {
        key: 'active_bookings',
        label: 'Pemesanan Aktif',
        hint: 'Sudah disetujui',
    },
    {
        key: 'pending_approvals',
        label: 'Menunggu Persetujuan',
        hint: 'Pending',
    },
    {
        key: 'overdue_services',
        label: 'Service Terlewat',
        hint: 'Jadwal lewat',
    },
];

const chartConfig = {
    total: { label: 'Total', color: 'var(--chart-1)' },
    completed: { label: 'Selesai', color: 'var(--chart-2)' },
} satisfies ChartConfig;

const approverStatCards: {
    key: keyof ApproverStats;
    label: string;
    hint: string;
}[] = [
    { key: 'my_pending', label: 'Menunggu Saya', hint: 'Belum saya putuskan' },
    {
        key: 'my_approved',
        label: 'Saya Setujui',
        hint: 'Level yang saya proses',
    },
    { key: 'my_rejected', label: 'Saya Tolak', hint: 'Level yang saya proses' },
    {
        key: 'overall_pending',
        label: 'Total Pending',
        hint: 'Di seluruh sistem',
    },
];

function ApproverDashboard({
    stats,
    pendingApprovals,
}: {
    stats: ApproverStats;
    pendingApprovals: PendingApproval[];
}) {
    return (
        <>
            <div className="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-4">
                {approverStatCards.map(({ key, label, hint }) => (
                    <Card key={key} className="gap-2 py-5">
                        <CardHeader className="gap-0.5 px-5">
                            <CardDescription>{label}</CardDescription>
                            <CardTitle className="text-3xl tabular-nums">
                                {stats[key]}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-5 text-sm text-muted-foreground">
                            {hint}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card>
                <CardHeader className="flex-row items-center justify-between">
                    <div>
                        <CardTitle>Menunggu Persetujuan Saya</CardTitle>
                        <CardDescription>
                            Pemesanan yang menunggu keputusan Anda
                        </CardDescription>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`${approvalsIndex().url}`}>
                            Lihat Semua
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    {pendingApprovals.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada pemesanan yang menunggu persetujuan Anda.
                        </p>
                    ) : (
                        <ul className="flex flex-col divide-y">
                            {pendingApprovals.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between gap-2 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium text-foreground">
                                            {item.booking_number}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {item.requester} · {item.vehicle}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        Level {item.my_level}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

export default function Dashboard(props: Props) {
    const { stats, monthly_usage } = props;

    if (props.is_approver) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <ApproverDashboard
                        stats={props.stats as ApproverStats}
                        pendingApprovals={props.pending_approvals ?? []}
                    />
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {(
                        statCards as {
                            key: keyof Stats;
                            label: string;
                            hint: string;
                        }[]
                    ).map(({ key, label, hint }) => (
                        <Card key={key} className="gap-2 py-5">
                            <CardHeader className="gap-0.5 px-5">
                                <CardDescription>{label}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {(stats as Stats)[key]}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-5 text-sm text-muted-foreground">
                                {hint}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Pemakaian Kendaraan per Bulan</CardTitle>
                        <CardDescription>
                            Jumlah pemesanan dalam 6 bulan terakhir
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            config={chartConfig}
                            className="h-80 w-full"
                        >
                            <BarChart data={monthly_usage ?? []}>
                                <CartesianGrid vertical={false} />
                                <XAxis
                                    dataKey="month"
                                    tickLine={false}
                                    axisLine={false}
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    allowDecimals={false}
                                />
                                <ChartTooltip
                                    cursor={{ fill: 'hsl(var(--muted))' }}
                                    content={<ChartTooltipContent />}
                                />
                                <ChartLegend content={<ChartLegendContent />} />
                                <Bar
                                    dataKey="total"
                                    fill="var(--color-total)"
                                    radius={[4, 4, 0, 0]}
                                />
                                <Bar
                                    dataKey="completed"
                                    fill="var(--color-completed)"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ChartContainer>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
