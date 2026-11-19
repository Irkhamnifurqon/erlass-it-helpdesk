import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Ticket = {
    id: number;
    ticket_number: string;
    title: string;
    description: string;
    status: string;
    solution?: string;
    category: { name: string };
    priority: { name: string };
    comments: {
        id: number;
        body: string;
        is_internal: boolean;
        user: { name: string };
        created_at: string;
    }[];
};
export default function TicketShow({ ticket }: { ticket: Ticket }) {
    const comment = useForm({ body: '', is_internal: false });
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        comment.post(`/tickets/${ticket.id}/comments`, {
            onSuccess: () => comment.reset(),
        });
    };
    return (
        <>
            <Head title={ticket.ticket_number} />
            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <Link
                    href="/tickets"
                    className="text-sm text-primary hover:underline"
                >
                    Kembali ke tiket
                </Link>
                <div className="rounded-xl border p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                {ticket.ticket_number} · {ticket.category.name}
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {ticket.title}
                            </h1>
                        </div>
                        <span className="rounded-full bg-muted px-3 py-1 text-sm capitalize">
                            {ticket.status.replace('_', ' ')}
                        </span>
                    </div>
                    <p className="mt-6 whitespace-pre-wrap">
                        {ticket.description}
                    </p>
                    {ticket.solution && (
                        <div className="mt-6 rounded-lg bg-muted/50 p-4">
                            <p className="font-medium">Solusi</p>
                            <p className="mt-2 whitespace-pre-wrap">
                                {ticket.solution}
                            </p>
                        </div>
                    )}
                </div>
                <div className="rounded-xl border p-6">
                    <h2 className="mb-4 text-lg font-semibold">Percakapan</h2>
                    <div className="space-y-4">
                        {ticket.comments.map((item) => (
                            <div
                                key={item.id}
                                className="rounded-lg bg-muted/50 p-4"
                            >
                                <div className="flex justify-between text-sm font-medium">
                                    <span>
                                        {item.user.name}
                                        {item.is_internal
                                            ? ' · Catatan internal'
                                            : ''}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {new Date(
                                            item.created_at,
                                        ).toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <p className="mt-2 whitespace-pre-wrap">
                                    {item.body}
                                </p>
                            </div>
                        ))}
                        {ticket.comments.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada komentar.
                            </p>
                        )}
                    </div>
                    <form onSubmit={submit} className="mt-6 space-y-3">
                        <textarea
                            placeholder="Tulis komentar..."
                            className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={comment.data.body}
                            onChange={(
                                e: React.ChangeEvent<HTMLTextAreaElement>,
                            ) => comment.setData('body', e.target.value)}
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <Input
                                type="checkbox"
                                className="size-4"
                                checked={comment.data.is_internal}
                                onChange={(e) =>
                                    comment.setData(
                                        'is_internal',
                                        e.target.checked,
                                    )
                                }
                            />{' '}
                            Catatan internal
                        </label>
                        <Button disabled={comment.processing}>
                            Kirim komentar
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}
