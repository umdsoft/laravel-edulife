<?php

use App\Http\Controllers\Admin\AchievementController;
use App\Http\Controllers\Admin\CoinPackageController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DailyMissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DirectionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CertificateTemplateController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin routes - requires auth and admin/super_admin role
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Users
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    
    // Teachers
    Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/{user}', [TeacherController::class, 'show'])->name('teachers.show');
    Route::patch('/teachers/{user}/verify', [TeacherController::class, 'verify'])->name('teachers.verify');
    Route::patch('/teachers/{user}/unverify', [TeacherController::class, 'unverify'])->name('teachers.unverify');
    Route::patch('/teachers/{user}/update-level', [TeacherController::class, 'updateLevel'])->name('teachers.update-level');
    
    // Courses
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::patch('/courses/{course}/approve', [CourseController::class, 'approve'])->name('courses.approve');
    Route::patch('/courses/{course}/reject', [CourseController::class, 'reject'])->name('courses.reject');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    
    // Directions
    Route::resource('directions', DirectionController::class)->except(['create', 'edit', 'show']);
    Route::patch('/directions/{direction}/toggle-status', [DirectionController::class, 'toggleStatus'])->name('directions.toggle-status');
    Route::post('/directions/reorder', [DirectionController::class, 'reorder'])->name('directions.reorder');
    
    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    
    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::post('/subscription-plans', [SubscriptionController::class, 'storePlan'])->name('subscriptions.store-plan');
    Route::put('/subscription-plans/{plan}', [SubscriptionController::class, 'updatePlan'])->name('subscriptions.update-plan');
    Route::delete('/subscription-plans/{plan}', [SubscriptionController::class, 'destroyPlan'])->name('subscriptions.destroy-plan');
    
    // Promo Codes
    Route::resource('promo-codes', PromoCodeController::class)->except(['create', 'edit', 'show']);
    Route::patch('/promo-codes/{promoCode}/toggle-status', [PromoCodeController::class, 'toggleStatus'])->name('promo-codes.toggle-status');
    
    // Achievements
    Route::resource('achievements', AchievementController::class)->except(['create', 'edit', 'show']);
    Route::patch('/achievements/{achievement}/toggle-status', [AchievementController::class, 'toggleStatus'])->name('achievements.toggle-status');
    
    // Daily Missions
    Route::resource('daily-missions', DailyMissionController::class)->except(['create', 'edit', 'show']);
    Route::patch('/daily-missions/{dailyMission}/toggle-status', [DailyMissionController::class, 'toggleStatus'])->name('daily-missions.toggle-status');
    
    // Coin Packages
    Route::resource('coin-packages', CoinPackageController::class)->except(['create', 'edit', 'show']);
    Route::patch('/coin-packages/{coinPackage}/toggle-status', [CoinPackageController::class, 'toggleStatus'])->name('coin-packages.toggle-status');
    Route::post('/coin-packages/reorder', [CoinPackageController::class, 'reorder'])->name('coin-packages.reorder');
    
    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [\App\Http\Controllers\Admin\NotificationController::class, 'send'])->name('notifications.send');
    
    // System Logs
    Route::get('/system-logs', [\App\Http\Controllers\Admin\SystemLogController::class, 'index'])->name('system-logs.index');
    
    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    Route::post('/certificates/{certificate}/regenerate', [CertificateController::class, 'regenerate'])->name('certificates.regenerate');
    Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificates.destroy');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Certificate Templates
    Route::get('/certificate-templates', [CertificateTemplateController::class, 'index'])->name('certificate-templates.index');
    Route::get('/certificate-templates/create', [CertificateTemplateController::class, 'create'])->name('certificate-templates.create');
    Route::post('/certificate-templates', [CertificateTemplateController::class, 'store'])->name('certificate-templates.store');
    Route::get('/certificate-templates/{template}', [CertificateTemplateController::class, 'show'])->name('certificate-templates.show');
    Route::get('/certificate-templates/{template}/edit', [CertificateTemplateController::class, 'edit'])->name('certificate-templates.edit');
    Route::put('/certificate-templates/{template}', [CertificateTemplateController::class, 'update'])->name('certificate-templates.update');
    Route::delete('/certificate-templates/{template}', [CertificateTemplateController::class, 'destroy'])->name('certificate-templates.destroy');
    Route::patch('/certificate-templates/{template}/toggle-default', [CertificateTemplateController::class, 'toggleDefault'])->name('certificate-templates.toggle-default');
    Route::post('/certificate-templates/{template}/preview', [CertificateTemplateController::class, 'preview'])->name('certificate-templates.preview');

    // Tags
    Route::resource('tags', \App\Http\Controllers\Admin\TagController::class)->except(['create', 'edit', 'show']);
    Route::post('/tags/merge', [\App\Http\Controllers\Admin\TagController::class, 'merge'])->name('tags.merge');

    // Teacher Payouts
    Route::get('/teacher-payouts', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'index'])->name('teacher-payouts.index');
    Route::get('/teacher-payouts/bank-accounts', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'bankAccounts'])->name('teacher-payouts.bank-accounts');
    Route::patch('/teacher-payouts/bank-accounts/{account}/verify', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'verifyBankAccount'])->name('teacher-payouts.verify-bank');
    Route::get('/teacher-payouts/create', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'create'])->name('teacher-payouts.create');
    Route::post('/teacher-payouts', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'store'])->name('teacher-payouts.store');
    Route::patch('/teacher-payouts/{payout}/mark-paid', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'markPaid'])->name('teacher-payouts.mark-paid');
    Route::patch('/teacher-payouts/{payout}/cancel', [\App\Http\Controllers\Admin\TeacherPayoutController::class, 'cancel'])->name('teacher-payouts.cancel');

    // Notifications (Enhanced)
    Route::get('/notifications/send', [\App\Http\Controllers\Admin\NotificationController::class, 'showSend'])->name('notifications.send-page');
    Route::get('/notifications/templates', [\App\Http\Controllers\Admin\NotificationController::class, 'templates'])->name('notifications.templates');
    Route::post('/notifications/templates', [\App\Http\Controllers\Admin\NotificationController::class, 'storeTemplate'])->name('notifications.store-template');
    Route::put('/notifications/templates/{template}', [\App\Http\Controllers\Admin\NotificationController::class, 'updateTemplate'])->name('notifications.update-template');
    Route::delete('/notifications/templates/{template}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroyTemplate'])->name('notifications.destroy-template');

    // Reviews
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'show'])->name('reviews.show');
    Route::patch('/reviews/{review}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/reject', [\App\Http\Controllers\Admin\ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Questions Bank
    Route::get('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'show'])->name('questions.show');
    Route::patch('/questions/{question}/approve', [\App\Http\Controllers\Admin\QuestionController::class, 'approve'])->name('questions.approve');
    Route::patch('/questions/{question}/reject', [\App\Http\Controllers\Admin\QuestionController::class, 'reject'])->name('questions.reject');
    Route::delete('/questions/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('questions.destroy');

    // Leaderboard Config
    Route::get('/leaderboard', [\App\Http\Controllers\Admin\LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::put('/leaderboard', [\App\Http\Controllers\Admin\LeaderboardController::class, 'update'])->name('leaderboard.update');
    Route::post('/leaderboard/reset', [\App\Http\Controllers\Admin\LeaderboardController::class, 'reset'])->name('leaderboard.reset');

    // Teacher Ratings
    Route::prefix('teacher-ratings')->name('teacher-ratings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TeacherRatingController::class, 'index'])->name('index');
        Route::post('/recalculate-all', [\App\Http\Controllers\Admin\TeacherRatingController::class, 'recalculateAll'])->name('recalculate-all');
        Route::get('/{teacher}', [\App\Http\Controllers\Admin\TeacherRatingController::class, 'show'])->name('show');
        Route::post('/{teacher}/override', [\App\Http\Controllers\Admin\TeacherRatingController::class, 'overrideLevel'])->name('override');
        Route::post('/{teacher}/recalculate', [\App\Http\Controllers\Admin\TeacherRatingController::class, 'recalculate'])->name('recalculate');
    });

    // ==================== OLYMPIAD ROUTES ====================
    Route::prefix('olympiads')->name('olympiads.')->group(function () {
        // CRUD
        Route::get('/', [\App\Http\Controllers\Admin\OlympiadController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\OlympiadController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\OlympiadController::class, 'store'])->name('store');
        Route::get('/{olympiad}', [\App\Http\Controllers\Admin\OlympiadController::class, 'show'])->name('show');
        Route::get('/{olympiad}/edit', [\App\Http\Controllers\Admin\OlympiadController::class, 'edit'])->name('edit');
        Route::put('/{olympiad}', [\App\Http\Controllers\Admin\OlympiadController::class, 'update'])->name('update');
        Route::delete('/{olympiad}', [\App\Http\Controllers\Admin\OlympiadController::class, 'destroy'])->name('destroy');
        Route::patch('/{olympiad}/status', [\App\Http\Controllers\Admin\OlympiadController::class, 'updateStatus'])->name('status');
        Route::post('/{olympiad}/duplicate', [\App\Http\Controllers\Admin\OlympiadController::class, 'duplicate'])->name('duplicate');
        Route::get('/{olympiad}/registrations', [\App\Http\Controllers\Admin\OlympiadController::class, 'registrations'])->name('registrations');
        
        // Monitoring
        Route::get('/{olympiad}/monitor', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'index'])->name('monitor');
        Route::get('/{olympiad}/monitor/stats', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'stats'])->name('monitor.stats');
        Route::get('/{olympiad}/monitor/participants', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'activeParticipants'])->name('monitor.participants');
        Route::get('/{olympiad}/monitor/leaderboard', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'leaderboard'])->name('monitor.leaderboard');
        Route::get('/{olympiad}/monitor/participant/{attempt}', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'participantDetails'])->name('monitor.participant');
        Route::post('/{olympiad}/monitor/{attempt}/force-submit', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'forceSubmit'])->name('monitor.force-submit');
        Route::post('/{olympiad}/monitor/{attempt}/disqualify', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'disqualify'])->name('monitor.disqualify');
        Route::post('/{olympiad}/monitor/{attempt}/reinstate', [\App\Http\Controllers\Admin\OlympiadMonitorController::class, 'reinstate'])->name('monitor.reinstate');
        
        // Grading
        Route::get('/{olympiad}/grading', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'index'])->name('grading.index');
        Route::get('/{olympiad}/grading/section/{section}', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'sectionQueue'])->name('grading.section');
        Route::get('/{olympiad}/grading/answer/{answer}', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'grade'])->name('grading.grade');
        Route::post('/{olympiad}/grading/answer/{answer}', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'submitGrade'])->name('grading.submit');
        Route::post('/{olympiad}/grading/bulk', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'bulkGrade'])->name('grading.bulk');
        Route::post('/{olympiad}/grading/finalize', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'finalize'])->name('grading.finalize');
        Route::get('/{olympiad}/grading/export', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'export'])->name('grading.export');
        Route::get('/{olympiad}/grading/question/{question}/analysis', [\App\Http\Controllers\Admin\OlympiadGradingController::class, 'questionAnalysis'])->name('grading.analysis');
    });
});
