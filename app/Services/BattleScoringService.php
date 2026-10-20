<?php

namespace App\Services;

use App\Models\BattleQuestionStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Olympiad-style battle scoring (docs/battle/SCORING.md):
 *
 *  1. empirical solve rate with Bayesian smoothing
 *        p̂ = (c + m·p₀) / (n + m)
 *     n / c: battle attempts / correct answers (plus course test answers at a
 *     reduced weight), p₀: the prior of the teacher's label (easy / medium /
 *     hard), m: how many "virtual attempts" the prior is worth;
 *  2. the value of a question grows as fewer people solve it
 *        linear: V = base × (1 + k·(1 − p̂))
 *        logit:  V = base × e^(β·b),  b = ln((1 − p̂) / p̂)   (Rasch / IRT difficulty)
 *  3. inside a battle a question is shared by those who solved it
 *        points = V × s^(−γ) × (1 + λ·(1 − t / T))
 *     s: solvers in this battle, t: answer time, T: time limit.
 */
class BattleScoringService
{
    /** p̂ never reaches 0 or 1, so the logit stays finite. */
    private const P_MIN = 0.01;
    private const P_MAX = 0.99;

    public function __construct(protected BattleScoringSettings $settings) {}

    public function settings(): BattleScoringSettings
    {
        return $this->settings;
    }

    /** Smoothed solve rate p̂. With no attempts it is the label's prior. */
    public function estimateSolveRate(float $attempts, float $correct, ?string $label): float
    {
        $m = $this->settings->priorStrength();
        $p0 = $this->settings->prior($label);
        $attempts = max(0.0, $attempts);
        $correct = min($attempts, max(0.0, $correct));

        $p = ($attempts + $m) > 0 ? ($correct + $m * $p0) / ($attempts + $m) : $p0;

        return min(self::P_MAX, max(self::P_MIN, $p));
    }

    /** IRT (Rasch) difficulty in logits: 0 at p̂ = ½, positive for hard questions. */
    public function difficultyLogit(float $p): float
    {
        $p = min(self::P_MAX, max(self::P_MIN, $p));

        return log((1 - $p) / $p);
    }

    public function weight(float $p): float
    {
        $p = min(self::P_MAX, max(self::P_MIN, $p));

        return $this->settings->model() === 'logit'
            ? exp($this->settings->logitBeta() * $this->difficultyLogit($p))
            : 1 + $this->settings->k() * (1 - $p);
    }

    /** Points a question is worth to a single solver (before the speed bonus). */
    public function questionValue(float $p): int
    {
        return max(1, (int) round($this->settings->base() * $this->weight($p)));
    }

    /** easy / medium / hard band of a solve rate. */
    public function level(float $p): string
    {
        if ($p >= $this->settings->easyMinP()) {
            return 'easy';
        }

        return $p >= $this->settings->hardMaxP() ? 'medium' : 'hard';
    }

    /**
     * One player's points for a round.
     *
     * @param  int  $solvers  players who answered this question correctly in this battle
     */
    public function roundPoints(int $value, bool $correct, int $solvers, int $timeTaken, int $limit): int
    {
        if (! $correct || $solvers < 1 || $value <= 0) {
            return 0;
        }

        $share = $solvers ** (-$this->settings->solverExponent());
        $speed = $limit > 0 ? 1 - min(1, max(0, $timeTaken / $limit)) : 0;

        return max(1, (int) round($value * $share * (1 + $this->settings->speedBonus() * $speed)));
    }

    /**
     * Attempts and correct answers per question: battle answers in full,
     * course test answers at the configured weight.
     *
     * @param  array<int, string>  $questionIds
     * @return array<string, array{attempts: float, correct: float}>
     */
    public function evidence(array $questionIds): array
    {
        $evidence = [];
        foreach (array_chunk(array_values(array_unique($questionIds)), 500) as $chunk) {
            foreach (BattleQuestionStat::whereIn('question_id', $chunk)->get(['question_id', 'attempts', 'correct']) as $stat) {
                $evidence[$stat->question_id] = ['attempts' => (float) $stat->attempts, 'correct' => (float) $stat->correct];
            }

            $weight = $this->settings->testWeight();
            if ($weight <= 0) {
                continue;
            }

            $rows = DB::table('test_answers')
                ->whereIn('question_id', $chunk)
                ->whereNotNull('is_correct')
                ->groupBy('question_id')
                ->selectRaw('question_id, COUNT(*) AS attempts, SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) AS correct')
                ->get();

            foreach ($rows as $row) {
                $current = $evidence[$row->question_id] ?? ['attempts' => 0.0, 'correct' => 0.0];
                $evidence[$row->question_id] = [
                    'attempts' => $current['attempts'] + $weight * (float) $row->attempts,
                    'correct' => $current['correct'] + $weight * (float) $row->correct,
                ];
            }
        }

        return $evidence;
    }

    /**
     * p̂, level and value of each question.
     *
     * @param  iterable<\App\Models\Question>  $questions  id + difficulty are enough
     * @return array<string, array{p: float, level: string, value: int}>
     */
    public function estimates(iterable $questions): array
    {
        $questions = collect($questions);
        $evidence = $this->evidence($questions->pluck('id')->all());

        return $questions->mapWithKeys(function ($question) use ($evidence) {
            $e = $evidence[$question->id] ?? ['attempts' => 0.0, 'correct' => 0.0];
            $p = $this->estimateSolveRate($e['attempts'], $e['correct'], $question->difficulty);

            return [$question->id => ['p' => $p, 'level' => $this->level($p), 'value' => $this->questionValue($p)]];
        })->all();
    }

    /** Count a finished round in the question's statistics. */
    public function recordAttempts(string $questionId, int $attempts, int $correct): void
    {
        if ($attempts <= 0) {
            return;
        }

        DB::table('battle_question_stats')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'question_id' => $questionId,
            'attempts' => 0,
            'correct' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('battle_question_stats')->where('question_id', $questionId)->update([
            'attempts' => DB::raw('attempts + '.(int) $attempts),
            'correct' => DB::raw('correct + '.(int) min($correct, $attempts)),
            'updated_at' => now(),
        ]);
    }
}
