<?php

use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Teacher\CourseController;
use App\Http\Controllers\Teacher\StudentsController;
use App\Http\Controllers\Teacher\ReviewsController;
use App\Http\Controllers\Teacher\AnalyticsController;
use App\Http\Controllers\Teacher\ModuleController;
use App\Http\Controllers\Teacher\LessonController;
use App\Http\Controllers\Teacher\VideoController;
use App\Http\Controllers\Teacher\TestController;
use App\Http\Controllers\Teacher\QuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')->middleware(['auth', 'role:teacher'])->name('teacher.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.delete-avatar');
    
    // Courses
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::patch('/courses/{course}/submit', [CourseController::class, 'submit'])->name('courses.submit');
    Route::post('/courses/{course}/thumbnail', [CourseController::class, 'updateThumbnail'])->name('courses.thumbnail');
    Route::post('/courses/{course}/preview-video', [CourseController::class, 'updatePreviewVideo'])->name('courses.preview-video');
    
    // Students
    Route::get('/students', [StudentsController::class, 'index'])->name('students.index');
    Route::get('/students/{enrollment}', [StudentsController::class, 'show'])->name('students.show');
    
    // Reviews
    Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [ReviewsController::class, 'reply'])->name('reviews.reply');
    
    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Curriculum
    Route::post('/courses/{course}/modules/reorder', [ModuleController::class, 'reorder'])->name('courses.modules.reorder');
    Route::resource('courses.modules', ModuleController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);
    
    Route::post('/courses/{course}/modules/{module}/lessons/reorder', [LessonController::class, 'reorder'])->name('courses.modules.lessons.reorder')->scopeBindings();
    Route::resource('courses.modules.lessons', LessonController::class)->shallow()->scoped()->only(['store', 'edit', 'update', 'destroy']);
    
    // Video Upload
    Route::post('/lessons/{lesson}/video/upload', [VideoController::class, 'upload'])->name('lessons.video.upload');
    Route::post('/lessons/{lesson}/video/upload-chunk', [VideoController::class, 'uploadChunk'])->name('lessons.video.upload-chunk');
    Route::get('/lessons/{lesson}/video/status', [VideoController::class, 'status'])->name('lessons.video.status');
    Route::delete('/lessons/{lesson}/video', [VideoController::class, 'destroy'])->name('lessons.video.destroy');
    
    // Tests
    Route::post('/courses/{course}/tests/{test}/toggle-status', [TestController::class, 'toggleStatus'])->name('courses.tests.toggle-status')->scopeBindings();
    Route::resource('courses.tests', TestController::class)->scoped();
    
    // Questions
    Route::post('/tests/{test}/questions/reorder', [QuestionController::class, 'reorder'])->name('tests.questions.reorder');
    Route::post('/questions/{question}/duplicate', [QuestionController::class, 'duplicate'])->name('questions.duplicate');
    // --- difficulty (wave 2): "Bankdan qo'shish" — exam bank questions copied into a course test ---
    Route::get('/tests/{test}/questions/from-bank', [\App\Http\Controllers\Teacher\TestBankController::class, 'create'])->name('tests.questions.bank');
    Route::get('/tests/{test}/questions/from-bank/search', [\App\Http\Controllers\Teacher\TestBankController::class, 'search'])->name('tests.questions.bank.search');
    Route::post('/tests/{test}/questions/from-bank', [\App\Http\Controllers\Teacher\TestBankController::class, 'store'])->middleware('throttle:30,1')->name('tests.questions.bank.store');
    // --- end difficulty ---
    Route::resource('tests.questions', QuestionController::class)->shallow()->except(['show']);

    // Attachments
    Route::post('/lessons/{lesson}/attachments/reorder', [\App\Http\Controllers\Teacher\AttachmentController::class, 'reorder'])->name('lessons.attachments.reorder');
    Route::resource('lessons.attachments', \App\Http\Controllers\Teacher\AttachmentController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);

    // Video Chapters
    Route::resource('lessons.chapters', \App\Http\Controllers\Teacher\VideoChapterController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);

    // Bulk Lessons
    Route::get('/courses/{course}/modules/{module}/bulk-lessons', [\App\Http\Controllers\Teacher\BulkLessonController::class, 'create'])->name('courses.modules.bulk-lessons.create')->scopeBindings();
    Route::post('/courses/{course}/modules/{module}/bulk-lessons', [\App\Http\Controllers\Teacher\BulkLessonController::class, 'store'])->name('courses.modules.bulk-lessons.store')->scopeBindings();

    // Course Q&A
    Route::get('/courses/{course}/qna', [\App\Http\Controllers\Teacher\QnaController::class, 'index'])->name('courses.qna.index');
    Route::post('/courses/{course}/qna/{question}/reply', [\App\Http\Controllers\Teacher\QnaController::class, 'reply'])->name('courses.qna.reply');
    Route::post('/courses/{course}/qna/{question}/toggle-pin', [\App\Http\Controllers\Teacher\QnaController::class, 'togglePin'])->name('courses.qna.toggle-pin');
    Route::post('/courses/{course}/qna/{question}/toggle-highlight', [\App\Http\Controllers\Teacher\QnaController::class, 'toggleHighlight'])->name('courses.qna.toggle-highlight');
    Route::delete('/courses/{course}/qna/{question}', [\App\Http\Controllers\Teacher\QnaController::class, 'destroy'])->name('courses.qna.destroy');

    // Course Announcements
    Route::post('/courses/{course}/announcements/{announcement}/toggle-pin', [\App\Http\Controllers\Teacher\AnnouncementController::class, 'togglePin'])->name('courses.announcements.toggle-pin')->scopeBindings();
    Route::resource('courses.announcements', \App\Http\Controllers\Teacher\AnnouncementController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);

    // Course Clone
    Route::post('/courses/{course}/clone', [\App\Http\Controllers\Teacher\CourseCloneController::class, 'store'])->name('courses.clone');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Teacher\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Teacher\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\Teacher\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
});

