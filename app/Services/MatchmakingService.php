<?php

namespace App\Services;

use App\Events\BattleMatchFound;
use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;
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

    /**
     * Find an opponent or wait for one.
     *
     * @throws \DomainException with a student-facing (Uzbek) message when no battle can be played
     */
    public function startSearch(User $user, ?string $directionId = null): Battle
    {
        $this->expireStaleSearches();

        $activeBattle = Battle::where(fn ($q) => $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id))
            ->whereIn('status', ['waiting', 'in_progress'])
            ->first();

        if ($activeBattle) {
            return $activeBattle;
        }

        if ($this->availableQuestions($directionId) < self::MIN_ROUNDS) {
            throw new \DomainException("Jang uchun savollar hali yetarli emas (kamida ".self::MIN_ROUNDS." ta test savoli kerak). Boshqa yo'nalishni tanlang yoki keyinroq urinib ko'ring.");
        }

        Cache::put("matchmaking_search_{$user->id}", now(), self::SEARCH_TIMEOUT * 2);

        return DB::transaction(function () use ($user, $directionId) {
            $match = $this->findMatch($user, $directionId);

            if ($match) {
                return $this->joinBattle($match, $user);
            }

            return Battle::create([
                'player1_id' => $user->id,
                'direction_id' => $directionId,
                'status' => 'waiting',
                'total_rounds' => self::ROUNDS,
                'expires_at' => now()->addSeconds(self::SEARCH_TIMEOUT),
            ]);
        });
    }

    /**
     * Single-choice / true-false questions of active course tests (each has a correct option).
     */
    public function questionPool(?string $directionId = null)
    {
        return Question::query()
            ->whereIn('type', ['single_choice', 'true_false'])
            ->whereHas('options', fn ($q) => $q->where('is_correct', true))
            ->whereHas('test', fn ($q) => $q->where('is_active', true))
            ->when($directionId, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('direction_id', $directionId)));
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

    private function findMatch(User $user, ?string $directionId): ?Battle
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
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();
    }

    private function joinBattle(Battle $battle, User $player2): Battle
    {
        $questions = $this->selectQuestions($battle);
        $startsAt = now()->addSeconds(self::COUNTDOWN);

        $battle->update([
            'player2_id' => $player2->id,
            'status' => 'in_progress',
            'started_at' => $startsAt,
            'current_round' => 1,
            'total_rounds' => $questions->count(),
        ]);

        foreach ($questions->values() as $index => $question) {
            BattleRound::create([
                'battle_id' => $battle->id,
                'question_id' => $question->id,
                'round_number' => $index + 1,
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

    private function selectQuestions(Battle $battle): Collection
    {
        $limit = max(self::MIN_ROUNDS, (int) ($battle->total_rounds ?: self::ROUNDS));

        return $this->questionPool($battle->direction_id)->inRandomOrder()->limit($limit)->get();
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
