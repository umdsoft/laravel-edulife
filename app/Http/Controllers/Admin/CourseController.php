<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Direction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request): Response
    {
        $query = Course::with(['teacher', 'direction']);

        // Search
        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by direction
        if ($directionId = $request->get('direction_id')) {
            $query->where('direction_id', $directionId);
        }

        // Paginate
        $courses = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnail,
                'price' => $course->price,
                'status' => $course->status,
                'teacher_name' => $course->teacher ? $course->teacher->first_name . ' ' . $course->teacher->last_name : 'N/A',
                'direction_name' => $course->direction->name_uz ?? 'N/A',
                'students_count' => $course->students_count ?? 0,
                'created_at' => $course->created_at->format('d.m.Y'),
            ]);

        // Get directions for filter
        $directions = Direction::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_uz']);

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'directions' => $directions,
            'filters' => $request->only(['search', 'status', 'direction_id']),
        ]);
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course): Response
    {
        $course->load(['teacher.profile', 'direction', 'modules.lessons', 'reviews']);

        return Inertia::render('Admin/Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail,
                'video_intro' => $course->video_intro,
                'price' => $course->price,
                'discount_price' => $course->discount_price,
                'status' => $course->status,
                'level' => $course->level,
                'language' => $course->language,
                'duration_hours' => $course->duration_hours,
                'rejection_reason' => $course->rejection_reason,
                'teacher' => [
                    'id' => $course->teacher->id,
                    'full_name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
                    'email' => $course->teacher->email,
                ],
                'direction' => [
                    'id' => $course->direction->id,
                    'name' => $course->direction->name_uz,
                ],
                'students_count' => $course->students_count ?? 0,
                'avg_rating' => round($course->reviews->avg('rating') ?? 0, 1),
                'reviews_count' => $course->reviews->count(),
                'modules_count' => $course->modules->count(),
                'lessons_count' => $course->modules->sum(fn($m) => $m->lessons->count()),
                'created_at' => $course->created_at->format('d.m.Y H:i'),
            ],
            'modules' => $course->modules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'sort_order' => $module->sort_order,
                'lessons_count' => $module->lessons->count(),
            ]),
        ]);
    }

    /**
     * Approve the course.
     */
    public function approve(Course $course): RedirectResponse
    {
        $course->update([
            'status' => 'published',
            'approved_at' => now(),
        ]);

        // TODO: Notify teacher

        return back()->with('success', 'Kurs muvaffaqiyatli tasdiqlandi!');
    }

    /**
     * Reject the course.
     */
    public function reject(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Rad etish sababini kiriting',
        ]);

        $course->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        // TODO: Notify teacher

        return back()->with('success', 'Kurs rad etildi!');
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Kurs muvaffaqiyatli o\'chirildi!');
    }
}
