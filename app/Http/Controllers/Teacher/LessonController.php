<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Http\Requests\Teacher\StoreLessonRequest;
use App\Http\Requests\Teacher\UpdateLessonRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LessonController extends Controller
{
    public function store(StoreLessonRequest $request, Course $course, Module $module)
    {
        $this->authorize('update', $course);
        
        // Get next sort order
        $maxOrder = $module->lessons()->max('sort_order') ?? 0;
        
        $lesson = $module->lessons()->create([
            'course_id' => $course->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type, // video, text, quiz
            'content' => $request->content, // for text lessons
            'duration' => 0,
            'sort_order' => $maxOrder + 1,
            'is_free' => $request->boolean('is_free'),
            'is_preview' => $request->boolean('is_preview'),
        ]);
        
        return back()->with('success', 'Dars qo\'shildi');
    }
    
    public function edit(Course $course, Module $module, Lesson $lesson)
    {
        $this->authorize('update', $course);
        
        $lesson->load(['video', 'attachments']);
        
        return Inertia::render('Teacher/Courses/Curriculum/LessonForm', [
            'course' => $course,
            'module' => $module,
            'lesson' => $lesson,
        ]);
    }
    
    public function update(UpdateLessonRequest $request, Course $course, Module $module, Lesson $lesson)
    {
        $this->authorize('update', $course);
        
        $lesson->update([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'content' => $request->content,
            'is_free' => $request->boolean('is_free'),
            'is_preview' => $request->boolean('is_preview'),
        ]);
        
        return back()->with('success', 'Dars yangilandi');
    }
    
    public function destroy(Course $course, Module $module, Lesson $lesson)
    {
        $this->authorize('update', $course);
        
        // Check if lesson has progress
        if ($lesson->lessonProgress()->exists()) {
            return back()->with('error', 'Bu darsda o\'quvchilar progressi bor');
        }
        
        // Delete video files
        if ($lesson->video) {
            Storage::disk('public')->deleteDirectory('videos/' . $lesson->id);
            $lesson->video()->delete();
        }
        
        // Delete attachments
        foreach ($lesson->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }
        $lesson->attachments()->delete();
        
        $lesson->delete();
        
        // Reorder remaining lessons
        $module->lessons()
            ->where('sort_order', '>', $lesson->sort_order)
            ->decrement('sort_order');
        
        return back()->with('success', 'Dars o\'chirildi');
    }
    
    public function reorder(Request $request, Course $course, Module $module)
    {
        $this->authorize('update', $course);
        
        $request->validate([
            'lessons' => ['required', 'array'],
            'lessons.*.id' => ['required', 'uuid'],
            'lessons.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);
        
        foreach ($request->lessons as $item) {
            Lesson::where('id', $item['id'])
                ->where('module_id', $module->id)
                ->update(['sort_order' => $item['sort_order']]);
        }
        
        return back()->with('success', 'Tartib saqlandi');
    }
}
