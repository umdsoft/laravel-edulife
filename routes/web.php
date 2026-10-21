<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateVerificationController;

// Public Certificate Verification
Route::get('/verify/{code}', [CertificateVerificationController::class, 'verify'])->middleware('throttle:30,1')->name('certificate.verify');

// Home route - redirect based on auth status
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return match ($user->role) {
            'super_admin', 'admin' => redirect('/admin/dashboard'),
            'teacher' => redirect('/teacher/dashboard'),
            'guardian' => redirect('/guardian/dashboard'), // exam stage 7
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
require __DIR__ . '/payments.php';

// --- exam: guardian panel + invite links (stage 7) ---
require __DIR__ . '/guardian.php';
Route::get('/r/{code}', \App\Http\Controllers\Exam\ReferralLandingController::class)
    ->where('code', '[A-Za-z0-9_]{4,40}')
    ->middleware('throttle:30,1')
    ->name('referral.landing');
// --- end exam: stage 7 ---

// --- exam: AI drafts (stage 4) + Telegram bot / Mini App (stage 6) ---
require __DIR__ . '/exam_integrations.php';
// --- end exam: stages 4 and 6 ---

// --- exam: variant generator, exam engine, student exam flow (stage 5) ---
require __DIR__ . '/exam_engine.php';
// --- end exam: stage 5 ---

// --- media: images and graphs in questions and lessons (agent M) ---
require __DIR__ . '/exam_media.php';
// --- end media ---

// --- paid exams: per-exam payment or a subscription with exams (agent P) ---
require __DIR__ . '/exam_payments.php';
// --- end paid exams ---
