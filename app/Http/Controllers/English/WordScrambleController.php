<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\WordScrambleService;
use App\Services\English\WordScrambleDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WordScrambleController extends Controller
{
    protected WordScrambleService $gameService;
    protected WordScrambleDataService $dataService;

    public function __construct(WordScrambleService $gameService, WordScrambleDataService $dataService)
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

        return Inertia::render('English/Games/WordScramble/Index', [
            'levels' => $this->dataService->getLevels($userId),
            'config' => $this->dataService->getConfig(),
            'userStats' => $this->dataService->getUserStats($userId),
            'categories' => $this->dataService->getCategories(),
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
            return redirect()->route('student.english.games.word-scramble.index')
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
            return redirect()->route('student.english.games.word-scramble.index')
                ->with('error', 'This level is locked');
        }

        return Inertia::render('English/Games/WordScramble/Play', [
            'level' => $levelData,
            'config' => $this->dataService->getConfig(),
            'hints' => $this->dataService->getHints(),
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $userId = Auth::id();
        $result = $this->gameService->startSession($userId, $level);

        return response()->json($result);
    }

    /**
     * Check an answer
     */
    public function checkAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'answer' => 'required|string',
            'time_spent' => 'required|integer|min:0',
        ]);

        $result = $this->gameService->checkAnswer(
            $request->session_id,
            $request->answer,
            $request->time_spent
        );

        return response()->json($result);
    }

    /**
     * Use a hint
     */
    public function useHint(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'hint_type' => 'required|string',
        ]);

        $result = $this->gameService->useHint(
            $request->session_id,
            $request->hint_type
        );

        return response()->json($result);
    }

    /**
     * Skip current word
     */
    public function skipWord(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->gameService->skipWord($request->session_id);

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
