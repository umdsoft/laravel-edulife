<?php

namespace App\Services;

use App\Events\BattleMatchFound;
use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    const SEARCH_TIMEOUT = 60; // seconds
    const ELO_RANGE_INITIAL = 100;
    const ELO_RANGE_INCREMENT = 50;
    const ELO_RANGE_MAX = 500;

    const ROUNDS = 5;
    const MIN_ROUNDS = 3;
    /** Seconds between "match found" and the first question. */
    const COUNTDOWN = 3;

    public function __construct(
        protected RewardSettings $rewards,
        protected BattleQuestionSelector $selector,
    ) {}

    /**
     * Find an opponent or wait for one.
     *
     * @param  string|null  $difficulty  easy / medium / hard, null = automatic (by the players' ELO)
     *
     * @throws \DomainException with a student-facing (Uzbek) message when no battle can be played
     */
    public function startSearch(User $user, ?string $directionId = null, ?string $difficulty = null): Battle
    {
        $difficulty = in_array($difficulty, BattleScoringSettings::LEVELS, true) ? $difficulty : null;
        $this->expireStaleSearches();

        $activeBattle = Battle::where(fn ($q) => $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id))
            ->whereIn('status', ['waiting', 'in_progress'])
            ->first();

        if ($activeBattle) {
            return $activeBattle;
        }

        $this->ensureWithinDailyLimit($user);

        // every question of a battle has the same level: that level needs enough of them
        $counts = $this->selector->levelCounts($directionId);
        $enough = $difficulty
            ? $counts[$difficulty] >= self::MIN_ROUNDS
            : max($counts) >= self::MIN_ROUNDS;
        if (! $enough) {
            throw new \DomainException(BattleQuestionSelector::notEnoughMessage($difficulty, $counts, self::MIN_ROUNDS));
        }

        Cache::put("matchmaking_search_{$user->id}", now(), self::SEARCH_TIMEOUT * 2);

        return DB::transaction(function () use ($user, $directionId, $difficulty) {
            $match = $this->findMatch($user, $directionId, $difficulty);

            if ($match) {
                return $this->joinBattle($match, $user, $directionId, $difficulty);
            }

            return Battle::create([
                'player1_id' => $user->id,
                'direction_id' => $directionId,
                'requested_difficulty' => $difficulty,
                'status' => 'waiting',
                // "Bir o'yindagi savollar" / "Har bir savolga vaqt" from the admin settings
                'total_rounds' => $this->rewards->battleRounds(),
                'time_per_question' => $this->rewards->battleSecondsPerQuestion(),
                'expires_at' => now()->addSeconds(self::SEARCH_TIMEOUT),
            ]);
        });
    }

    /**
     * Single-choice / true-false questions of active tests in published courses
     * (each has a correct option). See BattleQuestionSelector.
     */
    public function questionPool(?string $directionId = null)
    {
        return $this->selector->pool($directionId);
    }

    /** @return array{easy: int, medium: int, hard: int} */
    public function levelCounts(?string $directionId = null): array
    {
        return $this->selector->levelCounts($directionId);
    }

    public function availableQuestions(?string $directionId = null): int
    {
        return $this->questionPool($directionId)->count();
    }

    /**
     * Waiting searches nobody joined in time.
     */
    public function expireStaleSearches(): void
    {
        Battle::where('status', 'waiting')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    private function findMatch(User $user, ?string $directionId, ?string $difficulty): ?Battle
    {
        $userElo = (int) ($user->studentProfile?->elo_rating ?? 1000);

        // Expand the ELO range the longer the student has been searching
        $searchStarted = Cache::get("matchmaking_search_{$user->id}", now());
        $elapsedSeconds = (int) abs(now()->diffInSeconds($searchStarted));
        $eloRange = min(
            self::ELO_RANGE_MAX,
            self::ELO_RANGE_INITIAL + (floor($elapsedSeconds / 10) * self::ELO_RANGE_INCREMENT)
        );

        return Battle::where('status', 'waiting')
            ->where('player1_id', '!=', $user->id)
            ->whereNull('player2_id')
            ->where('expires_at', '>', now())
            ->whereHas('player1.studentProfile', fn ($q) => $q->whereBetween('elo_rating', [$userElo - $eloRange, $userElo + $eloRange]))
            ->when($directionId, fn ($q) => $q->where(fn ($w) => $w->where('direction_id', $directionId)->orWhereNull('direction_id')))
            // a chosen level only meets the same level or "automatic"
            ->when($difficulty, fn ($q) => $q->where(fn ($w) => $w->where('requested_difficulty', $difficulty)->orWhereNull('requested_difficulty')))
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();
    }

    private function joinBattle(Battle $battle, User $player2, ?string $directionId = null, ?string $difficulty = null): Battle
    {
        // "any topic" + a chosen topic plays the chosen one; the same for the level
        $directionId = $battle->direction_id ?: $directionId;
        $requested = $battle->requested_difficulty ?: $difficulty;
        $averageElo = (
            (int) ($battle->player1?->studentProfile?->elo_rating ?? 1000)
            + (int) ($player2->studentProfile?->elo_rating ?? 1000)
        ) / 2;

        $selection = $this->selector->select(
            $directionId,
            $requested,
            $averageElo,
            [$battle->player1_id, $player2->id],
            max(self::MIN_ROUNDS, (int) ($battle->total_rounds ?: self::ROUNDS)),
            self::MIN_ROUNDS,
        );
        $questions = $selection['questions'];
        $startsAt = now()->addSeconds(self::COUNTDOWN);

        $battle->update([
            'player2_id' => $player2->id,
            'direction_id' => $directionId,
            'requested_difficulty' => $requested,
            'difficulty' => $selection['level'],
            'question_seed' => $selection['seed'],
            'question_ids' => array_column($questions, 'id'),
            'status' => 'in_progress',
            'started_at' => $startsAt,
            'current_round' => 1,
            'total_rounds' => count($questions),
        ]);

        foreach (array_values($questions) as $index => $question) {
            BattleRound::create([
                'battle_id' => $battle->id,
                'question_id' => $question['id'],
                'round_number' => $index + 1,
                // fixed when the battle starts: both players play for the same value
                'question_value' => $question['value'],
                'p_estimate' => round($question['p'], 4),
                // the first question opens after the countdown; later ones when the previous round ends
                'started_at' => $index === 0 ? $startsAt : null,
            ]);
        }

        Cache::forget("matchmaking_search_{$player2->id}");
        Cache::forget("matchmaking_search_{$battle->player1_id}");

        // Realtime is optional (no websocket server in some environments): the match page polls
        rescue(fn () => broadcast(new BattleMatchFound($battle))->toOthers(), report: false);

        return $battle->fresh(['player1', 'player2', 'rounds']);
    }

    /**
     * The admin's "Kunlik limit" for the student's plan (0 = no limit): the
     * battles started today (Tashkent) count, searches nobody joined do not.
     *
     * @throws \DomainException
     */
    private function ensureWithinDailyLimit(User $user): void
    {
        $limit = $this->rewards->battleDailyLimit($user);
        if ($limit <= 0) {
            return;
        }

        $playedToday = Battle::where(fn ($q) => $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id))
            ->whereIn('status', ['in_progress', 'completed'])
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($playedToday >= $limit) {
            throw new \DomainException("Bugungi jang limiti tugadi ({$limit} ta). Ertaga yana o'ynashingiz mumkin.");
        }
    }

    public function cancelSearch(User $user): bool
    {
        $battle = Battle::where('player1_id', $user->id)
            ->where('status', 'waiting')
            ->first();

        Cache::forget("matchmaking_search_{$user->id}");

        if ($battle) {
            $battle->update(['status' => 'cancelled']);

            return true;
        }

        return false;
    }
}
