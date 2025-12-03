<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentsController extends Controller
{
    public function index()
    {
        $enrollments = Enrollment::with(['user.profile', 'course'])
            ->whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
            ->latest()
            ->paginate(20);
        
        // Stats
        $stats = [
            'total_students' => Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
                ->distinct('user_id')
                ->count(),
            'active_this_week' => Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
                ->where('last_activity_at', '>=', now()->subWeek())
                ->distinct('user_id')
                ->count(),
            'completed' => Enrollment::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
                ->where('status', 'completed')
                ->count(),
        ];
        
        return Inertia::render('Teacher/Students/Index', [
            'enrollments' => $enrollments,
            'stats' => $stats,
        ]);
    }
    
    public function show(Enrollment $enrollment)
    {
        $this->authorize('view', $enrollment);
        
        $enrollment->load([
            'user.profile',
            'course',
            'lessonProgresses.lesson',
            'testAttempts.test',
        ]);
        
        return Inertia::render('Teacher/Students/Show', [
            'enrollment' => $enrollment,
        ]);
    }
}