// --- exam: question bank, review queue, topics, import (stage 3) ---
// Teacher (ustoz) and admins: the bank is the source of every exam question.
Route::prefix('teacher/exam')->middleware(['auth', 'role:teacher,admin,super_admin'])->name('teacher.exam.')->group(function () {
    // Topic tree
    Route::get('/topics', [\App\Http\Controllers\Teacher\Exam\TopicController::class, 'index'])->name('topics.index');
    Route::post('/topics', [\App\Http\Controllers\Teacher\Exam\TopicController::class, 'store'])->name('topics.store');
    Route::post('/topics/reorder', [\App\Http\Controllers\Teacher\Exam\TopicController::class, 'reorder'])->name('topics.reorder');
    Route::patch('/topics/{topic}', [\App\Http\Controllers\Teacher\Exam\TopicController::class, 'update'])->name('topics.update');
    Route::post('/topics/{topic}/move', [\App\Http\Controllers\Teacher\Exam\TopicController::class, 'move'])->name('topics.move');
    Route::delete('/topics/{topic}', [\App\Http\Controllers\Teacher\Exam\TopicController::class, 'destroy'])->name('topics.destroy');

    // Question bank
    Route::get('/questions', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/create', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('/questions/{question}/duplicate', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'duplicate'])->name('questions.duplicate');
    Route::post('/questions/{question}/retire', [\App\Http\Controllers\Teacher\Exam\QuestionController::class, 'retire'])->name('questions.retire');

    // Review queue (one question at a time, keyboard driven)
    Route::get('/review', [\App\Http\Controllers\Teacher\Exam\ReviewController::class, 'show'])->name('review');
    Route::post('/review/{question}/mark', [\App\Http\Controllers\Teacher\Exam\ReviewController::class, 'mark'])->name('review.mark');
    Route::post('/review/{question}/answer', [\App\Http\Controllers\Teacher\Exam\ReviewController::class, 'answer'])->name('review.answer');
    Route::patch('/review/{question}', [\App\Http\Controllers\Teacher\Exam\ReviewController::class, 'update'])->name('review.update');
    Route::post('/review/{question}/approve', [\App\Http\Controllers\Teacher\Exam\ReviewController::class, 'approve'])->name('review.approve');
    Route::post('/review/{question}/reject', [\App\Http\Controllers\Teacher\Exam\ReviewController::class, 'reject'])->name('review.reject');

    // Import (CSV / Excel / JSON -> preview -> confirm)
    Route::get('/import', [\App\Http\Controllers\Teacher\Exam\ImportController::class, 'index'])->name('import.index');
    Route::get('/import/template', [\App\Http\Controllers\Teacher\Exam\ImportController::class, 'template'])->name('import.template');
    Route::post('/import', [\App\Http\Controllers\Teacher\Exam\ImportController::class, 'upload'])->name('import.upload');
    Route::get('/import/preview', [\App\Http\Controllers\Teacher\Exam\ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/confirm', [\App\Http\Controllers\Teacher\Exam\ImportController::class, 'confirm'])->name('import.confirm');
});
// --- end exam: question bank (stage 3) ---

// --- exam: guardian link approvals (stage 7) ---
Route::prefix('teacher/exam')->middleware(['auth', 'role:teacher,admin,super_admin'])->name('teacher.exam.')->group(function () {
    Route::get('/guardian-links', [\App\Http\Controllers\Teacher\Exam\GuardianLinkController::class, 'index'])->name('guardian-links.index');
    Route::post('/guardian-links/{link}/approve', [\App\Http\Controllers\Teacher\Exam\GuardianLinkController::class, 'approve'])->name('guardian-links.approve');
    Route::post('/guardian-links/{link}/reject', [\App\Http\Controllers\Teacher\Exam\GuardianLinkController::class, 'reject'])->name('guardian-links.reject');
});
// --- end exam: stage 7 ---
