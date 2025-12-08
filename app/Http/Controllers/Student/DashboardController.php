<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Direction;
use App\Models\DailyMission;
use App\Models\UserDailyMission;
use App\Models\English\UserEnglishProfile;
use App\Models\English\EnglishLevel;
use App\Models\English\UserLessonProgress;
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
        
        // English learning progress
        $englishProgress = $this->getEnglishProgress($user);
        
        // Lab progress
        $labProgress = $this->getLabProgress($user);
        
        // Daily missions
        $dailyMissions = $this->getDailyMissions($user);
        
        // Yo'nalishlar
        $directions = Direction::withCount(['courses' => fn($q) => $q->where('status', 'published')])
            ->orderByDesc('courses_count')
            ->limit(7)
            ->get();
        
        // Mashhur kurslar
        $popularCourses = Course::with(['teacher', 'direction'])
            ->where('status', 'published')
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('enrollments_count')
            ->limit(8)
            ->get();
        
        // Yangi kurslar
        $newCourses = Course::with(['teacher', 'direction'])
            ->where('status', 'published')
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
            'englishProgress' => $englishProgress,
            'labProgress' => $labProgress,
            'dailyMissions' => $dailyMissions,
        ]);
    }
    
    /**
     * Get English learning progress for user
     */
    private function getEnglishProgress($user): array
    {
        $englishProfile = UserEnglishProfile::with('currentLevel')
            ->where('user_id', $user->id)
            ->first();
        
        if (!$englishProfile) {
            return [
                'started' => false,
                'level' => null,
                'level_name' => null,
                'lessons_completed' => 0,
                'total_xp' => 0,
                'progress_percent' => 0,
            ];
        }
        
        // Calculate progress percentage based on lessons completed in current level
        // EnglishLevel -> units -> lessons, so we need to count lessons through units
        $totalLessons = 0;
        if ($englishProfile->currentLevel) {
            $totalLessons = \App\Models\English\EnglishLesson::whereHas('unit', function($q) use ($englishProfile) {
                $q->where('level_id', $englishProfile->current_level_id);
            })->count();
        }
        
        $completedInLevel = UserLessonProgress::where('user_id', $user->id)
            ->whereHas('lesson.unit.level', function($q) use ($englishProfile) {
                $q->where('id', $englishProfile->current_level_id);
            })
            ->where('status', 'completed')
            ->count();
        
        $progressPercent = $totalLessons > 0 
            ? round(($completedInLevel / $totalLessons) * 100) 
            : 0;
        
        return [
            'started' => true,
            'level' => $englishProfile->currentLevel?->code ?? 'A1',
            'level_name' => $englishProfile->currentLevel?->name ?? 'Beginner',
            'lessons_completed' => $englishProfile->lessons_completed ?? 0,
            'total_xp' => $englishProfile->total_xp ?? 0,
            'progress_percent' => $progressPercent,
            'words_learned' => $englishProfile->words_learned ?? 0,
            'streak' => $englishProfile->current_streak ?? 0,
        ];
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
        $today = Carbon::today()->toDateString();
        
        // Get today's user missions
        $userMissions = UserDailyMission::with('dailyMission')
            ->where('user_id', $user->id)
            ->where('mission_date', $today)
            ->get();
        
        // If no missions for today, create them
        if ($userMissions->isEmpty()) {
            $activeMissions = DailyMission::where('is_active', true)
                ->orderBy('sort_order')
                ->limit(3)
                ->get();
            
            foreach ($activeMissions as $mission) {
                UserDailyMission::create([
                    'user_id' => $user->id,
                    'daily_mission_id' => $mission->id,
                    'mission_date' => $today,
                    'current_progress' => 0,
                    'target_count' => $mission->target ?? 3,
                    'xp_reward' => $mission->xp_reward ?? 10,
                    'coin_reward' => $mission->coin_reward ?? 0,
                    'is_completed' => false,
                    'is_claimed' => false,
                ]);
            }
            
            // Refetch with relationship
            $userMissions = UserDailyMission::with('dailyMission')
                ->where('user_id', $user->id)
                ->where('mission_date', $today)
                ->get();
        }
        
        // Calculate time remaining until midnight
        $now = Carbon::now();
        $endOfDay = Carbon::today()->endOfDay();
        $hoursRemaining = $now->diffInHours($endOfDay);
        
        // Transform to frontend format
        $missions = $userMissions->map(function ($userMission) {
            $mission = $userMission->dailyMission;
            return [
                'id' => $userMission->id,
                'title' => $mission?->title ?? 'Kunlik vazifa',
                'description' => $mission?->description ?? '',
                'icon' => $mission?->icon ?? '🎯',
                'current' => $userMission->current_progress,
                'target' => $userMission->target_count,
                'progress_percent' => $userMission->progress_percent,
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
