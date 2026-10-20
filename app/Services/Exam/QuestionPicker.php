<?php

namespace App\Services\Exam;

use App\Enums\Exam\BlueprintPart;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Support\Difficulty;
use Illuminate\Database\Eloquent\Builder;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Taking questions out of the exam bank by difficulty (docs/exam/03 §2.2) —
 * one place for every "Bankdan qo'shish" flow (course tests, olympiads) and
 * the counts the blueprint editor shows.
 *
 * Filters ($filters):
 *   subject_id   exam_subjects.id
 *   topic_id     a topic; its whole subtree is included
 *   types        list of question_type values
 *   part         'closed' | 'open' (BlueprintPart) — the exam's split of types
 *   eligible     true: only what an exam variant may contain (approved + subject + topic + level + gradable type)
 *   approved     default true; false lets draft / in-review questions in (never used for students)
 *   exclude_ids  question ids that must not be picked (already in the test / olympiad)
 *   where        extra constraint: fn (Builder $q) => ...
 *
 * Distribution: {3: 5}, {1: 2, 3: 5, 5: 1}, {easy: 5, medium: 10, hard: 5} or {any: 10}.
 * The draw is seeded (same filters + bank + seed = same questions); for a
 * given student the questions of their recent exam variants come last.
 */
class QuestionPicker
{
    /** How far back "recently seen" looks for a student, in weeks. */
    public const AVOID_WEEKS = 8;

    public const ANY = 'any';

    public function query(array $filters = []): Builder
    {
        $query = QuestionBank::query();

        if (! empty($filters['eligible'])) {
            $query->examEligible();
        } elseif ($filters['approved'] ?? true) {
            $query->where('review_status', ReviewStatus::Approved->value);
        }
        if (! empty($filters['subject_id'])) {
            $query->forSubject($filters['subject_id']);
        }
        if (array_key_exists('topic_id', $filters) && $filters['topic_id'] !== null && $filters['topic_id'] !== '') {
            $topic = $filters['topic_id'] instanceof Topic ? $filters['topic_id'] : Topic::query()->find($filters['topic_id']);
            $topic ? $query->inTopicTree($topic) : $query->whereRaw('1 = 0');
        }
        if (! empty($filters['types'])) {
            $query->whereIn('question_type', array_values((array) $filters['types']));
        }
        if (! empty($filters['part'])) {
            $part = $filters['part'] instanceof BlueprintPart ? $filters['part'] : BlueprintPart::from($filters['part']);
            $query->forPart($part);
        }
        if (! empty($filters['exclude_ids'])) {
            $query->whereNotIn('id', array_values((array) $filters['exclude_ids']));
        }
        if (isset($filters['where']) && is_callable($filters['where'])) {
            ($filters['where'])($query);
        }

        return $query;
    }

    /** @return array<int, int> level 1..5 => questions available (questions without a level are not counted) */
    public function levelCounts(array $filters = []): array
    {
        $counts = array_fill_keys(Difficulty::LEVELS, 0);
        $rows = $this->query($filters)->reorder()->whereNotNull('difficulty_level')
            ->selectRaw('difficulty_level, COUNT(*) AS c')->groupBy('difficulty_level')->pluck('c', 'difficulty_level');
        foreach ($rows as $level => $c) {
            if (isset($counts[(int) $level])) {
                $counts[(int) $level] = (int) $c;
            }
        }

        return $counts;
    }

    /** @return array{easy: int, medium: int, hard: int} */
    public function bandCounts(array $filters = [], ?array $levelCounts = null): array
    {
        $levelCounts ??= $this->levelCounts($filters);
        $bands = [];
        foreach (Difficulty::BANDS as $band) {
            $bands[$band] = array_sum(array_map(fn ($l) => $levelCounts[$l] ?? 0, Difficulty::levelsOf($band)));
        }

        return $bands;
    }

