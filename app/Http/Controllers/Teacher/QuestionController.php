<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Question;
use App\Http\Requests\Teacher\StoreQuestionRequest;
use App\Http\Requests\Teacher\UpdateQuestionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function index(Test $test)
    {
        $this->authorize('update', $test->course);
        
        $questions = $test->questions()
            ->with('options')
            ->orderBy('sort_order')
            ->get();
        
        return Inertia::render('Teacher/Courses/Tests/Questions/Index', [
            'course' => $test->course,
            'test' => $test,
            'questions' => $questions,
        ]);
    }
    
    public function create(Test $test)
    {
        $this->authorize('update', $test->course);
        
        return Inertia::render('Teacher/Courses/Tests/Questions/Create', [
            'course' => $test->course,
            'test' => $test,
            'questionTypes' => $this->getQuestionTypes(),
        ]);
    }
    
    public function store(StoreQuestionRequest $request, Test $test)
    {
        $this->authorize('update', $test->course);
        
        // Get next sort order
        $maxOrder = $test->questions()->max('sort_order') ?? 0;
        
        $question = $test->questions()->create([
            'type' => $request->type,
            'question_text' => $request->question_text,
            'points' => $request->points ?? 1,
            'explanation' => $request->explanation,
            'sort_order' => $maxOrder + 1,
        ]);
        
        // Create options based on type
        $this->createOptions($question, $request->options);
        
        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', 'Savol qo\'shildi');
    }
    
    public function edit(Test $test, Question $question)
    {
        $this->authorize('update', $test->course);
        
        $question->load('options');
        
        return Inertia::render('Teacher/Courses/Tests/Questions/Edit', [
            'course' => $test->course,
            'test' => $test,
            'question' => $question,
            'questionTypes' => $this->getQuestionTypes(),
        ]);
    }
    
    public function update(UpdateQuestionRequest $request, Test $test, Question $question)
    {
        $this->authorize('update', $test->course);
        
        $question->update([
            'question_text' => $request->question_text,
            'points' => $request->points,
            'explanation' => $request->explanation,
        ]);
        
        // Update options
        $question->options()->delete();
        $this->createOptions($question, $request->options);
        
        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', 'Savol yangilandi');
    }
    
    public function destroy(Test $test, Question $question)
    {
        $this->authorize('update', $test->course);
        
        $question->options()->delete();
        $question->delete();
        
        // Reorder remaining questions
        $test->questions()
            ->where('sort_order', '>', $question->sort_order)
            ->decrement('sort_order');
        
        return back()->with('success', 'Savol o\'chirildi');
    }
    
    public function reorder(Request $request, Test $test)
    {
        $this->authorize('update', $test->course);
        
        $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.id' => ['required', 'uuid'],
            'questions.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);
        
        foreach ($request->questions as $item) {
            Question::where('id', $item['id'])
                ->where('test_id', $test->id)
                ->update(['sort_order' => $item['sort_order']]);
        }
        
        return back()->with('success', 'Tartib saqlandi');
    }
    
    private function createOptions(Question $question, array $options): void
    {
        foreach ($options as $option) {
            $question->options()->create([
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'] ?? false,
                'match_text' => $option['match_text'] ?? null,
                'correct_position' => $option['correct_position'] ?? null,
            ]);
        }
    }
    
    private function getQuestionTypes(): array
    {
        return [
            'single_choice' => 'Bitta to\'g\'ri javob',
            'multiple_choice' => 'Ko\'p to\'g\'ri javob',
            'true_false' => 'Rost/Yolg\'on',
            'fill_blank' => 'Bo\'sh joyni to\'ldirish',
            'matching' => 'Moslashtirish',
            'ordering' => 'Tartiblash',
        ];
    }
}
