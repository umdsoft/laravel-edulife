<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Models\Question;
use App\Services\BattleScoringService;
use App\Support\Difficulty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * "ustoz: qiyin · amalda: 82% yechgan" (docs/exam/03 §2.3): the observed
 * solve rate next to the level the teacher set. A SUGGESTION only — nothing
 * here ever changes a question's difficulty.
 *
 *  - exam bank: graded answers of finished, valid exam attempts;
 *  - course tests: the battle p̂ (docs/battle/SCORING.md — battle answers plus
 *    course test answers at their weight, Bayesian-smoothed).
 *
 * Bands come from the battle thresholds (p ≥ easy_min_p → oson, p < hard_max_p
 * → qiyin), so one scale is used everywhere. With fewer than min_answers
 * answers there is no hint.
 */
class DifficultyCalibration
{
    public function __construct(private BattleScoringService $scoring) {}

    public function minAnswers(): int
    {
        return max(1, (int) config('exam.calibration.min_answers', 20));
    }

    public function easyMinP(): float
    {
        return $this->scoring->settings()->easyMinP();
    }

    public function hardMaxP(): float
    {
        return $this->scoring->settings()->hardMaxP();
    }

    /**
     * Exam bank questions.
     *
     * @param  array<string, int|null>  $levels  question id => difficulty_level set by the teacher
     * @return array<string, array{rate: float, attempts: int, band: string, level: int, set_band: ?string, mismatch: bool}>
     */
    public function forBank(array $levels): array
    {
        $out = [];
        $ids = array_keys($levels);
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = $this->bankAnswers()->whereIn('a.question_id', $chunk)
                ->groupBy('a.question_id')
                ->selectRaw('a.question_id AS id, COUNT(*) AS n, SUM(CASE WHEN a.is_correct THEN 1 ELSE 0 END) AS c')
                ->get();
            foreach ($rows as $row) {
                $n = (int) $row->n;
                if ($n < $this->minAnswers()) {
                    continue;
                }
                $out[(string) $row->id] = $this->hint((int) $row->c / $n, $n, Difficulty::bandOf($levels[(string) $row->id] ?? null));
            }
        }

        return $out;
    }

    /**
     * Course test questions: the battle p̂ once there are enough attempts.
     *
     * @param  iterable<Question>  $questions  id + difficulty are enough
     * @return array<string, array{rate: float, attempts: int, band: string, level: int, set_band: ?string, mismatch: bool}>
     */
    public function forCourse(iterable $questions): array
    {
        $questions = collect($questions);
        if ($questions->isEmpty()) {
            return [];
        }
        $evidence = $this->scoring->evidence($questions->pluck('id')->map(fn ($id) => (string) $id)->all());

        $out = [];
        foreach ($questions as $q) {
            $e = $evidence[(string) $q->id] ?? null;
            if (! $e || $e['attempts'] < $this->minAnswers()) {
                continue;
            }
            $p = $this->scoring->estimateSolveRate($e['attempts'], $e['correct'], $q->difficulty);
            // same thresholds as BattleScoringService::level(), so the band is what battles use
            $out[(string) $q->id] = $this->hint($p, (int) round($e['attempts']), Difficulty::isBand($q->difficulty) ? $q->difficulty : null);
        }

        return $out;
    }

    /**
     * "belgilangan va amaldagi qiyinlik farq qiladi": bank questions whose
     * observed band differs from the band of the level the teacher set.
     */
    public function scopeBankMismatch(Builder $query): Builder
    {
        // literal numbers, not bindings: SQLite compares a bound float as TEXT
        $easy = sprintf('%.4F', $this->easyMinP());
        $hard = sprintf('%.4F', $this->hardMaxP());
        $rate = 'SUM(CASE WHEN a.is_correct THEN 1 ELSE 0 END) * 1.0 / COUNT(*)';

        $ids = $this->bankAnswers()
            ->join('question_bank as qb', 'qb.id', '=', 'a.question_id')
            ->whereNotNull('qb.difficulty_level')
            ->groupBy('a.question_id', 'qb.difficulty_level')
            ->havingRaw('COUNT(*) >= ?', [$this->minAnswers()])
            ->havingRaw("((({$rate}) >= {$easy} AND qb.difficulty_level >= 3) OR (({$rate}) < {$hard} AND qb.difficulty_level <= 3) OR (({$rate}) >= {$hard} AND ({$rate}) < {$easy} AND qb.difficulty_level <> 3))")
            ->select('a.question_id');

        return $query->whereIn($query->getModel()->qualifyColumn('id'), $ids);
    }

    private function bankAnswers(): QueryBuilder
    {
        return DB::table('exam_answers as a')
            ->join('exam_attempts as t', 't.id', '=', 'a.attempt_id')
            ->whereNotNull('a.is_correct')
            ->whereNotIn('t.status', [AttemptStatus::InProgress->value, AttemptStatus::Invalidated->value]);
    }

    /** @return array{rate: float, attempts: int, band: string, level: int, set_band: ?string, mismatch: bool} */
    private function hint(float $p, int $attempts, ?string $setBand): array
    {
        $band = Difficulty::bandForSolveRate($p, $this->easyMinP(), $this->hardMaxP());

        return [
            'rate' => round($p, 3),
            'attempts' => $attempts,
            'band' => $band,
            'level' => Difficulty::levelForSolveRate($p, $this->easyMinP(), $this->hardMaxP()),
            'set_band' => $setBand,
            'mismatch' => $setBand !== null && $setBand !== $band,
        ];
    }
}
