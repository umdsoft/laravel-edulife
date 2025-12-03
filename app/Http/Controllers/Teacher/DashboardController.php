<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TeacherEarning;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teacherProfile = $user->teacherProfile;
        
        // Stats
        $stats = [
            'total_courses' => Course::where('teacher_id', $user->id)->count(),
            'published_courses' => Course::where('teacher_id', $user->id)->where('status', 'published')->count(),
            'total_students' => Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', $user->id))->distinct('user_id')->count(),
            'total_earnings' => TeacherEarning::where('teacher_id', $user->id)->where('status', 'completed')->sum('amount'),
            'pending_earnings' => $teacherProfile->balance ?? 0,
            'avg_rating' => $teacherProfile->avg_rating ?? 0,
            'total_reviews' => Review::whereHas('course', fn($q) => $q->where('teacher_id', $user->id))->count(),
        ];
        
        // This month earnings
        $monthlyEarnings = TeacherEarning::where('teacher_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        // Recent enrollments
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->whereHas('course', fn($q) => $q->where('teacher_id', $user->id))
            ->latest()
            ->take(5)
            ->get();
        
        // Recent reviews
        $recentReviews = Review::with(['user', 'course'])
            ->whereHas('course', fn($q) => $q->where('teacher_id', $user->id))
            ->latest()
            ->take(5)
            ->get();
        
        // Courses with stats
        $courses = Course::where('teacher_id', $user->id)
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(5)
            ->get();
        
        // Monthly earnings chart (last 6 months)
        $earningsChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $earningsChart[] = [
                'month' => $date->format('M'),
                'amount' => TeacherEarning::where('teacher_id', $user->id)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount'),
            ];
        }
        
        return Inertia::render('Teacher/Dashboard', [
            'stats' => $stats,
            'monthlyEarnings' => $monthlyEarnings,
            'recentEnrollments' => $recentEnrollments,
            'recentReviews' => $recentReviews,
            'courses' => $courses,
            'earningsChart' => $earningsChart,
            'teacherProfile' => $teacherProfile,
        ]);
    }
}
