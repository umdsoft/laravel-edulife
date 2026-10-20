<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Question;
use App\Http\Requests\Teacher\StoreQuestionRequest;
use App\Http\Requests\Teacher\UpdateQuestionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // questions.course_id is NOT NULL
            'course_id' => $test->course_id,
            'type' => $request->type,
            'difficulty' => $request->difficulty, // required by StoreQuestionRequest
            'question_text' => $request->question_text,
            'points' => $request->points ?? 1,
            'explanation' => $request->explanation,
            'sort_order' => $maxOrder + 1,
        ]);
        
        $this->createOptions($question, $request->options);

        // "Saqlash va yana qo'shish": straight to an empty form of the same type and level
        if ($request->input('after') === 'create') {
            return redirect()->route('teacher.tests.questions.create', [
                'test' => $test, 'type' => $question->type, 'difficulty' => $question->difficulty,
            ])->with('success', 'Savol qo\'shildi. Keyingisini yozing.');
        }

        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', 'Savol qo\'shildi');
    }
    
    public function edit(Question $question)
    {
        $test = $question->test;
        $this->authorize('update', $test->course);
        
        $question->load('options');
        
        return Inertia::render('Teacher/Courses/Tests/Questions/Edit', [
            'course' => $test->course,
            'test' => $test,
            'question' => $question,
            'questionTypes' => $this->getQuestionTypes(),
        ]);
    }
    
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $test = $question->test;
        $this->authorize('update', $test->course);
        
        $question->update([
            'question_text' => $request->question_text,
            'points' => $request->points,
            'difficulty' => $request->input('difficulty', $question->difficulty),
            'explanation' => $request->explanation,
        ]);
        
        $this->syncOptions($question, $request->options);

        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', 'Savol yangilandi');
    }
    
    /**
     * Copy a question (with its options) right after the original — the quick way
     * to write a series of similar problems ("same, other numbers").
     */
    public function duplicate(Question $question)
    {
        $test = $question->test;
        $this->authorize('update', $test->course);

        $copy = DB::transaction(function () use ($question, $test) {
            $test->questions()->where('sort_order', '>', $question->sort_order)->increment('sort_order');

            $copy = $question->replicate(['correct_answer', 'matching_pairs', 'ordering_items', 'accepted_answers']);
            $copy->sort_order = $question->sort_order + 1;
            $copy->save();

            foreach ($question->options as $option) {
                $copy->options()->create($option->only(['option_text', 'option_image', 'is_correct', 'match_text', 'correct_position', 'sort_order']));
            }
            $copy->syncAnswerKey();

            return $copy;
        });

        return redirect()->route('teacher.questions.edit', $copy)
            ->with('success', "Savol nusxalandi — o'zgartirib saqlang.");
    }

    public function destroy(Question $question)
    {
        $test = $question->test;
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
    
    /**
     * Update the option rows in place (matched by id), add the new ones and
     * delete the removed ones. Recreating every row gave the options new ids, so
     * answers already saved by students (single / multiple choice store option
     * ids; matching keys derive from them) no longer matched: an attempt in
     * progress was graded wrong and past reviews lost the chosen answer.
     */
    private function syncOptions(Question $question, array $options): void
    {
        $existing = $question->options()->get()->keyBy('id');
        $keep = [];

        foreach (array_values($options) as $index => $option) {
            $attributes = [
                'option_text' => $option['option_text'],
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'match_text' => $option['match_text'] ?? null,
                'correct_position' => $option['correct_position'] ?? ($question->type === 'ordering' ? $index + 1 : null),
                'sort_order' => $index,
            ];

            $row = isset($option['id']) ? $existing->get($option['id']) : null;
            if ($row) {
                $row->update($attributes);
                $keep[] = $row->id;
            } else {
                $keep[] = $question->options()->create($attributes)->id;
            }
        }

        $question->options()->whereNotIn('id', $keep)->delete();
        $question->syncAnswerKey();
    }

    private function createOptions(Question $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            $question->options()->create([
                'option_text' => $option['option_text'],
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'match_text' => $option['match_text'] ?? null,
                'correct_position' => $option['correct_position'] ?? ($question->type === 'ordering' ? $index + 1 : null),
                'sort_order' => $index,
            ]);
        }

        // grading reads correct_answer / matching_pairs / ordering_items
        $question->syncAnswerKey();
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
