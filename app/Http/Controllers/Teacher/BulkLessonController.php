<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Http\Requests\Teacher\BulkStoreLessonRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BulkLessonController extends Controller
{
    public function create(Course $course, Module $module)
    {
        $this->authorize('update', $course);
        
        return Inertia::render('Teacher/Courses/Curriculum/BulkLessonCreate', [
            'course' => $course,
            'module' => $module,
        ]);
    }
    
    public function store(BulkStoreLessonRequest $request, Course $course, Module $module)
    {
        $this->authorize('update', $course);
        
        DB::transaction(function () use ($request, $course, $module) {
            $maxOrder = $module->lessons()->max('sort_order') ?? 0;
            
            foreach ($request->lessons as $index => $lessonData) {
                Lesson::create([
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                    'title' => $lessonData['title'],
                    'description' => $lessonData['description'] ?? null,
                    'type' => $lessonData['type'] ?? 'video',
                    'is_free' => $lessonData['is_free'] ?? false,
                    'is_preview' => $lessonData['is_preview'] ?? false,
                    'sort_order' => $maxOrder + $index + 1,
                    'video_status' => 'pending',
                ]);
            }
            
            // Update counts
            $course->updateCounts();
        });
        
        return redirect()->route('teacher.courses.edit', ['course' => $course->id, 'tab' => 'curriculum'])
            ->with('success', 'Darslar muvaffaqiyatli yaratildi');
    }
}
