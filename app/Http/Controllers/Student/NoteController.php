<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Http\Requests\Student\StoreNoteRequest;
use App\Http\Requests\Student\UpdateNoteRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NoteController extends Controller
{
    /**
     * All notes for a course
     */
    public function index(Course $course)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $user->enrollments()->where('course_id', $course->id)->firstOrFail();
        
        $notes = LessonNote::with(['lesson.module'])
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lesson_id');
        
        $course->load([
            'modules' => fn($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn($q) => $q->orderBy('sort_order'),
        ]);
        
        return Inertia::render('Student/Learn/Notes', [
            'course' => $course,
            'notes' => $notes,
        ]);
    }
    
    /**
     * Create note
     */
    public function store(StoreNoteRequest $request, Lesson $lesson)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $user->enrollments()->where('course_id', $lesson->course_id)->firstOrFail();
        
        $note = LessonNote::create([
            'user_id' => $user->id,
            'course_id' => $lesson->course_id,
            'lesson_id' => $lesson->id,
            'content' => $request->content,
            'video_timestamp' => $request->video_timestamp,
            'color' => $request->color ?? 'yellow',
        ]);
        
        return back()->with('success', 'Yozuv saqlandi');
    }
    
    /**
     * Update note
     */
    public function update(UpdateNoteRequest $request, LessonNote $note)
    {
        $this->authorize('update', $note);
        
        $note->update([
            'content' => $request->content,
            'color' => $request->color,
        ]);
        
        return back()->with('success', 'Yozuv yangilandi');
    }
    
    /**
     * Delete note
     */
    public function destroy(LessonNote $note)
    {
        $this->authorize('delete', $note);
        
        $note->delete();
        
        return back()->with('success', 'Yozuv o\'chirildi');
    }
    
    /**
     * Toggle pin
     */
    public function togglePin(LessonNote $note)
    {
        $this->authorize('update', $note);
        
        $note->update(['is_pinned' => !$note->is_pinned]);
        
        return back();
    }
}
