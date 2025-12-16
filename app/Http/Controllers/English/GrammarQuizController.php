<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\GrammarQuizService;
use App\Services\English\GrammarQuizDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class GrammarQuizController extends Controller
{
    private GrammarQuizService $service;
    private GrammarQuizDataService $dataService;

    public function __construct(GrammarQuizService $service, GrammarQuizDataService $dataService)
    {
        $this->service = $service;
        $this->dataService = $dataService;
    }

    /**
     * Show levels index page
     */
    public function index(): Response
    {
        $userId = auth()->id();
        $levels = $this->dataService->getLevelsWithProgress($userId);
        $stats = $this->dataService->getUserStats($userId);
        $config = $this->dataService->getConfig();
        $achievements = $this->dataService->getAchievements();
        $categories = $this->dataService->getCategories();
        $quizTypes = $this->dataService->getQuizTypes();

        // Calculate total stars
        $totalStars = 0;
        foreach ($levels as $level) {
            $totalStars += $level['stars'] ?? 0;
        }

        return Inertia::render('English/Games/GrammarQuiz/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'categories' => $categories,
            'quizTypes' => $quizTypes,
            'totalStars' => $totalStars,
        ]);
    }

    /**
     * Show play page
     */
    public function play(int $level): Response
    {
        $levelData = $this->dataService->getLevel($level);
        if (!$levelData) {
            return redirect()->route('student.english.games.grammar-quiz.index')
                ->with('error', 'Level not found');
        }

        $config = $this->dataService->getConfig();
        $quizTypes = $this->dataService->getQuizTypes();
        $powerups = $this->dataService->getPowerups();
        $difficultyLevels = $this->dataService->getDifficultyLevels();

        return Inertia::render('English/Games/GrammarQuiz/Play', [
            'level' => $levelData,
            'config' => $config,
            'quizTypes' => $quizTypes,
            'powerups' => $powerups,
            'difficultyLevels' => $difficultyLevels,
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level): JsonResponse
    {
        $validated = $request->validate([
            'quiz_type' => 'sometimes|string|in:fill_blank,error_correction,sentence_transformation,multiple_choice,mixed',
        ]);

        $result = $this->service->startSession(
            auth()->id(),
            $level,
            $validated['quiz_type'] ?? 'mixed'
        );

        return response()->json($result);
    }

    /**
     * Check answer
     */
    public function checkAnswer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'answer' => 'required',
            'time_spent' => 'required|integer|min:0',
        ]);

        $result = $this->service->checkAnswer(
            $validated['session_id'],
            $validated['answer'],
            $validated['time_spent']
        );

        return response()->json($result);
    }

    /**
     * Use hint
     */
    public function useHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->service->useHint($validated['session_id']);
        return response()->json($result);
    }

    /**
     * Skip question
     */
    public function skipQuestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->service->skipQuestion($validated['session_id']);
        return response()->json($result);
    }

    /**
     * Use 50/50 powerup
     */
    public function useFiftyFifty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->service->useFiftyFifty($validated['session_id']);
        return response()->json($result);
    }

    /**
     * Add extra time
     */
    public function addExtraTime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'seconds' => 'integer|min:1|max:60',
        ]);

        $result = $this->service->addExtraTime(
            $validated['session_id'],
            $validated['seconds'] ?? 15
        );

        return response()->json($result);
    }

    /**
     * Activate double points
     */
    public function activateDoublePoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->service->activateDoublePoints($validated['session_id']);
        return response()->json($result);
    }

    /**
     * Complete session
     */
    public function completeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->service->completeSession($validated['session_id']);
        return response()->json($result);
    }

    /**
     * Get session state
     */
    public function getSessionState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->service->getSessionState($validated['session_id']);
        return response()->json($result);
    }

    /**
     * Get user stats
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->dataService->getUserStats(auth()->id());
        return response()->json($stats);
    }

    /**
     * Get category info
     */
    public function getCategoryInfo(string $categoryId): JsonResponse
    {
        $category = $this->dataService->getCategoryInfo($categoryId);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        return response()->json($category);
    }
}
