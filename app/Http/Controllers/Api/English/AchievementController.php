<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Services\English\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function __construct(
        private AchievementService $achievementService
    ) {
    }

    /**
     * Get all achievements with progress
     */
    public function index(Request $request): JsonResponse
    {
        $achievements = $this->achievementService->getAllAchievements($request->user());

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Get user's unlocked achievements
     */
    public function unlocked(Request $request): JsonResponse
    {
        $achievements = $this->achievementService->getUnlockedAchievements($request->user());

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Get achievement statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->achievementService->getAchievementStats($request->user());

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
