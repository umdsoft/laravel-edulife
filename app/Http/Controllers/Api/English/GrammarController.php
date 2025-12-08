<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishGrammarExercise;
use App\Services\English\GrammarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrammarController extends Controller
{
    public function __construct(
        private GrammarService $grammarService
    ) {
    }

    /**
     * Get grammar rules for a level
     */
    public function index(Request $request, string $levelId): JsonResponse
    {
        $rules = $this->grammarService->getGrammarRulesForLevel($levelId, $request->user());

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Get single grammar rule with exercises
     */
    public function show(Request $request, string $ruleId): JsonResponse
    {
        $rule = $this->grammarService->getGrammarRuleWithExercises($ruleId, $request->user());

        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Grammar rule not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rule,
        ]);
    }

    /**
     * Get exercises for practice
     */
    public function exercises(Request $request, string $ruleId): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $exercises = $this->grammarService->getExercisesForPractice($ruleId, $request->user(), $limit);

        return response()->json([
            'success' => true,
            'data' => $exercises,
        ]);
    }

    /**
     * Submit exercise answer
     */
    public function submitAnswer(Request $request, string $exerciseId): JsonResponse
    {
        $validated = $request->validate([
            'answer' => 'required|string',
        ]);

        $exercise = EnglishGrammarExercise::findOrFail($exerciseId);
        $result = $this->grammarService->submitExerciseAnswer($exercise, $request->user(), $validated['answer']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
