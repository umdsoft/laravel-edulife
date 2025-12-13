<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\VocabularyQuizService;
use App\Services\English\VocabularyQuizDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VocabularyQuizController extends Controller
{
    protected VocabularyQuizService $quizService;
    protected VocabularyQuizDataService $dataService;

    public function __construct(VocabularyQuizService $quizService, VocabularyQuizDataService $dataService)
    {
        $this->quizService = $quizService;
        $this->dataService = $dataService;
    }

    /**
     * Show the game index page
     */
    public function index()
    {
        $userId = Auth::id();

        return Inertia::render('English/Games/VocabularyQuiz/Index', [
            'levels' => $this->dataService->getLevels($userId),
            'config' => $this->dataService->getConfig(),
            'userStats' => $this->dataService->getUserStats($userId),
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
            return redirect()->route('english.vocabulary-quiz.index')
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
            return redirect()->route('english.vocabulary-quiz.index')
                ->with('error', 'This level is locked');
        }

        return Inertia::render('English/Games/VocabularyQuiz/Play', [
            'level' => $levelData,
            'config' => $this->dataService->getConfig(),
            'powerups' => $this->dataService->getPowerups(),
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $userId = Auth::id();
        $result = $this->quizService->startSession($userId, $level);

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

        $result = $this->quizService->checkAnswer(
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
            'powerup_id' => 'required|string',
        ]);

        $result = $this->quizService->usePowerup(
            $request->session_id,
            $request->powerup_id
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
        $result = $this->quizService->completeSession($request->session_id, $userId);

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

    /**
     * Get leaderboard (placeholder for future implementation)
     */
    public function getLeaderboard()
    {
        // This would typically query a database for top scores
        // For now, return empty leaderboard
        return response()->json([
            'success' => true,
            'leaderboard' => [],
        ]);
    }
}
