<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::with(['quiz.course']); // Assuming relation to quiz and course

        if ($search = $request->get('search')) {
            $query->where('question_text', 'like', "%{$search}%");
        }

        $questions = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Question $question)
    {
        $question->load(['quiz.course', 'answers']); // Assuming answers relation

        return Inertia::render('Admin/Questions/Show', [
            'question' => $question,
        ]);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return back()->with('success', 'Savol o\'chirildi');
    }
}
