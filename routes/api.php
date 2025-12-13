<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// English Learning System Controllers
use App\Http\Controllers\Api\English\LevelController;
use App\Http\Controllers\Api\English\TopicController;
use App\Http\Controllers\Api\English\UnitController;
use App\Http\Controllers\Api\English\LessonController;
use App\Http\Controllers\Api\English\VocabularyController;
use App\Http\Controllers\Api\English\GrammarController;
use App\Http\Controllers\Api\English\GameController;
use App\Http\Controllers\Api\English\BattleController;
use App\Http\Controllers\Api\English\AIConversationController;
use App\Http\Controllers\Api\English\AchievementController;
use App\Http\Controllers\Api\English\LeaderboardController;
use App\Http\Controllers\Api\English\NotificationController;
use App\Http\Controllers\Api\English\ProfileController;
use App\Http\Controllers\Api\English\DailyChallengeController;
use App\Http\Controllers\Api\English\TournamentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| English Learning System API Routes (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/english')->middleware([
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'auth:sanctum',
])->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/profile/stats', [ProfileController::class, 'stats']);

    // Levels
    Route::get('/levels', [LevelController::class, 'index']);
    Route::get('/levels/current', [LevelController::class, 'current']);
    Route::get('/levels/{levelId}', [LevelController::class, 'show']);

    // Topics (nested under levels)
    Route::get('/levels/{levelId}/topics', [TopicController::class, 'index']);

    // Units
    Route::get('/topics/{topicId}/units', [UnitController::class, 'index']);
    Route::get('/units/{unitId}', [UnitController::class, 'show']);
    Route::post('/units/{unitId}/test', [UnitController::class, 'completeTest']);

    // Lessons
    Route::get('/lessons/{lessonId}', [LessonController::class, 'show']);
    Route::post('/lessons/{lessonId}/start', [LessonController::class, 'start']);
    Route::post('/lessons/{lessonId}/step', [LessonController::class, 'updateStep']);
    Route::post('/lessons/{lessonId}/complete', [LessonController::class, 'complete']);

    // Vocabulary
    Route::get('/levels/{levelId}/vocabulary', [VocabularyController::class, 'index']);
    Route::get('/vocabulary/review', [VocabularyController::class, 'review']);
    Route::get('/vocabulary/new', [VocabularyController::class, 'newWords']);
    Route::get('/vocabulary/stats', [VocabularyController::class, 'stats']);
    Route::get('/vocabulary/search', [VocabularyController::class, 'search']);
    Route::post('/vocabulary/{vocabularyId}/learn', [VocabularyController::class, 'learn']);
    Route::post('/vocabulary/{userVocabularyId}/review', [VocabularyController::class, 'submitReview']);

    // Grammar
    Route::get('/levels/{levelId}/grammar', [GrammarController::class, 'index']);
    Route::get('/grammar/{ruleId}', [GrammarController::class, 'show']);
    Route::get('/grammar/{ruleId}/exercises', [GrammarController::class, 'exercises']);
    Route::post('/grammar/exercises/{exerciseId}/answer', [GrammarController::class, 'submitAnswer']);

    // Games
    Route::get('/games/categories', [GameController::class, 'categories']);
    Route::get('/games/categories/{categoryId}', [GameController::class, 'index']);
    Route::get('/games/{gameId}', [GameController::class, 'show']);
    Route::post('/games/{gameId}/levels/{levelId}/start', [GameController::class, 'start']);
    Route::post('/games/attempts/{attemptId}/submit', [GameController::class, 'submit']);

    // Battles (Real-time with WebSocket)
    Route::get('/battles/active', [BattleController::class, 'active']);
    Route::get('/battles/history', [BattleController::class, 'history']);
    Route::get('/battles/{battleId}', [BattleController::class, 'show']);
    Route::post('/battles/match', [BattleController::class, 'findMatch']);
    Route::post('/battles/{battleId}/start', [BattleController::class, 'start']);
    Route::post('/battles/{battleId}/rounds/{roundId}/answer', [BattleController::class, 'submitAnswer']);
    Route::delete('/battles/{battleId}', [BattleController::class, 'cancel']);

    // AI Conversation
    Route::get('/ai/conversations', [AIConversationController::class, 'history']);
    Route::get('/ai/conversations/{conversationId}', [AIConversationController::class, 'show']);
    Route::post('/ai/conversations/start', [AIConversationController::class, 'start']);
    Route::post('/ai/conversations/{conversationId}/message', [AIConversationController::class, 'sendMessage']);
    Route::post('/ai/conversations/{conversationId}/end', [AIConversationController::class, 'end']);

    // Achievements
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::get('/achievements/unlocked', [AchievementController::class, 'unlocked']);
    Route::get('/achievements/stats', [AchievementController::class, 'stats']);

    // Leaderboards
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/leaderboard/my-rank', [LeaderboardController::class, 'myRank']);
    Route::get('/leaderboard/around', [LeaderboardController::class, 'around']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Daily Challenges
    Route::get('/challenges/today', [DailyChallengeController::class, 'today']);
    Route::get('/challenges/history', [DailyChallengeController::class, 'history']);
    Route::post('/challenges/{challengeId}/tasks/{taskId}/complete', [DailyChallengeController::class, 'completeTask']);

    // Tournaments
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/history', [TournamentController::class, 'history']);
    Route::get('/tournaments/{tournamentId}', [TournamentController::class, 'show']);
    Route::post('/tournaments/{tournamentId}/register', [TournamentController::class, 'register']);

    // Word Blitz Game API
    Route::prefix('word-blitz')->group(function () {
        Route::post('/check-answer', [\App\Http\Controllers\English\WordBlitzController::class, 'checkAnswer']);
        Route::post('/skip', [\App\Http\Controllers\English\WordBlitzController::class, 'skipWord']);
        Route::post('/complete', [\App\Http\Controllers\English\WordBlitzController::class, 'complete']);
        Route::post('/abandon', [\App\Http\Controllers\English\WordBlitzController::class, 'abandon']);
    });
});
