<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\SequenceRecallDataService;
use App\Services\English\SequenceRecallService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SequenceRecallController extends Controller
{
    protected SequenceRecallDataService $dataService;
    protected SequenceRecallService $gameService;

    public function __construct(SequenceRecallDataService $dataService, SequenceRecallService $gameService)
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
        $sequenceTypes = $this->dataService->getSequenceTypes();
        $totalStars = $this->dataService->getTotalStars();
        $memoryRanks = $this->dataService->getMemoryRanks();

        return Inertia::render('English/Games/SequenceRecall/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'gameModes' => $gameModes,
            'sequenceTypes' => $sequenceTypes,
            'totalStars' => $totalStars,
            'memoryRanks' => $memoryRanks,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.sequence-recall.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.sequence-recall.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $sequenceTypes = $this->dataService->getSequenceTypes();
        $powerups = $this->dataService->getPowerups();

        return Inertia::render('English/Games/SequenceRecall/Play', [
            'level' => $levelData,
            'config' => $config,
            'gameModes' => $gameModes,
            'sequenceTypes' => $sequenceTypes,
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
            'sequence_type' => 'nullable|string',
        ]);

        try {
            $mode = $request->input('mode', 'words');
            $sequenceType = $request->input('sequence_type', 'forward');
            $session = $this->gameService->startSession($level, $mode, $sequenceType);

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
     * Get the next sequence in the session
     */
    public function getNextSequence(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $sequence = $this->gameService->getNextSequence($request->input('session_id'));

            return response()->json([
                'success' => true,
                'data' => $sequence,
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
            'answer' => 'required|array',
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
            $result = $this->gameService->completeSession(
                $request->input('session_id')
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
        $modeStats = $stats['mode_stats'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
                'mode_stats' => $modeStats,
            ],
        ]);
    }

    /**
     * Get game mode information
     */
    public function getModeInfo(string $modeId)
    {
        $gameModes = $this->dataService->getGameModes();
        $mode = null;

        foreach ($gameModes as $gm) {
            if ($gm['id'] === $modeId) {
                $mode = $gm;
                break;
            }
        }

        if (!$mode) {
            return response()->json([
                'success' => false,
                'message' => 'Game mode not found',
            ], 404);
        }

        $progress = $this->dataService->getUserProgress();
        $modeStats = $progress['mode_stats'][$modeId] ?? [
            'sequences_completed' => 0,
            'perfect_recalls' => 0,
            'total_attempts' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'mode' => $mode,
                'stats' => $modeStats,
                'accuracy' => $modeStats['total_attempts'] > 0
                    ? round(($modeStats['sequences_completed'] / $modeStats['total_attempts']) * 100)
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
