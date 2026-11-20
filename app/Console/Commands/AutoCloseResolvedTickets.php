<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;

class AutoCloseResolvedTickets extends Command
{
    protected $signature = 'tickets:auto-close-resolved';
    protected $description = 'Automatically close resolved tickets after three days';

    public function handle(): int
    {
        $count = 0;

        Ticket::query()
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', now()->subDays(3))
            ->each(function (Ticket $ticket) use (&$count): void {
                $ticket->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
                $ticket->histories()->create([
                    'event' => 'status_changed',
                    'old_value' => ['status' => 'resolved'],
                    'new_value' => ['status' => 'closed'],
                    'description' => 'Status tiket ditutup otomatis setelah 3 hari dalam status Selesai ditangani.',
                ]);
                $count++;
            });

        $this->info("{$count} tiket ditutup otomatis.");

        return self::SUCCESS;
    }
}
