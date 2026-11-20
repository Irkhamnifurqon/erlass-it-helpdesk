<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $tickets = Ticket::query()->visibleTo($user);
        $statusCounts = (clone $tickets)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $categoryCounts = (clone $tickets)
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->select('categories.name', DB::raw('COUNT(*) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $pendingInvitations = TeamInvitation::query()
            ->with(['team', 'inviter'])
            ->where('email', $user->email)
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter?->name,
                'team' => [
                    'name' => $invitation->team?->name,
                    'slug' => $invitation->team?->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'role' => $user->roles()->pluck('name')->first() ?? 'Karyawan',
            'department' => $user->department?->name,
            'stats' => [
                'total' => (clone $tickets)->count(),
                'open' => (clone $tickets)->whereIn('status', ['open', 'assigned'])->count(),
                'inProgress' => (clone $tickets)->whereIn('status', ['in_progress', 'pending'])->count(),
                'resolved' => (clone $tickets)->whereIn('status', ['resolved', 'closed'])->count(),
                'overdue' => (clone $tickets)->whereNotIn('status', ['resolved', 'closed'])->whereHas('sla', fn ($query) => $query->where('resolution_due_at', '<', now()))->count(),
            ],
            'recentTickets' => $tickets->with(['category', 'priority'])->latest()->limit(5)->get(),
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
