<?php

namespace App\Services\English;

use App\Models\English\EnglishBattle;
use App\Models\English\EnglishBattleRound;
use App\Models\English\EnglishBattleQuestion;
use App\Models\User;
use App\Events\English\BattleStarted;
use App\Events\English\BattleRoundStarted;
use App\Events\English\BattleAnswerSubmitted;
use App\Events\English\BattleCompleted;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BattleService
{
    private const K_FACTOR = 32;
    private const ROUNDS_PER_BATTLE = 10;
    private const TIME_PER_QUESTION = 15000;

    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Find or create a battle match
     */
    public function findMatch(User $user, string $battleType = 'ranked'): EnglishBattle
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $eloRange = 200;
        $existingBattle = EnglishBattle::where('status', 'waiting')
            ->where('battle_type', $battleType)
            ->where('player1_id', '!=', $user->id)
            ->whereBetween('player1_elo_before', [
                $profile->elo_rating - $eloRange,
                $profile->elo_rating + $eloRange
            ])
            ->oldest()
            ->first();

        if ($existingBattle) {
            return $this->joinBattle($existingBattle, $user);
        }

        return $this->createBattle($user, $battleType);
    }

    /**
     * Create new battle
     */
    public function createBattle(User $user, string $battleType): EnglishBattle
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        return EnglishBattle::create([
            'id' => Str::uuid(),
            'player1_id' => $user->id,
            'player1_elo_before' => $profile->elo_rating,
            'battle_type' => $battleType,
            'level_id' => $profile->current_level_id,
            'status' => 'waiting',
            'settings' => [
                'rounds' => self::ROUNDS_PER_BATTLE,
                'time_per_question' => self::TIME_PER_QUESTION,
            ],
        ]);
    }

    /**
     * Join existing battle
     */
    public function joinBattle(EnglishBattle $battle, User $user): EnglishBattle
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $battle->update([
            'player2_id' => $user->id,
            'player2_elo_before' => $profile->elo_rating,
            'status' => 'ready',
            'matched_at' => now(),
        ]);

        broadcast(new BattleStarted($battle))->toOthers();

        return $battle;
    }

    /**
     * Start battle
     */
    public function startBattle(EnglishBattle $battle): EnglishBattle
    {
        $questions = $this->generateBattleQuestions($battle);

        foreach ($questions as $index => $question) {
            EnglishBattleRound::create([
                'id' => Str::uuid(),
                'battle_id' => $battle->id,
                'round_number' => $index + 1,
                'question_id' => $question->id,
                'question_data' => [
                    'id' => $question->id,
                    'question' => $question->question,
                    'question_type' => $question->question_type,
                    'options' => $question->options,
                    'difficulty' => $question->difficulty,
                ],
                'status' => $index === 0 ? 'active' : 'pending',
            ]);
        }

        $battle->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $firstRound = $battle->rounds()->where('round_number', 1)->first();
        $firstRound->update(['started_at' => now()]);

        broadcast(new BattleRoundStarted($battle, $firstRound))->toOthers();

        return $battle->fresh(['rounds']);
    }

    /**
     * Submit answer for a round
     */
    public function submitAnswer(
        EnglishBattle $battle,
        EnglishBattleRound $round,
        User $user,
        string $answer,
        int $timeMs
    ): array {
        $isPlayer1 = $battle->player1_id === $user->id;
        $playerPrefix = $isPlayer1 ? 'player1' : 'player2';

        if ($round->{$playerPrefix . '_answer'} !== null) {
            return ['error' => 'Already answered'];
        }

        $question = EnglishBattleQuestion::find($round->question_id);
        $isCorrect = strtolower(trim($answer)) === strtolower(trim($question->correct_answer));

        $points = 0;
        if ($isCorrect) {
            $points = $question->base_points ?? 10;
            $timeBonus = max(0, ($question->time_bonus_max ?? 5) * (1 - ($timeMs / self::TIME_PER_QUESTION)));
            $points += (int) round($timeBonus);
        }

        $round->update([
            $playerPrefix . '_answer' => $answer,
            $playerPrefix . '_correct' => $isCorrect,
            $playerPrefix . '_time_ms' => $timeMs,
            $playerPrefix . '_points' => $points,
            $playerPrefix . '_answered_at' => now(),
        ]);

        $question->updateStatistics($isCorrect, $timeMs);

        broadcast(new BattleAnswerSubmitted($battle, $round, $user->id, $isCorrect, $points))->toOthers();

        $round->refresh();
        if ($round->player1_answer !== null && $round->player2_answer !== null) {
            return $this->completeRound($battle, $round);
        }

        return [
            'is_correct' => $isCorrect,
            'points' => $points,
            'waiting_for_opponent' => true,
        ];
    }

    /**
     * Complete a round and move to next
     */
    private function completeRound(EnglishBattle $battle, EnglishBattleRound $round): array
    {
        $roundWinnerId = null;
        if ($round->player1_points > $round->player2_points) {
            $roundWinnerId = $battle->player1_id;
        } elseif ($round->player2_points > $round->player1_points) {
            $roundWinnerId = $battle->player2_id;
        }

        $round->update([
            'round_winner_id' => $roundWinnerId,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $battle->player1_score += $round->player1_points;
        $battle->player2_score += $round->player2_points;
        $battle->player1_correct += $round->player1_correct ? 1 : 0;
        $battle->player2_correct += $round->player2_correct ? 1 : 0;
        $battle->player1_total_time_ms += $round->player1_time_ms;
        $battle->player2_total_time_ms += $round->player2_time_ms;
        $battle->save();

        $completedRounds = $battle->rounds()->where('status', 'completed')->count();
        if ($completedRounds >= self::ROUNDS_PER_BATTLE) {
            return $this->completeBattle($battle);
        }

        $nextRound = $battle->rounds()->where('status', 'pending')->orderBy('round_number')->first();
        if ($nextRound) {
            $nextRound->update(['status' => 'active', 'started_at' => now()]);
            broadcast(new BattleRoundStarted($battle, $nextRound))->toOthers();
        }

        return [
            'round_completed' => true,
            'round_winner_id' => $roundWinnerId,
            'current_scores' => ['player1' => $battle->player1_score, 'player2' => $battle->player2_score],
            'next_round' => $nextRound?->round_number,
        ];
    }

    /**
     * Complete battle and calculate ELO
     */
    public function completeBattle(EnglishBattle $battle): array
    {
        $battle->refresh();

        $winnerId = null;
        $result = 'draw';

        if ($battle->player1_score > $battle->player2_score) {
            $winnerId = $battle->player1_id;
            $result = 'player1_win';
        } elseif ($battle->player2_score > $battle->player1_score) {
            $winnerId = $battle->player2_id;
            $result = 'player2_win';
        }

        $eloChange = $this->calculateEloChange($battle->player1_elo_before, $battle->player2_elo_before, $result);

        $roundsCount = self::ROUNDS_PER_BATTLE;
        $battle->player1_avg_time = round($battle->player1_total_time_ms / $roundsCount, 2);
        $battle->player2_avg_time = round($battle->player2_total_time_ms / $roundsCount, 2);

        $battle->winner_id = $winnerId;
        $battle->result = $result;
        $battle->elo_change = abs($eloChange);
        $battle->player1_elo_after = $battle->player1_elo_before + $eloChange;
        $battle->player2_elo_after = $battle->player2_elo_before - $eloChange;
        $battle->status = 'completed';
        $battle->completed_at = now();

        $rewards = $this->calculateBattleRewards($battle, $result);
        $battle->winner_xp = $rewards['winner_xp'];
        $battle->loser_xp = $rewards['loser_xp'];
        $battle->winner_coins = $rewards['winner_coins'];
        $battle->loser_coins = $rewards['loser_coins'];

        $battle->save();

        $this->updatePlayerProfiles($battle, $eloChange, $rewards);

        broadcast(new BattleCompleted($battle))->toOthers();

        return [
            'battle_completed' => true,
            'winner_id' => $winnerId,
            'result' => $result,
            'final_scores' => ['player1' => $battle->player1_score, 'player2' => $battle->player2_score],
            'elo_changes' => ['player1' => $eloChange, 'player2' => -$eloChange],
            'new_elo' => ['player1' => $battle->player1_elo_after, 'player2' => $battle->player2_elo_after],
            'rewards' => $rewards,
        ];
    }

    /**
     * Calculate ELO change
     */
    private function calculateEloChange(int $elo1, int $elo2, string $result): int
    {
        $expected1 = 1 / (1 + pow(10, ($elo2 - $elo1) / 400));

        $actual1 = match ($result) {
            'player1_win' => 1, 'player2_win' => 0, 'draw' => 0.5, default => 0.5,
        };

        return (int) round(self::K_FACTOR * ($actual1 - $expected1));
    }

    private function calculateBattleRewards(EnglishBattle $battle, string $result): array
    {
        $multiplier = $battle->battle_type === 'ranked' ? 1.5 : 1.0;

        return [
            'winner_xp' => (int) round(50 * $multiplier),
            'loser_xp' => (int) round(15 * $multiplier),
            'winner_coins' => (int) round(25 * $multiplier),
            'loser_coins' => (int) round(5 * $multiplier),
        ];
    }

    private function updatePlayerProfiles(EnglishBattle $battle, int $eloChange, array $rewards): void
    {
        $profile1 = $this->levelService->getOrCreateProfile(User::find($battle->player1_id));
        $profile2 = $this->levelService->getOrCreateProfile(User::find($battle->player2_id));

        $profile1->elo_rating = max(100, $battle->player1_elo_after);
        $profile2->elo_rating = max(100, $battle->player2_elo_after);

        $profile1->battles_played++;
        $profile2->battles_played++;

        if ($battle->winner_id === $battle->player1_id) {
            $profile1->battles_won++;
            $profile1->battle_win_streak++;
            $profile1->addXp($rewards['winner_xp']);
            $profile1->addCoins($rewards['winner_coins']);

            $profile2->battle_win_streak = 0;
            $profile2->addXp($rewards['loser_xp']);
            $profile2->addCoins($rewards['loser_coins']);
        } elseif ($battle->winner_id === $battle->player2_id) {
            $profile2->battles_won++;
            $profile2->battle_win_streak++;
            $profile2->addXp($rewards['winner_xp']);
            $profile2->addCoins($rewards['winner_coins']);

            $profile1->battle_win_streak = 0;
            $profile1->addXp($rewards['loser_xp']);
            $profile1->addCoins($rewards['loser_coins']);
        }

        $profile1->best_battle_win_streak = max($profile1->best_battle_win_streak ?? 0, $profile1->battle_win_streak);
        $profile2->best_battle_win_streak = max($profile2->best_battle_win_streak ?? 0, $profile2->battle_win_streak);

        $profile1->save();
        $profile2->save();
    }

    private function generateBattleQuestions(EnglishBattle $battle): Collection
    {
        return EnglishBattleQuestion::where('level_id', $battle->level_id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(self::ROUNDS_PER_BATTLE)
            ->get();
    }

    public function cancelBattle(EnglishBattle $battle, User $user): bool
    {
        if (!in_array($battle->status, ['waiting', 'ready'])) {
            return false;
        }

        $battle->update([
            'status' => 'cancelled',
            'result' => $user->id === $battle->player1_id ? 'player1_forfeit' : 'player2_forfeit',
        ]);

        return true;
    }

    public function getBattleHistory(User $user, int $limit = 20): Collection
    {
        return EnglishBattle::with(['player1', 'player2', 'winner'])
            ->forUser($user->id)
            ->completed()
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get();
    }

    public function getActiveBattle(User $user): ?EnglishBattle
    {
        return EnglishBattle::forUser($user->id)->active()->first();
    }
}
