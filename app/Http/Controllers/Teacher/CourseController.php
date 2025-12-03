<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Direction;
use App\Models\Tag;
use App\Http\Requests\Teacher\StoreCourseRequest;
use App\Http\Requests\Teacher\UpdateCourseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('teacher_id', Auth::id())
            ->withCount(['enrollments', 'modules', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(12);
        
        return Inertia::render('Teacher/Courses/Index', [
            'courses' => $courses,
        ]);
    }
    
    public function create()
    {
        return Inertia::render('Teacher/Courses/Create', [
            'directions' => Direction::active()->ordered()->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }
    
    public function store(StoreCourseRequest $request)
    {
        $course = Course::create([
            'teacher_id' => Auth::id(),
            'direction_id' => $request->direction_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . Str::random(6),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'level' => $request->level,
            'language' => $request->language ?? 'uz',
            'price' => $request->price ?? 0,
            'discount_price' => $request->discount_price,
            'is_free' => $request->boolean('is_free'),
            'requirements' => $request->requirements,
            'what_will_learn' => $request->what_will_learn,
            'who_is_for' => $request->who_is_for,
            'status' => 'draft',
        ]);
        
        // Attach tags
        if ($request->tags) {
            $course->tags()->attach($request->tags);
        }
        
        return redirect()->route('teacher.courses.edit', $course)
            ->with('success', 'Kurs yaratildi. Endi modul va darslarni qo\'shing.');
    }
    
    public function show(Course $course)
    {
        $this->authorize('view', $course);
        
        $course->load([
            'modules.lessons',
            'enrollments' => fn($q) => $q->latest()->take(10),
            'reviews' => fn($q) => $q->latest()->take(5),
        ]);
        
        $course->loadCount(['enrollments', 'modules', 'reviews']);
        $course->loadAvg('reviews', 'rating');
        
        return Inertia::render('Teacher/Courses/Show', [
            'course' => $course,
        ]);
    }
    
    public function edit(Course $course)
    {
        $this->authorize('update', $course);
        
        $course->load(['tags', 'modules.lessons']);
        
        return Inertia::render('Teacher/Courses/Edit', [
            'course' => $course,
            'directions' => Direction::active()->ordered()->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }
    
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $course->update([
            'direction_id' => $request->direction_id,
            'title' => $request->title,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'level' => $request->level,
            'language' => $request->language,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'is_free' => $request->boolean('is_free'),
            'requirements' => $request->requirements,
            'what_will_learn' => $request->what_will_learn,
            'who_is_for' => $request->who_is_for,
        ]);
        
        // Sync tags
        if ($request->has('tags')) {
            $course->tags()->sync($request->tags);
        }
        
        return back()->with('success', 'Kurs yangilandi');
    }
    
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        
        // Only draft courses can be deleted
        if ($course->status !== 'draft') {
            return back()->with('error', 'Faqat draft kurslarni o\'chirish mumkin');
        }
        
        // Check enrollments
        if ($course->enrollments()->exists()) {
            return back()->with('error', 'O\'quvchilari bor kursni o\'chirib bo\'lmaydi');
        }
        
        $course->delete();
        
        return redirect()->route('teacher.courses.index')
            ->with('success', 'Kurs o\'chirildi');
    }
    
    public function submit(Course $course)
    {
        $this->authorize('update', $course);
        
        // Validate course is ready
        if ($course->modules()->count() === 0) {
            return back()->with('error', 'Kamida bitta modul bo\'lishi kerak');
        }
        
        $lessonsCount = $course->modules()->withCount('lessons')->get()->sum('lessons_count');
        if ($lessonsCount === 0) {
            return back()->with('error', 'Kamida bitta dars bo\'lishi kerak');
        }
        
        if (!$course->thumbnail_url) {
            return back()->with('error', 'Kurs rasmi yuklanishi kerak');
        }
        
        $course->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        
        return back()->with('success', 'Kurs tekshiruvga yuborildi');
    }
    
    public function updateThumbnail(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $request->validate([
            'thumbnail' => ['required', 'image', 'max:5120'], // 5MB
        ]);
        
        // Delete old thumbnail
        if ($course->thumbnail_url) {
            Storage::disk('public')->delete($course->thumbnail_url);
        }
        
        $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        $course->update(['thumbnail_url' => $path]);
        
        return back()->with('success', 'Kurs rasmi yangilandi');
    }
    
    public function updatePreviewVideo(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $request->validate([
            'preview_video' => ['required', 'mimes:mp4,mov,avi', 'max:102400'], // 100MB
        ]);
        
        // Delete old preview video
        if ($course->preview_video_url) {
            Storage::disk('public')->delete($course->preview_video_url);
        }
        
        $path = $request->file('preview_video')->store('courses/previews', 'public');
        $course->update(['preview_video_url' => $path]);
        
        return back()->with('success', 'Preview video yangilandi');
    }
}
