<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\ArticleMasterDataService;
use App\Services\English\ArticleMasterService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleMasterController extends Controller
{
    protected ArticleMasterDataService $dataService;
    protected ArticleMasterService $gameService;

    public function __construct(ArticleMasterDataService $dataService, ArticleMasterService $gameService)
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
        $articles = $this->dataService->getArticles();
        $totalStars = $this->dataService->getTotalStars();
        $articleMasterRanks = $this->dataService->getArticleMasterRanks();

        return Inertia::render('English/Games/ArticleMaster/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'categories' => $categories,
            'articles' => $articles,
            'totalStars' => $totalStars,
            'articleMasterRanks' => $articleMasterRanks,
        ]);
    }

    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.article-master.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.article-master.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $powerups = $this->dataService->getPowerups();

        return Inertia::render('English/Games/ArticleMaster/Play', [
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

    public function submitAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'question_index' => 'required|integer|min:0',
            'answer' => 'required|string',
            'time_spent' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->submitAnswer(
                $request->input('session_id'),
                $request->input('question_index'),
                $request->input('answer'),
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

    public function getStats()
    {
        $stats = $this->dataService->getUserStats();
        $achievements = $this->dataService->getUserAchievements();
        $categoryStats = $this->dataService->getCategoryStats();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
                'category_stats' => $categoryStats,
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
            'data' => $category,
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
