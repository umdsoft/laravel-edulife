<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseQuestion;
use App\Http\Requests\Student\StoreQuestionRequest;
use App\Notifications\NewQuestionNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentQnaController extends Controller
{
    public function index(Course $course)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $user->enrollments()->where('course_id', $course->id)->firstOrFail();
        
        $questions = CourseQuestion::with(['user', 'lesson', 'replies.user'])
            ->where('course_id', $course->id)
            ->questions()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(20);
        
        $course->load([
            'modules' => fn($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn($q) => $q->orderBy('sort_order'),
        ]);
        
        return Inertia::render('Student/Learn/Qna', [
            'course' => $course,
            'questions' => $questions,
        ]);
    }
    
    public function store(StoreQuestionRequest $request, Course $course)
    {
        $user = Auth::user();
        
        // Verify enrollment
        $user->enrollments()->where('course_id', $course->id)->firstOrFail();
        
        $question = CourseQuestion::create([
            'course_id' => $course->id,
            'lesson_id' => $request->lesson_id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);
        
        // Notify teacher
        $course->teacher->notify(new NewQuestionNotification($question));
        
        // XP for asking question
        $user->studentProfile->addXp(5);
        
        return back()->with('success', 'Savol yuborildi');
    }
    
    public function upvote(CourseQuestion $question)
    {
        $user = Auth::user();
        
        // Simple upvote (can be improved with pivot table for tracking)
        $question->increment('upvotes');
        
        return back();
    }
}
