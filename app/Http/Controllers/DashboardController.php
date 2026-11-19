<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $tickets = Ticket::query()->visibleTo($user);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'role' => $user->roles()->pluck('name')->first() ?? 'Karyawan',
            'department' => $user->department?->name,
            'stats' => [
                'total' => (clone $tickets)->count(),
                'open' => (clone $tickets)->whereIn('status', ['open', 'assigned'])->count(),
                'inProgress' => (clone $tickets)->whereIn('status', ['in_progress', 'pending'])->count(),
                'resolved' => (clone $tickets)->whereIn('status', ['resolved', 'closed'])->count(),
            ],
            'recentTickets' => $tickets->with(['category', 'priority'])->latest()->limit(5)->get(),
        ]);
    }
}
