<?php

use App\Http\Controllers\Guardian\DashboardController;
use App\Http\Controllers\Guardian\LinkController;
use App\Http\Controllers\Guardian\ReportController;
use Illuminate\Support\Facades\Route;

/*
 * Guardian (ota-ona) panel — exam stage 7. A guardian sees only their own,
 * teacher-approved children (App\Services\Exam\GuardianLinkService).
 */

// parents register through the Telegram bot on the common page ("Ota-onaman")
Route::get('/guardian/register', fn () => redirect('/register?role=guardian'))->name('guardian.register');

Route::middleware(['auth', 'role:guardian'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::redirect('/', '/guardian/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/children/{child}', [DashboardController::class, 'child'])->name('children.show');

    Route::post('/links', [LinkController::class, 'store'])->middleware('throttle:10,1')->name('links.store');
    Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
});
