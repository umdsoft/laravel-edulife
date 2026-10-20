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

// --- Telegram WebView (wave 2): exam and battle inside the Mini App ---------
// docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §3, docs/exam/INTEGRATIONS.md §2.7
Route::post('/telegram/app/reauth', [MiniAppController::class, 'reauth'])->middleware('throttle:20,1')->name('telegram.app.reauth');
// result card for WebApp.shareToStory (Telegram fetches it without our session)
Route::get('/telegram/card/{attempt}', \App\Http\Controllers\Telegram\ShareCardController::class)
    ->middleware(['signed', 'throttle:30,1'])->name('telegram.card');

Route::middleware(['auth', 'role:student'])->prefix('student/battles')->name('student.battles.')->group(function () {
    Route::post('/challenge', [\App\Http\Controllers\Student\BattleChallengeController::class, 'store'])->middleware('throttle:10,1')->name('challenge.store');
    Route::get('/challenge/{code}', [\App\Http\Controllers\Student\BattleChallengeController::class, 'show'])->name('challenge.show');
    Route::post('/challenge/{code}', [\App\Http\Controllers\Student\BattleChallengeController::class, 'accept'])->middleware('throttle:20,1')->name('challenge.accept');
    Route::get('/{battle}/state', [\App\Http\Controllers\Student\BattleChallengeController::class, 'state'])->middleware('throttle:120,1')->name('state');
});

// Local emulator: a signed initData + a mock Telegram.WebApp around the app in an iframe.
// Every action answers 404 unless APP_ENV=local (DevEmulatorController).
Route::prefix('telegram/dev')->name('telegram.dev.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Telegram\DevEmulatorController::class, 'index'])->name('index');
    Route::post('/sign', [\App\Http\Controllers\Telegram\DevEmulatorController::class, 'sign'])->name('sign');
    Route::get('/sdk.js', [\App\Http\Controllers\Telegram\DevEmulatorController::class, 'sdk'])->name('sdk');
    // TW: requestContact() for the WebView registration
    Route::post('/contact', [\App\Http\Controllers\Telegram\DevEmulatorController::class, 'contact'])->name('contact');
});

// --- TW (Telegram WebView-first): registration in the Mini App, "Saytda ochish" handoff ---
// docs/exam/TELEGRAM_AUDIT.md, docs/exam/INTEGRATIONS.md §2.8
Route::get('/telegram/app/register', [\App\Http\Controllers\Telegram\MiniAppRegistrationController::class, 'show'])->name('telegram.app.register');
Route::post('/telegram/app/register', [\App\Http\Controllers\Telegram\MiniAppRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('telegram.app.register.store');
Route::post('/telegram/app/contact', [\App\Http\Controllers\Telegram\MiniAppRegistrationController::class, 'contact'])->middleware('throttle:30,1')->name('telegram.app.contact');
Route::post('/telegram/app/site-link', [MiniAppController::class, 'siteLink'])->middleware(['auth', 'throttle:10,1'])->name('telegram.app.site-link');
Route::get('/telegram/site-login/{token}', [MiniAppController::class, 'siteLogin'])->middleware('throttle:20,1')->name('telegram.site-login');
// --- end TW ---
// --- end Telegram WebView ---------------------------------------------------
