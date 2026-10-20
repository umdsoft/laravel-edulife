<?php

use App\Http\Controllers\Admin\Exam\AiUsageController;
use App\Http\Controllers\Teacher\Exam\AiSourceController;
use App\Http\Controllers\Telegram\MiniAppController;
use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| "Shanba imtihoni" integrations — stage 4 (AI drafts), stage 6 (Telegram)
|--------------------------------------------------------------------------
| See docs/exam/INTEGRATIONS.md.
*/

// --- stage 4: AI qoralamalar -------------------------------------------
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin/exam')->name('admin.exam.')->group(function () {
    Route::get('/ai-drafts', [AiUsageController::class, 'index'])->name('ai-drafts.index');
    Route::post('/ai-drafts', [AiUsageController::class, 'store'])->middleware('throttle:10,1')->name('ai-drafts.store');
});

Route::middleware(['auth', 'role:teacher'])->prefix('teacher/exam')->name('teacher.exam.')->group(function () {
    Route::get('/ai-drafts', [AiSourceController::class, 'index'])->name('ai-drafts.index');
    Route::post('/ai-drafts/run', [AiSourceController::class, 'run'])->middleware('throttle:10,1')->name('ai-drafts.run');
    Route::post('/ai-drafts/sources', [AiSourceController::class, 'store'])->middleware('throttle:20,1')->name('ai-drafts.sources.store');
    Route::patch('/ai-drafts/sources/{document}/toggle', [AiSourceController::class, 'toggle'])->name('ai-drafts.sources.toggle');
    Route::delete('/ai-drafts/sources/{document}', [AiSourceController::class, 'destroy'])->name('ai-drafts.sources.destroy');
});

// --- stage 6: Telegram bot + Mini App ------------------------------------
// Webhook: no session/CSRF (Telegram can not send them); authenticated by the
// X-Telegram-Bot-Api-Secret-Token header, rate limited per IP.
Route::withoutMiddleware(['web'])->group(function () {
    Route::post('/telegram/webhook', WebhookController::class)->middleware('throttle:600,1')->name('telegram.webhook');
});

Route::get('/telegram/app', [MiniAppController::class, 'show'])->name('telegram.app');
Route::post('/telegram/app/auth', [MiniAppController::class, 'auth'])->middleware('throttle:20,1')->name('telegram.app.auth');
Route::get('/telegram/link', [MiniAppController::class, 'link'])->middleware(['auth', 'throttle:10,1'])->name('telegram.link');
