<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\UserMission;
use App\Services\MissionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MissionController extends Controller
{
    public function __construct(
        protected MissionService $missionService
    ) {}
    
    public function index()
    {
        $user = Auth::user();
        
        // Generate missions for today if not already generated
        $this->missionService->generateDailyMissions($user);
        
        $today = Carbon::today();
        
        $missions = UserMission::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->with('mission')
            ->get();
        
        // Time until reset (midnight)
        $resetTime = Carbon::tomorrow();
        $hoursUntilReset = now()->diffInHours($resetTime);
        $minutesUntilReset = now()->diffInMinutes($resetTime) % 60;
        $secondsUntilReset = now()->diffInSeconds($resetTime) % 60;
        
        return Inertia::render('Student/Missions/Index', [
            'missions' => $missions,
            'completed_count' => $missions->where('is_completed', true)->count(),
            'total_count' => $missions->count(),
            'reset_time' => [
                'hours' => $hoursUntilReset,
                'minutes' => $minutesUntilReset,
                'seconds' => $secondsUntilReset,
            ],
        ]);
    }
    
    public function claim(UserMission $userMission)
    {
        $user = Auth::user();
        
        if ($userMission->user_id !== $user->id) {
            abort(403);
        }
        
        $result = $this->missionService->claimReward($user, $userMission);
        
        return response()->json($result);
    }
}
