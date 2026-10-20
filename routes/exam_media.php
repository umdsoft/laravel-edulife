<?php

use App\Http\Controllers\Media\QuestionImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Images and graphs in questions and lessons (agent M)
|--------------------------------------------------------------------------
| See docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §1 and docs/exam/RASM_VA_GRAFIK.md.
*/

// "Rasm" button of MarkdownEditor: teacher and admin only, rate limited.
Route::middleware(['auth', 'role:teacher,admin,super_admin', 'throttle:60,1'])
    ->post('/media/question-images', [QuestionImageController::class, 'store'])
    ->name('media.question-images.store');

