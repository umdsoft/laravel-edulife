<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\VocabularyReviewDataService;
use App\Services\English\VocabularyReviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VocabularyReviewController extends Controller
{
    protected VocabularyReviewDataService $dataService;
    protected VocabularyReviewService $reviewService;

    public function __construct(
        VocabularyReviewDataService $dataService,
        VocabularyReviewService $reviewService
    ) {
        $this->dataService = $dataService;
        $this->reviewService = $reviewService;
    }

    public function index()
    {
        $config = $this->dataService->getConfig();
        $stats = $this->dataService->getUserStats();
        $categoryProgress = $this->dataService->getCategoryProgress();
        $achievements = $this->dataService->getUserAchievements();
        $reviewSummary = $this->reviewService->getReviewSummary();

        return Inertia::render('English/VocabularyReview/Index', [
            'config' => $config,
            'stats' => $stats,
            'categoryProgress' => $categoryProgress,
            'achievements' => $achievements,
            'reviewSummary' => $reviewSummary,
            'reviewModes' => $this->dataService->getReviewModes(),
            'dailyGoals' => $this->dataService->getDailyGoals(),
            'tips' => $this->dataService->getTips(),
        ]);
    }

    public function review(Request $request)
    {
        $mode = $request->get('mode', 'mixed');
        $categoryId = $request->get('category');
        $wordCount = min(50, max(5, (int)$request->get('count', 20)));

        $session = $this->reviewService->startReviewSession($mode, $categoryId, $wordCount);
        $config = $this->dataService->getConfig();

        return Inertia::render('English/VocabularyReview/Review', [
            'session' => $session,
            'config' => $config,
            'scoringConfig' => $this->dataService->getScoringConfig(),
        ]);
    }

    public function startSession(Request $request)
    {
        $mode = $request->input('mode', 'mixed');
        $categoryId = $request->input('category_id');
        $wordCount = min(50, max(5, (int)$request->input('word_count', 20)));

        $session = $this->reviewService->startReviewSession($mode, $categoryId, $wordCount);

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    public function checkAnswer(Request $request)
    {
        $wordId = $request->input('word_id');
        $answer = $request->input('answer');
        $reviewType = $request->input('review_type');

        if (!$wordId || $answer === null || !$reviewType) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required parameters',
            ], 400);
        }

        $result = $this->reviewService->checkAnswer($wordId, $answer, $reviewType);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    public function completeSession(Request $request)
    {
        $sessionData = $request->validate([
            'correct' => 'required|integer|min:0',
            'total' => 'required|integer|min:1',
            'streak' => 'integer|min:0',
            'time_taken' => 'integer|min:0',
        ]);

        $result = $this->reviewService->completeSession($sessionData);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    public function getStats()
    {
        return response()->json([
            'success' => true,
            'stats' => $this->dataService->getUserStats(),
            'categoryProgress' => $this->dataService->getCategoryProgress(),
            'reviewSummary' => $this->reviewService->getReviewSummary(),
        ]);
    }

    public function getWordsForReview(Request $request)
    {
        $limit = min(50, max(5, (int)$request->get('limit', 20)));
        $words = $this->dataService->getWordsForReview($limit);

        return response()->json([
            'success' => true,
            'words' => $words,
            'count' => count($words),
        ]);
    }

    public function getCategoryWords(string $categoryId)
    {
        $words = $this->dataService->getWordsByCategory($categoryId);
        $stats = $this->dataService->getCategoryStats($categoryId);

        return response()->json([
            'success' => true,
            'words' => $words,
            'stats' => $stats,
        ]);
    }

    public function getAchievements()
    {
        return response()->json([
            'success' => true,
            'achievements' => $this->dataService->getUserAchievements(),
        ]);
    }

    public function updateDailyGoal(Request $request)
    {
        $goalId = $request->input('goal_id');
        $goals = $this->dataService->getDailyGoals();

        $validGoal = false;
        foreach ($goals as $goal) {
            if ($goal['id'] === $goalId) {
                $validGoal = true;
                break;
            }
        }

        if (!$validGoal) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid goal ID',
            ], 400);
        }

        $progress = $this->dataService->getUserProgress();
        $progress['daily_goal'] = $goalId;
        $this->dataService->saveUserProgress($progress);

        return response()->json([
            'success' => true,
            'goal_id' => $goalId,
        ]);
    }

    public function markWordDifficult(Request $request)
    {
        $wordId = $request->input('word_id');

        if (!$wordId) {
            return response()->json([
                'success' => false,
                'error' => 'Word ID is required',
            ], 400);
        }

        $progress = $this->dataService->getUserProgress();

        if (!in_array($wordId, $progress['words_difficult'] ?? [])) {
            $progress['words_difficult'][] = $wordId;
            $this->dataService->saveUserProgress($progress);
        }

        return response()->json([
            'success' => true,
            'message' => 'Word marked as difficult',
        ]);
    }

    public function removeWordDifficult(Request $request)
    {
        $wordId = $request->input('word_id');

        if (!$wordId) {
            return response()->json([
                'success' => false,
                'error' => 'Word ID is required',
            ], 400);
        }

        $progress = $this->dataService->getUserProgress();
        $progress['words_difficult'] = array_filter(
            $progress['words_difficult'] ?? [],
            fn($id) => $id !== $wordId
        );
        $this->dataService->saveUserProgress($progress);

        return response()->json([
            'success' => true,
            'message' => 'Word removed from difficult list',
        ]);
    }
}
