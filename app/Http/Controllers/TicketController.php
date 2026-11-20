<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\TicketRating;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Models\IssueType;
use App\Notifications\TicketNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Dompdf\Dompdf;
use Dompdf\Options;

class TicketController extends Controller
{
    private const TRANSITIONS = [
        'open' => ['assigned'],
        'assigned' => ['in_progress'],
        'in_progress' => ['pending_user', 'on_hold', 'resolved'],
        'pending_user' => ['in_progress', 'on_hold', 'resolved'],
        'on_hold' => ['in_progress', 'resolved'],
        'pending' => ['in_progress', 'resolved'],
        'resolved' => ['closed', 'reopened'],
        'closed' => [],
        'reopened' => ['in_progress'],
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Ticket::query()->with(['category', 'priority', 'requester', 'activeAssignment.assignee']);
        $query->visibleTo($user);
        $query->when($request->string('q')->toString(), fn ($q, $term) => $q->where(fn ($inner) => $inner
            ->where('ticket_number', 'like', "%{$term}%")->orWhere('title', 'like', "%{$term}%")));
        $query->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status));

        return Inertia::render('tickets/index', [
            'tickets' => $query->latest()->paginate(15)->withQueryString()->through(fn (Ticket $ticket) => [
                ...$ticket->toArray(),
                'recent_activity' => $this->hasUnreadTicketActivity($ticket, $user),
            ]),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tickets/create', [
            'categories' => Category::with(['subcategories' => fn ($q) => $q->where('is_active', true), 'issueTypes' => fn ($q) => $q->where('is_active', true)])
                ->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::orderBy('sort_order')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['required', 'exists:categories,id'],
            'issue_type_id' => ['nullable', 'exists:issue_types,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,mp4,mov,webm', 'max:51200'],
        ]);
        if (! empty($data['issue_type_id'])) {
            $issueType = IssueType::query()
                ->whereKey($data['issue_type_id'])
                ->where('category_id', $data['category_id'])
                ->where('is_active', true)
                ->firstOrFail();
            if ($issueType->name !== 'Lainnya') {
                $data['title'] = $issueType->name;
            }
            abort_if($issueType->name === 'Lainnya' && blank($data['title']), 422, 'Judul kendala wajib diisi.');
            $data['subcategory_id'] = $issueType->subcategory_id;
        } else {
            abort_if(blank($data['title'] ?? null), 422, 'Judul kendala wajib diisi.');
        }

        $ticket = DB::transaction(function () use ($data, $request) {
            $files = $request->file('attachments', []);
            unset($data['attachments']);
            $ticket = Ticket::create($data + ['requester_id' => $request->user()->id, 'status' => 'open', 'ticket_number' => 'PENDING']);
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();
            $sequence = Ticket::whereBetween('created_at', [$monthStart, $monthEnd])->lockForUpdate()->count();
            $ticket->update([
                'ticket_number' => sprintf(
                    'ERL-IT-%s-%02d-%03d',
                    now()->format('Y'),
                    now()->format('m'),
                    $sequence,
                ),
            ]);

            $policy = $ticket->category()->with('slaPolicy')->first()?->slaPolicy;
            if ($policy?->is_active) {
                $ticket->sla()->create([
                    'response_target_minutes' => $policy->response_target_minutes,
                    'resolution_target_minutes' => $policy->resolution_target_minutes,
                    'response_due_at' => now()->addMinutes($policy->response_target_minutes),
                    'resolution_due_at' => now()->addMinutes($policy->resolution_target_minutes),
                ]);
            }
            $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'created', 'description' => 'Tiket dibuat.']);
            foreach ($files as $file) {
                $this->storeAttachment($ticket, $request->user(), $file);
            }
            return $ticket;
        });
        $this->notifyItTeam($ticket, 'created', "Tiket {$ticket->ticket_number} berhasil dibuat dan masuk antrean IT.");
        $request->user()->notify(new TicketNotification($ticket, 'created', "Tiket {$ticket->ticket_number} berhasil dibuat."));

        return to_route('tickets.show', $ticket)->with('success', "Tiket {$ticket->ticket_number} berhasil dibuat.");
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        Gate::authorize('view', $ticket);
        DB::table('ticket_reads')->updateOrInsert(
            ['ticket_id' => $ticket->id, 'user_id' => $request->user()->id],
            ['viewed_at' => now(), 'updated_at' => now()],
        );
        $ticket->load(['category', 'subcategory', 'issueType', 'priority', 'requester', 'activeAssignment.assignee', 'comments.user', 'comments.attachments', 'histories.user', 'sla', 'rating', 'attachments.user']);
        if ($request->filled('month')) {
            $ticket->setRelation('histories', $ticket->histories->filter(fn ($history) => $history->created_at?->format('Y-m') === $request->string('month')->toString())->values());
        }
        $comments = $ticket->comments->filter(fn ($comment) => ! $comment->is_internal || $this->canSeeInternalNotes($request->user()));
        return Inertia::render('tickets/show', [
            'ticket' => $ticket->setRelation('comments', $comments->values()),
            'statusOptions' => $this->statusOptions($request->user(), $ticket),
            'canCommentInternal' => $this->canSeeInternalNotes($request->user()),
            'canAssign' => $request->user()->hasRole('Administrator'),
            'canExtendSla' => $request->user()->hasRole('Administrator') && ! in_array($ticket->status, ['resolved', 'closed'], true),
            'itUsers' => $request->user()->hasRole('Administrator')
                ? User::where('is_active', true)
                    ->whereHas('department', fn ($query) => $query->where('code', 'IT'))
                    ->whereHas('roles', fn ($query) => $query->where('name', 'Administrator'))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
        ]);
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);
        $data = $request->validate(['assigned_to' => ['required', 'exists:users,id']]);
        $assignee = \App\Models\User::findOrFail($data['assigned_to']);
        abort_unless(
            $assignee->is_active
            && $assignee->hasRole('Administrator')
            && $assignee->department?->code === 'IT',
            422,
            'Petugas harus aktif, memiliki role Administrator, dan berasal dari departemen IT.',
        );

        DB::transaction(function () use ($ticket, $request, $assignee) {
            $ticket->assignments()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
            $ticket->assignments()->create(['assigned_to' => $assignee->id, 'assigned_by' => $request->user()->id, 'assigned_at' => now()]);
            $old = $ticket->status;
            if ($old === 'open') $ticket->update(['status' => 'assigned']);
            $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'assigned', 'old_value' => ['status' => $old], 'new_value' => ['assignee_id' => $assignee->id], 'description' => "Ditugaskan kepada {$assignee->name}."]);
            $assignee->notify(new TicketNotification($ticket, 'assigned', "Tiket {$ticket->ticket_number} ditugaskan kepada Anda."));
            $ticket->touch();
        });
        $request->user()->notify(new TicketNotification($ticket, 'assigned', "Assignment tiket {$ticket->ticket_number} berhasil disimpan."));
        return back()->with('success', 'Assignment berhasil disimpan.');
    }

    public function status(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('updateStatus', $ticket);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::TRANSITIONS))],
            'solution' => ['nullable', 'required_if:status,resolved', 'string', 'max:10000'],
            'reason' => ['nullable', 'required_if:status,pending_user,on_hold,reopened', 'string', 'max:2000'],
        ]);
        $target = $data['status'];
        $actor = $request->user();
        $allowed = ($actor->hasRole('Administrator') && in_array($target, self::TRANSITIONS[$ticket->status] ?? [], true))
            || ($ticket->requester_id === $actor->id
                && (
                    ($ticket->status === 'resolved' && $target === 'closed')
                    || ($ticket->status === 'resolved' && $target === 'reopened')
                ));
        abort_unless($allowed, 403, 'Anda tidak berwenang melakukan transisi ini.');

        $old = $ticket->status;
        $ticket->update([
            'status' => $target,
            'solution' => $data['solution'] ?? $ticket->solution,
            'resolved_at' => $target === 'resolved' ? now() : ($target === 'reopened' ? null : $ticket->resolved_at),
            'closed_at' => $target === 'closed' ? now() : ($target === 'reopened' ? null : $ticket->closed_at),
        ]);
        $labels = [
            'open' => 'Terbuka',
            'assigned' => 'Ditugaskan',
            'in_progress' => 'Sedang ditangani',
            'pending_user' => 'Menunggu pelapor',
            'on_hold' => 'Ditunda',
            'resolved' => 'Selesai ditangani',
            'closed' => 'Ditutup',
            'reopened' => 'Dibuka kembali',
            'pending' => 'Menunggu',
        ];
        $description = $target === 'reopened'
            ? "Status tiket dibuka kembali dari {$labels[$old]} menjadi {$labels[$target]}."
            : "Status tiket diperbarui dari {$labels[$old]} menjadi {$labels[$target]}.";
        if (! blank($data['reason'] ?? null)) {
            $description .= ' Alasan: '.$data['reason'];
        }
        $ticket->histories()->create(['user_id' => $actor->id, 'event' => $target === 'reopened' ? 'reopened' : 'status_changed', 'old_value' => ['status' => $old], 'new_value' => ['status' => $target], 'description' => $description]);
        if ($target === 'resolved' && $ticket->sla) $ticket->sla->update(['resolved_at' => now()]);
        $ticket->touch();
        $recipients = collect([$ticket->requester, $ticket->activeAssignment?->assignee])->merge(User::whereHas('roles', fn ($q) => $q->where('name', 'Administrator'))->get())->filter()->unique('id');
        Notification::send($recipients, new TicketNotification($ticket, 'status_changed', "Status tiket {$ticket->ticket_number} berubah menjadi {$target}."));
        return back()->with('success', 'Status tiket diperbarui.');
    }

    public function comment(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('comment', $ticket);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,mp4,mov,webm', 'max:51200'],
        ]);
        abort_if(($data['is_internal'] ?? false) && ! $this->canSeeInternalNotes($request->user()), 403);
        $files = $request->file('attachments', []);
        unset($data['attachments']);
        $comment = $ticket->comments()->create(['user_id' => $request->user()->id, ...$data]);
        foreach ($files as $file) {
            $this->storeAttachment($ticket, $request->user(), $file, $comment);
        }
        $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'comment_added', 'description' => 'Percakapan baru ditambahkan.']);
        $ticket->touch();
        $this->notifyTicketParticipants($ticket, 'comment_added', "Percakapan baru pada tiket {$ticket->ticket_number}.");
        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function extendSla(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);
        abort_if(in_array($ticket->status, ['resolved', 'closed'], true), 422, 'SLA tiket yang sudah selesai atau ditutup tidak dapat diperpanjang.');

        $data = $request->validate([
            'additional_days' => ['required', 'integer', 'between:1,30'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $sla = $ticket->sla;
        abort_if(! $sla, 422, 'Tiket ini belum memiliki data SLA.');

        $oldDueAt = $sla->resolution_due_at;
        $baseDueAt = $oldDueAt && $oldDueAt->isFuture() ? $oldDueAt : now();
        $newDueAt = $baseDueAt->copy()->addDays((int) $data['additional_days']);
        $sla->update(['resolution_due_at' => $newDueAt]);

        $ticket->histories()->create([
            'user_id' => $request->user()->id,
            'event' => 'sla_extended',
            'old_value' => ['resolution_due_at' => $oldDueAt?->toIso8601String()],
            'new_value' => [
                'resolution_due_at' => $newDueAt->toIso8601String(),
                'additional_days' => (int) $data['additional_days'],
            ],
            'description' => "Target penyelesaian diperpanjang {$data['additional_days']} hari menjadi {$newDueAt->locale('id')->translatedFormat('d M Y, H:i')}. Alasan: {$data['reason']}",
        ]);
        $ticket->touch();
        $this->notifyTicketParticipants(
            $ticket,
            'sla_extended',
            "Target penyelesaian tiket {$ticket->ticket_number} diperpanjang.",
        );

        return back()->with('success', "Target penyelesaian diperpanjang {$data['additional_days']} hari.");
    }

    public function rate(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('rate', $ticket);
        $data = $request->validate(['score' => ['required', 'integer', 'between:1,5'], 'feedback' => ['nullable', 'string', 'max:2000']]);
        TicketRating::updateOrCreate(['ticket_id' => $ticket->id], $data + ['user_id' => $request->user()->id]);
        $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'rating_submitted', 'description' => "Pelapor memberi rating {$data['score']} dari 5."]);
        $ticket->touch();
        return back()->with('success', 'Penilaian tersimpan.');
    }

    public function downloadPdf(Request $request, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);
        $ticket->load(['category', 'subcategory', 'issueType', 'priority', 'requester.department', 'activeAssignment.assignee', 'sla', 'rating', 'histories.user', 'comments.user']);
        $pdf = $this->renderTicketPdf([$ticket], 'RINCIAN TIKET');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$ticket->ticket_number.'.pdf"',
        ]);
    }

    public function downloadAllPdf(Request $request)
    {
        abort_unless($request->user()->hasRole('Administrator'), 403);

        $query = Ticket::query()
            ->with(['category', 'subcategory', 'issueType', 'priority', 'requester.department', 'activeAssignment.assignee', 'sla', 'rating', 'histories.user', 'comments.user']);
        $query->when($request->string('q')->toString(), fn ($q, $term) => $q->where(fn ($inner) => $inner
            ->where('ticket_number', 'like', "%{$term}%")
            ->orWhere('title', 'like', "%{$term}%")));
        $query->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status));
        $tickets = $query->latest()->get();
        $pdf = $this->renderTicketPdf($tickets->all(), 'REKAP SELURUH TIKET');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-tiket-it-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    private function renderTicketPdf(array $tickets, string $title): string
    {
        $logo = base64_encode(file_get_contents(public_path('images/erlass-logo.png')));
        $labels = [
            'open' => 'Terbuka',
            'assigned' => 'Ditugaskan',
            'in_progress' => 'Sedang ditangani',
            'pending_user' => 'Menunggu pelapor',
            'on_hold' => 'Ditunda',
            'resolved' => 'Selesai ditangani',
            'closed' => 'Ditutup',
            'reopened' => 'Dibuka kembali',
            'pending' => 'Menunggu',
        ];
        $ticketCards = collect($tickets)->map(function (Ticket $ticket) use ($labels): string {
            $status = $labels[$ticket->status] ?? $ticket->status;
            $comments = $ticket->comments->map(fn ($comment) =>
                '<div class="conversation"><strong>'.e($comment->user?->name ?? 'Pengguna').'</strong>
                <span>'.e($comment->created_at?->locale('id')->translatedFormat('d/m/Y H:i') ?? '-').'</span>
                <p>'.nl2br(e($comment->body)).'</p></div>'
            )->implode('');
            $histories = $ticket->histories->map(function ($history) use ($labels): string {
                $oldStatus = $history->old_value['status'] ?? null;
                $newStatus = $history->new_value['status'] ?? null;
                $heading = $history->event === 'created'
                    ? 'Tiket dibuat'
                    : ($history->event === 'assigned' ? 'Tiket ditugaskan' : ($history->event === 'reopened' ? 'Tiket dibuka kembali' : ($oldStatus && $newStatus ? 'Perubahan status' : 'Aktivitas tiket')));
                $transition = $oldStatus && $newStatus
                    ? '<p class="transition">'.e($labels[$oldStatus] ?? $oldStatus).' <span>&rarr;</span> '.e($labels[$newStatus] ?? $newStatus).'</p>'
                    : '';
                return '<div class="history"><strong>'.e($heading).'</strong><span>'.e($history->user?->name ?? 'Sistem').' &middot; '.e($history->created_at?->locale('id')->translatedFormat('d/m/Y H:i') ?? '-').'</span>'.$transition.'<p>'.e($history->description ?? '').'</p></div>';
            })->implode('');
            if ($comments === '') $comments = '<p class="muted">Belum ada percakapan.</p>';
            if ($histories === '') $histories = '<p class="muted">Belum ada riwayat perubahan.</p>';
            $resolution = $ticket->resolved_at
                ? $ticket->resolved_at->locale('id')->translatedFormat('d/m/Y H:i')
                : 'Belum diselesaikan';

            return '<section class="ticket">
                <div class="ticket-title"><small>'.$ticket->ticket_number.'</small><h2>'.e($ticket->title).'</h2></div>
                <table class="details"><tr><td><b>Pemohon</b><strong>'.e($ticket->requester?->name ?? '-').'</strong></td><td><b>Departemen</b><strong>'.e($ticket->requester?->department?->name ?? '-').'</strong></td><td><b>Kategori</b><strong>'.e($ticket->category?->name ?? '-').'</strong></td></tr>
                <tr><td><b>Prioritas</b><strong>'.e($ticket->priority?->name ?? '-').'</strong></td><td><b>Status</b><strong>'.e($status).'</strong></td><td><b>Petugas IT</b><strong>'.e($ticket->activeAssignment?->assignee?->name ?? 'Belum ditetapkan').'</strong></td></tr>
                <tr><td><b>Tanggal dibuat</b><strong>'.e($ticket->created_at?->locale('id')->translatedFormat('d/m/Y H:i') ?? '-').'</strong></td><td colspan="2"><b>Tanggal diselesaikan</b><strong>'.e($resolution).'</strong></td></tr></table>
                <h3>Deskripsi Kendala</h3><div class="box">'.nl2br(e($ticket->description)).'</div>
                <h3>Percakapan</h3>'.$comments.'
                <h3>Riwayat Perubahan Status</h3>'.$histories.'
            </section>';
        })->implode('');
        if ($ticketCards === '') $ticketCards = '<div class="empty">Tidak ada tiket pada laporan ini.</div>';
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>
            @page { margin: 13mm 12mm 15mm 12mm; }
            body { margin:0; font-family:DejaVu Sans,sans-serif; color:#26364a; font-size:9px; }
            .header { position:fixed; top:-8mm; left:0; right:0; height:20mm; border-bottom:3px solid #2874cf; }
            .brand { float:left; width:31mm; } .brand img { width:31mm; }
            .heading { float:left; margin-left:4mm; padding-top:2mm; } .heading h1 { margin:0; color:#183b69; font-size:15px; } .heading p { margin:2mm 0 0; color:#64748b; }
            .meta { float:right; text-align:right; color:#64748b; padding-top:3mm; }
            .ticket { margin-top:9mm; border-bottom:2px solid #2874cf; padding-bottom:7mm; page-break-inside:avoid; }
            .ticket-title { margin-top:22mm; } .ticket-title small { color:#2874cf; font-weight:bold; }
            h2 { margin:1.5mm 0 4mm; color:#183b69; font-size:14px; } h3 { margin:5mm 0 2mm; color:#183b69; font-size:11px; }
            .details { width:100%; border-collapse:collapse; table-layout:fixed; } .details td { width:33.33%; border:1px solid #dbe4ef; padding:2mm; background:#f8fbff; }
            .details b { display:block; color:#64748b; margin-bottom:1mm; } .details strong { display:block; color:#26364a; }
            .box { border:1px solid #dbe4ef; padding:3mm; background:#fff; line-height:1.5; }
            .conversation,.history { border:1px solid #dbe4ef; border-left:3px solid #2874cf; background:#f8fbff; margin-bottom:2mm; padding:2.5mm 3mm; }
            .conversation strong,.history strong { display:block; color:#183b69; } .conversation span,.history span { color:#64748b; font-size:8px; }
            .conversation p,.history p { margin:2mm 0 0; } .history .transition { color:#183b69; font-weight:bold; } .history .transition span { color:#2874cf; }
            .muted { color:#94a3b8; font-style:italic; } .empty { margin-top:30mm; padding:10mm; text-align:center; border:1px dashed #cbd5e1; color:#64748b; }
            .footer { position:fixed; bottom:-10mm; left:0; right:0; height:8mm; border-top:1px solid #dbe4ef; color:#64748b; font-size:8px; padding-top:2mm; }
            .footer .left { float:left; } .footer .right { float:right; }
        </style></head><body>
            <div class="header"><div class="brand"><img src="data:image/png;base64,'.$logo.'"></div><div class="heading"><h1>Erlass IT Helpdesk</h1><p>'.e($title).'</p></div><div class="meta">Dicetak<br>'.now()->locale('id')->translatedFormat('d M Y, H:i').'</div></div>
            '.$ticketCards.'
            <div class="footer"><span class="left">PT Erlass Prokreatif Indonesia</span><span class="right">Laporan internal Petugas IT</span></div>
        </body></html>';
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private function ticketPdfDocument(array $tickets, string $title): string
    {
        $escape = static function (mixed $value): string {
            $text = preg_replace('/\s+/', ' ', (string) $value);
            $text = substr($text ?? '-', 0, 95);
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        };
        $labels = [
            'open' => 'Terbuka',
            'assigned' => 'Ditugaskan',
            'in_progress' => 'Sedang ditangani',
            'pending_user' => 'Menunggu pelapor',
            'on_hold' => 'Ditunda',
            'resolved' => 'Selesai ditangani',
            'closed' => 'Ditutup',
            'reopened' => 'Dibuka kembali',
            'pending' => 'Menunggu',
        ];

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $pageReferences = [];
        $pages = array_chunk($tickets, 4);
        if ($pages === []) {
            $pages = [[]];
        }

        foreach ($pages as $pageIndex => $pageTickets) {
            $content = "q\n0.04 0.18 0.38 rg 0 742 612 50 re f\n";
            $content .= "0.10 0.42 0.82 rg 0 728 612 14 re f Q\n";
            $content .= "BT\n/F2 18 Tf 38 760 Td (ERLASS IT HELPDESK) Tj\n";
            $content .= "/F1 9 Tf 38 747 Td (".$escape($title).") Tj\n";
            $content .= "/F1 8 Tf 410 760 Td (Dicetak: ".$escape(now()->locale('id')->translatedFormat('d M Y, H:i')).") Tj\nET\n";

            $y = 700;
            if ($pageTickets === []) {
                $content .= "BT\n/F1 10 Tf 38 650 Td (Tidak ada tiket yang dapat ditampilkan.) Tj\nET\n";
            }

            foreach ($pageTickets as $ticket) {
                $status = $labels[$ticket->status] ?? $ticket->status;
                $assignee = $ticket->activeAssignment?->assignee?->name ?? 'Belum ditugaskan';
                $department = $ticket->requester?->department?->name ?? '-';
                $resolution = $ticket->resolved_at
                    ? $ticket->resolved_at->diffInMinutes($ticket->created_at).' menit'
                    : '-';

                $content .= "q\n0.96 0.98 1.00 rg 38 ".($y - 118)." 536 126 re f\n";
                $content .= "0.10 0.42 0.82 rg 38 ".($y - 118)." 5 126 re f Q\n";
                $content .= "BT\n/F2 13 Tf 52 ".($y - 18)." Td (".$escape($ticket->ticket_number).") Tj\n";
                $content .= "/F1 8 Tf 52 ".($y - 36)." Td (Judul: ".$escape($ticket->title).") Tj\n";
                $content .= "/F1 8 Tf 52 ".($y - 52)." Td (Kategori: ".$escape($ticket->category?->name).' | Status: '.$escape($status).") Tj\n";
                $content .= "/F1 8 Tf 52 ".($y - 68)." Td (Pelapor: ".$escape($ticket->requester?->name).' | Departemen: '.$escape($department).") Tj\n";
                $content .= "/F1 8 Tf 52 ".($y - 84)." Td (Petugas: ".$escape($assignee).' | Prioritas: '.$escape($ticket->priority?->name).") Tj\n";
                $content .= "/F1 8 Tf 52 ".($y - 100)." Td (Dibuat: ".$escape($ticket->created_at?->format('d/m/Y H:i')).' | Penyelesaian: '.$escape($resolution).") Tj\nET\n";
                $y -= 138;
            }

            $content .= "q\n0.04 0.18 0.38 rg 0 18 612 24 re f Q\n";
            $content .= "BT\n/F1 7 Tf 38 27 Td (PT Erlass Prokreatif Indonesia) Tj\n";
            $content .= "/F1 7 Tf 470 27 Td (Halaman ".($pageIndex + 1)." / ".count($pages).") Tj\nET\n";

            $contentIndex = count($objects) + 1;
            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $pageObjectIndex = count($objects) + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentIndex.' 0 R >>';
            $pageReferences[] = $pageObjectIndex.' 0 R';
        }

        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.count($pageReferences).' >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    public function markNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    public function downloadAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        Gate::authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);

        return response()->file(
            Storage::disk('local')->path($attachment->stored_path),
            ['Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"'],
        );
    }

    private function storeAttachment(Ticket $ticket, User $user, \Illuminate\Http\UploadedFile $file, ?TicketComment $comment = null): TicketAttachment
    {
        $path = $file->store('ticket-attachments', 'local');
        return $ticket->attachments()->create([
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'comment_id' => $comment?->id,
        ]);
    }

    private function notifyItTeam(Ticket $ticket, string $event, string $message): void
    {
        $users = User::where('is_active', true)->whereHas('roles', fn ($query) => $query->where('name', 'Administrator'))->get();
        Notification::send($users, new TicketNotification($ticket, $event, $message));
    }

    private function notifyTicketParticipants(Ticket $ticket, string $event, string $message): void
    {
        $users = collect([$ticket->requester])
            ->merge(User::whereHas('roles', fn ($query) => $query->where('name', 'Administrator'))->get())
            ->filter()
            ->unique('id');
        Notification::send($users, new TicketNotification($ticket, $event, $message));
    }

    private function hasUnreadTicketActivity(Ticket $ticket, User $user): bool
    {
        $viewedAt = DB::table('ticket_reads')
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->value('viewed_at');

        if (! $viewedAt) {
            return true;
        }

        return $ticket->updated_at?->greaterThan(\Illuminate\Support\Carbon::parse($viewedAt)) ?? false;
    }

    private function canSeeInternalNotes(\App\Models\User $user): bool { return $user->hasRole('Administrator'); }
    /**
     * Return only transitions that the current actor can actually submit.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(User $user, Ticket $ticket): array
    {
        $labels = [
            'open' => 'Terbuka',
            'assigned' => 'Ditugaskan',
            'in_progress' => 'Sedang ditangani',
            'pending_user' => 'Menunggu pelapor',
            'on_hold' => 'Ditunda',
            'resolved' => 'Selesai ditangani',
            'closed' => 'Ditutup',
            'reopened' => 'Dibuka kembali',
            'pending' => 'Menunggu',
        ];
        $targets = self::TRANSITIONS[$ticket->status] ?? [];
        if ($user->id === $ticket->requester_id && ! $user->hasRole('Administrator')) {
            $targets = array_values(array_intersect($targets, ['closed', 'reopened']));
        } elseif (! $user->hasRole('Administrator')) {
            $targets = [];
        }

        return array_map(fn (string $target) => ['value' => $target, 'label' => $labels[$target] ?? $target], $targets);
    }
}
