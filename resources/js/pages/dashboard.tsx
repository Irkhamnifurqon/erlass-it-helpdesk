import { Head } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { DashboardInvitation } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    role: string;
    department?: string;
    stats: {
        total: number;
        open: number;
        inProgress: number;
        resolved: number;
    };
    recentTickets: {
        id: number;
        ticket_number: string;
        title: string;
        status: string;
        category: { name: string };
    }[];
};

export default function Dashboard({
    pendingInvitations = [],
    role,
    department,
    stats,
    recentTickets,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
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
                        <Link href="/tickets/create">Buat tiket</Link>
                    </Button>
                </div>
                <div className="grid gap-4 md:grid-cols-4">
                    {[
                        ['Total tiket', stats.total],
                        ['Open', stats.open],
                        ['Dalam proses', stats.inProgress],
                        ['Selesai', stats.resolved],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-xl border p-5">
                            <p className="text-sm text-muted-foreground">
                                {label}
                            </p>
                            <p className="mt-2 text-3xl font-semibold">
                                {value}
                            </p>
                        </div>
                    ))}
                </div>
                <div className="rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Tiket terbaru</h2>
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
                                    {ticket.status.replace('_', ' ')}
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
