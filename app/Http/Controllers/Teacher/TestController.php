<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Test;
use App\Models\Module;
use App\Models\Lesson;
use App\Http\Requests\Teacher\StoreTestRequest;
use App\Http\Requests\Teacher\UpdateTestRequest;
use Inertia\Inertia;

class TestController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('update', $course);
        
        // tests.questions_count is the minimum needed to activate; withCount('questions')
        // would overwrite it, so the added questions are counted under another name
        $tests = Test::where('course_id', $course->id)
            ->with(['testable'])
            ->withCount('questions as questions_added')
            ->orderBy('type')
            ->orderBy('created_at')
            ->get()
            ->each(fn ($test) => $test->setAttribute('required_questions', (int) $test->questions_count))
            ->groupBy('type');
        
        return Inertia::render('Teacher/Courses/Tests/Index', [
            'course' => $course,
            'tests' => $tests,
            'modules' => $course->modules()->with('lessons')->get(),
        ]);
    }
    
    public function create(Course $course)
    {
        $this->authorize('update', $course);
        
        return Inertia::render('Teacher/Courses/Tests/Create', [
            'course' => $course,
            'modules' => $course->modules()->with('lessons')->get(),
            'testTypes' => $this->getTestTypes(),
        ]);
    }
    
    public function store(StoreTestRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        
        $testableType = null;
        $testableId = null;
        
        if ($request->type === 'lesson_test') {
            $testableType = Lesson::class;
            $testableId = $request->lesson_id;
        } elseif ($request->type === 'module_test') {
            $testableType = Module::class;
            $testableId = $request->module_id;
        }
        
        // Map request type to DB enum
        $dbType = match($request->type) {
            'lesson_test' => 'lesson',
            'module_test' => 'module',
            'final_test' => 'final',
        };
        
        $test = Test::create([
            'course_id' => $course->id,
            'testable_type' => $testableType,
            'testable_id' => $testableId,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $dbType,
            'passing_score' => $this->getPassScore($request->type),
            'time_limit' => $this->getTimeLimit($request->type),
            'questions_count' => $this->getQuestionsCount($request->type),
            'max_attempts' => $request->max_attempts ?? 3,
            'shuffle_questions' => $request->boolean('shuffle_questions', true),
            'shuffle_options' => $request->boolean('shuffle_options', true),
            'show_correct_answers' => $request->boolean('show_correct_answers', false),
            'is_active' => false,
        ]);
        
        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', 'Test yaratildi. Endi savollar qo\'shing.');
    }
    
    public function show(Course $course, Test $test)
    {
        $this->authorize('update', $course);

        $test->load('testable');
        $test->loadCount('questions');

        $submitted = $test->attempts()->where('status', 'submitted');

        $stats = [
            'total_attempts' => (clone $submitted)->count(),
            'students' => (clone $submitted)->distinct('user_id')->count('user_id'),
            'avg_score' => round((float) (clone $submitted)->avg('score'), 1),
            'pass_rate' => $this->calculatePassRate($test),
            'avg_time' => (int) round((float) (clone $submitted)->avg('time_spent')),
        ];

        $attempts = (clone $submitted)
            ->with('user:id,first_name,last_name,avatar')
            ->latest('submitted_at')
            ->take(50)
            ->get()
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'enrollment_id' => $attempt->enrollment_id,
                'student' => $attempt->user?->full_name,
                'avatar_url' => $attempt->user?->avatar_url,
                'attempt_number' => $attempt->attempt_number,
                'score' => (float) $attempt->score,
                'is_passed' => (bool) $attempt->is_passed,
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'time_spent' => $attempt->time_spent,
                'submitted_at' => $attempt->submitted_at,
            ]);

        // Share of correct answers per question, hardest first
        $questions = $test->questions()
            ->withCount([
                'answers as answered_count' => fn ($q) => $q->where('is_answered', true),
                'answers as correct_count' => fn ($q) => $q->where('is_correct', true),
            ])
            ->orderBy('sort_order')
            ->get(['id', 'question_text', 'type', 'sort_order'])
            ->map(fn ($question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'answered_count' => (int) $question->answered_count,
                'correct_rate' => $question->answered_count > 0
                    ? (int) round($question->correct_count / $question->answered_count * 100)
                    : null,
            ])
            ->sortBy(fn ($q) => $q['correct_rate'] ?? 101)
            ->values();

        return Inertia::render('Teacher/Courses/Tests/Show', [
            'course' => $course->only(['id', 'title']),
            'test' => $test,
            'stats' => $stats,
            'attempts' => $attempts,
            'questions' => $questions,
        ]);
    }

    public function edit(Course $course, Test $test)
    {
        $this->authorize('update', $course);
        
        return Inertia::render('Teacher/Courses/Tests/Edit', [
            'course' => $course,
            'test' => $test,
            'modules' => $course->modules()->with('lessons')->get(),
            'testTypes' => $this->getTestTypes(),
        ]);
    }
    
    public function update(UpdateTestRequest $request, Course $course, Test $test)
    {
        $this->authorize('update', $course);
        
        $test->update([
            'title' => $request->title,
            'description' => $request->description,
            'max_attempts' => $request->max_attempts,
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_options' => $request->boolean('shuffle_options'),
            'show_correct_answers' => $request->boolean('show_correct_answers'),
        ]);
        
        return back()->with('success', 'Test yangilandi');
    }
    
    public function destroy(Course $course, Test $test)
    {
        $this->authorize('update', $course);
        
        // Check for attempts
        if ($test->attempts()->exists()) {
            return back()->with('error', 'Bu testda urinishlar mavjud. O\'chirib bo\'lmaydi.');
        }
        
        $test->questions()->delete();
        $test->delete();
        
        return redirect()->route('teacher.courses.tests.index', $course)
            ->with('success', 'Test o\'chirildi');
    }
    
    public function toggleStatus(Course $course, Test $test)
    {
        $this->authorize('update', $course);
        
        // Check questions count before activating
        if (!$test->is_active && $test->questions()->count() < $test->questions_count) {
            return back()->with('error', 'Testni faollashtirish uchun kamida ' . $test->questions_count . ' ta savol kerak');
        }
        
        $test->update(['is_active' => !$test->is_active]);
        
        return back()->with('success', $test->is_active ? 'Test faollashtirildi' : 'Test o\'chirildi');
    }
    
    private function getTestTypes(): array
    {
        return [
            'lesson_test' => [
                'label' => 'Dars testi',
                'pass_score' => 86,
                'time_limit' => 30,
                'questions_count' => 10,
            ],
            'module_test' => [
                'label' => 'Modul testi',
                'pass_score' => 90,
                'time_limit' => 45,
                'questions_count' => 20,
            ],
            'final_test' => [
                'label' => 'Yakuniy test',
                'pass_score' => 85,
                'time_limit' => 60,
                'questions_count' => 30,
            ],
        ];
    }
    
    private function getPassScore(string $type): int
    {
        return match($type) {
            'lesson_test' => 86,
            'module_test' => 90,
            'final_test' => 85,
            default => 80,
        };
    }
    
    private function getTimeLimit(string $type): int
    {
        return match($type) {
            'lesson_test' => 30,
            'module_test' => 45,
            'final_test' => 60,
            default => 30,
        };
    }
    
    private function getQuestionsCount(string $type): int
    {
        return match($type) {
            'lesson_test' => 10,
            'module_test' => 20,
            'final_test' => 30,
            default => 10,
        };
    }
    
    private function calculatePassRate(Test $test): float
    {
        $total = $test->attempts()->where('status', 'submitted')->count();
        if ($total === 0) return 0;

        $passed = $test->attempts()->where('status', 'submitted')->where('is_passed', true)->count();
        return round(($passed / $total) * 100, 1);
    }
}
