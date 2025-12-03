<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\TeacherEarning;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Overall stats
        $stats = [
            'total_views' => Course::where('teacher_id', $user->id)->sum('views_count'),
            'total_enrollments' => Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', $user->id))->count(),
            'completion_rate' => $this->calculateCompletionRate($user->id),
            'total_watch_time' => LessonProgress::whereHas('lesson.module.course', fn($q) => $q->where('teacher_id', $user->id))->sum('watch_time'),
        ];
        
        // Course performance
        $coursePerformance = Course::where('teacher_id', $user->id)
            ->where('status', 'published')
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->get()
            ->map(fn($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'enrollments' => $course->enrollments_count,
                'rating' => round($course->reviews_avg_rating ?? 0, 1),
                'completion_rate' => $this->calculateCourseCompletionRate($course->id),
                'earnings' => TeacherEarning::where('course_id', $course->id)->sum('amount'),
            ]);
        
        // Daily enrollments (last 30 days)
        $dailyEnrollments = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyEnrollments[] = [
                'date' => $date->format('d.m'),
                'count' => Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', $user->id))
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        }
        
        // Traffic sources
        $trafficSources = [
            ['source' => 'Qidiruv', 'count' => 450, 'percent' => 45],
            ['source' => 'To\'g\'ridan-to\'g\'ri', 'count' => 300, 'percent' => 30],
            ['source' => 'Ijtimoiy tarmoq', 'count' => 150, 'percent' => 15],
            ['source' => 'Boshqa', 'count' => 100, 'percent' => 10],
        ];
        
        return Inertia::render('Teacher/Analytics/Index', [
            'stats' => $stats,
            'coursePerformance' => $coursePerformance,
            'dailyEnrollments' => $dailyEnrollments,
            'trafficSources' => $trafficSources,
        ]);
    }
    
    private function calculateCompletionRate($teacherId)
    {
        $total = Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))->count();
        if ($total === 0) return 0;
        
        $completed = Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', $teacherId))
            ->where('status', 'completed')
            ->count();
        
        return round(($completed / $total) * 100);
    }
    
    private function calculateCourseCompletionRate($courseId)
    {
        $total = Enrollment::where('course_id', $courseId)->count();
        if ($total === 0) return 0;
        
        $completed = Enrollment::where('course_id', $courseId)
            ->where('status', 'completed')
            ->count();
        
        return round(($completed / $total) * 100);
    }
    
    public function export()
    {
        // Export to Excel
        // ... implementation
    }
}
