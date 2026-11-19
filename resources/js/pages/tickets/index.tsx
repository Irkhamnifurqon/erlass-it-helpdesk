import { Head, Link, router } from '@inertiajs/react';
import { Search, Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Ticket = {
    id: number;
    ticket_number: string;
    title: string;
    status: string;
    created_at: string;
    category: { name: string };
    priority: { name: string; color: string };
};
type Props = {
    tickets: {
        data: Ticket[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string; status?: string };
};

export default function TicketsIndex({ tickets, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
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
                    <Button asChild>
                        <Link href="/tickets/create">
                            <Plus /> Buat tiket
                        </Link>
                    </Button>
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
                                <tr key={ticket.id} className="border-t">
                                    <td className="p-3 font-medium">
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
                                        {ticket.status.replace('_', ' ')}
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
                <div className="flex gap-2">
                    {tickets.links.map(
                        (link, index) =>
                            link.url && (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    onClick={() => router.get(link.url!)}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                    )}
                </div>
            </div>
        </>
    );
}
