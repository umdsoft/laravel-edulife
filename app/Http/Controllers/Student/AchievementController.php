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
                    'unlocked_at' => $userAchievement?->unlocked_at,
                ];
            });
        
        return Inertia::render('Student/Achievements/Index', [
            'achievements' => $achievements,
            'total_unlocked' => $achievements->where('is_unlocked', true)->count(),
            'total_achievements' => $achievements->count(),
        ]);
    }
    
    public function claim(UserAchievement $achievement)
    {
        $user = Auth::user();
        
        if ($achievement->user_id !== $user->id) {
            abort(403);
        }
        
        $result = $this->achievementService->claim($user, $achievement);
        
        return response()->json($result);
    }
}
