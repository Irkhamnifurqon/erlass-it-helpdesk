<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ReportController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('tickets/export/pdf', [TicketController::class, 'downloadAllPdf'])->name('tickets.export.pdf');
    Route::get('tickets/{ticket}/export/pdf', [TicketController::class, 'downloadPdf'])->name('tickets.export.pdf.single');
    Route::post('tickets/{ticket}/assignments', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('tickets/{ticket}/status', [TicketController::class, 'status'])->name('tickets.status');
    Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comment');
    Route::post('tickets/{ticket}/rating', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::post('tickets/{ticket}/sla-extension', [TicketController::class, 'extendSla'])->name('tickets.sla-extension');
    Route::post('notifications/{notification}/read', [TicketController::class, 'markNotificationRead'])->name('notifications.read');
    Route::get('tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::middleware('helpdesk:Administrator')->prefix('admin')->group(function () {
        Route::get('organization', [OrganizationController::class, 'index'])->name('admin.organization');
        Route::post('departments', [OrganizationController::class, 'storeDepartment'])->name('admin.departments.store');
        Route::post('positions', [OrganizationController::class, 'storePosition'])->name('admin.positions.store');
        Route::patch('positions/{position}/toggle', [OrganizationController::class, 'togglePosition'])->name('admin.positions.toggle');
        Route::post('users', [OrganizationController::class, 'storeUser'])->name('admin.users.store');
        Route::get('reports', [ReportController::class, 'index'])->name('admin.reports');
        Route::get('reports/excel', [ReportController::class, 'excel'])->name('admin.reports.excel');
        Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('admin.reports.pdf');
    });
});

require __DIR__.'/settings.php';
