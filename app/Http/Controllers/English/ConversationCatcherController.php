<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\ConversationCatcherDataService;
use App\Services\English\ConversationCatcherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationCatcherController extends Controller
{
    protected ConversationCatcherDataService $dataService;
    protected ConversationCatcherService $gameService;

    public function __construct(ConversationCatcherDataService $dataService, ConversationCatcherService $gameService)
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
        $conversationMasterRanks = $this->dataService->getConversationMasterRanks();

        return Inertia::render('English/Games/ConversationCatcher/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'gameModes' => $gameModes,
            'categories' => $categories,
            'totalStars' => $totalStars,
            'conversationMasterRanks' => $conversationMasterRanks,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.conversation-catcher.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.conversation-catcher.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $powerups = $this->dataService->getPowerups();

        return Inertia::render('English/Games/ConversationCatcher/Play', [
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
     * Submit an answer
     */
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'dialogue_index' => 'required|integer|min:0',
            'answer' => 'required|string',
            'time_spent' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->submitAnswer(
                $request->input('session_id'),
                $request->input('dialogue_index'),
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
        $categoryStats = $this->dataService->getCategoryStats();

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
     * Get dialogues by category
     */
    public function getDialoguesByCategory(string $categoryId)
    {
        $dialogues = $this->dataService->getDialoguesByCategory($categoryId);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $categoryId,
                'dialogues_count' => count($dialogues),
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
