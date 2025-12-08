<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishBattle;
use App\Models\English\EnglishBattleRound;
use App\Services\English\BattleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BattleController extends Controller
{
    public function __construct(
        private BattleService $battleService
    ) {
    }

    /**
     * Find match or create battle
     */
    public function findMatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'battle_type' => 'sometimes|string|in:ranked,casual,practice',
        ]);

        $battle = $this->battleService->findMatch(
            $request->user(),
            $validated['battle_type'] ?? 'ranked'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'battle' => $battle,
                'waiting' => $battle->status === 'waiting',
            ],
        ]);
    }

    /**
     * Start battle (when both players ready)
     */
    public function start(Request $request, string $battleId): JsonResponse
    {
        $battle = EnglishBattle::where('id', $battleId)
            ->where(function ($q) use ($request) {
                $q->where('player1_id', $request->user()->id)
                    ->orWhere('player2_id', $request->user()->id);
            })
            ->where('status', 'ready')
            ->firstOrFail();

        $battle = $this->battleService->startBattle($battle);

        return response()->json([
            'success' => true,
            'data' => $battle,
        ]);
    }

    /**
     * Submit answer for a round
     */
    public function submitAnswer(Request $request, string $battleId, string $roundId): JsonResponse
    {
        $validated = $request->validate([
            'answer' => 'required|string',
            'time_ms' => 'required|integer|min:0|max:60000',
        ]);

        $battle = EnglishBattle::where('id', $battleId)
            ->where(function ($q) use ($request) {
                $q->where('player1_id', $request->user()->id)
                    ->orWhere('player2_id', $request->user()->id);
            })
            ->where('status', 'in_progress')
            ->firstOrFail();

        $round = EnglishBattleRound::where('id', $roundId)
            ->where('battle_id', $battleId)
            ->where('status', 'active')
            ->firstOrFail();

        $result = $this->battleService->submitAnswer(
            $battle,
            $round,
            $request->user(),
            $validated['answer'],
            $validated['time_ms']
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Cancel battle
     */
    public function cancel(Request $request, string $battleId): JsonResponse
    {
        $battle = EnglishBattle::where('id', $battleId)
            ->where(function ($q) use ($request) {
                $q->where('player1_id', $request->user()->id)
                    ->orWhere('player2_id', $request->user()->id);
            })
            ->firstOrFail();

        $cancelled = $this->battleService->cancelBattle($battle, $request->user());

        return response()->json([
            'success' => $cancelled,
            'message' => $cancelled ? 'Battle cancelled' : 'Cannot cancel battle',
        ]);
    }

    /**
     * Get battle history
     */
    public function history(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $battles = $this->battleService->getBattleHistory($request->user(), $limit);

        return response()->json([
            'success' => true,
            'data' => $battles,
        ]);
    }

    /**
     * Get active battle
     */
    public function active(Request $request): JsonResponse
    {
        $battle = $this->battleService->getActiveBattle($request->user());

        return response()->json([
            'success' => true,
            'data' => $battle,
        ]);
    }

    /**
     * Get battle details
     */
    public function show(Request $request, string $battleId): JsonResponse
    {
        $battle = EnglishBattle::with(['rounds', 'player1', 'player2', 'winner'])
            ->where('id', $battleId)
            ->where(function ($q) use ($request) {
                $q->where('player1_id', $request->user()->id)
                    ->orWhere('player2_id', $request->user()->id);
            })
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $battle,
        ]);
    }
}
