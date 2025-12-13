<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\WordMatchService;
use App\Services\English\WordMatchDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WordMatchController extends Controller
{
    protected WordMatchService $gameService;
    protected WordMatchDataService $dataService;

    public function __construct(WordMatchService $gameService, WordMatchDataService $dataService)
    {
        $this->gameService = $gameService;
        $this->dataService = $dataService;
    }

    /**
     * Show the game index page
     */
    public function index()
    {
        $userId = Auth::id();

        return Inertia::render('English/Games/WordMatch/Index', [
            'levels' => $this->dataService->getLevels($userId),
            'config' => $this->dataService->getConfig(),
            'userStats' => $this->dataService->getUserStats($userId),
            'gameModes' => $this->dataService->getGameModes(),
            'powerups' => $this->dataService->getPowerups(),
            'achievements' => $this->dataService->getAchievements(),
            'userAchievements' => $this->dataService->getUserAchievements($userId),
        ]);
    }

    /**
     * Show the game play page
     */
    public function play(int $level)
    {
        $userId = Auth::id();
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.word-match.index')
                ->with('error', 'Level not found');
        }

        // Check if level is unlocked
        $levels = $this->dataService->getLevels($userId);
        $isUnlocked = false;
        foreach ($levels as $l) {
            if ($l['number'] === $level) {
                $isUnlocked = $l['unlocked'] ?? false;
                break;
            }
        }

        if (!$isUnlocked) {
            return redirect()->route('student.english.games.word-match.index')
                ->with('error', 'This level is locked');
        }

        return Inertia::render('English/Games/WordMatch/Play', [
            'level' => $levelData,
            'config' => $this->dataService->getConfig(),
            'gameModes' => $this->dataService->getGameModes(),
            'powerups' => $this->dataService->getPowerups(),
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $request->validate([
            'game_mode' => 'sometimes|string|in:classic_match,memory_flip,speed_match',
        ]);

        $userId = Auth::id();
        $gameMode = $request->input('game_mode', 'classic_match');

        $result = $this->gameService->startSession($userId, $level, $gameMode);

        return response()->json($result);
    }

    /**
     * Check a match
     */
    public function checkMatch(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'card1_id' => 'required|string',
            'card2_id' => 'required|string',
            'time_spent' => 'required|integer|min:0',
        ]);

        $result = $this->gameService->checkMatch(
            $request->session_id,
            $request->card1_id,
            $request->card2_id,
            $request->time_spent
        );

        return response()->json($result);
    }

    /**
     * Check speed match answer
     */
    public function checkSpeedAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'answer' => 'required|string',
            'time_spent' => 'required|integer|min:0',
        ]);

        $result = $this->gameService->checkSpeedAnswer(
            $request->session_id,
            $request->answer,
            $request->time_spent
        );

        return response()->json($result);
    }

    /**
     * Use a powerup
     */
    public function usePowerup(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'powerup_type' => 'required|string',
        ]);

        $result = $this->gameService->usePowerup(
            $request->session_id,
            $request->powerup_type
        );

        return response()->json($result);
    }

    /**
     * Complete a session
     */
    public function completeSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $userId = Auth::id();
        $result = $this->gameService->completeSession($request->session_id, $userId);

        return response()->json($result);
    }

    /**
     * Get session state
     */
    public function getSessionState(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->gameService->getSessionState($request->session_id);

        return response()->json($result);
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        $userId = Auth::id();

        return response()->json([
            'success' => true,
            'stats' => $this->dataService->getUserStats($userId),
            'achievements' => $this->dataService->getUserAchievements($userId),
        ]);
    }
}
