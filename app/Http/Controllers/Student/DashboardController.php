<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Direction;
use App\Services\CourseRecommendationService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
        ]);
    }
}
