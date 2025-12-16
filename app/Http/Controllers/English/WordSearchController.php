<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\WordSearchDataService;
use App\Services\English\WordSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WordSearchController extends Controller
{
    protected WordSearchDataService $dataService;
    protected WordSearchService $gameService;

    public function __construct(WordSearchDataService $dataService, WordSearchService $gameService)
    {
        $this->dataService = $dataService;
        $this->gameService = $gameService;
    }

    public function index()
    {
        return Inertia::render('English/Games/WordSearch/Index', [
            'levels' => $this->dataService->getLevelsWithStatus(),
            'stats' => $this->dataService->getUserStats(),
            'config' => $this->dataService->getConfig(),
            'achievements' => $this->dataService->getUserAchievements(),
            'categories' => $this->dataService->getCategories(),
            'gameModes' => $this->dataService->getGameModes(),
            'totalStars' => $this->dataService->getTotalStars(),
            'wordFinderRanks' => $this->dataService->getWordFinderRanks(),
        ]);
    }

    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.word-search.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.word-search.index')
                ->with('error', 'Level is locked');
        }

        return Inertia::render('English/Games/WordSearch/Play', [
            'level' => $levelData,
            'config' => $this->dataService->getConfig(),
            'powerups' => $this->dataService->getPowerups(),
        ]);
    }

    public function startSession(Request $request, int $level)
    {
        $request->validate(['mode' => 'nullable|string']);

        try {
            $session = $this->gameService->startSession($level, $request->input('mode', 'classic'));
            return response()->json(['success' => true, 'data' => $session]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function checkWord(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'word' => 'required|string',
            'positions' => 'required|array',
        ]);

        try {
            $result = $this->gameService->checkWord(
                $request->input('session_id'),
                $request->input('word'),
                $request->input('positions')
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function completeSession(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        try {
            $result = $this->gameService->completeSession($request->input('session_id'));
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function getSessionState(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        $state = $this->gameService->getSessionState($request->input('session_id'));

        if (!$state) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $state]);
    }

    public function getStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $this->dataService->getUserStats(),
                'achievements' => $this->dataService->getUserAchievements(),
            ],
        ]);
    }

    public function getLeaderboard()
    {
        return response()->json([
            'success' => true,
            'data' => ['daily' => [], 'weekly' => [], 'all_time' => []],
        ]);
    }
}
