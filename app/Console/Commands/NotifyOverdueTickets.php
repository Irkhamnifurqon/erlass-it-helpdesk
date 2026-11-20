<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyOverdueTickets extends Command
{
    protected $signature = 'tickets:notify-overdue';
    protected $description = 'Notify the IT team about tickets that exceeded their SLA';

    public function handle(): int
    {
        $recipients = User::where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'Administrator'))
            ->get();
        $count = 0;
        Ticket::query()
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereHas('sla', fn ($query) => $query->where('resolution_due_at', '<', now()))
            ->with('sla')
            ->each(function (Ticket $ticket) use ($recipients, &$count): void {
                $alreadySent = $recipients->flatMap(fn ($user) => $user->notifications()
                    ->where('type', TicketNotification::class)
                    ->where('created_at', '>=', now()->subDay())
                    ->get())
                    ->contains(fn ($notification) => ($notification->data['ticket_id'] ?? null) === $ticket->id && ($notification->data['event'] ?? null) === 'sla_breached');
                if (! $alreadySent) {
                    Notification::send($recipients, new TicketNotification($ticket, 'sla_breached', "Tiket {$ticket->ticket_number} telah melewati SLA."));
                    $ticket->histories()->create(['event' => 'sla_breached', 'description' => 'SLA tiket terlewati.']);
                    $count++;
                }
            });
        $this->info("{$count} tiket overdue dinotifikasi.");
        return self::SUCCESS;
    }
}
