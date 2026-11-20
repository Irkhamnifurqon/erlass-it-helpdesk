import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Inbox,
    Plus,
    TicketCheck,
} from 'lucide-react';

const statusLabels: Record<string, string> = {
    open: 'Terbuka',
    assigned: 'Ditugaskan',
    in_progress: 'Sedang ditangani',
    pending_user: 'Menunggu pelapor',
    on_hold: 'Ditunda',
    resolved: 'Selesai ditangani',
    closed: 'Ditutup',
    reopened: 'Dibuka kembali',
    pending: 'Menunggu',
};

type Props = {
    role: string;
    department?: string;
    stats: {
        total: number;
        open: number;
        inProgress: number;
        resolved: number;
        overdue: number;
    };
    recentTickets: {
        id: number;
        ticket_number: string;
        title: string;
        status: string;
        category: { name: string };
    }[];
    statusCounts: Record<string, number>;
    categoryCounts: { name: string; total: number }[];
};

export default function Dashboard({
    role,
    department,
    stats,
    recentTickets,
    statusCounts,
    categoryCounts,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-muted/30 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Dashboard {role}
                        </h1>
                        <p className="text-muted-foreground">
                            {department
                                ? `Departemen ${department}`
                                : 'Ringkasan layanan IT'}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/tickets/create">
                            <Plus className="size-4" />
                            Buat tiket
                        </Link>
                    </Button>
                </div>
                <div className="grid gap-4 md:grid-cols-5">
                    {[
                        {
                            label: 'Total tiket',
                            value: stats.total,
                            icon: Inbox,
                            tone: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
                        },
                        {
                            label: 'Terbuka / ditugaskan',
                            value: stats.open,
                            icon: TicketCheck,
                            tone: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300',
                        },
                        {
                            label: 'Sedang ditangani',
                            value: stats.inProgress,
                            icon: Clock3,
                            tone: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                        },
                        {
                            label: 'Selesai',
                            value: stats.resolved,
                            icon: CheckCircle2,
                            tone: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
                        },
                        {
                            label: 'Overdue',
                            value: stats.overdue,
                            icon: AlertTriangle,
                            tone: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                        },
                    ].map(({ label, value, icon: Icon, tone }) => (
                        <div
                            key={label}
                            className="rounded-xl border bg-background p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        {label}
                                    </p>
                                    <p className="mt-2 text-3xl font-semibold">
                                        {value}
                                    </p>
                                </div>
                                <span
                                    className={`flex size-10 items-center justify-center rounded-lg ${tone}`}
                                >
                                    <Icon className="size-5" />
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Tiket terbaru</h2>
                    </div>
                    <div className="grid gap-6 lg:grid-cols-2">
                        <section className="rounded-xl border bg-background p-5">
                            <h2 className="mb-4 font-semibold">
                                Tiket per status
                            </h2>
                            <div className="flex items-center gap-6">
                                <div
                                    className="relative size-32 shrink-0 rounded-full"
                                    style={{
                                        background:
                                            'conic-gradient(#2563eb 0 35%, #06b6d4 35% 62%, #f59e0b 62% 82%, #10b981 82% 100%)',
                                    }}
                                >
                                    <div className="absolute inset-3 flex items-center justify-center rounded-full bg-background text-center">
                                        <div>
                                            <p className="text-2xl font-bold">
                                                {stats.total}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Tiket
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div className="min-w-0 flex-1 space-y-3">
                                    {Object.entries(statusLabels).map(
                                        ([status, label]) => (
                                            <div
                                                key={status}
                                                className="space-y-1"
                                            >
                                                <div className="flex items-center justify-between text-sm">
                                                    <span>{label}</span>
                                                    <span className="font-semibold">
                                                        {statusCounts[status] ??
                                                            0}
                                                    </span>
                                                </div>
                                                <div className="h-1.5 rounded-full bg-muted">
                                                    <div
                                                        className="h-1.5 rounded-full bg-primary"
                                                        style={{
                                                            width: `${Math.min(100, ((statusCounts[status] ?? 0) / Math.max(1, stats.total)) * 100)}%`,
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </div>
                        </section>
                        <section className="rounded-xl border bg-background p-5">
                            <h2 className="mb-4 font-semibold">
                                Tiket per kategori
                            </h2>
                            <div className="space-y-3">
                                {categoryCounts.map((category) => (
                                    <div key={category.name}>
                                        <div className="mb-1 flex justify-between text-sm">
                                            <span>{category.name}</span>
                                            <span>{category.total}</span>
                                        </div>
                                        <div className="h-2 rounded-full bg-muted">
                                            <div
                                                className="h-2 rounded-full bg-primary"
                                                style={{
                                                    width: `${Math.min(100, (category.total / Math.max(1, stats.total)) * 100)}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))}
                                {categoryCounts.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada data kategori.
                                    </p>
                                )}
                            </div>
                        </section>
                    </div>
                    <div className="divide-y">
                        {recentTickets.map((ticket) => (
                            <Link
                                key={ticket.id}
                                href={`/tickets/${ticket.id}`}
                                className="flex items-center justify-between gap-4 p-4 hover:bg-muted/50"
                            >
                                <div>
                                    <p className="font-medium">
                                        {ticket.ticket_number} · {ticket.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {ticket.category.name}
                                    </p>
                                </div>
                                <span className="text-sm text-muted-foreground capitalize">
                                    {statusLabels[ticket.status] ??
                                        ticket.status}
                                </span>
                            </Link>
                        ))}
                        {recentTickets.length === 0 && (
                            <p className="p-5 text-sm text-muted-foreground">
                                Belum ada tiket.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ],
});
