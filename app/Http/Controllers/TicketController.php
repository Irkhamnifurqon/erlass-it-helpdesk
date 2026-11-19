<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\TicketRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    private const TRANSITIONS = [
        'open' => ['assigned'],
        'assigned' => ['in_progress'],
        'in_progress' => ['pending', 'resolved'],
        'pending' => ['in_progress', 'resolved'],
        'resolved' => ['closed', 'reopened'],
        'closed' => ['reopened'],
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
            'tickets' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tickets/create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::orderBy('sort_order')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
        ]);

        $ticket = DB::transaction(function () use ($data, $request) {
            $ticket = Ticket::create($data + ['requester_id' => $request->user()->id, 'status' => 'open', 'ticket_number' => 'PENDING']);
            $ticket->update(['ticket_number' => sprintf('ERL-%s-%05d', now()->year, $ticket->id)]);

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
            return $ticket;
        });

        return to_route('tickets.show', $ticket)->with('success', "Tiket {$ticket->ticket_number} berhasil dibuat.");
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        Gate::authorize('view', $ticket);
        $ticket->load(['category', 'priority', 'requester', 'activeAssignment.assignee', 'comments.user', 'histories.user', 'sla', 'rating']);
        $comments = $ticket->comments->filter(fn ($comment) => ! $comment->is_internal || $this->canSeeInternalNotes($request->user()));
        return Inertia::render('tickets/show', ['ticket' => $ticket->setRelation('comments', $comments->values())]);
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);
        $data = $request->validate(['assigned_to' => ['required', 'exists:users,id']]);
        $assignee = \App\Models\User::findOrFail($data['assigned_to']);
        abort_unless($assignee->is_active && $assignee->hasRole('IT Support'), 422, 'Petugas harus aktif dan memiliki role IT Support.');

        DB::transaction(function () use ($ticket, $request, $assignee) {
            $ticket->assignments()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
            $ticket->assignments()->create(['assigned_to' => $assignee->id, 'assigned_by' => $request->user()->id, 'assigned_at' => now()]);
            $old = $ticket->status;
            if ($old === 'open') $ticket->update(['status' => 'assigned']);
            $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'assigned', 'old_value' => ['status' => $old], 'new_value' => ['assignee_id' => $assignee->id], 'description' => "Ditugaskan kepada {$assignee->name}."]);
        });
        return back()->with('success', 'Assignment berhasil disimpan.');
    }

    public function status(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('updateStatus', $ticket);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(self::TRANSITIONS))], 'solution' => ['nullable', 'string', 'max:10000'], 'reason' => ['nullable', 'string', 'max:2000']]);
        $target = $data['status'];
        $actor = $request->user();
        $allowed = $this->isManager($request)
            || ($actor->hasRole('IT Support') && (int) $ticket->activeAssignment?->assigned_to === $actor->id && in_array($target, self::TRANSITIONS[$ticket->status] ?? [], true))
            || ($ticket->requester_id === $actor->id && in_array($target, ['closed', 'reopened'], true));
        abort_unless($allowed, 403, 'Anda tidak berwenang melakukan transisi ini.');
        if (in_array($target, ['pending', 'reopened'], true)) abort_if(blank($data['reason'] ?? null), 422, 'Alasan wajib diisi.');
        if ($target === 'resolved') abort_if(blank($data['solution'] ?? null), 422, 'Solusi wajib diisi.');

        $old = $ticket->status;
        $ticket->update(['status' => $target, 'solution' => $data['solution'] ?? $ticket->solution, 'resolved_at' => $target === 'resolved' ? now() : $ticket->resolved_at, 'closed_at' => $target === 'closed' ? now() : $ticket->closed_at]);
        $ticket->histories()->create(['user_id' => $actor->id, 'event' => $target === 'reopened' ? 'reopened' : 'status_changed', 'old_value' => ['status' => $old], 'new_value' => ['status' => $target], 'description' => $data['reason'] ?? null]);
        if ($target === 'resolved' && $ticket->sla) $ticket->sla->update(['resolved_at' => now()]);
        return back()->with('success', 'Status tiket diperbarui.');
    }

    public function comment(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('comment', $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000'], 'is_internal' => ['boolean']]);
        abort_if(($data['is_internal'] ?? false) && ! $this->canSeeInternalNotes($request->user()), 403);
        $ticket->comments()->create(['user_id' => $request->user()->id, ...$data]);
        $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'comment_added']);
        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function rate(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('rate', $ticket);
        $data = $request->validate(['score' => ['required', 'integer', 'between:1,5'], 'feedback' => ['nullable', 'string', 'max:2000']]);
        TicketRating::updateOrCreate(['ticket_id' => $ticket->id], $data + ['user_id' => $request->user()->id]);
        $ticket->histories()->create(['user_id' => $request->user()->id, 'event' => 'rating_submitted']);
        return back()->with('success', 'Penilaian tersimpan.');
    }

    private function canSeeInternalNotes(\App\Models\User $user): bool { return $user->hasAnyRole('Administrator', 'Supervisor IT', 'IT Support'); }
    private function isManager(Request $request): bool { return $request->user()->hasRole('Administrator', 'Supervisor IT'); }
}
