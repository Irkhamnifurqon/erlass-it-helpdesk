import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { toast } from 'sonner';
import { Download } from 'lucide-react';

type Ticket = {
    id: number;
    requester_id: number;
    ticket_number: string;
    title: string;
    description: string;
    status: string;
    solution?: string;
    category: { name: string };
    subcategory?: { name: string };
    issue_type?: { name: string };
    priority: { name: string };
    is_overdue?: boolean;
    resolved_at?: string;
    closed_at?: string;
    sla?: {
        response_target_minutes: number;
        resolution_target_minutes: number;
        response_due_at?: string;
        resolution_due_at?: string;
        resolved_at?: string;
    };
    rating?: { score: number; feedback?: string };
    active_assignment?: { assignee: { id: number; name: string } };
    requester: { id: number; name: string };
    attachments: {
        id: number;
        original_name: string;
        size: number;
        comment_id?: number | null;
    }[];
    histories: {
        id: number;
        event: string;
        description?: string;
        old_value?: { status?: string };
        new_value?: { status?: string };
        created_at: string;
        user?: { name: string };
    }[];
    comments: {
        id: number;
        body: string;
        is_internal: boolean;
        user: { name: string };
        created_at: string;
        attachments: {
            id: number;
            original_name: string;
            size: number;
        }[];
    }[];
};
type StatusOption = { value: string; label: string };
const priorityLabels: Record<string, string> = {
    Low: 'Rendah',
    Medium: 'Sedang',
    High: 'Tinggi',
    Critical: 'Kritis',
};
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
export default function TicketShow({
    ticket,
    statusOptions,
    canCommentInternal,
    canAssign,
    canExtendSla,
    itUsers,
}: {
    ticket: Ticket;
    statusOptions: StatusOption[];
    canCommentInternal: boolean;
    canAssign: boolean;
    canExtendSla: boolean;
    itUsers: { id: number; name: string }[];
}) {
    const comment = useForm<{
        body: string;
        is_internal: boolean;
        attachments: File[];
    }>({
        body: '',
        is_internal: false,
        attachments: [],
    });
    const status = useForm({
        status: '',
        solution: '',
        reason: '',
    });
    const assignment = useForm({ assigned_to: '' });
    const rating = useForm({ score: '', feedback: '' });
    const slaExtension = useForm({ additional_days: '', reason: '' });
    const { auth } = usePage().props as {
        auth: { user: { id: number; roles?: string[] } };
    };
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        comment.post(`/tickets/${ticket.id}/comments`, {
            onSuccess: () => {
                comment.reset();
                toast.success('Balasan dan lampiran berhasil dikirim');
            },
        });
    };
    const updateStatus = (event: React.FormEvent) => {
        event.preventDefault();
        status.post(`/tickets/${ticket.id}/status`, {
            onSuccess: () => toast.success('Status tiket berhasil diperbarui'),
        });
    };
    const updateAssignment = (event: React.FormEvent) => {
        event.preventDefault();
        assignment.post(`/tickets/${ticket.id}/assignments`, {
            onSuccess: () => toast.success('Petugas IT berhasil ditugaskan'),
        });
    };
    const submitRating = (event: React.FormEvent) => {
        event.preventDefault();
        rating.post(`/tickets/${ticket.id}/rating`, {
            onSuccess: () => toast.success('Rating kinerja petugas tersimpan'),
        });
    };
    const submitSlaExtension = (event: React.FormEvent) => {
        event.preventDefault();
        slaExtension.post(`/tickets/${ticket.id}/sla-extension`, {
            onSuccess: () => {
                slaExtension.reset();
                toast.success('Target penyelesaian berhasil diperpanjang');
            },
        });
    };
    return (
        <>
            <Head title={ticket.ticket_number} />
            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Link
                        href="/tickets"
                        className="text-sm text-primary hover:underline"
                    >
                        Kembali ke tiket
                    </Link>
                    <Button variant="outline" asChild>
                        <a href={`/tickets/${ticket.id}/export/pdf`} download>
                            <Download className="size-4" />
                            Unduh tiket PDF
                        </a>
                    </Button>
                </div>
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
                        <span
                            className={`rounded-full px-3 py-1 text-sm capitalize ${
                                ticket.is_overdue
                                    ? 'bg-destructive/15 text-destructive'
                                    : 'bg-muted'
                            }`}
                        >
                            {statusLabels[ticket.status] ?? ticket.status}
                            {ticket.is_overdue ? ' · Overdue' : ''}
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
                    <div className="mt-6 grid gap-3 text-sm text-muted-foreground md:grid-cols-3">
                        <span>Jenis: {ticket.issue_type?.name ?? '-'}</span>
                        <span>
                            Prioritas:{' '}
                            {priorityLabels[ticket.priority.name] ??
                                ticket.priority.name}
                        </span>
                        <span>
                            Petugas:{' '}
                            {ticket.active_assignment?.assignee.name ??
                                'Belum ditugaskan'}
                        </span>
                    </div>
                    {ticket.sla && (
                        <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-900 dark:bg-blue-950/30">
                            <p className="font-semibold">Target penyelesaian</p>
                            <p className="mt-1 text-muted-foreground">
                                {ticket.sla.resolution_target_minutes >= 1440
                                    ? `${Math.floor(ticket.sla.resolution_target_minutes / 1440)} hari`
                                    : `${ticket.sla.resolution_target_minutes} menit`}{' '}
                                dihitung sejak tiket dibuat. Overdue terjadi
                                jika batas tersebut terlewati dan tiket belum
                                selesai atau ditutup.
                            </p>
                        </div>
                    )}
                    {ticket.sla?.resolution_due_at && (
                        <p className="mt-2 text-sm">
                            Batas saat ini:{' '}
                            <span className="font-semibold">
                                {new Date(
                                    ticket.sla.resolution_due_at,
                                ).toLocaleString('id-ID')}
                            </span>
                        </p>
                    )}
                </div>
                {canExtendSla && ticket.sla && (
                    <form
                        onSubmit={submitSlaExtension}
                        className="grid gap-4 rounded-xl border p-5 md:grid-cols-[180px_1fr_auto]"
                    >
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Perpanjang (hari)
                            </label>
                            <Input
                                type="number"
                                min="1"
                                max="30"
                                required
                                value={slaExtension.data.additional_days}
                                onChange={(event) =>
                                    slaExtension.setData(
                                        'additional_days',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Alasan perpanjangan
                            </label>
                            <Input
                                required
                                value={slaExtension.data.reason}
                                onChange={(event) =>
                                    slaExtension.setData(
                                        'reason',
                                        event.target.value,
                                    )
                                }
                                placeholder="Contoh: Menunggu sparepart atau pihak eksternal"
                            />
                        </div>
                        <div className="flex items-end">
                            <Button
                                type="submit"
                                disabled={
                                    slaExtension.processing ||
                                    !slaExtension.data.additional_days ||
                                    !slaExtension.data.reason
                                }
                            >
                                Perpanjang waktu
                            </Button>
                        </div>
                    </form>
                )}
                {canAssign && (
                    <form
                        onSubmit={updateAssignment}
                        className="flex flex-wrap items-end gap-3 rounded-xl border p-5"
                    >
                        <div className="min-w-64 flex-1 space-y-2">
                            <label className="text-sm font-medium">
                                Tugaskan petugas IT
                            </label>
                            <select
                                className="w-full rounded-md border border-input bg-background px-3 py-2"
                                value={assignment.data.assigned_to}
                                onChange={(event) =>
                                    assignment.setData(
                                        'assigned_to',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Pilih petugas IT</option>
                                {itUsers.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <Button
                            type="submit"
                            disabled={
                                assignment.processing ||
                                !assignment.data.assigned_to
                            }
                        >
                            Tugaskan
                        </Button>
                    </form>
                )}
                {auth.user.id === ticket.requester_id &&
                    ticket.status === 'closed' &&
                    !ticket.rating && (
                        <form
                            onSubmit={submitRating}
                            className="space-y-3 rounded-xl border p-5"
                        >
                            <h2 className="text-lg font-semibold">
                                Beri rating kinerja petugas
                            </h2>
                            <div className="flex flex-wrap gap-2">
                                {[1, 2, 3, 4, 5].map((score) => (
                                    <button
                                        type="button"
                                        key={score}
                                        onClick={() =>
                                            rating.setData(
                                                'score',
                                                String(score),
                                            )
                                        }
                                        className={`size-10 rounded-full border text-lg transition ${
                                            Number(rating.data.score) >= score
                                                ? 'border-yellow-400 bg-yellow-400 text-yellow-950'
                                                : 'hover:bg-muted'
                                        }`}
                                        aria-label={`${score} bintang`}
                                    >
                                        ★
                                    </button>
                                ))}
                            </div>
                            <textarea
                                className="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Catatan untuk petugas (opsional)"
                                value={rating.data.feedback}
                                onChange={(event) =>
                                    rating.setData(
                                        'feedback',
                                        event.target.value,
                                    )
                                }
                            />
                            <Button
                                type="submit"
                                disabled={
                                    rating.processing || !rating.data.score
                                }
                            >
                                Kirim rating
                            </Button>
                        </form>
                    )}
                <div className="grid gap-6 md:grid-cols-2">
                    {statusOptions.length > 0 && (
                        <form
                            onSubmit={updateStatus}
                            className="space-y-3 rounded-xl border p-6"
                        >
                            <h2 className="text-lg font-semibold">
                                Perbarui status
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                Admin IT mengelola alur penanganan. Pelapor
                                menutup tiket setelah selesai atau membuka
                                kembali jika belum sesuai.
                            </p>
                            <select
                                className="w-full rounded-md border border-input bg-background px-3 py-2"
                                value={status.data.status}
                                onChange={(event) => {
                                    status.setData({
                                        status: event.target.value,
                                        solution: '',
                                        reason: '',
                                    });
                                }}
                            >
                                <option value="">Pilih status baru</option>
                                {statusOptions.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            {['resolved'].includes(status.data.status) && (
                                <textarea
                                    required
                                    className="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Tuliskan solusi penanganan..."
                                    value={status.data.solution}
                                    onChange={(event) =>
                                        status.setData(
                                            'solution',
                                            event.target.value,
                                        )
                                    }
                                />
                            )}
                            {['pending_user', 'on_hold', 'reopened'].includes(
                                status.data.status,
                            ) && (
                                <textarea
                                    required
                                    className="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Tuliskan alasan perubahan..."
                                    value={status.data.reason}
                                    onChange={(event) =>
                                        status.setData(
                                            'reason',
                                            event.target.value,
                                        )
                                    }
                                />
                            )}
                            <Button
                                disabled={
                                    status.processing || !status.data.status
                                }
                            >
                                Simpan status
                            </Button>
                            <InputError
                                message={
                                    status.errors.status ??
                                    status.errors.solution ??
                                    status.errors.reason
                                }
                            />
                            {ticket.histories.filter((history) =>
                                [
                                    'status_changed',
                                    'reopened',
                                    'assigned',
                                    'sla_extended',
                                ].includes(history.event),
                            ).length > 0 && (
                                <div className="mt-4 space-y-2 border-t pt-4">
                                    <p className="text-sm font-semibold">
                                        Riwayat perubahan status
                                    </p>
                                    {ticket.histories
                                        .filter((history) =>
                                            [
                                                'status_changed',
                                                'reopened',
                                                'assigned',
                                                'sla_extended',
                                            ].includes(history.event),
                                        )
                                        .map((history) => (
                                            <div
                                                key={history.id}
                                                className="rounded-md bg-muted/50 px-3 py-2 text-xs"
                                            >
                                                <p className="font-medium">
                                                    {history.event ===
                                                    'assigned'
                                                        ? 'Tiket ditugaskan'
                                                        : history.event ===
                                                            'reopened'
                                                          ? 'Tiket dibuka kembali'
                                                          : history.event ===
                                                              'sla_extended'
                                                            ? 'Perpanjangan waktu penyelesaian'
                                                            : 'Status diperbarui'}
                                                </p>
                                                {history.old_value?.status &&
                                                    history.new_value
                                                        ?.status && (
                                                        <p className="mt-1 font-medium">
                                                            {statusLabels[
                                                                history
                                                                    .old_value
                                                                    .status
                                                            ] ??
                                                                history
                                                                    .old_value
                                                                    .status}{' '}
                                                            →{' '}
                                                            {statusLabels[
                                                                history
                                                                    .new_value
                                                                    .status
                                                            ] ??
                                                                history
                                                                    .new_value
                                                                    .status}
                                                        </p>
                                                    )}
                                                <p className="text-muted-foreground">
                                                    {history.user?.name ??
                                                        'Sistem'}{' '}
                                                    ·{' '}
                                                    {new Date(
                                                        history.created_at,
                                                    ).toLocaleString('id-ID')}
                                                    {history.description
                                                        ? ` · ${history.description}`
                                                        : ''}
                                                </p>
                                            </div>
                                        ))}
                                </div>
                            )}
                        </form>
                    )}
                    <div className="space-y-3 rounded-xl border p-6">
                        <h2 className="text-lg font-semibold">Lampiran</h2>
                        {ticket.attachments
                            .filter((attachment) => !attachment.comment_id)
                            .map((attachment) => (
                                <a
                                    key={attachment.id}
                                    className="group flex items-center gap-3 rounded-lg border bg-background p-3 text-sm transition hover:border-primary hover:bg-muted/40"
                                    href={`/tickets/${ticket.id}/attachments/${attachment.id}`}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-xs font-bold text-primary">
                                        FILE
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate font-medium group-hover:text-primary">
                                            {attachment.original_name}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {(
                                                attachment.size /
                                                1024 /
                                                1024
                                            ).toFixed(1)}{' '}
                                            MB · Klik untuk membuka
                                        </span>
                                    </span>
                                </a>
                            ))}
                        {ticket.attachments.filter(
                            (attachment) => !attachment.comment_id,
                        ).length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada lampiran.
                            </p>
                        )}
                    </div>
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
                                {item.attachments?.length > 0 && (
                                    <div className="mt-3 space-y-2 border-t pt-3">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Lampiran pada balasan ini
                                        </p>
                                        {item.attachments.map((attachment) => (
                                            <a
                                                key={attachment.id}
                                                className="flex items-center gap-3 rounded-lg border bg-background px-3 py-2 text-sm transition hover:border-primary hover:bg-muted/40"
                                                href={`/tickets/${ticket.id}/attachments/${attachment.id}`}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <span className="flex size-9 items-center justify-center rounded-md bg-primary/10 text-[10px] font-bold text-primary">
                                                    FILE
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate font-medium">
                                                        {
                                                            attachment.original_name
                                                        }
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {(
                                                            attachment.size /
                                                            1024 /
                                                            1024
                                                        ).toFixed(1)}{' '}
                                                        MB
                                                    </span>
                                                </span>
                                            </a>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                        {ticket.comments.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada komentar.
                            </p>
                        )}
                    </div>
                    {ticket.histories
                        .filter((history) => history.event === 'created')
                        .map((history) => (
                            <p
                                key={history.id}
                                className="mt-6 text-sm text-muted-foreground"
                            >
                                {history.event}{' '}
                                {history.description ?? 'Tiket dibuat.'} ·{' '}
                                {history.user?.name ?? 'Sistem'} ·{' '}
                                {new Date(history.created_at).toLocaleString(
                                    'id-ID',
                                )}
                            </p>
                        ))}
                    <form onSubmit={submit} className="mt-6 space-y-3">
                        <textarea
                            placeholder="Tulis komentar..."
                            className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={comment.data.body}
                            onChange={(
                                e: React.ChangeEvent<HTMLTextAreaElement>,
                            ) => comment.setData('body', e.target.value)}
                        />
                        {canCommentInternal && (
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
                        )}
                        <InputError
                            message={
                                comment.errors.body ??
                                comment.errors.attachments
                            }
                        />
                        <Input
                            key={comment.data.attachments
                                .map(
                                    (file) =>
                                        `${file.name}-${file.lastModified}`,
                                )
                                .join('|')}
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,video/mp4,video/quicktime,video/webm"
                            onChange={(event) => {
                                const files = Array.from(
                                    event.target.files ?? [],
                                );
                                const oversized = files.find(
                                    (file) => file.size > 50 * 1024 * 1024,
                                );
                                if (oversized) {
                                    toast.error(
                                        `${oversized.name} melebihi batas 50 MB`,
                                    );
                                    event.target.value = '';
                                    return;
                                }
                                comment.setData(
                                    'attachments',
                                    files.slice(0, 5),
                                );
                            }}
                        />
                        <p className="text-xs text-muted-foreground">
                            Lampiran (JPG, PNG, PDF, DOC, XLS, TXT, MP4, MOV,
                            WebM; maks. 50 MB), maksimal 5 file.
                        </p>
                        {comment.data.attachments.length > 0 && (
                            <ul className="space-y-2 text-sm">
                                {comment.data.attachments.map((file, index) => (
                                    <li
                                        key={`${file.name}-${file.lastModified}`}
                                        className="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2"
                                    >
                                        <span className="truncate">
                                            {file.name}
                                        </span>
                                        <button
                                            type="button"
                                            className="ml-3 text-destructive hover:underline"
                                            onClick={() =>
                                                comment.setData(
                                                    'attachments',
                                                    comment.data.attachments.filter(
                                                        (_, fileIndex) =>
                                                            fileIndex !== index,
                                                    ),
                                                )
                                            }
                                        >
                                            Hapus
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <Button disabled={comment.processing}>
                            Kirim komentar
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}
