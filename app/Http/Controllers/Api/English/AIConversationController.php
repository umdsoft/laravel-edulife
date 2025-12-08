<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishAIConversation;
use App\Services\English\AIConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIConversationController extends Controller
{
    public function __construct(
        private AIConversationService $aiService
    ) {
    }

    /**
     * Start new conversation
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:255',
            'scenario' => 'sometimes|string|in:general,restaurant,shopping,travel,job_interview',
            'difficulty' => 'sometimes|string|in:beginner,intermediate,advanced',
        ]);

        $conversation = $this->aiService->startConversation(
            $request->user(),
            $validated['topic'],
            $validated['scenario'] ?? 'general',
            $validated['difficulty'] ?? 'intermediate'
        );

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ]);
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request, string $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $conversation = EnglishAIConversation::where('id', $conversationId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $result = $this->aiService->sendMessage($conversation, $validated['message']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * End conversation
     */
    public function end(Request $request, string $conversationId): JsonResponse
    {
        $conversation = EnglishAIConversation::where('id', $conversationId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $result = $this->aiService->endConversation($conversation);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get conversation history
     */
    public function history(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $conversations = $this->aiService->getConversationHistory($request->user(), $limit);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * Get conversation details
     */
    public function show(Request $request, string $conversationId): JsonResponse
    {
        $conversation = EnglishAIConversation::with('messages')
            ->where('id', $conversationId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ]);
    }
}
