<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Services\English\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {
    }

    /**
     * Get leaderboard
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|in:xp,streak,battle_wins,vocabulary,lessons,accuracy,streak,all_time_xp',
            'period' => 'sometimes|string|in:daily,weekly,monthly,all_time',
            'limit' => 'sometimes|integer|min:10|max:100',
        ]);

        $rankings = $this->leaderboardService->getLeaderboard(
            $validated['type'] ?? 'xp',
            $validated['period'] ?? 'weekly',
            $validated['limit'] ?? 100
        );

        return response()->json([
            'success' => true,
            'data' => $rankings,
        ]);
    }

    /**
     * Get user's rank
     */
    public function myRank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string',
            'period' => 'sometimes|string|in:daily,weekly,monthly,all_time',
        ]);

        $rank = $this->leaderboardService->getUserRank(
            $request->user(),
            $validated['type'] ?? 'xp',
            $validated['period'] ?? 'weekly'
        );

        return response()->json([
            'success' => true,
            'data' => $rank,
        ]);
    }

    /**
     * Get users around current user
     */
    public function around(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string',
            'period' => 'sometimes|string|in:daily,weekly,monthly,all_time',
            'range' => 'sometimes|integer|min:3|max:10',
        ]);

        $users = $this->leaderboardService->getUsersAround(
            $request->user(),
            $validated['type'] ?? 'xp',
            $validated['period'] ?? 'weekly',
            $validated['range'] ?? 5
        );

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
