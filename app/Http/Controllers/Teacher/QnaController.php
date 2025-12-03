<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseQuestion;
use App\Http\Requests\Teacher\StoreQuestionAnswerRequest;
use App\Notifications\QuestionAnsweredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class QnaController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('update', $course);
        
        $questions = CourseQuestion::with(['user', 'lesson', 'replies.user'])
            ->where('course_id', $course->id)
            ->questions() // only parent questions, not replies
            ->latest()
            ->paginate(20);
        
        // Stats
        $stats = [
            'total' => CourseQuestion::where('course_id', $course->id)->questions()->count(),
            'unanswered' => CourseQuestion::where('course_id', $course->id)->questions()->unanswered()->count(),
            'pinned' => CourseQuestion::where('course_id', $course->id)->questions()->where('is_pinned', true)->count(),
        ];
        
        return Inertia::render('Teacher/Courses/Qna/Index', [
            'course' => $course,
            'questions' => $questions,
            'stats' => $stats,
            'filters' => request()->only(['status', 'lesson_id']),
        ]);
    }
    
    public function reply(StoreQuestionAnswerRequest $request, Course $course, CourseQuestion $question)
    {
        $this->authorize('update', $course);
        
        // Create reply
        $reply = CourseQuestion::create([
            'course_id' => $course->id,
            'lesson_id' => $question->lesson_id,
            'user_id' => Auth::id(),
            'parent_id' => $question->id,
            'content' => $request->content,
            'is_answered' => true,
        ]);
        
        // Mark original question as answered
        $question->markAsAnswered();
        
        // Notify student
        $question->user->notify(new QuestionAnsweredNotification($question, $reply));
        
        return back()->with('success', 'Javob yuborildi');
    }
    
    public function togglePin(Course $course, CourseQuestion $question)
    {
        $this->authorize('update', $course);
        
        $question->update(['is_pinned' => !$question->is_pinned]);
        
        return back()->with('success', $question->is_pinned ? 'Savol qadaldi' : 'Savol qadalmaydigan qilindi');
    }
    
    public function toggleHighlight(Course $course, CourseQuestion $question)
    {
        $this->authorize('update', $course);
        
        $question->update(['is_highlighted' => !$question->is_highlighted]);
        
        return back()->with('success', $question->is_highlighted ? 'Savol muhim deb belgilandi' : 'Belgi olib tashlandi');
    }
    
    public function destroy(Course $course, CourseQuestion $question)
    {
        $this->authorize('update', $course);
        
        $question->delete();
        
        return back()->with('success', 'Savol o\'chirildi');
    }
}
