<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\DailyChallengeDataService;
use App\Services\English\DailyChallengeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyChallengeController extends Controller
{
    protected DailyChallengeDataService $dataService;
    protected DailyChallengeService $gameService;

    public function __construct(DailyChallengeDataService $dataService, DailyChallengeService $gameService)
    {
        $this->dataService = $dataService;
        $this->gameService = $gameService;
    }

    /**
     * Display the game index page
     */
    public function index()
    {
        $levels = $this->dataService->getLevelsWithStatus();
        $stats = $this->dataService->getUserStats();
        $config = $this->dataService->getConfig();
        $achievements = $this->dataService->getUserAchievements();
        $categories = $this->dataService->getCategories();
        $challengeTypes = $this->dataService->getChallengeTypes();
        $totalStars = $this->dataService->getTotalStars();
        $quizMasterRanks = $this->dataService->getQuizMasterRanks();
        $streakRewards = $this->dataService->getStreakRewards();
        $isDailyCompleted = $this->dataService->isDailyChallengeCompleted();

        return Inertia::render('English/Games/DailyChallenge/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'categories' => $categories,
            'challengeTypes' => $challengeTypes,
            'totalStars' => $totalStars,
            'quizMasterRanks' => $quizMasterRanks,
            'streakRewards' => $streakRewards,
            'isDailyCompleted' => $isDailyCompleted,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.daily-challenge.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.daily-challenge.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $powerups = $this->dataService->getPowerups();
        $challengeTypes = $this->dataService->getChallengeTypes();

        return Inertia::render('English/Games/DailyChallenge/Play', [
            'level' => $levelData,
            'config' => $config,
            'powerups' => $powerups,
            'challengeTypes' => $challengeTypes,
        ]);
    }

    /**
     * Display daily challenge page
     */
    public function daily()
    {
        $config = $this->dataService->getConfig();
        $powerups = $this->dataService->getPowerups();
        $isDailyCompleted = $this->dataService->isDailyChallengeCompleted();
        $stats = $this->dataService->getUserStats();

        return Inertia::render('English/Games/DailyChallenge/Daily', [
            'config' => $config,
            'powerups' => $powerups,
            'isDailyCompleted' => $isDailyCompleted,
            'stats' => $stats,
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $request->validate([
            'challenge_type' => 'nullable|string',
        ]);

        try {
            $challengeType = $request->input('challenge_type', 'daily');
            $session = $this->gameService->startSession($level, $challengeType);

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start daily challenge
     */
    public function startDailyChallenge()
    {
        try {
            $session = $this->gameService->startDailyChallenge();

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'question_index' => 'required|integer|min:0',
            'answer' => 'required|string',
            'time_spent' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->submitAnswer(
                $request->input('session_id'),
                $request->input('question_index'),
                $request->input('answer'),
                $request->input('time_spent')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Use a powerup
     */
    public function usePowerup(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'powerup_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->usePowerup(
                $request->input('session_id'),
                $request->input('powerup_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update time remaining
     */
    public function updateTime(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'time_remaining' => 'required|integer|min:0',
        ]);

        try {
            $result = $this->gameService->updateTimeRemaining(
                $request->input('session_id'),
                $request->input('time_remaining')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete a session
     */
    public function completeSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->completeSession($request->input('session_id'));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current session state
     */
    public function getSessionState(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $state = $this->gameService->getSessionState($request->input('session_id'));

        if (!$state) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $state,
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        $stats = $this->dataService->getUserStats();
        $achievements = $this->dataService->getUserAchievements();
        $currentRank = $this->dataService->getCurrentRank();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
                'current_rank' => $currentRank,
            ],
        ]);
    }

    /**
     * Get streak information
     */
    public function getStreakInfo()
    {
        $stats = $this->dataService->getUserStats();
        $streakRewards = $this->dataService->getStreakRewards();

        return response()->json([
            'success' => true,
            'data' => [
                'current_streak' => $stats['current_streak'] ?? 0,
                'best_streak' => $stats['best_streak'] ?? 0,
                'streak_rewards' => $streakRewards,
            ],
        ]);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'daily' => [],
                'weekly' => [],
                'all_time' => [],
            ],
        ]);
    }
}
