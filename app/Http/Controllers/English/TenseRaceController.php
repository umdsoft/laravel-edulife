<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\TenseRaceDataService;
use App\Services\English\TenseRaceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenseRaceController extends Controller
{
    protected TenseRaceDataService $dataService;
    protected TenseRaceService $gameService;

    public function __construct(TenseRaceDataService $dataService, TenseRaceService $gameService)
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
        $tenseCategories = $this->dataService->getTenseCategories();
        $gameModes = $this->dataService->getGameModes();
        $totalStars = $this->dataService->getTotalStars();
        $tenseMasterRanks = $this->dataService->getTenseMasterRanks();

        return Inertia::render('English/Games/TenseRace/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'tenseCategories' => $tenseCategories,
            'gameModes' => $gameModes,
            'totalStars' => $totalStars,
            'tenseMasterRanks' => $tenseMasterRanks,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.tense-race.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.tense-race.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $powerups = $this->dataService->getPowerups();
        $tenseCategories = $this->dataService->getTenseCategories();

        return Inertia::render('English/Games/TenseRace/Play', [
            'level' => $levelData,
            'config' => $config,
            'gameModes' => $gameModes,
            'powerups' => $powerups,
            'tenseCategories' => $tenseCategories,
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
            $mode = $request->input('mode', 'mixed');
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
            'question_id' => 'required|string',
            'answer' => 'required|integer|min:0|max:3',
            'time' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->checkAnswer(
                $request->input('session_id'),
                $request->input('question_id'),
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
        $tenseStats = $stats['tense_stats'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
                'tense_stats' => $tenseStats,
            ],
        ]);
    }

    /**
     * Get tense category information
     */
    public function getTenseInfo(string $tenseId)
    {
        $tenseCategories = $this->dataService->getTenseCategories();
        $tense = null;

        foreach ($tenseCategories as $tc) {
            if ($tc['id'] === $tenseId) {
                $tense = $tc;
                break;
            }
        }

        if (!$tense) {
            return response()->json([
                'success' => false,
                'message' => 'Tense not found',
            ], 404);
        }

        $progress = $this->dataService->getUserProgress();
        $tenseStats = $progress['tense_stats'][$tenseId] ?? [
            'correct' => 0,
            'total' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'tense' => $tense,
                'stats' => $tenseStats,
                'accuracy' => $tenseStats['total'] > 0
                    ? round(($tenseStats['correct'] / $tenseStats['total']) * 100)
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
