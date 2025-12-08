<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishGame;
use App\Models\English\EnglishGameLevel;
use App\Models\English\UserGameAttempt;
use App\Services\English\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(
        private GameService $gameService
    ) {
    }

    /**
     * Get all game categories
     */
    public function categories(): JsonResponse
    {
        $categories = $this->gameService->getGameCategories();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get games by category
     */
    public function index(Request $request, string $categoryId): JsonResponse
    {
        $games = $this->gameService->getGamesByCategory($categoryId, $request->user());

        return response()->json([
            'success' => true,
            'data' => $games,
        ]);
    }

    /**
     * Get game with levels
     */
    public function show(Request $request, string $gameId): JsonResponse
    {
        $game = $this->gameService->getGameWithLevels($gameId, $request->user());

        if (!$game) {
            return response()->json(['success' => false, 'message' => 'Game not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $game,
        ]);
    }

    /**
     * Start game session
     */
    public function start(Request $request, string $gameId, string $levelId): JsonResponse
    {
        $game = EnglishGame::findOrFail($gameId);
        $level = EnglishGameLevel::findOrFail($levelId);

        $attempt = $this->gameService->startGame($game, $level, $request->user());
        $content = $this->gameService->getGameContent($game, $level, $request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'questions' => $content,
            ],
        ]);
    }

    /**
     * Submit game results
     */
    public function submit(Request $request, string $attemptId): JsonResponse
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'score' => 'required|integer|min:0',
            'correct_count' => 'required|integer|min:0',
            'total_count' => 'required|integer|min:1',
            'time_spent_seconds' => 'required|integer|min:0',
        ]);

        $attempt = UserGameAttempt::where('id', $attemptId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $result = $this->gameService->submitGameResults(
            $attempt,
            $validated['answers'],
            $validated['score'],
            $validated['correct_count'],
            $validated['total_count'],
            $validated['time_spent_seconds']
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
