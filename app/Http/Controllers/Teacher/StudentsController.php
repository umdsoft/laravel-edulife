<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentsController extends Controller
{
    private const STATUSES = ['active', 'completed', 'expired', 'refunded'];

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'course' => (string) $request->query('course', ''),
            'status' => in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : '',
        ];

        $teacherEnrollments = fn () => Enrollment::whereHas('course', fn ($q) => $q->where('teacher_id', Auth::id()));

        $enrollments = $teacherEnrollments()
            ->with(['user', 'course:id,title'])
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                // every word must match the first or last name ("ali val" finds "Ali Valiyev")
                $q->whereHas('user', function ($u) use ($filters) {
                    foreach (preg_split('/\s+/', $filters['search']) as $word) {
                        $term = '%'.addcslashes($word, '%_\\').'%';
                        $u->where(fn ($w) => $w->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term));
                    }
                });
            })
            ->when($filters['course'] !== '', fn ($q) => $q->where('course_id', $filters['course']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_students' => $teacherEnrollments()->distinct('user_id')->count('user_id'),
            'active_this_week' => $teacherEnrollments()
                ->where('last_activity_at', '>=', now()->subWeek())
                ->distinct('user_id')
                ->count('user_id'),
            'completed' => $teacherEnrollments()->where('status', 'completed')->count(),
        ];

        return Inertia::render('Teacher/Students/Index', [
            'enrollments' => $enrollments,
            'stats' => $stats,
            'filters' => $filters,
            'courses' => Course::where('teacher_id', Auth::id())->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(Enrollment $enrollment)
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['user', 'course.modules' => fn ($q) => $q->orderBy('sort_order'), 'course.modules.lessons' => fn ($q) => $q->orderBy('sort_order')]);

        $progress = LessonProgress::where('enrollment_id', $enrollment->id)->get()->keyBy('lesson_id');

        $modules = $enrollment->course->modules->map(fn ($module) => [
            'id' => $module->id,
            'title' => $module->title,
            'lessons' => $module->lessons->map(function ($lesson) use ($progress) {
                $row = $progress->get($lesson->id);

                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'type' => $lesson->type,
                    'is_completed' => (bool) $row?->is_completed,
                    'completed_at' => $row?->completed_at,
                    'watch_percentage' => (int) ($row?->watch_percentage ?? 0),
                    'started' => $row !== null,
                ];
            })->values(),
        ])->values();

        $attempts = TestAttempt::with('test:id,title,type,passing_score')
            ->where('enrollment_id', $enrollment->id)
            ->latest('started_at')
            ->get()
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'test_title' => $attempt->test?->title,
                'test_type' => $attempt->test?->type,
                'attempt_number' => $attempt->attempt_number,
                'status' => $attempt->status,
                'score' => (float) $attempt->score,
                'is_passed' => (bool) $attempt->is_passed,
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'time_spent' => $attempt->time_spent,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
            ]);

        $totalLessons = $modules->sum(fn ($m) => count($m['lessons']));
        $completedLessons = $progress->where('is_completed', true)->count();

        return Inertia::render('Teacher/Students/Show', [
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'progress' => (float) $enrollment->progress,
                'created_at' => $enrollment->created_at,
                'last_activity_at' => $enrollment->last_activity_at,
                'completed_at' => $enrollment->completed_at,
                'total_watch_time' => (int) $progress->sum('watch_time'),
            ],
            'student' => [
                'id' => $enrollment->user->id,
                'full_name' => $enrollment->user->full_name,
                'first_name' => $enrollment->user->first_name,
                'last_name' => $enrollment->user->last_name,
                'avatar_url' => $enrollment->user->avatar_url,
            ],
            'course' => ['id' => $enrollment->course->id, 'title' => $enrollment->course->title],
            'modules' => $modules,
            'attempts' => $attempts,
            'summary' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'tests_passed' => $attempts->where('is_passed', true)->pluck('test_title')->unique()->count(),
                'attempts' => $attempts->count(),
            ],
        ]);
    }
}
