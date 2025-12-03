<?php

use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Teacher\BankAccountController;
use App\Http\Controllers\Teacher\CourseController;
use App\Http\Controllers\Teacher\EarningsController;
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
    
    // Bank Accounts
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
    Route::patch('/bank-accounts/{bankAccount}/set-primary', [BankAccountController::class, 'setPrimary'])->name('bank-accounts.set-primary');
    
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
    
    // Earnings
    Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
    Route::get('/earnings/history', [EarningsController::class, 'history'])->name('earnings.history');
    Route::post('/earnings/withdraw', [EarningsController::class, 'withdraw'])->name('earnings.withdraw');
    
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
    Route::resource('courses.modules', ModuleController::class)->shallow();
    
    Route::post('/courses/{course}/modules/{module}/lessons/reorder', [LessonController::class, 'reorder'])->name('courses.modules.lessons.reorder');
    Route::resource('courses.modules.lessons', LessonController::class)->shallow();
    
    // Video Upload
    Route::post('/lessons/{lesson}/video/upload', [VideoController::class, 'upload'])->name('lessons.video.upload');
    Route::post('/lessons/{lesson}/video/upload-chunk', [VideoController::class, 'uploadChunk'])->name('lessons.video.upload-chunk');
    Route::get('/lessons/{lesson}/video/status', [VideoController::class, 'status'])->name('lessons.video.status');
    Route::delete('/lessons/{lesson}/video', [VideoController::class, 'destroy'])->name('lessons.video.destroy');
    
    // Tests
    Route::post('/courses/{course}/tests/{test}/toggle-status', [TestController::class, 'toggleStatus'])->name('courses.tests.toggle-status');
    Route::resource('courses.tests', TestController::class);
    
    // Questions
    Route::post('/tests/{test}/questions/reorder', [QuestionController::class, 'reorder'])->name('tests.questions.reorder');
    Route::resource('tests.questions', QuestionController::class)->shallow();

    // Attachments
    Route::post('/lessons/{lesson}/attachments/reorder', [\App\Http\Controllers\Teacher\AttachmentController::class, 'reorder'])->name('lessons.attachments.reorder');
    Route::resource('lessons.attachments', \App\Http\Controllers\Teacher\AttachmentController::class)->shallow();

    // Video Chapters
    Route::resource('lessons.chapters', \App\Http\Controllers\Teacher\VideoChapterController::class)->shallow();

    // Bulk Lessons
    Route::get('/courses/{course}/modules/{module}/bulk-lessons', [\App\Http\Controllers\Teacher\BulkLessonController::class, 'create'])->name('courses.modules.bulk-lessons.create');
    Route::post('/courses/{course}/modules/{module}/bulk-lessons', [\App\Http\Controllers\Teacher\BulkLessonController::class, 'store'])->name('courses.modules.bulk-lessons.store');

    // Course Q&A
    Route::get('/courses/{course}/qna', [\App\Http\Controllers\Teacher\QnaController::class, 'index'])->name('courses.qna.index');
    Route::post('/courses/{course}/qna/{question}/reply', [\App\Http\Controllers\Teacher\QnaController::class, 'reply'])->name('courses.qna.reply');
    Route::post('/courses/{course}/qna/{question}/toggle-pin', [\App\Http\Controllers\Teacher\QnaController::class, 'togglePin'])->name('courses.qna.toggle-pin');
    Route::post('/courses/{course}/qna/{question}/toggle-highlight', [\App\Http\Controllers\Teacher\QnaController::class, 'toggleHighlight'])->name('courses.qna.toggle-highlight');
    Route::delete('/courses/{course}/qna/{question}', [\App\Http\Controllers\Teacher\QnaController::class, 'destroy'])->name('courses.qna.destroy');

    // Course Announcements
    Route::post('/courses/{course}/announcements/{announcement}/toggle-pin', [\App\Http\Controllers\Teacher\AnnouncementController::class, 'togglePin'])->name('courses.announcements.toggle-pin');
    Route::resource('courses.announcements', \App\Http\Controllers\Teacher\AnnouncementController::class)->shallow();

    // Course Clone
    Route::post('/courses/{course}/clone', [\App\Http\Controllers\Teacher\CourseCloneController::class, 'store'])->name('courses.clone');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Teacher\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Teacher\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\Teacher\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

    // Rating
    Route::get('/rating', [\App\Http\Controllers\Teacher\RatingController::class, 'index'])->name('rating.index');
});
