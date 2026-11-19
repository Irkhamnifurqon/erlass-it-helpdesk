<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\OrganizationController;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('helpdesk.dashboard');
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('tickets/{ticket}/assignments', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('tickets/{ticket}/status', [TicketController::class, 'status'])->name('tickets.status');
    Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comment');
    Route::post('tickets/{ticket}/rating', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::middleware('helpdesk:Administrator')->prefix('admin')->group(function () {
        Route::get('organization', [OrganizationController::class, 'index'])->name('admin.organization');
        Route::post('departments', [OrganizationController::class, 'storeDepartment'])->name('admin.departments.store');
        Route::post('positions', [OrganizationController::class, 'storePosition'])->name('admin.positions.store');
        Route::patch('positions/{position}/toggle', [OrganizationController::class, 'togglePosition'])->name('admin.positions.toggle');
    });
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
