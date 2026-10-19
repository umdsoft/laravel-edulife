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
        $query = User::where('role', 'teacher')
            ->with(['teacherProfile'])
            ->withCount('courses');

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

        // Paginate
        $teachers = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($teacher) => [
                'id' => $teacher->id,
                'full_name' => $teacher->first_name . ' ' . $teacher->last_name,
                'phone' => $teacher->phone,
                'email' => $teacher->email,
                'is_verified' => $teacher->teacherProfile?->verified_at !== null,
                'total_students' => $teacher->teacherProfile->total_students ?? 0,
                'total_courses' => $teacher->courses_count ?? 0,
                'avg_rating' => round($teacher->teacherProfile->avg_rating ?? 0, 1),
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
            'courses' => function ($query) {
                $query->withCount('enrollments');
            },
        ]);

        return Inertia::render('Admin/Teachers/Show', [
            'teacher' => [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'bio' => $user->teacherProfile->bio ?? '',
                'expertise' => $user->teacherProfile->expertise ?? '',
                'is_verified' => $user->teacherProfile?->verified_at !== null,
                'verified_at' => $user->teacherProfile?->verified_at?->format('d.m.Y H:i'),
                'total_students' => $user->teacherProfile->total_students ?? 0,
                'total_courses' => $user->courses->count(),
                'avg_rating' => round($user->teacherProfile->avg_rating ?? 0, 1),
                'created_at' => $user->created_at->format('d.m.Y'),
            ],
            'courses' => $user->courses->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'students_count' => $course->enrollments_count ?? 0,
                'avg_rating' => round($course->avg_rating ?? 0, 1),
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
}
