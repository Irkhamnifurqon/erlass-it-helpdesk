import { Head, Link, router, usePage } from '@inertiajs/react';
import { Search, Plus, Download } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

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

type Ticket = {
    id: number;
    ticket_number: string;
    title: string;
    status: string;
    created_at: string;
    updated_at?: string;
    recent_activity?: boolean;
    category: { name: string };
    priority: { name: string; color: string };
};
type Props = {
    tickets: {
        data: Ticket[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    filters: { q?: string; status?: string };
};

export default function TicketsIndex({ tickets, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const { helpdesk } = usePage().props;
    const isAdmin = helpdesk?.roles?.includes('Administrator') ?? false;
    const exportQuery = new URLSearchParams();
    if (filters.q) exportQuery.set('q', filters.q);
    if (filters.status) exportQuery.set('status', filters.status);
    const exportUrl = `/tickets/export/pdf${exportQuery.size > 0 ? `?${exportQuery.toString()}` : ''}`;
    const search = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/tickets', { q }, { preserveState: true });
    };
    return (
        <>
            <Head title="Tiket" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Tiket Helpdesk
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola permintaan layanan IT.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {isAdmin && (
                            <Button variant="outline" asChild>
                                <a href={exportUrl} download>
                                    <Download className="size-4" />
                                    Unduh semua tiket
                                </a>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href="/tickets/create">
                                <Plus /> Buat tiket
                            </Link>
                        </Button>
                    </div>
                </div>
                <form onSubmit={search} className="flex max-w-lg gap-2">
                    <Input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nomor atau judul tiket..."
                    />
                    <Button variant="outline" type="submit">
                        <Search />
                    </Button>
                </form>
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="p-3">Nomor</th>
                                <th className="p-3">Judul</th>
                                <th className="p-3">Kategori</th>
                                <th className="p-3">Prioritas</th>
                                <th className="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tickets.data.map((ticket) => (
                                <tr
                                    key={ticket.id}
                                    className={`border-t transition ${
                                        ticket.recent_activity
                                            ? 'bg-blue-50 ring-1 ring-blue-200 ring-inset dark:bg-blue-950/30'
                                            : ''
                                    }`}
                                >
                                    <td className="p-3 font-medium">
                                        {ticket.recent_activity && (
                                            <span
                                                className="mr-2 inline-block size-2 animate-pulse rounded-full bg-blue-500"
                                                title="Ada aktivitas baru"
                                            />
                                        )}
                                        <Link
                                            className="text-primary hover:underline"
                                            href={`/tickets/${ticket.id}`}
                                        >
                                            {ticket.ticket_number}
                                        </Link>
                                    </td>
                                    <td className="p-3">{ticket.title}</td>
                                    <td className="p-3">
                                        {ticket.category.name}
                                    </td>
                                    <td className="p-3">
                                        {ticket.priority.name}
                                    </td>
                                    <td className="p-3 capitalize">
                                        {statusLabels[ticket.status] ??
                                            ticket.status}
                                    </td>
                                </tr>
                            ))}
                            {tickets.data.length === 0 && (
                                <tr>
                                    <td
                                        className="p-6 text-center text-muted-foreground"
                                        colSpan={5}
                                    >
                                        Belum ada tiket.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {tickets.from ?? 0}-{tickets.to ?? 0} dari{' '}
                        {tickets.total} tiket · {tickets.per_page} tiket per
                        halaman
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {tickets.links.map(
                            (link, index) =>
                                link.url && (
                                    <Button
                                        key={index}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        title={
                                            link.label.includes('Previous')
                                                ? 'Halaman sebelumnya'
                                                : link.label.includes('Next')
                                                  ? 'Halaman berikutnya'
                                                  : `Halaman ${link.label}`
                                        }
                                        onClick={() => router.get(link.url!)}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ),
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
