<?php

use App\Http\Controllers\Auth\TelegramLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website login through the Telegram bot (agent L) — docs/auth/TELEGRAM_LOGIN.md
|--------------------------------------------------------------------------
| JSON endpoints of the login / register pages; web group (session + CSRF), guests only.
| Rate limiters: App\Services\Auth\TelegramLoginRateLimits.
*/

Route::middleware('guest')->prefix('login')->name('login.')->group(function () {
    Route::post('/phone', [TelegramLoginController::class, 'sendCode'])->middleware('throttle:telegram-login-send')->name('phone');
    Route::post('/phone/verify', [TelegramLoginController::class, 'verify'])->middleware('throttle:telegram-login-verify')->name('phone.verify');
    Route::post('/telegram', [TelegramLoginController::class, 'startLink'])->middleware('throttle:telegram-login-link')->name('telegram');
    Route::post('/telegram/status', [TelegramLoginController::class, 'status'])->middleware('throttle:telegram-login-status')->name('telegram.status');
});

// The Telegram side: the bot's web_app button opens the Mini App, which confirms the waiting browser
// (initData HMAC on every call, CSRF-exempt like /telegram/app/auth — bootstrap/app.php).
Route::get('/telegram/app/login', [\App\Http\Controllers\Telegram\MiniAppLoginController::class, 'show'])->name('telegram.app.login');
Route::post('/telegram/app/login', [\App\Http\Controllers\Telegram\MiniAppLoginController::class, 'confirm'])->middleware('throttle:telegram-app')->name('telegram.app.login.confirm');

// Local emulator "Bot chat" (/telegram/dev): the messages the bot would send + playing the Telegram user.
// Every action answers 404 unless APP_ENV=local (DevBotChatController).
Route::prefix('telegram/dev')->name('telegram.dev.')->group(function () {
    Route::get('/outbox', [\App\Http\Controllers\Telegram\DevBotChatController::class, 'outbox'])->name('outbox');
    Route::delete('/outbox', [\App\Http\Controllers\Telegram\DevBotChatController::class, 'clear'])->name('outbox.clear');
    Route::post('/update', [\App\Http\Controllers\Telegram\DevBotChatController::class, 'update'])->name('update');
});
