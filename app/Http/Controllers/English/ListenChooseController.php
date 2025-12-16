<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\ListenChooseDataService;
use App\Services\English\ListenChooseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListenChooseController extends Controller
{
    protected ListenChooseDataService $dataService;
    protected ListenChooseService $gameService;

    public function __construct(ListenChooseDataService $dataService, ListenChooseService $gameService)
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
        $audioCategories = $this->dataService->getAudioCategories();
        $gameModes = $this->dataService->getGameModes();
        $totalStars = $this->dataService->getTotalStars();
        $listeningRanks = $this->dataService->getListeningRanks();

        return Inertia::render('English/Games/ListenChoose/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'audioCategories' => $audioCategories,
            'gameModes' => $gameModes,
            'totalStars' => $totalStars,
            'listeningRanks' => $listeningRanks,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.listen-choose.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.listen-choose.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $powerups = $this->dataService->getPowerups();
        $audioCategories = $this->dataService->getAudioCategories();
        $audioSettings = $this->dataService->getAudioSettings();

        return Inertia::render('English/Games/ListenChoose/Play', [
            'level' => $levelData,
            'config' => $config,
            'gameModes' => $gameModes,
            'powerups' => $powerups,
            'audioCategories' => $audioCategories,
            'audioSettings' => $audioSettings,
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
            $mode = $request->input('mode', 'word');
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
            'item_id' => 'required|string',
            'answer' => 'required|integer|min:0|max:3',
            'time' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->checkAnswer(
                $request->input('session_id'),
                $request->input('item_id'),
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
     * Use replay
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
            'final_time' => 'nullable|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->completeSession(
                $request->input('session_id'),
                $request->input('final_time')
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

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
            ],
        ]);
    }

    /**
     * Get audio category information
     */
    public function getCategoryInfo(string $categoryId)
    {
        $audioCategories = $this->dataService->getAudioCategories();
        $category = null;

        foreach ($audioCategories as $cat) {
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

        $progress = $this->dataService->getUserProgress();
        $categoryStats = $progress['category_stats'][$categoryId] ?? [
            'correct' => 0,
            'total' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'stats' => $categoryStats,
                'accuracy' => $categoryStats['total'] > 0
                    ? round(($categoryStats['correct'] / $categoryStats['total']) * 100)
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
