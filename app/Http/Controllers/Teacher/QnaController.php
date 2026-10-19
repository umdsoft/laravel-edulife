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
        
        $status = (string) request('status', '');
        $lessonId = (string) request('lesson_id', '');

        // the tabs sent ?status=… but it was never applied
        $questions = CourseQuestion::with(['user', 'lesson', 'replies.user'])
            ->where('course_id', $course->id)
            ->questions() // only parent questions, not replies
            ->when($status === 'unanswered', fn ($q) => $q->unanswered())
            ->when($status === 'answered', fn ($q) => $q->where('is_answered', true))
            ->when($status === 'pinned', fn ($q) => $q->where('is_pinned', true))
            ->when($status === 'highlighted', fn ($q) => $q->where('is_highlighted', true))
            ->when($lessonId !== '', fn ($q) => $q->where('lesson_id', $lessonId))
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(20)
            ->withQueryString();
        
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
        abort_unless((string) $question->course_id === (string) $course->id, 404);
        
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
        abort_unless((string) $question->course_id === (string) $course->id, 404);
        
        $question->update(['is_pinned' => !$question->is_pinned]);
        
        return back()->with('success', $question->is_pinned ? 'Savol qadaldi' : 'Savol qadalmaydigan qilindi');
    }
    
    public function toggleHighlight(Course $course, CourseQuestion $question)
    {
        $this->authorize('update', $course);
        abort_unless((string) $question->course_id === (string) $course->id, 404);
        
        $question->update(['is_highlighted' => !$question->is_highlighted]);
        
        return back()->with('success', $question->is_highlighted ? 'Savol muhim deb belgilandi' : 'Belgi olib tashlandi');
    }
    
    public function destroy(Course $course, CourseQuestion $question)
    {
        $this->authorize('update', $course);
        abort_unless((string) $question->course_id === (string) $course->id, 404);
        
        $question->delete();
        
        return back()->with('success', 'Savol o\'chirildi');
    }
}
