<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Direction;
use App\Models\Notification as AppNotification;
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
                'video_intro' => $course->preview_video,
                // while discounted, courses.price is the discounted price and original_price the list price
                'price' => (float) ($course->original_price ?: $course->price),
                'discount_price' => $course->original_price ? (float) $course->price : null,
                'is_free' => (bool) $course->is_free,
                'status' => $course->status,
                'level' => $course->difficulty,
                'language' => $course->language,
                'duration_hours' => $course->total_duration ? round($course->total_duration / 3600, 1) : null,
                'rejection_reason' => $course->rejection_reason,
                // a rejected course that was live before was taken down ("Nashrdan olingan")
                'was_published' => $course->published_at !== null,
                'teacher' => [
                    'id' => $course->teacher->id,
                    'full_name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
                    'email' => $course->teacher->email,
                ],
                'direction' => [
                    'id' => $course->direction->id,
                    'name' => $course->direction->name_uz,
                ],
                'students_count' => $course->enrollments()->count(),
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
        $old = $course->status;
        $wasPublished = $course->published_at !== null;
        $course->update([
            'status' => 'published',
            'approved_at' => now(),
            'published_at' => $course->published_at ?? now(),
            'rejection_reason' => null,
        ]);

        // a course taken down earlier is published again, not "approved"
        $republished = $old === 'rejected' && $wasPublished;

        AuditLog::log('updated', $course, ['status' => $old], ['status' => 'published'], ($republished ? 'Kurs qayta nashr qilindi: ' : 'Kurs tasdiqlandi: ') . $course->title);
        if ($course->teacher) {
            AppNotification::send($course->teacher, 'course_approved', $republished ? 'Kursingiz qayta nashr qilindi' : 'Kursingiz tasdiqlandi',
                "«{$course->title}» nashr qilindi va o'quvchilarga ko'rinadi.", ['course_id' => $course->id]);
        }

        return back()->with('success', $republished ? 'Kurs qayta nashr qilindi!' : 'Kurs muvaffaqiyatli tasdiqlandi!');
    }

    /**
     * Reject the course.
     */
    public function reject(Request $request, Course $course): RedirectResponse
    {
        $old = $course->status;
        // a published course is taken down ("Nashrdan olish"), anything else is rejected
        $unpublishing = $old === 'published';

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => $unpublishing ? 'Nashrdan olish sababini kiriting' : 'Rad etish sababini kiriting',
        ]);

        $course->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::log('updated', $course, ['status' => $old], ['status' => 'rejected', 'rejection_reason' => $request->rejection_reason],
            ($unpublishing ? 'Kurs nashrdan olindi: ' : 'Kurs rad etildi: ') . $course->title);
        if ($course->teacher) {
            AppNotification::send($course->teacher, 'course_rejected', $unpublishing ? 'Kursingiz nashrdan olindi' : 'Kursingiz rad etildi',
                "«{$course->title}»: {$request->rejection_reason}", ['course_id' => $course->id], 'high');
        }

        return back()->with('success', $unpublishing ? 'Kurs nashrdan olindi!' : 'Kurs rad etildi!');
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();
        AuditLog::log('deleted', $course, [], [], "Kurs o'chirildi: {$course->title}");

        return redirect()->route('admin.courses.index')
            ->with('success', 'Kurs muvaffaqiyatli o\'chirildi!');
    }
}
