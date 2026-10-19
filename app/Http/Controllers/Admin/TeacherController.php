<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(Request $request): Response
    {
        // courses() are the courses a user is enrolled in; a teacher's own courses are teacherCourses()
        $query = User::where('role', 'teacher')
            ->with(['teacherProfile'])
            ->withCount('teacherCourses as courses_count');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by verification
        if ($request->filled('is_verified')) {
            $isVerified = $request->get('is_verified') === 'verified';
            $query->whereHas('teacherProfile', function ($q) use ($isVerified) {
                if ($isVerified) {
                    $q->whereNotNull('verified_at');
                } else {
                    $q->whereNull('verified_at');
                }
            });
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();
        $stats = $this->teachingStats($teachers->pluck('id')->all());

        $teachers = $teachers->through(fn ($teacher) => [
                'id' => $teacher->id,
                'full_name' => $teacher->first_name . ' ' . $teacher->last_name,
                'phone' => $teacher->phone,
                'email' => $teacher->email,
                'is_verified' => $teacher->teacherProfile?->verified_at !== null,
                'total_students' => $stats[$teacher->id]['students'] ?? 0,
                'total_courses' => $teacher->courses_count ?? 0,
                'avg_rating' => $stats[$teacher->id]['rating'] ?? 0,
                'created_at' => $teacher->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => $request->only(['search', 'is_verified']),
        ]);
    }

    /**
     * Display the specified teacher.
     */
    public function show(User $user): Response
    {
        if ($user->role !== 'teacher') {
            abort(404);
        }

        $user->load([
            'teacherProfile',
            'teacherCourses' => fn ($query) => $query->withCount('enrollments')->withAvg('reviews', 'rating')->latest(),
        ]);
        $stats = $this->teachingStats([$user->id])[$user->id] ?? ['students' => 0, 'rating' => 0];

        return Inertia::render('Admin/Teachers/Show', [
            'teacher' => [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'bio' => $user->teacherProfile->bio ?? '',
                'expertise' => implode(', ', (array) ($user->teacherProfile?->expertise ?? [])),
                'is_verified' => $user->teacherProfile?->verified_at !== null,
                'verified_at' => $user->teacherProfile?->verified_at?->format('d.m.Y H:i'),
                'total_students' => $stats['students'],
                'total_courses' => $user->teacherCourses->count(),
                'avg_rating' => $stats['rating'],
                'created_at' => $user->created_at->format('d.m.Y'),
            ],
            'courses' => $user->teacherCourses->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'students_count' => $course->enrollments_count ?? 0,
                'avg_rating' => round((float) ($course->reviews_avg_rating ?? 0), 1),
                'price' => $course->price,
                'status' => $course->status,
            ]),
        ]);
    }

    /**
     * Verify the teacher.
     */
    public function verify(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Foydalanuvchi o\'qituvchi emas']);
        }

        $user->teacherProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'verified_at' => now(),
                'is_verified' => true,
            ]
        );

        return back()->with('success', 'O\'qituvchi tasdiqlandi!');
    }

    /**
     * Unverify the teacher.
     */
    public function unverify(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Foydalanuvchi o\'qituvchi emas']);
        }

        $user->teacherProfile()->update([
            'verified_at' => null,
            'is_verified' => false,
        ]);

        return back()->with('success', 'Tasdiqlash bekor qilindi!');
    }

    /**
     * Distinct students and average review rating per teacher, computed live
     * (the teacher_profiles counters are not maintained).
     *
     * @return array<string, array{students: int, rating: float}>
     */
    private function teachingStats(array $teacherIds): array
    {
        if (! $teacherIds) {
            return [];
        }

        $students = \App\Models\Enrollment::join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->whereIn('courses.teacher_id', $teacherIds)
            ->groupBy('courses.teacher_id')
            ->selectRaw('courses.teacher_id, COUNT(DISTINCT enrollments.user_id) as total')
            ->pluck('total', 'courses.teacher_id');

        $ratings = \App\Models\Review::join('courses', 'courses.id', '=', 'reviews.course_id')
            ->whereIn('courses.teacher_id', $teacherIds)
            ->groupBy('courses.teacher_id')
            ->selectRaw('courses.teacher_id, AVG(reviews.rating) as avg_rating')
            ->pluck('avg_rating', 'courses.teacher_id');

        return collect($teacherIds)->mapWithKeys(fn ($id) => [$id => [
            'students' => (int) ($students[$id] ?? 0),
            'rating' => round((float) ($ratings[$id] ?? 0), 1),
        ]])->all();
    }
}
