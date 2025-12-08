<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishVocabulary;
use App\Models\English\UserVocabulary;
use App\Services\English\VocabularyService;
use App\Services\English\LevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VocabularyController extends Controller
{
    public function __construct(
        private VocabularyService $vocabularyService,
        private LevelService $levelService
    ) {
    }

    /**
     * Get vocabulary for a level
     */
    public function index(Request $request, string $levelId): JsonResponse
    {
        $filters = $request->only(['category', 'unit', 'difficulty']);
        $vocabulary = $this->vocabularyService->getVocabularyForLevel($levelId, $request->user(), $filters);

        return response()->json([
            'success' => true,
            'data' => $vocabulary,
        ]);
    }

    /**
     * Get words due for review (SM-2)
     */
    public function review(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $words = $this->vocabularyService->getWordsForReview($request->user(), $limit);

        return response()->json([
            'success' => true,
            'data' => $words,
        ]);
    }

    /**
     * Get new words to learn
     */
    public function newWords(Request $request): JsonResponse
    {
        $profile = $this->levelService->getOrCreateProfile($request->user());
        $limit = $request->input('limit', 10);

        $words = $this->vocabularyService->getNewWordsToLearn(
            $request->user(),
            $profile->current_level_id,
            $limit
        );

        return response()->json([
            'success' => true,
            'data' => $words,
        ]);
    }

    /**
     * Learn a word
     */
    public function learn(Request $request, string $vocabularyId): JsonResponse
    {
        $vocabulary = EnglishVocabulary::findOrFail($vocabularyId);
        $userVocab = $this->vocabularyService->learnWord($vocabulary, $request->user());

        return response()->json([
            'success' => true,
            'data' => $userVocab,
        ]);
    }

    /**
     * Submit review (SM-2)
     */
    public function submitReview(Request $request, string $userVocabularyId): JsonResponse
    {
        $validated = $request->validate([
            'quality' => 'required|integer|min:0|max:5',
        ]);

        $userVocab = UserVocabulary::where('id', $userVocabularyId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $result = $this->vocabularyService->processReview($userVocab, $validated['quality']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get vocabulary statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $levelId = $request->input('level_id');
        $stats = $this->vocabularyService->getVocabularyStats($request->user(), $levelId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Search vocabulary
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'level_id' => 'sometimes|uuid',
        ]);

        $results = $this->vocabularyService->searchVocabulary(
            $validated['query'],
            $request->user(),
            $validated['level_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
