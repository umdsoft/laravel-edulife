<?php

/*
 * "Shanba imtihoni" stage 5: exam engine routes (required from routes/web.php).
 *   teacher.exam.blueprints.*  exam plans + live bank sufficiency
 *   teacher.exam.sessions.*    Saturday sessions, variants, live participation, results, CSV
 *   student.exam.*             exam home, placement / session start, take, autosave, result
 */

use App\Http\Controllers\Student\Exam\AttemptController;
use App\Http\Controllers\Student\Exam\ExamController;
use App\Http\Controllers\Teacher\Exam\BlueprintController;
use App\Http\Controllers\Teacher\Exam\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher'])->prefix('teacher/exam')->name('teacher.exam.')->group(function () {
    Route::get('/blueprints', [BlueprintController::class, 'index'])->name('blueprints.index');
    Route::get('/blueprints/create', [BlueprintController::class, 'create'])->name('blueprints.create');
    Route::post('/blueprints', [BlueprintController::class, 'store'])->name('blueprints.store');
    Route::post('/blueprints/capacity', [BlueprintController::class, 'capacity'])->middleware('throttle:120,1')->name('blueprints.capacity');
    Route::get('/blueprints/{blueprint}/edit', [BlueprintController::class, 'edit'])->name('blueprints.edit');
    Route::put('/blueprints/{blueprint}', [BlueprintController::class, 'update'])->name('blueprints.update');
    Route::delete('/blueprints/{blueprint}', [BlueprintController::class, 'destroy'])->name('blueprints.destroy');

    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
    Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::post('/sessions/{session}/schedule', [SessionController::class, 'schedule'])->name('sessions.schedule');
    Route::post('/sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('sessions.cancel');
    Route::post('/sessions/{session}/close', [SessionController::class, 'close'])->name('sessions.close');
    Route::post('/sessions/{session}/variants', [SessionController::class, 'generateVariants'])->name('sessions.variants');
    Route::get('/sessions/{session}/live', [SessionController::class, 'live'])->name('sessions.live');
    Route::get('/sessions/{session}/export', [SessionController::class, 'export'])->name('sessions.export');
    Route::post('/sessions/{session}/attempts/{attempt}/retake', [SessionController::class, 'retake'])->name('sessions.retake');
});

Route::middleware(['auth', 'role:student'])->prefix('student/exam')->name('student.exam.')->group(function () {
    Route::get('/', [ExamController::class, 'index'])->name('index');
    Route::post('/placement/{subject:code}', [ExamController::class, 'startPlacement'])->middleware('throttle:20,1')->name('placement.start');
    Route::post('/sessions/{session}/start', [ExamController::class, 'startSession'])->middleware('throttle:20,1')->name('sessions.start');

    Route::get('/attempts/{attempt}', [AttemptController::class, 'take'])->name('take');
    Route::post('/attempts/{attempt}/answers', [AttemptController::class, 'saveAnswers'])->middleware('throttle:120,1')->name('answers');
    Route::post('/attempts/{attempt}/events', [AttemptController::class, 'logEvent'])->middleware('throttle:60,1')->name('events');
    Route::post('/attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('submit');
    Route::get('/attempts/{attempt}/result', [AttemptController::class, 'result'])->name('result');
});
