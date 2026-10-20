<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Which questions a battle plays (docs/battle/SCORING.md):
 *  - questions of active tests in published / approved courses of the
 *    battle's direction, of the auto-gradable one-click types;
 *  - all of one difficulty level (from the smoothed solve rate p̂, which is
 *    the teacher's label until there is evidence);
 *  - drawn at random with a stored seed; the ordered ids are stored on the
 *    battle and the option order is derived from the seed, so both players
 *    and every reload see exactly the same thing;
 *  - questions either player saw in their recent battles come last.
 */
class BattleQuestionSelector
{
    /** One click, one right option: graded instantly and identically for both players. */
    public const TYPES = ['single_choice', 'true_false'];

    public const LEVEL_LABELS = ['easy' => 'Oson', 'medium' => "O'rta", 'hard' => 'Qiyin'];

    /** The level to try first, then the nearest ones. */
    private const FALLBACK = [
        'easy' => ['easy', 'medium', 'hard'],
        'medium' => ['medium', 'easy', 'hard'],
        'hard' => ['hard', 'medium', 'easy'],
    ];

    public function __construct(
        protected BattleScoringService $scoring,
        protected BattleScoringSettings $settings,
    ) {}

    public function pool(?string $directionId = null): Builder
    {
        return Question::query()
            ->whereIn('type', self::TYPES)
            ->whereHas('options', fn ($q) => $q->where('is_correct', true))
            ->whereHas('test', fn ($q) => $q->where('is_active', true))
            ->whereHas('course', fn ($c) => $c->whereIn('status', ['published', 'approved'])
                ->when($directionId, fn ($q) => $q->where('direction_id', $directionId)));
    }

    /**
     * Every eligible question with its p̂, level and value.
     *
     * @return array<string, array{p: float, level: string, value: int}>
     */
    public function classify(?string $directionId = null): array
    {
        return $this->scoring->estimates($this->pool($directionId)->get(['id', 'difficulty']));
    }

    /** @return array{easy: int, medium: int, hard: int} */
    public function levelCounts(?string $directionId = null): array
    {
        $counts = array_fill_keys(BattleScoringSettings::LEVELS, 0);
        foreach ($this->classify($directionId) as $estimate) {
            $counts[$estimate['level']]++;
        }

        return $counts;
    }

    public function levelForElo(float $elo): string
    {
        if ($elo >= $this->settings->eloHardFrom()) {
            return 'hard';
        }

        return $elo >= $this->settings->eloMediumFrom() ? 'medium' : 'easy';
    }

    /**
     * The preferred level when it has enough questions, else (unless strict)
     * the nearest level that does; null when none has.
     */
    public function resolveLevel(array $counts, string $preferred, int $needed, bool $strict = false): ?string
    {
        $order = $strict ? [$preferred] : (self::FALLBACK[$preferred] ?? self::FALLBACK['medium']);
        foreach ($order as $level) {
            if (($counts[$level] ?? 0) >= $needed) {
                return $level;
            }
        }

        return null;
    }

    /**
     * The questions of a new battle.
     *
     * @param  array<int, string>  $playerIds
     * @return array{level: string, seed: int, questions: array<int, array{id: string, p: float, value: int}>}
     *
     * @throws \DomainException
     */
    public function select(?string $directionId, ?string $requested, float $averageElo, array $playerIds, int $rounds, int $minRounds, ?int $seed = null): array
    {
        $estimates = $this->classify($directionId);
        $counts = array_fill_keys(BattleScoringSettings::LEVELS, 0);
        foreach ($estimates as $e) {
            $counts[$e['level']]++;
        }

        $strict = in_array($requested, BattleScoringSettings::LEVELS, true);
        $preferred = $strict ? $requested : $this->levelForElo($averageElo);
        $level = $this->resolveLevel($counts, $preferred, $minRounds, $strict);
        if (! $level) {
            throw new \DomainException(self::notEnoughMessage($strict ? $requested : null, $counts, $minRounds));
        }

        $candidates = array_keys(array_filter($estimates, fn ($e) => $e['level'] === $level));
        $seed ??= random_int(1, 2 ** 31 - 1);
        $ids = $this->pick($candidates, $this->recentlySeen($playerIds), max($minRounds, $rounds), $seed);

        return [
            'level' => $level,
            'seed' => $seed,
            'questions' => array_map(fn ($id) => ['id' => $id, 'p' => $estimates[$id]['p'], 'value' => $estimates[$id]['value']], $ids),
        ];
    }

    /**
     * A seeded random draw: questions the players have not seen recently come
     * first (shuffled), then the ones seen longest ago.
     *
     * @param  array<int, string>  $candidates
     * @param  array<string, int>  $lastSeen  question id => unix time it was last played by either player
     * @return array<int, string>
     */
    public function pick(array $candidates, array $lastSeen, int $count, int $seed): array
    {
        $candidates = array_values(array_unique($candidates));
        sort($candidates, SORT_STRING); // the draw depends on the seed only, not on database order
        $randomizer = new Randomizer(new Mt19937($seed));

        $fresh = array_values(array_filter($candidates, fn ($id) => ! isset($lastSeen[$id])));
        $seen = array_values(array_filter($candidates, fn ($id) => isset($lastSeen[$id])));

        $fresh = $fresh ? $randomizer->shuffleArray($fresh) : [];
        $seen = $seen ? $randomizer->shuffleArray($seen) : [];
        usort($seen, fn ($a, $b) => $lastSeen[$a] <=> $lastSeen[$b]); // stable: ties keep the shuffled order

        return array_slice([...$fresh, ...$seen], 0, $count);
    }

    /**
     * Questions played in either player's last N battles.
     *
     * @param  array<int, string>  $playerIds
     * @return array<string, int>  question id => unix time last played
     */
    public function recentlySeen(array $playerIds): array
    {
        $limit = $this->settings->recentBattles();
        if ($limit <= 0) {
            return [];
        }

        $battleIds = collect($playerIds)->filter()->unique()->flatMap(fn ($id) => Battle::query()
            ->where(fn ($q) => $q->where('player1_id', $id)->orWhere('player2_id', $id))
            ->whereIn('status', ['in_progress', 'completed'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('id'))->unique()->values();

        if ($battleIds->isEmpty()) {
            return [];
        }

        return BattleRound::whereIn('battle_id', $battleIds)
            ->get(['question_id', 'created_at'])
            ->groupBy('question_id')
            ->map(fn (Collection $rounds) => (int) $rounds->max(fn ($r) => $r->created_at?->getTimestamp() ?? 0))
            ->all();
    }

    /**
     * The options in the battle's own order: the same for both players and
     * every reload. True / false keeps "Rost, Yolg'on".
     */
    public function orderedOptions(Battle $battle, Question $question): Collection
    {
        $options = $question->relationLoaded('options') ? $question->options : $question->options()->get();
        if ($question->type === 'true_false') {
            return $options->values();
        }

        $seed = (string) ($battle->question_seed ?: crc32((string) $battle->id));

        return $options->sortBy(fn ($o) => hash('sha256', $seed.'|'.$o->id))->values();
    }

    /** The student-facing explanation when a battle cannot be put together. */
    public static function notEnoughMessage(?string $level, array $counts, int $needed): string
    {
        if ($level) {
            $label = self::LEVEL_LABELS[$level] ?? $level;

            return "\"{$label}\" darajasida jang uchun savollar hali yetarli emas: {$counts[$level]} ta bor, kamida {$needed} ta kerak. "
                ."Boshqa qiyinlikni yoki \"Avtomatik\"ni tanlang.";
        }

        return "Jang uchun savollar hali yetarli emas (bir xil qiyinlikdagi kamida {$needed} ta test savoli kerak). "
            ."Boshqa yo'nalishni tanlang yoki keyinroq urinib ko'ring.";
    }
}
