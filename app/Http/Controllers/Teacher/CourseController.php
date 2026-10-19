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
    public function index(Request $request)
    {
        $statuses = ['draft', 'pending', 'approved', 'published', 'rejected'];
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), $statuses, true) ? $request->query('status') : '',
        ];

        $courses = Course::where('teacher_id', Auth::id())
            ->when($filters['search'] !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($filters['search'], '%_\\').'%'))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->withCount(['enrollments', 'modules', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // tab counters cover all of the teacher's courses, not just the current page
        $statusCounts = Course::where('teacher_id', Auth::id())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n);

        return Inertia::render('Teacher/Courses/Index', [
            'courses' => $courses,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
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
            'difficulty' => $request->level,
            'language' => $request->language ?? 'uz',
            ...$this->pricingAttributes($request),
            'is_free' => $request->boolean('is_free'),
            'requirements' => $request->requirements,
            'what_you_learn' => $request->what_will_learn,
            'target_audience' => $request->who_is_for,
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

        // The course workspace starts at its details tab (CourseNav); there is no separate overview page
        return redirect()->route('teacher.courses.edit', $course);
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
            'difficulty' => $request->level,
            // language is NOT NULL; the edit form may omit it
            'language' => $request->language ?? $course->language,
            ...$this->pricingAttributes($request),
            'is_free' => $request->boolean('is_free'),
            'requirements' => $request->requirements,
            'what_you_learn' => $request->what_will_learn,
            'target_audience' => $request->who_is_for,
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
        
        if (!$course->thumbnail) {
            return back()->with('error', 'Kurs rasmi yuklanishi kerak');
        }

        // a lesson without material can never be completed by a student
        $empty = $this->lessonsWithoutContent($course);
        if ($empty->isNotEmpty()) {
            $names = $empty->take(3)->map(fn ($title) => "«{$title}»")->implode(', ');
            $more = $empty->count() > 3 ? ' va yana '.($empty->count() - 3).' ta' : '';

            return back()->with('error', "Ba'zi darslarda material yo'q: {$names}{$more}. Video yuklang, matn yozing yoki test darsiga faol test qo'shing.");
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
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }
        
        $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        $course->update(['thumbnail' => $path]);
        
        return back()->with('success', 'Kurs rasmi yangilandi');
    }
    
    public function updatePreviewVideo(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $request->validate([
            'preview_video' => ['required', 'mimes:mp4,mov,avi', 'max:102400'], // 100MB
        ]);
        
        // Delete old preview video
        if ($course->preview_video) {
            Storage::disk('public')->delete($course->preview_video);
        }
        
        $path = $request->file('preview_video')->store('courses/previews', 'public');
        $course->update(['preview_video' => $path]);

        return back()->with('success', 'Preview video yangilandi');
    }

    /**
     * Titles of lessons that have nothing to study: a video lesson without an
     * uploaded video or link, a text lesson without text, a quiz lesson without
     * an active test.
     */
    private function lessonsWithoutContent(Course $course)
    {
        $lessons = \App\Models\Lesson::where('course_id', $course->id)
            ->with('video:id,lesson_id')
            ->orderBy('sort_order')
            ->get(['id', 'title', 'type', 'video_url', 'text_content']);

        $quizIds = \App\Models\Test::where('testable_type', \App\Models\Lesson::class)
            ->whereIn('testable_id', $lessons->where('type', 'quiz')->pluck('id'))
            ->where('is_active', true)
            ->pluck('testable_id')
            ->all();

        return $lessons->filter(fn ($lesson) => match ($lesson->type) {
            'video' => ! $lesson->video && blank($lesson->getRawOriginal('video_url')),
            'text' => blank(strip_tags((string) $lesson->text_content)),
            'quiz' => ! in_array($lesson->id, $quizIds, true),
            default => false,
        })->pluck('title')->values();
    }

    /**
     * Map the form's price/discount_price onto the courses columns.
     *
     * courses.price is what a student pays; original_price keeps the list
     * price while a discount is active (there is no discount_price column).
     */
    private function pricingAttributes(Request $request): array
    {
        if ($request->boolean('is_free')) {
            return ['price' => 0, 'original_price' => null];
        }

        $price = (float) ($request->price ?? 0);
        $discount = $request->filled('discount_price') ? (float) $request->discount_price : null;

        if ($discount !== null && $discount < $price) {
            return ['price' => $discount, 'original_price' => $price];
        }

        return ['price' => $price, 'original_price' => null];
    }
}