    /**
     * Distribution lines in the order they are filled: single levels, then
     * bands, then "any" (so a broad line never takes what a narrow one needs).
     *
     * @return list<array{key: string, levels: list<int>, count: int}>
     *
     * @throws \InvalidArgumentException on an unknown key or a negative count
     */
    public static function normalizeDistribution(array $distribution): array
    {
        $lines = [];
        foreach ($distribution as $key => $count) {
            $count = filter_var($count, FILTER_VALIDATE_INT);
            if ($count === false || $count < 0) {
                throw new \InvalidArgumentException("Bad count for difficulty «{$key}».");
            }
            if ($count === 0) {
                continue;
            }
            $key = (string) $key;
            $levels = match (true) {
                Difficulty::isLevel($key) => [(int) $key],
                Difficulty::isBand($key) => Difficulty::levelsOf($key),
                $key === self::ANY => Difficulty::LEVELS,
                default => throw new \InvalidArgumentException("Unknown difficulty «{$key}»."),
            };
            $lines[] = ['key' => $key, 'levels' => $levels, 'count' => $count];
        }
        usort($lines, fn ($a, $b) => count($a['levels']) <=> count($b['levels']));

        return $lines;
    }

    /**
     * @param  array<int|string, int>  $distribution
     * @param  string|null  $userId  a student: questions of their recent exam variants come last
     * @return array{
     *     ids: list<string>, seed: int, requested: int, picked: int, shortfall: int,
     *     lines: list<array{key: string, levels: list<int>, requested: int, available: int, picked: int, shortfall: int}>
     * }
     */
    public function pick(array $filters, array $distribution, ?int $seed = null, ?string $userId = null): array
    {
        $seed ??= random_int(1, 2 ** 31 - 1);
        $rng = new Randomizer(new Mt19937($seed));
        $seen = $userId ? $this->recentlySeen($userId, $filters['subject_id'] ?? null) : [];

        $used = [];
        $ids = [];
        $lines = [];
        foreach (self::normalizeDistribution($distribution) as $line) {
            $candidates = $this->query($filters)->reorder()->whereIn('difficulty_level', $line['levels'])
                ->orderBy('id')->pluck('id')->map(fn ($id) => (string) $id)->all();
            $candidates = array_values(array_filter($candidates, fn ($id) => ! isset($used[$id])));
            sort($candidates, SORT_STRING); // the draw depends on the seed only, not on database order

            $fresh = array_values(array_filter($candidates, fn ($id) => ! isset($seen[$id])));
            $stale = array_values(array_filter($candidates, fn ($id) => isset($seen[$id])));
            $fresh = $fresh ? $rng->shuffleArray($fresh) : [];
            $stale = $stale ? $rng->shuffleArray($stale) : [];
            usort($stale, fn ($a, $b) => $seen[$a] <=> $seen[$b]); // seen longest ago first; ties keep the shuffle

            $take = array_slice([...$fresh, ...$stale], 0, $line['count']);
            foreach ($take as $id) {
                $used[$id] = true;
                $ids[] = $id;
            }
            $lines[] = [
                'key' => $line['key'],
                'levels' => $line['levels'],
                'requested' => $line['count'],
                'available' => count($candidates),
                'picked' => count($take),
                'shortfall' => $line['count'] - count($take),
            ];
        }

        $requested = array_sum(array_column($lines, 'requested'));

        return [
            'ids' => $ids,
            'seed' => $seed,
            'requested' => $requested,
            'picked' => count($ids),
            'shortfall' => $requested - count($ids),
            'lines' => $lines,
        ];
    }

    /**
     * Questions of the student's exam variants generated in the last weeks.
     *
     * @return array<string, int> question id => unix time it was last given
     */
    public function recentlySeen(string $userId, ?string $subjectId = null, int $weeks = self::AVOID_WEEKS): array
    {
        if ($weeks <= 0) {
            return [];
        }

        $seen = [];
        $variants = ExamVariant::query()
            ->where('user_id', $userId)
            ->where('generated_at', '>=', now()->subWeeks($weeks))
            ->when($subjectId, fn ($q) => $q->whereIn('session_id', \App\Models\Exam\ExamSession::withTrashed()->where('subject_id', $subjectId)->select('id')))
            ->get(['question_ids', 'generated_at']);
        foreach ($variants as $variant) {
            $at = $variant->generated_at?->getTimestamp() ?? 0;
            foreach ((array) $variant->question_ids as $id) {
                $seen[(string) $id] = max($seen[(string) $id] ?? 0, $at);
            }
        }

        return $seen;
    }
}
