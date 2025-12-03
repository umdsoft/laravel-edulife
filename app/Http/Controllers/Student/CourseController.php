<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Direction;
use App\Models\CourseView;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['teacher', 'direction', 'tags'])
            ->where('status', 'published')
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating');
        
        // Filters
        if ($request->direction) {
            $query->where('direction_id', $request->direction);
        }
        
        if ($request->level) {
            $query->where('level', $request->level);
        }
        
        if ($request->price === 'free') {
            $query->where('is_free', true);
        } elseif ($request->price === 'paid') {
            $query->where('is_free', false);
        }
        
        if ($request->rating) {
            $query->having('reviews_avg_rating', '>=', $request->rating);
        }
        
        // Sort
        $query->when($request->sort, function ($q, $sort) {
            return match($sort) {
                'popular' => $q->orderByDesc('enrollments_count'),
                'rating' => $q->orderByDesc('reviews_avg_rating'),
                'newest' => $q->latest(),
                'price_low' => $q->orderBy('price'),
                'price_high' => $q->orderByDesc('price'),
                default => $q->orderByDesc('enrollments_count'),
            };
        }, fn($q) => $q->orderByDesc('enrollments_count'));
        
        $courses = $query->paginate(12)->withQueryString();
        
        $directions = Direction::withCount(['courses' => fn($q) => $q->where('status', 'published')])
            ->orderByDesc('courses_count')
            ->get();
        
        return Inertia::render('Student/Courses/Index', [
            'courses' => $courses,
            'directions' => $directions,
            'filters' => $request->only(['direction', 'level', 'price', 'rating', 'sort']),
        ]);
    }
    
    public function category(Direction $direction, Request $request)
    {
        $query = Course::with(['teacher', 'tags'])
            ->where('status', 'published')
            ->where('direction_id', $direction->id)
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating');
        
        // Filters (same as index)
        if ($request->level) {
            $query->where('level', $request->level);
        }
        
        if ($request->price === 'free') {
            $query->where('is_free', true);
        } elseif ($request->price === 'paid') {
            $query->where('is_free', false);
        }
        
        // Sort
        $query->when($request->sort, function ($q, $sort) {
            return match($sort) {
                'popular' => $q->orderByDesc('enrollments_count'),
                'rating' => $q->orderByDesc('reviews_avg_rating'),
                'newest' => $q->latest(),
                'price_low' => $q->orderBy('price'),
                'price_high' => $q->orderByDesc('price'),
                default => $q->orderByDesc('enrollments_count'),
            };
        }, fn($q) => $q->orderByDesc('enrollments_count'));
        
        $courses = $query->paginate(12)->withQueryString();
        
        return Inertia::render('Student/Courses/Category', [
            'direction' => $direction,
            'courses' => $courses,
            'filters' => $request->only(['level', 'price', 'sort']),
        ]);
    }
    
    public function show(Course $course)
    {
        $course->load([
            'teacher.teacherProfile',
            'direction',
            'tags',
            'modules' => fn($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn($q) => $q->orderBy('sort_order'),
            'reviews' => fn($q) => $q->with('user')->latest()->limit(10),
        ]);
        
        $course->loadCount(['enrollments', 'reviews', 'lessons']);
        $course->loadAvg('reviews', 'rating');
        
        // Record view
        CourseView::create([
            'course_id' => $course->id,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'viewed_date' => now(), // Using viewed_date as per existing model
        ]);
        
        // Increment course views
        $course->increment('views_count');
        
        $user = Auth::user();
        
        // User enrollment status
        $enrollment = null;
        $isEnrolled = false;
        $isInWishlist = false;
        
        if ($user) {
            $enrollment = $user->enrollments()->where('course_id', $course->id)->first();
            $isEnrolled = (bool) $enrollment;
            $isInWishlist = $user->hasInWishlist($course);
        }
        
        // Similar courses
        $similarCourses = Course::with(['teacher'])
            ->where('status', 'published')
            ->where('id', '!=', $course->id)
            ->where(function ($q) use ($course) {
                $q->where('direction_id', $course->direction_id)
                  ->orWhereHas('tags', fn($q2) => $q2->whereIn('id', $course->tags->pluck('id')));
            })
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->limit(4)
            ->get();
        
        // Rating distribution
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingDistribution[$i] = $course->reviews()->where('rating', $i)->count();
        }
        
        return Inertia::render('Student/Courses/Show', [
            'course' => $course,
            'enrollment' => $enrollment,
            'isEnrolled' => $isEnrolled,
            'isInWishlist' => $isInWishlist,
            'similarCourses' => $similarCourses,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }
    
    public function previewLesson(Course $course, Lesson $lesson)
    {
        // Faqat preview darslarni ko'rsatish
        if (!$lesson->is_preview && !$lesson->is_free) {
            abort(403, 'Bu dars faqat kursga yozilganlar uchun');
        }
        
        $lesson->load(['video', 'module']);
        
        return Inertia::render('Student/Courses/Preview', [
            'course' => $course,
            'lesson' => $lesson,
        ]);
    }
}
