<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\ErrorHunterService;
use App\Services\English\ErrorHunterDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ErrorHunterController extends Controller
{
    protected ErrorHunterService $gameService;
    protected ErrorHunterDataService $dataService;

    public function __construct(ErrorHunterService $gameService, ErrorHunterDataService $dataService)
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

        return Inertia::render('English/Games/ErrorHunter/Index', [
            'levels' => $this->dataService->getLevels($userId),
            'config' => $this->dataService->getConfig(),
            'userStats' => $this->dataService->getUserStats($userId),
            'gameModes' => $this->dataService->getGameModes(),
            'errorTypes' => $this->dataService->getErrorTypes(),
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
            return redirect()->route('student.english.games.error-hunter.index')
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
            return redirect()->route('student.english.games.error-hunter.index')
                ->with('error', 'This level is locked');
        }

        return Inertia::render('English/Games/ErrorHunter/Play', [
            'level' => $levelData,
            'config' => $this->dataService->getConfig(),
            'gameModes' => $this->dataService->getGameModes(),
            'errorTypes' => $this->dataService->getErrorTypes(),
            'powerups' => $this->dataService->getPowerups(),
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $request->validate([
            'game_mode' => 'sometimes|string|in:spot_error,fix_error,rewrite',
        ]);

        $userId = Auth::id();
        $gameMode = $request->input('game_mode', 'spot_error');

        $result = $this->gameService->startSession($userId, $level, $gameMode);

        return response()->json($result);
    }

    /**
     * Check an answer
     */
    public function checkAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'sentence_id' => 'required|string',
            'answer' => 'required',
            'time_spent' => 'required|integer|min:0',
            'mode' => 'required|string|in:spot_error,fix_error,rewrite',
        ]);

        $result = $this->gameService->checkAnswer(
            $request->session_id,
            $request->sentence_id,
            $request->answer,
            $request->time_spent,
            $request->mode
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
