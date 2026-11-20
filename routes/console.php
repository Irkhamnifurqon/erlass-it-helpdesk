<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::command('tickets:notify-overdue')->hourly()->description('Notify overdue helpdesk tickets');
Schedule::command('tickets:auto-close-resolved')->daily()->description('Auto-close resolved tickets after three days');
