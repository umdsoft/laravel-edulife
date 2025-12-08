<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateVerificationController;

// Public Certificate Verification
Route::get('/verify/{code}', [CertificateVerificationController::class, 'verify'])->name('certificate.verify');

// Home route - redirect based on auth status
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return match ($user->role) {
            'super_admin', 'admin' => redirect('/admin/dashboard'),
            'teacher' => redirect('/teacher/dashboard'),
            default => redirect('/student/dashboard'),
        };
    }

    return redirect()->route('login');
});

// Include auth routes
require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/teacher.php';
require __DIR__ . '/student.php';
require __DIR__ . '/english.php';