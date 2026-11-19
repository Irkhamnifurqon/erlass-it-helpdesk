<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return Ticket::query()->whereKey($ticket->id)->visibleTo($user)->exists();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole('Administrator', 'Supervisor IT');
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole('Administrator', 'Supervisor IT')
            || ($user->hasRole('IT Support') && $ticket->activeAssignment()->where('assigned_to', $user->id)->exists())
            || ($user->id === $ticket->requester_id && in_array($ticket->status, ['resolved', 'closed'], true));
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function rate(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->requester_id && $ticket->status === 'closed';
    }
}
