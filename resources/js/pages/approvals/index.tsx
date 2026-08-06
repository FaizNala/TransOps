import { Head, router } from '@inertiajs/react';
import { Check, Clock, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { decide } from '@/routes/approvals';

type ChainItem = {
    level: number;
    name: string | null;
    status: 'pending' | 'approved' | 'rejected';
    decided_at: string | null;
};

type ApprovalItem = {
    id: number;
    booking_number: string;
    purpose: string;
    destination: string;
    start_at: string;
    end_at: string;
    requester: string | null;
    requester_department: string | null;
    vehicle: string;
    driver: string | null;
    my_level: number;
    can_decide: boolean;
    chain: ChainItem[];
};

type Props = {
    approvals: ApprovalItem[];
};

function Stepper({ chain }: { chain: ChainItem[] }) {
    const currentLevel =
        chain.find((item) => item.status === 'pending')?.level ?? null;

    return (
        <div className="flex flex-wrap items-center gap-1">
            {chain.map((item, index) => {
                const state =
                    item.status === 'approved'
                        ? 'approved'
                        : item.status === 'rejected'
                          ? 'rejected'
                          : item.level === currentLevel
                            ? 'current'
                            : 'upcoming';

                return (
                    <div key={item.level} className="flex items-center gap-1">
                        {index > 0 && <div className="h-px w-4 bg-border" />}
                        <div
                            className={`flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs ${
                                state === 'approved'
                                    ? 'border-transparent bg-emerald-600/10 text-emerald-700 dark:text-emerald-400'
                                    : state === 'rejected'
                                      ? 'border-transparent bg-destructive/10 text-destructive'
                                      : state === 'current'
                                        ? 'border-transparent bg-primary text-primary-foreground'
                                        : 'border-border text-muted-foreground'
                            }`}
                        >
                            {state === 'approved' ? (
                                <Check className="size-3.5" />
                            ) : state === 'rejected' ? (
                                <X className="size-3.5" />
                            ) : (
                                <Clock className="size-3.5" />
                            )}
                            <span className="font-medium">
                                Level {item.level}
                            </span>
                            <span className="hidden sm:inline">
                                {item.name}
                            </span>
                            {item.decided_at && (
                                <span className="hidden text-muted-foreground md:inline">
                                    {item.decided_at}
                                </span>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function ApprovalCard({ item }: { item: ApprovalItem }) {
    const [processing, setProcessing] = useState(false);
    const noteRef = useRef<HTMLTextAreaElement>(null);

    const submit = (action: 'approved' | 'rejected') => {
        setProcessing(true);
        router.post(
            decide(item.id).url,
            { action, note: noteRef.current?.value ?? '' },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    };

    return (
        <div className="rounded-lg border border-border bg-card p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <span className="font-semibold text-foreground">
                        {item.booking_number}
                    </span>
                    <Badge variant="secondary">Menunggu</Badge>
                </div>
                {item.can_decide ? (
                    <Badge>Giliran Anda</Badge>
                ) : (
                    <Badge variant="outline">Menunggu level sebelumnya</Badge>
                )}
            </div>

            <div className="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                <p className="text-muted-foreground">
                    Pemohon:{' '}
                    <span className="text-foreground">
                        {item.requester}
                        {item.requester_department
                            ? ` · ${item.requester_department}`
                            : ''}
                    </span>
                </p>
                <p className="text-muted-foreground">
                    Kendaraan:{' '}
                    <span className="text-foreground">{item.vehicle}</span>
                </p>
                <p className="text-muted-foreground">
                    Driver:{' '}
                    <span className="text-foreground">{item.driver}</span>
                </p>
                <p className="text-muted-foreground">
                    Destinasi:{' '}
                    <span className="text-foreground">{item.destination}</span>
                </p>
                <p className="text-muted-foreground sm:col-span-2">
                    Tujuan:{' '}
                    <span className="text-foreground">{item.purpose}</span>
                </p>
                <p className="text-muted-foreground sm:col-span-2">
                    Jadwal:{' '}
                    <span className="text-foreground">
                        {item.start_at} - {item.end_at}
                    </span>
                </p>
            </div>

            <div className="mt-4">
                <Stepper chain={item.chain} />
            </div>

            {item.can_decide && (
                <div className="mt-4 flex flex-col gap-2">
                    <Label htmlFor={`note-${item.id}`} className="text-sm">
                        Catatan (opsional)
                    </Label>
                    <Textarea
                        id={`note-${item.id}`}
                        ref={noteRef}
                        placeholder="Catatan persetujuan/penolakan"
                    />
                    <div className="flex gap-2">
                        <Button
                            onClick={() => submit('approved')}
                            disabled={processing}
                        >
                            {processing ? 'Menyimpan...' : 'Approve'}
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => submit('rejected')}
                            disabled={processing}
                        >
                            Reject
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

export default function ApprovalsIndex({ approvals }: Props) {
    return (
        <>
            <Head title="Persetujuan" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h1 className="text-lg font-semibold text-foreground">
                    Menunggu Persetujuan
                </h1>

                {approvals.length === 0 ? (
                    <div className="rounded-lg border border-border bg-card p-6">
                        <p className="text-sm text-muted-foreground">
                            Tidak ada pemesanan yang menunggu persetujuan Anda.
                        </p>
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        {approvals.map((item) => (
                            <ApprovalCard key={item.id} item={item} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
