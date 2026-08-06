import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export type SummaryCardItem = {
    label: string;
    value: number | string;
    hint?: string;
};

export function SummaryCards({ items }: { items: SummaryCardItem[] }) {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-4">
            {items.map(({ label, value, hint }) => (
                <Card key={label} className="gap-2 py-5">
                    <CardHeader className="gap-0.5 px-5">
                        <CardDescription>{label}</CardDescription>
                        <CardTitle className="text-3xl tabular-nums">
                            {value}
                        </CardTitle>
                    </CardHeader>
                    {hint && (
                        <CardContent className="px-5 text-sm text-muted-foreground">
                            {hint}
                        </CardContent>
                    )}
                </Card>
            ))}
        </div>
    );
}
