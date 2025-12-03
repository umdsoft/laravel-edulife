<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $type = $request->type ?? 'xp';
        
        $leaderboard = match($type) {
            'xp' => $this->getXPLeaderboard(),
            'battles' => $this->getBattleLeaderboard(),
            'weekly' => $this->getWeeklyLeaderboard(),
            default => $this->getXPLeaderboard(),
        };
        
        // Find user's position
        $userPosition = $leaderboard->search(function ($item) use ($user) {
            return $item['user_id'] === $user->id;
        });
        
        return Inertia::render('Student/Leaderboard/Index', [
            'leaderboard' => $leaderboard->take(100),
            'user_position' => $userPosition !== false ? $userPosition + 1 : null,
            'type' => $type,
        ]);
    }
    
    private function getXPLeaderboard()
    {
        return StudentProfile::with('user')
            ->where('xp', '>', 0)
            ->orderByDesc('xp')
            ->limit(100)
            ->get()
            ->map(function ($profile, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $profile->user_id,
                    'name' => $profile->user->first_name . ' ' . $profile->user->last_name,
                    'avatar' => $profile->user->avatar,
                    'level' => $profile->level,
                    'xp' => $profile->xp,
                    'streak_days' => $profile->streak_days,
                    'rank_badge' => $profile->rank,
                ];
            });
    }
    
    private function getBattleLeaderboard()
    {
        return StudentProfile::with('user')
            ->where('battles_won', '>', 0)
            ->orderByDesc('elo_rating')
            ->limit(100)
            ->get()
            ->map(function ($profile, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $profile->user_id,
                    'name' => $profile->user->first_name . ' ' . $profile->user->last_name,
                    'avatar' => $profile->user->avatar,
                    'elo_rating' => $profile->elo_rating,
                    'battles_won' => $profile->battles_won,
                    'battles_played' => $profile->battles_won + $profile->battles_lost + $profile->battles_draw,
                    'rank_badge' => $profile->rank,
                ];
            });
    }
    
    private function getWeeklyLeaderboard()
    {
        // For weekly, we'd typically track XP earned this week
        // For simplicity, this uses total XP
        return $this->getXPLeaderboard();
    }
}
