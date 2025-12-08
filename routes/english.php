<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\English\LevelController;
use App\Http\Controllers\Api\English\LessonController;
use App\Http\Controllers\Api\English\VocabularyController;
use App\Http\Controllers\Api\English\GameController;
use App\Http\Controllers\Api\English\BattleController;
use App\Http\Controllers\Api\English\AchievementController;
use App\Http\Controllers\Api\English\LeaderboardController;
use App\Http\Controllers\Api\English\ProfileController;

/*
|--------------------------------------------------------------------------
| English Learning Web Routes
|--------------------------------------------------------------------------
|
| Routes for the English Learning frontend pages using Inertia.js
|
*/

Route::middleware(['auth', 'verified', 'role:student'])->prefix('student/english')->name('student.english.')->group(function () {

    // Dashboard
    Route::get('/', function () {
        $profile = app(ProfileController::class)->show(request())->getData(true);

        return Inertia::render('English/Dashboard', [
            'profile' => $profile['data'] ?? null,
            'dailyGoal' => [
                'xp_target' => 100,
                'xp_current' => $profile['data']['daily_xp'] ?? 0,
                'tasks_target' => 5,
                'tasks_completed' => 3,
            ],
            'quickActions' => [
                ['id' => 1, 'title' => 'Continue Lesson', 'icon' => '📚', 'route' => '/student/english/levels', 'gradient' => 'from-blue-500 to-blue-600'],
                ['id' => 2, 'title' => 'Review Words', 'icon' => '🔄', 'route' => '/student/english/vocabulary/review', 'gradient' => 'from-purple-500 to-purple-600'],
                ['id' => 3, 'title' => 'Play Games', 'icon' => '🎮', 'route' => '/student/english/games', 'gradient' => 'from-green-500 to-green-600'],
                ['id' => 4, 'title' => 'Battle Arena', 'icon' => '⚔️', 'route' => '/student/english/battle', 'gradient' => 'from-red-500 to-red-600'],
            ],
        ]);
    })->name('dashboard');

    // Learning Path
    Route::get('/levels', [LevelController::class, 'index'])->name('levels');

    // API Endpoints for Learning Path
    Route::get('/api/levels/{code}/modules', [LevelController::class, 'modules'])->name('learn.modules');

    // Lesson Page
    Route::get('/lesson/{lesson}', [LessonController::class, 'show'])->name('lesson');
    Route::post('/lesson/{lesson}/complete', [LessonController::class, 'complete'])->name('lesson.complete');


    // Vocabulary Review
    Route::get('/vocabulary/review', function () {
        return Inertia::render('English/VocabularyReview', [
            'wordsForReview' => [], // Will be fetched via API
        ]);
    })->name('vocabulary.review');

    // Games
    Route::get('/games', function () {
        return Inertia::render('English/Games', [
            'games' => [
                ['id' => 1, 'name' => 'Word Scramble', 'game_type' => 'word_scramble', 'icon' => '🔀'],
                ['id' => 2, 'name' => 'Word Match', 'game_type' => 'word_match', 'icon' => '🎯'],
                ['id' => 3, 'name' => 'Spelling Bee', 'game_type' => 'spelling_bee', 'icon' => '🐝'],
                ['id' => 4, 'name' => 'Hangman', 'game_type' => 'hangman', 'icon' => '☠️'],
            ],
        ]);
    })->name('games.index');

    Route::get('/games/{gameId}/play', function ($gameId) {
        return Inertia::render('English/GamePlay', [
            'gameId' => $gameId,
            'game' => [], // Will be fetched via API
            'questions' => [],
        ]);
    })->name('games.play');

    // Battle
    Route::get('/battle', function () {
        $profile = app(ProfileController::class)->show(request())->getData(true);

        return Inertia::render('English/BattleLobby', [
            'profile' => $profile['data'] ?? null,
            'battleHistory' => [],
            'stats' => [
                'wins' => 0,
                'losses' => 0,
                'win_rate' => 0,
            ],
        ]);
    })->name('battle.lobby');

    Route::get('/battle/{battleId}', function ($battleId) {
        return Inertia::render('English/BattleArena', [
            'battle' => ['id' => $battleId],
            'currentRound' => null,
            'player' => null,
            'opponent' => null,
        ]);
    })->name('battle.arena');

    // Achievements
    Route::get('/achievements', function () {
        return Inertia::render('English/Achievements', [
            'achievements' => [], // Will be fetched via API
            'userAchievements' => [],
            'stats' => [],
        ]);
    })->name('achievements');

    // Leaderboard
    Route::get('/leaderboard', function () {
        $profile = app(ProfileController::class)->show(request())->getData(true);

        return Inertia::render('English/Leaderboard', [
            'leaderboards' => [],
            'userRanks' => [],
            'profile' => $profile['data'] ?? null,
        ]);
    })->name('leaderboard');

    // Profile
    Route::get('/profile', function () {
        $profile = app(ProfileController::class)->show(request())->getData(true);

        return Inertia::render('English/Profile', [
            'profile' => $profile['data'] ?? null,
            'stats' => [],
            'recentActivity' => [],
            'achievements' => [],
        ]);
    })->name('profile');
});
