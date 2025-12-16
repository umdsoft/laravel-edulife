<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\SpellingBeeDataService;
use App\Services\English\SpellingBeeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpellingBeeController extends Controller
{
    protected SpellingBeeDataService $dataService;
    protected SpellingBeeService $gameService;

    public function __construct(SpellingBeeDataService $dataService, SpellingBeeService $gameService)
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
        $gameModes = $this->dataService->getGameModes();
        $categories = $this->dataService->getCategories();
        $totalStars = $this->dataService->getTotalStars();
        $spellingMasterRanks = $this->dataService->getSpellingMasterRanks();

        return Inertia::render('English/Games/SpellingBee/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'gameModes' => $gameModes,
            'categories' => $categories,
            'totalStars' => $totalStars,
            'spellingMasterRanks' => $spellingMasterRanks,
        ]);
    }

    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.spelling-bee.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.spelling-bee.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $powerups = $this->dataService->getPowerups();

        return Inertia::render('English/Games/SpellingBee/Play', [
            'level' => $levelData,
            'config' => $config,
            'gameModes' => $gameModes,
            'powerups' => $powerups,
        ]);
    }

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

    public function useHint(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->useHint($request->input('session_id'));

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

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
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
