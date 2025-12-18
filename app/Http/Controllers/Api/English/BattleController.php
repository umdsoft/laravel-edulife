<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishBattle;
use App\Models\English\EnglishBattleRound;
use App\Services\English\BattleService;
use App\Services\English\LevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BattleController extends Controller
{
    private const BATTLE_CREATE_COST = 30;

    public function __construct(
        private BattleService $battleService,
        private LevelService $levelService
    ) {
    }

    /**
     * Create a new battle
     * - AI/practice battles: FREE (no coins)
     * - Friend/Random battles: costs 30 coins
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'battle_type' => 'sometimes|string|in:ranked,casual,quick,friendly,practice,tournament',
            'rounds' => 'sometimes|integer|min:5|max:20',
            'time_per_question' => 'sometimes|integer|min:10000|max:30000',
            'opponent_type' => 'sometimes|string|in:ai,random,friend',
            'difficulty' => 'sometimes|string|in:easy,medium,hard,mixed',
            'level' => 'sometimes|string|in:mixed,A1,A2,B1,B2,C1,C2',
        ]);

        $user = $request->user();
        $profile = $this->levelService->getOrCreateProfile($user);
        $studentProfile = $user->studentProfile;

        $battleType = $validated['battle_type'] ?? 'casual';
        $opponentType = $validated['opponent_type'] ?? 'random';
        $selectedLevel = $validated['level'] ?? 'mixed';

        // Determine level_id based on selected level code
        $levelId = $profile->current_level_id; // Default to user's current level
        if ($selectedLevel !== 'mixed') {
            $level = \App\Models\English\EnglishLevel::where('code', $selectedLevel)->first();
            if ($level) {
                $levelId = $level->id;
            }
        }

        // AI/practice battles are FREE, others cost coins
        $isFreeBattle = $battleType === 'practice' || $opponentType === 'ai';
        $coinsSpent = 0;

        if (!$isFreeBattle) {
            // Check if user has enough coins (from main StudentProfile)
            $availableCoins = $studentProfile ? $studentProfile->coins : ($profile->coins ?? 0);
            if ($availableCoins < self::BATTLE_CREATE_COST) {
                return response()->json([
                    'success' => false,
                    'error' => 'insufficient_coins',
                    'message' => "Battle yaratish uchun " . self::BATTLE_CREATE_COST . " coin kerak. Sizda: {$availableCoins} coin",
                    'required' => self::BATTLE_CREATE_COST,
                    'available' => $availableCoins,
                ], 400);
            }

            // Deduct coins from both profiles
            if ($studentProfile) {
                $studentProfile->spendCoins(self::BATTLE_CREATE_COST);
            }
            $profile->coins = max(0, ($profile->coins ?? 0) - self::BATTLE_CREATE_COST);
            $profile->save();
            $coinsSpent = self::BATTLE_CREATE_COST;
        }

        // Generate unique battle code
        $code = $this->generateBattleCode();

        // Create battle
        $battle = EnglishBattle::create([
            'id' => Str::uuid(),
            'player1_id' => $user->id,
            'player1_elo_before' => $profile->elo_rating ?? 1000,
            'battle_type' => $battleType,
            'level_id' => $levelId,
            'status' => 'waiting',
            'code' => $code,
            'name' => $validated['name'] ?? null,
            'settings' => [
                'rounds' => $validated['rounds'] ?? 10,
                'time_per_question' => $validated['time_per_question'] ?? 15000,
                'opponent_type' => $opponentType,
                'difficulty' => $validated['difficulty'] ?? 'mixed',
                'level' => $selectedLevel, // Store selected level for reference
            ],
        ]);

        // Refresh to get updated coins
        $studentProfile?->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'battle' => $battle->fresh(['player1']),
                'code' => $code,
                'coins_spent' => $coinsSpent,
                'coins_remaining' => $studentProfile ? $studentProfile->coins : $profile->coins,
                'is_free' => $isFreeBattle,
            ],
        ]);
    }

    /**
     * Join a battle by code
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $code = strtoupper($validated['code']);

        // Find battle by code
        $battle = EnglishBattle::where('code', $code)
            ->where('status', 'waiting')
            ->first();

        if (!$battle) {
            return response()->json([
                'success' => false,
                'error' => 'battle_not_found',
                'message' => "Battle topilmadi yoki allaqachon boshlangan",
            ], 404);
        }

        // Check if trying to join own battle
        if ($battle->player1_id === $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'own_battle',
                'message' => "O'zingiz yaratgan battle'ga qo'shila olmaysiz",
            ], 400);
        }

        // Join battle
        $battle = $this->battleService->joinBattle($battle, $user);

        return response()->json([
            'success' => true,
            'data' => [
                'battle' => $battle->fresh(['player1', 'player2']),
                'message' => 'Battle\'ga muvaffaqiyatli qo\'shildingiz!',
            ],
        ]);
    }

    /**
     * Find match or create battle
     */
    public function findMatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'battle_type' => 'sometimes|string|in:ranked,casual,quick,friendly,practice,tournament',
        ]);

        $user = $request->user();
        $profile = $this->levelService->getOrCreateProfile($user);
        $battleType = $validated['battle_type'] ?? 'ranked';

        // For ranked matches, check if user has enough coins
        if ($battleType === 'ranked' && $profile->coins < self::BATTLE_CREATE_COST) {
            return response()->json([
                'success' => false,
                'error' => 'insufficient_coins',
                'message' => "Ranked battle uchun " . self::BATTLE_CREATE_COST . " coin kerak. Sizda: {$profile->coins} coin",
                'required' => self::BATTLE_CREATE_COST,
                'available' => $profile->coins,
            ], 400);
        }

        $battle = $this->battleService->findMatch($user, $battleType);

        // If new battle was created (waiting status), deduct coins for ranked
        if ($battle->status === 'waiting' && $battleType === 'ranked') {
            $profile->spendCoins(self::BATTLE_CREATE_COST);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'battle' => $battle->fresh(['player1', 'player2']),
                'waiting' => $battle->status === 'waiting',
                'message' => $battle->status === 'waiting'
                    ? 'Raqib kutilmoqda...'
                    : 'Raqib topildi! Battle boshlanmoqda...',
            ],
        ]);
    }

    /**
     * Generate unique 6-character battle code
     */
    private function generateBattleCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (EnglishBattle::where('code', $code)->where('status', 'waiting')->exists());

        return $code;
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

    /**
     * Complete battle and award rewards
     * - AI/practice battles: Only XP, no coins, no ELO change
     * - Real battles: XP + coins + ELO change
     * Also saves all results to database for history
     */
    public function complete(Request $request, string $battleId): JsonResponse
    {
        $validated = $request->validate([
            'player_score' => 'required|integer|min:0',
            'opponent_score' => 'required|integer|min:0',
            'player_correct' => 'sometimes|integer|min:0',
            'total_rounds' => 'sometimes|integer|min:1',
            'accuracy' => 'sometimes|numeric|min:0|max:100',
            'avg_time' => 'sometimes|numeric|min:0',
        ]);

        $user = $request->user();
        $profile = $this->levelService->getOrCreateProfile($user);
        $studentProfile = $user->studentProfile;

        // Get battle to check if it's AI/practice
        $battle = EnglishBattle::find($battleId);
        if (!$battle) {
            return response()->json([
                'success' => false,
                'message' => 'Battle topilmadi',
            ], 404);
        }

        $isAIBattle = $battle->battle_type === 'practice' ||
            ($battle->settings['opponent_type'] ?? null) === 'ai';

        $isPlayer1 = $battle->player1_id === $user->id;

        // Determine if player won
        $playerWon = $validated['player_score'] > $validated['opponent_score'];
        $isDraw = $validated['player_score'] === $validated['opponent_score'];

        // Calculate rewards based on battle type
        $xpReward = 0;
        $coinReward = 0;
        $eloChange = 0;
        $loserXp = 0;
        $loserCoins = 0;

        if ($isAIBattle) {
            // AI battles: Only XP, no coins, no ELO change
            if ($playerWon) {
                $xpReward = 30;
            } elseif ($isDraw) {
                $xpReward = 15;
            } else {
                $xpReward = 10;
            }
        } else {
            // Real battles: Full rewards
            if ($playerWon) {
                $xpReward = 50;
                $coinReward = 25;
                $loserXp = 15;
                $loserCoins = 5;
                $eloChange = rand(10, 25);
                $profile->battles_won = ($profile->battles_won ?? 0) + 1;
                $profile->win_streak = ($profile->win_streak ?? 0) + 1;
                $profile->best_win_streak = max($profile->best_win_streak ?? 0, $profile->win_streak);
            } elseif ($isDraw) {
                $xpReward = 25;
                $coinReward = 10;
                $loserXp = 25;
                $loserCoins = 10;
                $eloChange = 0;
            } else {
                $xpReward = 15;
                $coinReward = 5;
                $loserXp = 50;
                $loserCoins = 25;
                $eloChange = -rand(5, 15);
                $profile->win_streak = 0;
            }

            $profile->battles_played = ($profile->battles_played ?? 0) + 1;

            // Update ELO only for real battles
            $newElo = max(100, ($profile->elo_rating ?? 1000) + $eloChange);
            $profile->elo_rating = $newElo;
        }

        // Add XP to English profile
        if ($xpReward > 0) {
            $profile->total_xp = ($profile->total_xp ?? 0) + $xpReward;
            $profile->current_level_xp = ($profile->current_level_xp ?? 0) + $xpReward;
            $profile->today_xp_earned = ($profile->today_xp_earned ?? 0) + $xpReward;
        }

        // Add coins only for non-AI battles
        if ($coinReward > 0 && !$isAIBattle) {
            $profile->coins = ($profile->coins ?? 0) + $coinReward;
        }

        $profile->save();

        // Add rewards to main StudentProfile
        if ($studentProfile) {
            if ($xpReward > 0) {
                $studentProfile->addXp($xpReward);
            }
            if ($coinReward > 0 && !$isAIBattle) {
                $studentProfile->addCoins($coinReward);
            }
        }

        // ============ SAVE BATTLE RESULTS TO DATABASE ============
        $newElo = $profile->elo_rating ?? 1000;
        $oldElo = $battle->player1_elo_before;

        // Determine result type
        $result = $isDraw ? 'draw' : ($playerWon ? 'player1_win' : 'player2_win');
        if (!$isPlayer1) {
            $result = $isDraw ? 'draw' : ($playerWon ? 'player2_win' : 'player1_win');
        }

        // Update battle record with full results
        $battle->update([
            'status' => 'completed',
            'completed_at' => now(),
            // Scores
            'player1_score' => $isPlayer1 ? $validated['player_score'] : $validated['opponent_score'],
            'player2_score' => $isPlayer1 ? $validated['opponent_score'] : $validated['player_score'],
            'player1_correct' => $isPlayer1 ? ($validated['player_correct'] ?? 0) : 0,
            'player2_correct' => $isPlayer1 ? 0 : ($validated['player_correct'] ?? 0),
            // ELO
            'player1_elo_after' => $isPlayer1 ? $newElo : $battle->player1_elo_before,
            'player2_elo_after' => $isPlayer1 ? ($battle->player2_elo_before ?? 1000) : $newElo,
            'elo_change' => abs($eloChange),
            // Winner
            'winner_id' => $isDraw ? null : ($playerWon ? $user->id : ($isAIBattle ? null : $battle->player2_id)),
            'result' => $result,
            // Rewards
            'winner_xp' => $playerWon ? $xpReward : $loserXp,
            'loser_xp' => $playerWon ? $loserXp : $xpReward,
            'winner_coins' => $isAIBattle ? 0 : ($playerWon ? $coinReward : $loserCoins),
            'loser_coins' => $isAIBattle ? 0 : ($playerWon ? $loserCoins : $coinReward),
            // Time tracking
            'player1_avg_time' => $isPlayer1 ? ($validated['avg_time'] ?? 0) : 0,
            'player2_avg_time' => $isPlayer1 ? 0 : ($validated['avg_time'] ?? 0),
        ]);

        // Refresh to get updated values
        $studentProfile?->refresh();
        $profile->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'player_won' => $playerWon,
                'is_draw' => $isDraw,
                'is_ai_battle' => $isAIBattle,
                'rewards' => [
                    'xp' => $xpReward,
                    'coins' => $isAIBattle ? 0 : $coinReward,
                ],
                'elo_change' => $isAIBattle ? 0 : $eloChange,
                'new_elo' => $newElo,
                'battle_id' => $battle->id,
                'profile' => [
                    'coins' => $studentProfile ? $studentProfile->coins : $profile->coins,
                    'total_xp' => $profile->total_xp,
                    'elo_rating' => $profile->elo_rating,
                    'battles_played' => $profile->battles_played,
                    'battles_won' => $profile->battles_won,
                    'win_streak' => $profile->win_streak,
                ],
            ],
        ]);
    }
}
