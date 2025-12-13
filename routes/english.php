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
use App\Http\Controllers\English\SentenceBuilderController;
use App\Http\Controllers\English\WordBlitzController;
use App\Http\Controllers\English\FlashcardController;
use App\Http\Controllers\English\DictationController;
use App\Http\Controllers\English\VocabularyQuizController;
use App\Http\Controllers\English\WordScrambleController;
use App\Http\Controllers\English\WordMatchController;
use App\Http\Controllers\English\ErrorHunterController;
use App\Http\Controllers\English\MinimalPairsController;

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
        // Game-specific routes mapping
        $gameRoutes = [
            'word_blitz' => '/student/english/games/word-blitz',
            'flashcard' => '/student/english/games/flashcard',
            'flashcard_flip' => '/student/english/games/flashcard',
            'sentence_builder' => '/student/english/games/sentence-builder',
            'dictation' => '/student/english/games/dictation',
            'vocabulary_quiz' => '/student/english/games/vocabulary-quiz',
            'word_scramble' => '/student/english/games/word-scramble',
            'word_match' => '/student/english/games/word-match',
            'error_hunter' => '/student/english/games/error-hunter',
            'minimal_pairs' => '/student/english/games/minimal-pairs',
        ];
        
        $games = \App\Models\English\EnglishGame::with('category')
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->map(function ($game) use ($gameRoutes) {
                // Determine route: use specific route or fallback to generic
                $routeUrl = $gameRoutes[$game->code] ?? "/student/english/games/{$game->id}/play";
                
                return [
                    'id' => $game->id,
                    'code' => $game->code,
                    'name' => $game->name,
                    'name_uz' => $game->name_uz,
                    'game_type' => $game->code,
                    'icon' => $game->icon ?? '🎮',
                    'description' => $game->description_uz ?? $game->description,
                    'color' => $game->color ?? 'from-blue-500 to-blue-600',
                    'is_premium' => $game->is_premium,
                    'route_url' => $routeUrl,
                    'totalLevels' => $game->total_levels ?? 10,
                    'currentLevel' => 1,
                    'best_stars' => 0,
                    'timesPlayed' => 0,
                    'xpReward' => $game->xp_reward ?? 50,
                ];
            });
        
        return Inertia::render('English/Games', [
            'games' => $games,
        ]);
    })->name('games.index');

    Route::get('/games/{gameId}/play', function ($gameId) {
        $game = \App\Models\English\EnglishGame::with('levels')->find($gameId);
        
        if (!$game) {
            abort(404, 'Game not found');
        }
        
        $level = $game->levels->first();
        
        return Inertia::render('English/GamePlay', [
            'gameId' => $gameId,
            'game' => $game,
            'level' => $level,
            'content' => [], // Will be loaded in component
            'attempt' => null,
        ]);
    })->name('games.play');

    // Games Section
    Route::prefix('games')->name('games.')->group(function () {
        
        // Word Blitz Routes
        Route::prefix('word-blitz')->name('word-blitz.')->group(function () {
            Route::get('/', [WordBlitzController::class, 'index'])->name('index');
            Route::get('/play/{level}', [WordBlitzController::class, 'play'])->name('play');
            
            // API endpoints
            Route::post('/check', [WordBlitzController::class, 'checkAnswer'])->name('check');
            Route::post('/complete', [WordBlitzController::class, 'completeSession'])->name('complete');
        });

        // Flashcard Flip Routes
        Route::prefix('flashcard')->name('flashcard.')->group(function () {
            Route::get('/', [FlashcardController::class, 'index'])->name('index');
            Route::get('/play/{levelNumber}', [FlashcardController::class, 'play'])->name('play');
            
            // API endpoints
            Route::post('/check', [FlashcardController::class, 'checkAnswer'])->name('check');
            Route::post('/complete', [FlashcardController::class, 'completeSession'])->name('complete');
        });

        // Sentence Builder Routes
        Route::prefix('sentence-builder')->name('sentence-builder.')->group(function () {
            Route::get('/', [SentenceBuilderController::class, 'index'])->name('index');
            Route::get('/play/{levelNumber}', [SentenceBuilderController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/check', [SentenceBuilderController::class, 'checkAnswer'])->name('check');
            Route::post('/hint', [SentenceBuilderController::class, 'getHint'])->name('hint');
            Route::post('/complete', [SentenceBuilderController::class, 'complete'])->name('complete');
        });

        // Dictation Routes
        Route::prefix('dictation')->name('dictation.')->group(function () {
            Route::get('/', [DictationController::class, 'index'])->name('index');
            Route::get('/play/{level}', [DictationController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [DictationController::class, 'startSession'])->name('start');
            Route::post('/check', [DictationController::class, 'checkAnswer'])->name('check');
            Route::post('/hint', [DictationController::class, 'useHint'])->name('hint');
            Route::post('/replay', [DictationController::class, 'recordReplay'])->name('replay');
            Route::post('/skip', [DictationController::class, 'skipItem'])->name('skip');
            Route::post('/complete', [DictationController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [DictationController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [DictationController::class, 'getStats'])->name('stats');
        });

        // Vocabulary Quiz Routes
        Route::prefix('vocabulary-quiz')->name('vocabulary-quiz.')->group(function () {
            Route::get('/', [VocabularyQuizController::class, 'index'])->name('index');
            Route::get('/play/{level}', [VocabularyQuizController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [VocabularyQuizController::class, 'startSession'])->name('start');
            Route::post('/check', [VocabularyQuizController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [VocabularyQuizController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [VocabularyQuizController::class, 'completeSession'])->name('complete');
            Route::get('/stats', [VocabularyQuizController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [VocabularyQuizController::class, 'getLeaderboard'])->name('leaderboard');
        });

        // Word Scramble Routes
        Route::prefix('word-scramble')->name('word-scramble.')->group(function () {
            Route::get('/', [WordScrambleController::class, 'index'])->name('index');
            Route::get('/play/{level}', [WordScrambleController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [WordScrambleController::class, 'startSession'])->name('start');
            Route::post('/check', [WordScrambleController::class, 'checkAnswer'])->name('check');
            Route::post('/hint', [WordScrambleController::class, 'useHint'])->name('hint');
            Route::post('/skip', [WordScrambleController::class, 'skipWord'])->name('skip');
            Route::post('/complete', [WordScrambleController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [WordScrambleController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [WordScrambleController::class, 'getStats'])->name('stats');
        });

        // Word Match Routes
        Route::prefix('word-match')->name('word-match.')->group(function () {
            Route::get('/', [WordMatchController::class, 'index'])->name('index');
            Route::get('/play/{level}', [WordMatchController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [WordMatchController::class, 'startSession'])->name('start');
            Route::post('/check', [WordMatchController::class, 'checkMatch'])->name('check');
            Route::post('/check-speed', [WordMatchController::class, 'checkSpeedAnswer'])->name('check-speed');
            Route::post('/powerup', [WordMatchController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [WordMatchController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [WordMatchController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [WordMatchController::class, 'getStats'])->name('stats');
        });

        // Error Hunter Routes
        Route::prefix('error-hunter')->name('error-hunter.')->group(function () {
            Route::get('/', [ErrorHunterController::class, 'index'])->name('index');
            Route::get('/play/{level}', [ErrorHunterController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [ErrorHunterController::class, 'startSession'])->name('start');
            Route::post('/check', [ErrorHunterController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [ErrorHunterController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [ErrorHunterController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [ErrorHunterController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [ErrorHunterController::class, 'getStats'])->name('stats');
        });

        // Minimal Pairs Routes
        Route::prefix('minimal-pairs')->name('minimal-pairs.')->group(function () {
            Route::get('/', [MinimalPairsController::class, 'index'])->name('index');
            Route::get('/play/{level}', [MinimalPairsController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [MinimalPairsController::class, 'startSession'])->name('start');
            Route::post('/check', [MinimalPairsController::class, 'checkAnswer'])->name('check');
            Route::post('/replay', [MinimalPairsController::class, 'useReplay'])->name('replay');
            Route::post('/powerup', [MinimalPairsController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [MinimalPairsController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [MinimalPairsController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [MinimalPairsController::class, 'getStats'])->name('stats');
            Route::get('/category/{categoryId}', [MinimalPairsController::class, 'getCategoryInfo'])->name('category');
        });
    });

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
