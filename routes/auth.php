<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    
    // Rate limit: 5 attempts per minute for login
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
    
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    
    // Rate limit: 3 registration attempts per minute
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:3,1')
        ->name('register.submit');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// --- auth: website login through the Telegram bot (agent L, docs/auth/TELEGRAM_LOGIN.md) ---
require __DIR__ . '/auth_telegram.php';
// --- end auth: Telegram login ---
