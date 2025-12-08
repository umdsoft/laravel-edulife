<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Services\English\LevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get user English profile
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $this->levelService->getOrCreateProfile($request->user());
        $profile->load('currentLevel');

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    /**
     * Get user stats
     */
    public function stats(Request $request): JsonResponse
    {
        $profile = $this->levelService->getOrCreateProfile($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'total_xp' => $profile->total_xp,
                'coins' => $profile->coins,
                'gems' => $profile->gems,
                'elo_rating' => $profile->elo_rating,
                'current_streak' => $profile->current_streak,
                'longest_streak' => $profile->longest_streak,
                'battles_played' => $profile->battles_played,
                'battles_won' => $profile->battles_won,
                'battle_win_rate' => $profile->battles_played > 0
                    ? round(($profile->battles_won / $profile->battles_played) * 100, 1)
                    : 0,
                'total_study_days' => $profile->total_study_days,
            ],
        ]);
    }
}
