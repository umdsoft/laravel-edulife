<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teacherProfile = $user->teacherProfile;

        $teacherEnrollments = fn () => Enrollment::whereHas('course', fn ($q) => $q->where('teacher_id', $user->id));

        // Stats
        $stats = [
            'total_courses' => Course::where('teacher_id', $user->id)->count(),
            'published_courses' => Course::where('teacher_id', $user->id)->where('status', 'published')->count(),
            'total_students' => $teacherEnrollments()->distinct('user_id')->count(),
            'monthly_enrollments' => $teacherEnrollments()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'avg_rating' => $teacherProfile->avg_rating ?? 0,
            'total_reviews' => Review::whereHas('course', fn ($q) => $q->where('teacher_id', $user->id))->count(),
        ];

        // Recent enrollments
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->whereHas('course', fn ($q) => $q->where('teacher_id', $user->id))
            ->latest()
            ->take(5)
            ->get();

        // Recent reviews
        $recentReviews = Review::with(['user', 'course'])
            ->whereHas('course', fn ($q) => $q->where('teacher_id', $user->id))
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

        // Monthly new enrollments chart (last 6 months)
        $enrollmentsChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $enrollmentsChart[] = [
                'month' => $date->format('M'),
                'value' => $teacherEnrollments()
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        return Inertia::render('Teacher/Dashboard', [
            'stats' => $stats,
            'recentEnrollments' => $recentEnrollments,
            'recentReviews' => $recentReviews,
            'courses' => $courses,
            'enrollmentsChart' => $enrollmentsChart,
            'teacherProfile' => $teacherProfile,
        ]);
    }
}
