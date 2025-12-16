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

use App\Http\Controllers\English\GrammarQuizController;
use App\Http\Controllers\English\RapidFireController;
use App\Http\Controllers\English\TenseRaceController;
use App\Http\Controllers\English\ListenChooseController;
use App\Http\Controllers\English\SequenceRecallController;
use App\Http\Controllers\English\TrueFalseController;
use App\Http\Controllers\English\HangmanController;
use App\Http\Controllers\English\NumberListenerController;
use App\Http\Controllers\English\SpellingBeeController;
use App\Http\Controllers\English\WordRecallController;
use App\Http\Controllers\English\TypingRaceController;
use App\Http\Controllers\English\FillTheGapController;
use App\Http\Controllers\English\VerbConjugatorController;
use App\Http\Controllers\English\BeatTheClockController;
use App\Http\Controllers\English\ConversationCatcherController;
use App\Http\Controllers\English\DailyChallengeController;
use App\Http\Controllers\English\WordSearchController;
use App\Http\Controllers\English\CrosswordPuzzleController;
use App\Http\Controllers\English\ArticleMasterController;
use App\Http\Controllers\English\WordChainController;
use App\Http\Controllers\English\AnagramSolverController;

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

            'grammar_quiz' => '/student/english/games/grammar-quiz',
            'rapid_fire' => '/student/english/games/rapid-fire',
            'tense_race' => '/student/english/games/tense-race',
            'listen_choose' => '/student/english/games/listen-choose',
            'sequence_recall' => '/student/english/games/sequence-recall',
            'true_false' => '/student/english/games/true-false',
            'hangman' => '/student/english/games/hangman',
            'number_listener' => '/student/english/games/number-listener',
            'spelling_bee' => '/student/english/games/spelling-bee',
            'word_recall' => '/student/english/games/word-recall',
            'typing_race' => '/student/english/games/typing-race',
            'fill_the_gap' => '/student/english/games/fill-the-gap',
            'verb_conjugator' => '/student/english/games/verb-conjugator',
            'beat_the_clock' => '/student/english/games/beat-the-clock',
            'conversation_catcher' => '/student/english/games/conversation-catcher',
            'daily_challenge' => '/student/english/games/daily-challenge',
            'word_search' => '/student/english/games/word-search',
            'crossword_puzzle' => '/student/english/games/crossword-puzzle',
            'article_master' => '/student/english/games/article-master',
            'word_chain' => '/student/english/games/word-chain',
            'anagram_solver' => '/student/english/games/anagram-solver',
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



        // Grammar Quiz Routes
        Route::prefix('grammar-quiz')->name('grammar-quiz.')->group(function () {
            Route::get('/', [GrammarQuizController::class, 'index'])->name('index');
            Route::get('/play/{level}', [GrammarQuizController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [GrammarQuizController::class, 'startSession'])->name('start');
            Route::post('/check', [GrammarQuizController::class, 'checkAnswer'])->name('check');
            Route::post('/hint', [GrammarQuizController::class, 'useHint'])->name('hint');
            Route::post('/skip', [GrammarQuizController::class, 'skipQuestion'])->name('skip');
            Route::post('/extra-time', [GrammarQuizController::class, 'addExtraTime'])->name('extra-time');
            Route::post('/fifty-fifty', [GrammarQuizController::class, 'useFiftyFifty'])->name('fifty-fifty');
            Route::post('/double-points', [GrammarQuizController::class, 'activateDoublePoints'])->name('double-points');
            Route::post('/complete', [GrammarQuizController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [GrammarQuizController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [GrammarQuizController::class, 'getStats'])->name('stats');
            Route::get('/category/{categoryId}', [GrammarQuizController::class, 'getCategoryInfo'])->name('category');
        });

        // Rapid Fire Routes
        Route::prefix('rapid-fire')->name('rapid-fire.')->group(function () {
            Route::get('/', [RapidFireController::class, 'index'])->name('index');
            Route::get('/play/{level}', [RapidFireController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [RapidFireController::class, 'startSession'])->name('start');
            Route::post('/check', [RapidFireController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [RapidFireController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [RapidFireController::class, 'completeSession'])->name('complete');
            Route::post('/update-time', [RapidFireController::class, 'updateTime'])->name('update-time');
            Route::post('/session-state', [RapidFireController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [RapidFireController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [RapidFireController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [RapidFireController::class, 'getCategoryInfo'])->name('category');
        });

        // Tense Race Routes
        Route::prefix('tense-race')->name('tense-race.')->group(function () {
            Route::get('/', [TenseRaceController::class, 'index'])->name('index');
            Route::get('/play/{level}', [TenseRaceController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [TenseRaceController::class, 'startSession'])->name('start');
            Route::post('/check', [TenseRaceController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [TenseRaceController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [TenseRaceController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [TenseRaceController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [TenseRaceController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [TenseRaceController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/tense/{tenseId}', [TenseRaceController::class, 'getTenseInfo'])->name('tense');
        });

        // Listen & Choose Routes
        Route::prefix('listen-choose')->name('listen-choose.')->group(function () {
            Route::get('/', [ListenChooseController::class, 'index'])->name('index');
            Route::get('/play/{level}', [ListenChooseController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [ListenChooseController::class, 'startSession'])->name('start');
            Route::post('/check', [ListenChooseController::class, 'checkAnswer'])->name('check');
            Route::post('/replay', [ListenChooseController::class, 'useReplay'])->name('replay');
            Route::post('/powerup', [ListenChooseController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [ListenChooseController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [ListenChooseController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [ListenChooseController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [ListenChooseController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [ListenChooseController::class, 'getCategoryInfo'])->name('category');
        });

        // Sequence Recall Routes
        Route::prefix('sequence-recall')->name('sequence-recall.')->group(function () {
            Route::get('/', [SequenceRecallController::class, 'index'])->name('index');
            Route::get('/play/{level}', [SequenceRecallController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [SequenceRecallController::class, 'startSession'])->name('start');
            Route::post('/next-sequence', [SequenceRecallController::class, 'getNextSequence'])->name('next-sequence');
            Route::post('/check', [SequenceRecallController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [SequenceRecallController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [SequenceRecallController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [SequenceRecallController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [SequenceRecallController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [SequenceRecallController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/mode/{modeId}', [SequenceRecallController::class, 'getModeInfo'])->name('mode');
        });

        // True or False Routes
        Route::prefix('true-false')->name('true-false.')->group(function () {
            Route::get('/', [TrueFalseController::class, 'index'])->name('index');
            Route::get('/play/{level}', [TrueFalseController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [TrueFalseController::class, 'startSession'])->name('start');
            Route::post('/check', [TrueFalseController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [TrueFalseController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [TrueFalseController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [TrueFalseController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [TrueFalseController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [TrueFalseController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [TrueFalseController::class, 'getCategoryInfo'])->name('category');
        });

        // Hangman Routes
        Route::prefix('hangman')->name('hangman.')->group(function () {
            Route::get('/', [HangmanController::class, 'index'])->name('index');
            Route::get('/play/{level}', [HangmanController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [HangmanController::class, 'startSession'])->name('start');
            Route::post('/guess', [HangmanController::class, 'guessLetter'])->name('guess');
            Route::post('/next-word', [HangmanController::class, 'nextWord'])->name('next-word');
            Route::post('/powerup', [HangmanController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [HangmanController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [HangmanController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [HangmanController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [HangmanController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [HangmanController::class, 'getCategoryInfo'])->name('category');
        });

        // Number Listener Routes
        Route::prefix('number-listener')->name('number-listener.')->group(function () {
            Route::get('/', [NumberListenerController::class, 'index'])->name('index');
            Route::get('/play/{level}', [NumberListenerController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [NumberListenerController::class, 'startSession'])->name('start');
            Route::post('/check', [NumberListenerController::class, 'checkAnswer'])->name('check');
            Route::post('/replay', [NumberListenerController::class, 'useReplay'])->name('replay');
            Route::post('/powerup', [NumberListenerController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [NumberListenerController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [NumberListenerController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [NumberListenerController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [NumberListenerController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [NumberListenerController::class, 'getCategoryInfo'])->name('category');
        });

        // Spelling Bee Routes
        Route::prefix('spelling-bee')->name('spelling-bee.')->group(function () {
            Route::get('/', [SpellingBeeController::class, 'index'])->name('index');
            Route::get('/play/{level}', [SpellingBeeController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [SpellingBeeController::class, 'startSession'])->name('start');
            Route::post('/check', [SpellingBeeController::class, 'checkAnswer'])->name('check');
            Route::post('/hint', [SpellingBeeController::class, 'useHint'])->name('hint');
            Route::post('/powerup', [SpellingBeeController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [SpellingBeeController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [SpellingBeeController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [SpellingBeeController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [SpellingBeeController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [SpellingBeeController::class, 'getCategoryInfo'])->name('category');
        });

        // Word Recall Routes
        Route::prefix('word-recall')->name('word-recall.')->group(function () {
            Route::get('/', [WordRecallController::class, 'index'])->name('index');
            Route::get('/play/{level}', [WordRecallController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [WordRecallController::class, 'startSession'])->name('start');
            Route::post('/next-round', [WordRecallController::class, 'getNextRound'])->name('next-round');
            Route::post('/check-round', [WordRecallController::class, 'checkRound'])->name('check-round');
            Route::post('/powerup', [WordRecallController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [WordRecallController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [WordRecallController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [WordRecallController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [WordRecallController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [WordRecallController::class, 'getCategoryInfo'])->name('category');
        });

        // Typing Race Routes
        Route::prefix('typing-race')->name('typing-race.')->group(function () {
            Route::get('/', [TypingRaceController::class, 'index'])->name('index');
            Route::get('/play/{level}', [TypingRaceController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [TypingRaceController::class, 'startSession'])->name('start');
            Route::post('/submit', [TypingRaceController::class, 'submitText'])->name('submit');
            Route::post('/powerup', [TypingRaceController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [TypingRaceController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [TypingRaceController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [TypingRaceController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [TypingRaceController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [TypingRaceController::class, 'getCategoryInfo'])->name('category');
        });

        // Fill the Gap Routes
        Route::prefix('fill-the-gap')->name('fill-the-gap.')->group(function () {
            Route::get('/', [FillTheGapController::class, 'index'])->name('index');
            Route::get('/play/{level}', [FillTheGapController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [FillTheGapController::class, 'startSession'])->name('start');
            Route::post('/check', [FillTheGapController::class, 'checkAnswer'])->name('check');
            Route::post('/powerup', [FillTheGapController::class, 'usePowerup'])->name('powerup');
            Route::post('/complete', [FillTheGapController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [FillTheGapController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [FillTheGapController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [FillTheGapController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [FillTheGapController::class, 'getCategoryInfo'])->name('category');
        });

        // Verb Conjugator Routes
        Route::prefix('verb-conjugator')->name('verb-conjugator.')->group(function () {
            Route::get('/', [VerbConjugatorController::class, 'index'])->name('index');
            Route::get('/play/{level}', [VerbConjugatorController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [VerbConjugatorController::class, 'startSession'])->name('start-session');
            Route::post('/answer', [VerbConjugatorController::class, 'submitAnswer'])->name('submit-answer');
            Route::post('/powerup', [VerbConjugatorController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/complete', [VerbConjugatorController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [VerbConjugatorController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [VerbConjugatorController::class, 'getStats'])->name('stats');
            Route::get('/tense/{tenseId}', [VerbConjugatorController::class, 'getTenseInfo'])->name('tense-info');
            Route::get('/conjugation', [VerbConjugatorController::class, 'getVerbConjugation'])->name('verb-conjugation');
        });

        // Beat the Clock Routes
        Route::prefix('beat-the-clock')->name('beat-the-clock.')->group(function () {
            Route::get('/', [BeatTheClockController::class, 'index'])->name('index');
            Route::get('/play/{level}', [BeatTheClockController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [BeatTheClockController::class, 'startSession'])->name('start-session');
            Route::post('/answer', [BeatTheClockController::class, 'submitAnswer'])->name('submit-answer');
            Route::post('/powerup', [BeatTheClockController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/update-time', [BeatTheClockController::class, 'updateTime'])->name('update-time');
            Route::post('/complete', [BeatTheClockController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [BeatTheClockController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [BeatTheClockController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [BeatTheClockController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [BeatTheClockController::class, 'getCategoryInfo'])->name('category-info');
        });

        // Conversation Catcher Routes
        Route::prefix('conversation-catcher')->name('conversation-catcher.')->group(function () {
            Route::get('/', [ConversationCatcherController::class, 'index'])->name('index');
            Route::get('/play/{level}', [ConversationCatcherController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [ConversationCatcherController::class, 'startSession'])->name('start-session');
            Route::post('/answer', [ConversationCatcherController::class, 'submitAnswer'])->name('submit-answer');
            Route::post('/powerup', [ConversationCatcherController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/update-time', [ConversationCatcherController::class, 'updateTime'])->name('update-time');
            Route::post('/complete', [ConversationCatcherController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [ConversationCatcherController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [ConversationCatcherController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [ConversationCatcherController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [ConversationCatcherController::class, 'getDialoguesByCategory'])->name('category-info');
        });

        // Daily Challenge Routes
        Route::prefix('daily-challenge')->name('daily-challenge.')->group(function () {
            Route::get('/', [DailyChallengeController::class, 'index'])->name('index');
            Route::get('/play/{level}', [DailyChallengeController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [DailyChallengeController::class, 'startSession'])->name('start-session');
            Route::post('/answer', [DailyChallengeController::class, 'submitAnswer'])->name('submit-answer');
            Route::post('/powerup', [DailyChallengeController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/update-time', [DailyChallengeController::class, 'updateTime'])->name('update-time');
            Route::post('/complete', [DailyChallengeController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [DailyChallengeController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [DailyChallengeController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [DailyChallengeController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [DailyChallengeController::class, 'getCategoryInfo'])->name('category-info');
            Route::get('/daily', [DailyChallengeController::class, 'getDailyChallenge'])->name('daily');
            Route::get('/weekly', [DailyChallengeController::class, 'getWeeklyChallenge'])->name('weekly');
        });

        // Word Search Routes
        Route::prefix('word-search')->name('word-search.')->group(function () {
            Route::get('/', [WordSearchController::class, 'index'])->name('index');
            Route::get('/play/{level}', [WordSearchController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [WordSearchController::class, 'startSession'])->name('start-session');
            Route::post('/check-word', [WordSearchController::class, 'checkWord'])->name('check-word');
            Route::post('/powerup', [WordSearchController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/complete', [WordSearchController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [WordSearchController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [WordSearchController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [WordSearchController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [WordSearchController::class, 'getCategoryInfo'])->name('category-info');
        });

        // Crossword Puzzle Routes
        Route::prefix('crossword-puzzle')->name('crossword-puzzle.')->group(function () {
            Route::get('/', [CrosswordPuzzleController::class, 'index'])->name('index');
            Route::get('/play/{level}', [CrosswordPuzzleController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [CrosswordPuzzleController::class, 'startSession'])->name('start-session');
            Route::post('/submit-word', [CrosswordPuzzleController::class, 'submitWord'])->name('submit-word');
            Route::post('/update-cell', [CrosswordPuzzleController::class, 'updateCell'])->name('update-cell');
            Route::post('/powerup', [CrosswordPuzzleController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/update-time', [CrosswordPuzzleController::class, 'updateTime'])->name('update-time');
            Route::post('/complete', [CrosswordPuzzleController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [CrosswordPuzzleController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [CrosswordPuzzleController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [CrosswordPuzzleController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [CrosswordPuzzleController::class, 'getCategoryInfo'])->name('category-info');
        });

        // Article Master Routes
        Route::prefix('article-master')->name('article-master.')->group(function () {
            Route::get('/', [ArticleMasterController::class, 'index'])->name('index');
            Route::get('/play/{level}', [ArticleMasterController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [ArticleMasterController::class, 'startSession'])->name('start-session');
            Route::post('/answer', [ArticleMasterController::class, 'submitAnswer'])->name('submit-answer');
            Route::post('/powerup', [ArticleMasterController::class, 'usePowerup'])->name('use-powerup');
            Route::post('/update-time', [ArticleMasterController::class, 'updateTime'])->name('update-time');
            Route::post('/complete', [ArticleMasterController::class, 'completeSession'])->name('complete-session');
            Route::post('/session-state', [ArticleMasterController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [ArticleMasterController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [ArticleMasterController::class, 'getLeaderboard'])->name('leaderboard');
            Route::get('/category/{categoryId}', [ArticleMasterController::class, 'getCategoryInfo'])->name('category-info');
        });

        // Word Chain Routes
        Route::prefix('word-chain')->name('word-chain.')->group(function () {
            Route::get('/', [WordChainController::class, 'index'])->name('index');
            Route::get('/play/{level}', [WordChainController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [WordChainController::class, 'startSession'])->name('start');
            Route::post('/submit', [WordChainController::class, 'submitWord'])->name('submit');
            Route::post('/hint', [WordChainController::class, 'getHint'])->name('hint');
            Route::post('/skip', [WordChainController::class, 'skipLetter'])->name('skip');
            Route::post('/powerup', [WordChainController::class, 'usePowerup'])->name('powerup');
            Route::post('/time', [WordChainController::class, 'updateTime'])->name('time');
            Route::post('/complete', [WordChainController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [WordChainController::class, 'getSessionState'])->name('session-state');
            Route::post('/available', [WordChainController::class, 'getAvailableWords'])->name('available');
            Route::get('/stats', [WordChainController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [WordChainController::class, 'getLeaderboard'])->name('leaderboard');
        });

        // Anagram Solver Routes
        Route::prefix('anagram-solver')->name('anagram-solver.')->group(function () {
            Route::get('/', [AnagramSolverController::class, 'index'])->name('index');
            Route::get('/play/{level}', [AnagramSolverController::class, 'play'])->name('play');

            // API endpoints
            Route::post('/start/{level}', [AnagramSolverController::class, 'startSession'])->name('start');
            Route::post('/answer', [AnagramSolverController::class, 'submitAnswer'])->name('answer');
            Route::post('/hint', [AnagramSolverController::class, 'getHint'])->name('hint');
            Route::post('/shuffle', [AnagramSolverController::class, 'shuffleWord'])->name('shuffle');
            Route::post('/skip', [AnagramSolverController::class, 'skipWord'])->name('skip');
            Route::post('/reveal', [AnagramSolverController::class, 'revealWord'])->name('reveal');
            Route::post('/powerup', [AnagramSolverController::class, 'usePowerup'])->name('powerup');
            Route::post('/time', [AnagramSolverController::class, 'updateTime'])->name('time');
            Route::post('/complete', [AnagramSolverController::class, 'completeSession'])->name('complete');
            Route::post('/session-state', [AnagramSolverController::class, 'getSessionState'])->name('session-state');
            Route::get('/stats', [AnagramSolverController::class, 'getStats'])->name('stats');
            Route::get('/leaderboard', [AnagramSolverController::class, 'getLeaderboard'])->name('leaderboard');
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
