<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\WordChainDataService;
use App\Services\English\WordChainService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WordChainController extends Controller
{
    protected WordChainDataService $dataService;
    protected WordChainService $gameService;

    public function __construct(WordChainDataService $dataService, WordChainService $gameService)
    {
        $this->dataService = $dataService;
        $this->gameService = $gameService;
    }

    public function index()
    {
        $levels = $this->dataService->getLevelsWithStatus();
        $stats = $this->dataService->getUserStats();
        $config = $this->dataService->getConfig();
        $achievements = $this->dataService->getUserAchievements();
        $categories = $this->dataService->getCategories();
        $totalStars = $this->dataService->getTotalStars();
        $chainMasterRanks = $this->dataService->getChainMasterRanks();

        return Inertia::render('English/Games/WordChain/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'categories' => $categories,
            'totalStars' => $totalStars,
            'chainMasterRanks' => $chainMasterRanks,
        ]);
    }

    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.word-chain.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.word-chain.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $powerups = $this->dataService->getPowerups();

        return Inertia::render('English/Games/WordChain/Play', [
            'level' => $levelData,
            'config' => $config,
            'powerups' => $powerups,
        ]);
    }

    public function startSession(Request $request, int $level)
    {
        try {
            $session = $this->gameService->startSession($level);

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

    public function submitWord(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'word' => 'required|string|min:2',
            'time_spent' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->submitWord(
                $request->input('session_id'),
                $request->input('word'),
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

    public function getHint(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->getHint($request->input('session_id'));

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

    public function skipLetter(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->skipLetter($request->input('session_id'));

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

    public function getAvailableWords(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->getAvailableWordsCount($request->input('session_id'));

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
