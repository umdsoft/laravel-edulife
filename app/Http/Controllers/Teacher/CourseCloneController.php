<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseCloneService;
use Illuminate\Http\Request;

class CourseCloneController extends Controller
{
    public function __construct(
        protected CourseCloneService $cloneService
    ) {}
    
    public function store(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'clone_media' => ['boolean'],
            'clone_curriculum' => ['boolean'],
            'clone_tests' => ['boolean'],
            'clone_attachments' => ['boolean'],
        ]);
        
        $newCourse = $this->cloneService->clone($course, $request->all());
        
        return redirect()->route('teacher.courses.edit', $newCourse->id)
            ->with('success', 'Kurs muvaffaqiyatli nusxalandi');
    }
}
