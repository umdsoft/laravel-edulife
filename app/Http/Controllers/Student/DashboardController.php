<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Direction;
use App\Models\DailyMission;
use App\Models\UserDailyMission;
use App\Models\LabExperiment;
use App\Models\LabAttempt;
use App\Services\CourseRecommendationService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        protected CourseRecommendationService $recommendationService
    ) {}
    
    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        
        // Streak yangilash
        if ($profile) {
            $profile->updateStreak();
        }
        
        // Davom etish kerak bo'lgan kurslar
        $continueLearning = Enrollment::with(['course.teacher', 'lastLesson'])
            ->where('user_id', $user->id)
            ->where('is_completed', false)
            ->orderByDesc('last_activity_at')
            ->limit(4)
            ->get();
        
        // Tavsiya etilgan kurslar
        $recommendations = $this->recommendationService->getRecommendations($user, 8);
        
        // Statistika
        $stats = [
            'courses_in_progress' => Enrollment::where('user_id', $user->id)
                ->where('is_completed', false)
                ->count(),
            'courses_completed' => $profile ? $profile->courses_completed : 0,
            'total_watch_time' => $profile ? $profile->formatted_watch_time : '0 daqiqa',
            'current_streak' => $profile ? $profile->streak_days : 0,
            'xp' => $profile ? $profile->xp : 0,
            'level' => $profile ? $profile->level : 1,
            'level_progress' => $profile ? $profile->level_progress : 0,
            'coins' => $profile ? $profile->coins : 0,
        ];
        
        // Lab progress
        $labProgress = $this->getLabProgress($user);
        
        // Daily missions
        $dailyMissions = $this->getDailyMissions($user);
        
        // Yo'nalishlar
        $directions = Direction::active()
            ->withCount(['courses' => fn($q) => $q->where('status', 'published')])
            ->orderByDesc('courses_count')
            ->limit(7)
            ->get();
        
        // Mashhur kurslar
        $popularCourses = Course::with(['teacher', 'direction'])
            ->where('status', 'published')
            ->inActiveDirection()
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('enrollments_count')
            ->limit(8)
            ->get();
        
        // Yangi kurslar
        $newCourses = Course::with(['teacher', 'direction'])
            ->where('status', 'published')
            ->inActiveDirection()
            ->latest()
            ->limit(8)
            ->get();
        
        return Inertia::render('Student/Dashboard', [
            'profile' => $profile,
            'stats' => $stats,
            'continueLearning' => $continueLearning,
            'recommendations' => $recommendations,
            'directions' => $directions,
            'popularCourses' => $popularCourses,
            'newCourses' => $newCourses,
            'labProgress' => $labProgress,
            'dailyMissions' => $dailyMissions,
        ]);
    }
    
    /**
     * Get Lab progress for user
     */
    private function getLabProgress($user): array
    {
        $completedExperiments = LabAttempt::where('user_id', $user->id)
            ->completed()
            ->distinct('experiment_id')
            ->count('experiment_id');
        
        $totalExperiments = LabExperiment::active()->count();
        
        $progressPercent = $totalExperiments > 0 
            ? round(($completedExperiments / $totalExperiments) * 100) 
            : 0;
        
        // Get recent activity
        $lastAttempt = LabAttempt::where('user_id', $user->id)
            ->latest()
            ->first();
        
        return [
            'started' => $completedExperiments > 0 || $lastAttempt !== null,
            'completed_simulations' => $completedExperiments,
            'total_simulations' => $totalExperiments,
            'progress_percent' => $progressPercent,
            'last_activity' => $lastAttempt?->created_at,
        ];
    }
    
    /**
     * Get or create daily missions for user
     */
    private function getDailyMissions($user): array
    {
        // Same source as /student/missions: user_missions, which MissionService
        // generates and progresses (user_daily_missions was never updated)
        app(\App\Services\MissionService::class)->generateDailyMissions($user);

        $userMissions = \App\Models\UserMission::with('mission')
            ->where('user_id', $user->id)
            ->where('assigned_date', Carbon::today())
            ->get();

        // Calculate time remaining until midnight
        $now = Carbon::now();
        $endOfDay = Carbon::today()->endOfDay();
        // Carbon 3 returns fractional diffs
        $hoursRemaining = (int) $now->diffInHours($endOfDay);
        
        // Transform to frontend format
        $missions = $userMissions->map(function ($userMission) {
            $mission = $userMission->mission;
            return [
                'id' => $userMission->id,
                'title' => $mission?->title ?? 'Kunlik vazifa',
                'description' => $mission?->description ?? '',
                'icon' => $mission?->icon ?? '🎯',
                'current' => (int) $userMission->current_value,
                'target' => (int) $userMission->target_value,
                'progress_percent' => $userMission->progress_percentage,
                'xp_reward' => $mission?->xp_reward ?? 10,
                'coin_reward' => $mission?->coin_reward ?? 0,
                'is_completed' => $userMission->is_completed,
                'is_claimed' => $userMission->is_claimed,
            ];
        });
        
        return [
            'missions' => $missions,
            'hours_remaining' => $hoursRemaining,
            'completed_count' => $userMissions->where('is_completed', true)->count(),
            'total_count' => $userMissions->count(),
        ];
    }
}
