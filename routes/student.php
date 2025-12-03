<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\CourseController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\WishlistController;
use App\Http\Controllers\Student\SearchController;
use App\Http\Controllers\Student\LearnController;
use App\Http\Controllers\Student\LessonProgressController;
use App\Http\Controllers\Student\NoteController;
use App\Http\Controllers\Student\StudentQnaController;
use App\Http\Controllers\Student\StudentReviewController;
use App\Http\Controllers\Student\StudentAnnouncementController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\BattleController;
use App\Http\Controllers\Student\TournamentController;
use App\Http\Controllers\Student\LeaderboardController;
use App\Http\Controllers\Student\TestController;
use App\Http\Controllers\Student\TestAttemptController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\SettingsController;
use App\Http\Controllers\Student\FriendController;
use App\Http\Controllers\Student\SupportController;
use App\Http\Controllers\Student\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Browse Courses
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/category/{direction}', [CourseController::class, 'category'])->name('courses.category');
    Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course:slug}/preview/{lesson}', [CourseController::class, 'previewLesson'])->name('courses.preview');
    
    // Enrollment
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll'])->name('courses.enroll');
    Route::get('/my-courses', [EnrollmentController::class, 'index'])->name('my-courses.index');
    Route::get('/my-courses/in-progress', [EnrollmentController::class, 'inProgress'])->name('my-courses.in-progress');
    Route::get('/my-courses/completed', [EnrollmentController::class, 'completed'])->name('my-courses.completed');
    
    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{course}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    
    // Search
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

    // Learning
    Route::prefix('learn')->name('learn.')->group(function () {
        // Course learning dashboard
        Route::get('/course/{course:slug}', [LearnController::class, 'course'])->name('course');
        
        // Lesson view
        Route::get('/course/{course:slug}/lesson/{lesson}', [LearnController::class, 'lesson'])->name('lesson');
        
        // Progress tracking
        Route::post('/lesson/{lesson}/progress', [LessonProgressController::class, 'update'])->name('progress.update');
        Route::post('/lesson/{lesson}/complete', [LessonProgressController::class, 'complete'])->name('progress.complete');
        Route::post('/lesson/{lesson}/video-log', [LessonProgressController::class, 'logVideoWatch'])->name('progress.video-log');
        
        // Notes
        Route::get('/course/{course}/notes', [NoteController::class, 'index'])->name('notes.index');
        Route::post('/lesson/{lesson}/notes', [NoteController::class, 'store'])->name('notes.store');
        Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        Route::patch('/notes/{note}/toggle-pin', [NoteController::class, 'togglePin'])->name('notes.toggle-pin');
        
        // Q&A
        Route::get('/course/{course}/qna', [StudentQnaController::class, 'index'])->name('qna.index');
        Route::post('/course/{course}/qna', [StudentQnaController::class, 'store'])->name('qna.store');
        Route::post('/qna/{question}/upvote', [StudentQnaController::class, 'upvote'])->name('qna.upvote');
        
        // Announcements
        Route::get('/course/{course}/announcements', [StudentAnnouncementController::class, 'index'])->name('announcements.index');
    });

    // Reviews
    Route::get('/courses/{course}/reviews/create', [StudentReviewController::class, 'create'])->name('reviews.create');
    Route::post('/courses/{course}/reviews', [StudentReviewController::class, 'store'])->name('reviews.store');
    Route::put('/reviews/{review}', [StudentReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [StudentReviewController::class, 'destroy'])->name('reviews.destroy');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    Route::get('/certificates/{certificate}/download-image', [CertificateController::class, 'downloadImage'])->name('certificates.download-image');
    Route::put('/certificates/{certificate}/visibility', [CertificateController::class, 'updateVisibility'])->name('certificates.visibility');

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar');
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('avatar.delete');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/account', [SettingsController::class, 'account'])->name('account');
        Route::put('/account', [SettingsController::class, 'updateAccount'])->name('account.update');
        Route::get('/privacy', [SettingsController::class, 'privacy'])->name('privacy');
        Route::put('/privacy', [SettingsController::class, 'updatePrivacy'])->name('privacy.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::delete('/account', [SettingsController::class, 'deleteAccount'])->name('account.delete');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [NotificationController::class, 'destroyAll'])->name('destroy-all');
    });

    // Friends
    Route::prefix('friends')->name('friends.')->group(function () {
        Route::get('/', [FriendController::class, 'index'])->name('index');
        Route::get('/followers', [FriendController::class, 'followers'])->name('followers');
        Route::get('/following', [FriendController::class, 'following'])->name('following');
        Route::post('/{user}/follow', [FriendController::class, 'follow'])->name('follow');
        Route::delete('/{user}/unfollow', [FriendController::class, 'unfollow'])->name('unfollow');
    });

    // Support
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportController::class, 'index'])->name('index');
        Route::get('/faq', [SupportController::class, 'faq'])->name('faq');
        Route::get('/create', [SupportController::class, 'create'])->name('create');
        Route::post('/', [SupportController::class, 'store'])->name('store');
        Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [SupportController::class, 'close'])->name('close');
        Route::post('/{ticket}/rate', [SupportController::class, 'rate'])->name('rate');
    });



    // Gamification - XP
    Route::get('/xp', [XPController::class, 'index'])->name('xp.index');

    // Gamification - Achievements
    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::post('/achievements/{achievement}/claim', [AchievementController::class, 'claim'])->name('achievements.claim');

    // Gamification - Battles
    Route::get('/battles', [BattleController::class, 'index'])->name('battles.index');
    Route::post('/battles/find', [BattleController::class, 'findMatch'])->name('battles.find');
    Route::post('/battles/cancel', [BattleController::class, 'cancelSearch'])->name('battles.cancel');
    Route::get('/battles/history', [BattleController::class, 'history'])->name('battles.history');
    Route::get('/battles/{battle}', [BattleController::class, 'show'])->name('battles.show');
    Route::post('/battles/{battle}/answer', [BattleController::class, 'submitAnswer'])->name('battles.answer');

    // Gamification - Tournaments
    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register'])->name('tournaments.register');
    Route::delete('/tournaments/{tournament}/withdraw', [TournamentController::class, 'withdraw'])->name('tournaments.withdraw');

    // Gamification - Missions
    Route::get('/missions', [MissionController::class, 'index'])->name('missions.index');
    Route::post('/missions/{userMission}/claim', [MissionController::class, 'claim'])->name('missions.claim');

    // Gamification - Coins & Shop
    Route::get('/coins', [CoinController::class, 'index'])->name('coins.index');
    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::post('/shop/{item}/purchase', [ShopController::class, 'purchase'])->name('shop.purchase');
    Route::get('/shop/my-items', [ShopController::class, 'myItems'])->name('shop.myItems');
    Route::post('/shop/my-items/{purchase}/equip', [ShopController::class, 'equip'])->name('shop.equip');

    // Gamification - Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');


    // Tests
    Route::prefix('tests')->name('tests.')->group(function () {
        // Test list for course
        Route::get('/course/{course}', [TestController::class, 'index'])->name('index');
        
        // Test history
        Route::get('/history', [TestController::class, 'history'])->name('history');
        
        // Start test (show info page)
        Route::get('/{test}/start', [TestController::class, 'start'])->name('start');
        
        // Begin attempt
        Route::post('/{test}/begin', [TestAttemptController::class, 'begin'])->name('begin');
        
        // Active attempt
        Route::get('/attempt/{attempt}', [TestAttemptController::class, 'show'])->name('attempt');
        
        // Save answer
        Route::post('/attempt/{attempt}/answer', [TestAttemptController::class, 'saveAnswer'])->name('save-answer');
        
        // Flag question for review
        Route::post('/attempt/{attempt}/flag/{question}', [TestAttemptController::class, 'flagQuestion'])->name('flag-question');
        
        // Submit test
        Route::post('/attempt/{attempt}/submit', [TestAttemptController::class, 'submit'])->name('submit');
        
        // Anti-cheat events
        Route::post('/attempt/{attempt}/log-event', [TestAttemptController::class, 'logEvent'])->name('log-event');
        
        // Result
        Route::get('/attempt/{attempt}/result', [TestAttemptController::class, 'result'])->name('result');
        
        // Review answers
        Route::get('/attempt/{attempt}/review', [TestAttemptController::class, 'review'])->name('review');
    });
});
