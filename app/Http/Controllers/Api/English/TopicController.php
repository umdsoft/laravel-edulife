<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Services\English\TopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct(
        private TopicService $topicService
    ) {
    }

    /**
     * Get topics for a level
     */
    public function index(Request $request, string $levelId): JsonResponse
    {
        $topics = $this->topicService->getTopicsForLevel($levelId, $request->user());

        return response()->json([
            'success' => true,
            'data' => $topics,
        ]);
    }
}
