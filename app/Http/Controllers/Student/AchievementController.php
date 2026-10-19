<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AchievementController extends Controller
{
    public function __construct(
        protected AchievementService $achievementService
    ) {}
    
    public function index()
    {
        $user = Auth::user();

        // catch up on progress made before an achievement's hook existed
        $this->achievementService->checkAchievements($user);

        $achievements = Achievement::where('is_active', true)
            ->with(['userAchievements' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }])
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($achievement) use ($user) {
                $userAchievement = $achievement->userAchievements->first();
                
                return [
                    'id' => $achievement->id,
                    'code' => $achievement->code,
                    'title' => $achievement->title,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'category' => $achievement->category,
                    'rarity' => $achievement->rarity,
                    'xp_reward' => $achievement->xp_reward,
                    'coin_reward' => $achievement->coin_reward,
                    'is_unlocked' => $userAchievement !== null,
                    'is_claimed' => $userAchievement?->is_claimed ?? false,
                    'unlocked_at' => $userAchievement?->earned_at,
                ];
            });
        
        return Inertia::render('Student/Achievements/Index', [
            'achievements' => $achievements,
            'total_unlocked' => $achievements->where('is_unlocked', true)->count(),
            'total_achievements' => $achievements->count(),
        ]);
    }
    
    /**
     * The page posts the Achievement id; the reward belongs to the student's
     * UserAchievement row for it (only once it is unlocked).
     */
    public function claim(Achievement $achievement)
    {
        $user = Auth::user();

        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();

        if (! $userAchievement) {
            return response()->json(['success' => false, 'message' => 'Bu yutuq hali ochilmagan'], 422);
        }

        $result = $this->achievementService->claim($user, $userAchievement);

        if (! ($result['success'] ?? false) && ($result['message'] ?? '') === 'Already claimed') {
            $result['message'] = 'Mukofot allaqachon olingan';
        }

        return response()->json($result);
    }
}
