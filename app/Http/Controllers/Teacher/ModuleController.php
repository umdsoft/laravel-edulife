<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Http\Requests\Teacher\StoreModuleRequest;
use App\Http\Requests\Teacher\UpdateModuleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModuleController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('update', $course);
        
        $modules = $course->modules()
            ->with(['lessons' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
        
        return Inertia::render('Teacher/Courses/Curriculum/Index', [
            'course' => $course,
            'modules' => $modules,
        ]);
    }
    
    public function store(StoreModuleRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        
        // Get next sort order
        $maxOrder = $course->modules()->max('sort_order') ?? 0;
        
        $module = $course->modules()->create([
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $maxOrder + 1,
            'is_free' => $request->boolean('is_free'),
        ]);
        
        return back()->with('success', 'Modul qo\'shildi');
    }
    
    public function update(UpdateModuleRequest $request, Course $course, Module $module)
    {
        $this->authorize('update', $course);
        
        $module->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_free' => $request->boolean('is_free'),
        ]);
        
        return back()->with('success', 'Modul yangilandi');
    }
    
    public function destroy(Course $course, Module $module)
    {
        $this->authorize('update', $course);
        
        // Check if module has lessons with progress
        $hasProgress = $module->lessons()
            ->whereHas('lessonProgress')
            ->exists();
        
        if ($hasProgress) {
            return back()->with('error', 'Bu modulda o\'quvchilar progressi bor. O\'chirib bo\'lmaydi.');
        }
        
        // Delete all lessons first
        $module->lessons()->delete();
        $module->delete();
        
        // Reorder remaining modules
        $course->modules()
            ->where('sort_order', '>', $module->sort_order)
            ->decrement('sort_order');
        
        return back()->with('success', 'Modul o\'chirildi');
    }
    
    public function reorder(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.id' => ['required', 'uuid'],
            'modules.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);
        
        foreach ($request->modules as $item) {
            Module::where('id', $item['id'])
                ->where('course_id', $course->id)
                ->update(['sort_order' => $item['sort_order']]);
        }
        
        return back()->with('success', 'Tartib saqlandi');
    }
}
