<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\NumberListenerDataService;
use App\Services\English\NumberListenerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NumberListenerController extends Controller
{
    protected NumberListenerDataService $dataService;
    protected NumberListenerService $gameService;

    public function __construct(NumberListenerDataService $dataService, NumberListenerService $gameService)
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
        $gameModes = $this->dataService->getGameModes();
        $categories = $this->dataService->getCategories();
        $totalStars = $this->dataService->getTotalStars();
        $numberMasterRanks = $this->dataService->getNumberMasterRanks();

        return Inertia::render('English/Games/NumberListener/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'gameModes' => $gameModes,
            'categories' => $categories,
            'totalStars' => $totalStars,
            'numberMasterRanks' => $numberMasterRanks,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.number-listener.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.number-listener.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $powerups = $this->dataService->getPowerups();

        return Inertia::render('English/Games/NumberListener/Play', [
            'level' => $levelData,
            'config' => $config,
            'gameModes' => $gameModes,
            'powerups' => $powerups,
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $request->validate([
            'mode' => 'nullable|string',
        ]);

        try {
            $mode = $request->input('mode', 'classic');
            $session = $this->gameService->startSession($level, $mode);

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
     * Check an answer
     */
    public function checkAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'answer' => 'required|string',
            'time' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->checkAnswer(
                $request->input('session_id'),
                $request->input('answer'),
                $request->input('time')
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
     * Use a replay
     */
    public function useReplay(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->useReplay($request->input('session_id'));

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
        $categoryStats = $stats['category_stats'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
                'category_stats' => $categoryStats,
            ],
        ]);
    }

    /**
     * Get category information
     */
    public function getCategoryInfo(string $categoryId)
    {
        $categories = $this->dataService->getCategories();
        $category = null;

        foreach ($categories as $cat) {
            if ($cat['id'] === $categoryId) {
                $category = $cat;
                break;
            }
        }

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $stats = $this->dataService->getUserStats();
        $categoryStats = $stats['category_stats'][$categoryId] ?? [
            'correct' => 0,
            'attempted' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'stats' => $categoryStats,
                'accuracy' => $categoryStats['attempted'] > 0
                    ? round(($categoryStats['correct'] / $categoryStats['attempted']) * 100)
                    : 0,
            ],
        ]);
    }

    /**
     * Get leaderboard (placeholder for future implementation)
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
