<?php

namespace App\Services\Lab;

use App\Models\LabAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Server-side checking of virtual-lab tasks.
 *
 * The browser never receives a task's `expected` block (see LabExperiment::publicTasks()),
 * and it never sends a score: it sends what the student did (measurements emitted by the
 * simulation, calculator results, or a written answer) and this service decides.
 *
 * Rules per task:
 *  - expected.source = "measurement": among the submitted measurements, the LAST one whose
 *    name starts with expected.measurement (case/apostrophe-insensitive) is compared with
 *    expected.value after unit normalisation (SI prefixes: mA↔A, ms↔s, mm↔cm↔m, kV/m↔V/m,
 *    mN↔N, …). Correct when |v − e| ≤ tolerance_percent % · |e|.
 *  - expected.source = "calculation": same comparison for the LAST submitted calculator
 *    result whose unit is compatible with expected.unit (a missing unit means expected.unit).
 *  - no `expected`:
 *      instruction            → completed on acknowledgement (no answer needed);
 *      conclusion, graph_analysis and any other written task → completed when the text has
 *        at least MIN_TEXT_LETTERS letters and MIN_TEXT_WORDS distinct words (≥ 2 letters)
 *        and is not just a copy of the task's title/description;
 *      measurement/calculation without `expected` → completed when any measurement /
 *        calculation is submitted.
 *
 * A correct answer scores the task's max_score (from the experiment, never from the client).
 * Recording is idempotent: an already completed task is never re-scored, and a submission
 * that contains nothing relevant (e.g. a different measurement) is not counted as an attempt.
 */
class LabTaskChecker
{
    public const MIN_TEXT_LETTERS = 40;
    public const MIN_TEXT_WORDS = 6;
    public const MAX_TEXT_LENGTH = 5000;

    /** Statuses in which an attempt accepts answers (a paused attempt is resumed). */
    public const OPEN_STATUSES = ['in_progress', 'paused'];

    private const PREFIXES = [
        'T' => 1e12, 'G' => 1e9, 'M' => 1e6, 'k' => 1e3, '' => 1.0,
        'c' => 1e-2, 'm' => 1e-3, 'µ' => 1e-6, 'n' => 1e-9, 'p' => 1e-12,
    ];

    /** Base units that may carry an SI prefix — longest first so "V/m" wins over "m". */
    private const BASE_UNITS = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];

    /** The centi prefix is only meaningful for these bases (cm, cL). */
    private const CENTI_BASES = ['m', 'L'];

    // ═══════════════════════════════════════════════════════════════════════
    // RECORDING
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Check a submission for one task of the attempt and record the outcome.
     *
     * @param array{measurements?: array, calculations?: array, text?: string|null} $answer
     * @return array{task_number:int, correct:bool, counted:bool, already_completed:bool, score:int, max_score:int, feedback:string, task:array, progress:array}
     *
     * @throws LabTaskCheckException
     */
    public function check(User $user, LabAttempt $attempt, int $step, array $answer): array
    {
        return DB::transaction(function () use ($user, $attempt, $step, $answer) {
            /** @var LabAttempt|null $locked */
            $locked = LabAttempt::whereKey($attempt->getKey())->lockForUpdate()->first();

            if (!$locked || (string) $locked->user_id !== (string) $user->getKey()) {
                throw LabTaskCheckException::notOwner();
            }
            if (!in_array($locked->status, self::OPEN_STATUSES, true)) {
                throw LabTaskCheckException::notInProgress();
            }

            $experiment = $locked->experiment;
            $task = $experiment?->findTask($step);
            if (!$task) {
                throw LabTaskCheckException::unknownTask($step);
            }

            $maxScore = max(0, (int) ($task['max_score'] ?? 0));
            $existing = $locked->taskEntry($step);

            if (($existing['status'] ?? null) === 'completed') {
                $result = [
                    'correct' => true,
                    'counted' => false,
                    'already_completed' => true,
                    'score' => (int) ($existing['score'] ?? 0),
                    'feedback' => 'Bu vazifa allaqachon bajarilgan.',
                ];
            } else {
                $verdict = $this->evaluate($task, $answer);
                $attempts = (int) ($existing['attempts'] ?? 0) + ($verdict['relevant'] ? 1 : 0);

                if ($verdict['correct']) {
                    $entry = [
                        'status' => 'completed',
                        'correct' => true,
                        'score' => $maxScore,
                        'max_score' => $maxScore,
                        'attempts' => $attempts,
                        'feedback' => $verdict['feedback'],
                        'user_input' => $verdict['submitted'],
                        'completed_at' => now()->toIso8601String(),
                    ];
                } else {
                    $entry = [
                        'status' => 'in_progress',
                        'correct' => false,
                        'score' => 0,
                        'max_score' => $maxScore,
                        'attempts' => $attempts,
                        'feedback' => $verdict['feedback'],
                    ];
                    if ($verdict['relevant']) {
                        $entry['user_input'] = $verdict['submitted'];
                        $entry['last_checked_at'] = now()->toIso8601String();
                    }
                }

                $this->record($locked, $step, $entry, $task, $verdict);

                $result = [
                    'correct' => $verdict['correct'],
                    'counted' => $verdict['relevant'],
                    'already_completed' => false,
                    'score' => $entry['score'],
                    'feedback' => $verdict['feedback'],
                ];
            }

            $attempt->setRawAttributes($locked->getAttributes(), true);

            return [
                'task_number' => $step,
                'max_score' => $maxScore,
                ...$result,
                'task' => $locked->publicTasksProgress()[$step] ?? null,
                'progress' => $this->progressSummary($locked),
            ];
        });
    }

    /**
     * Progress numbers the client shows (and the completion gate uses).
     */
    public function progressSummary(LabAttempt $attempt): array
    {
        $total = (int) $attempt->total_tasks;
        $completed = (int) $attempt->completed_tasks;

        return [
            'current_task' => (int) $attempt->current_task,
            'completed_tasks' => $completed,
            'total_tasks' => $total,
            'progress_percent' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'raw_score' => (int) $attempt->raw_score,
            'can_complete' => $total > 0 && $completed >= $total,
        ];
    }

    private function record(LabAttempt $attempt, int $step, array $entry, array $task, array $verdict): void
    {
        $experiment = $attempt->experiment;
        $attempt->total_tasks = count($experiment->tasks ?? []);

        if ($attempt->status === 'paused') {
            $attempt->status = 'in_progress';
        }
        $attempt->last_activity_at = now();

        if ($verdict['correct'] && ($task['type'] ?? null) === 'conclusion' && is_string($verdict['submitted'])
            && blank($attempt->conclusion_text)) {
            $attempt->conclusion_text = $verdict['submitted'];
        }

        // Merges the entry, recounts completed_tasks and saves.
        $attempt->updateTaskProgress($step, $entry);

        $attempt->raw_score = (int) collect($attempt->tasks_progress ?? [])
            ->where('status', 'completed')
            ->sum('score');

        if ($verdict['correct']) {
            $attempt->current_task = $this->nextOpenStep($attempt, $step);
        }

        $attempt->save();
    }

    /** First unfinished step after $step (wrapping around); $step itself when all are done. */
    private function nextOpenStep(LabAttempt $attempt, int $step): int
    {
        $steps = array_map(fn ($t) => (int) ($t['step'] ?? 0), $attempt->experiment->tasks ?? []);
        sort($steps);
        $done = collect($attempt->tasks_progress ?? [])->where('status', 'completed')->pluck('step')->map(fn ($s) => (int) $s)->all();
        $open = array_values(array_diff($steps, $done));

        if ($open === []) {
            return $step;
        }
        foreach ($open as $s) {
            if ($s > $step) {
                return $s;
            }
        }

        return $open[0];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EVALUATION (pure — no database)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Decide whether a submission solves a task.
     *
     * @return array{correct:bool, relevant:bool, feedback:string, submitted:mixed}
     */
    public function evaluate(array $task, array $answer): array
    {
        $expected = $task['expected'] ?? null;
        $type = $task['type'] ?? null;

        if (is_array($expected)) {
            return ($expected['source'] ?? null) === 'calculation'
                ? $this->evaluateCalculation($expected, $answer['calculations'] ?? [])
                : $this->evaluateMeasurement($expected, $answer['measurements'] ?? []);
        }

        return match ($type) {
            'instruction' => $this->verdict(true, true, "Vazifa bajarildi. Keyingi vazifaga o'ting.", null),
            'measurement' => $this->evaluateAnyMeasurement($answer['measurements'] ?? []),
            'calculation' => $this->evaluateAnyCalculation($answer['calculations'] ?? []),
            default => $this->evaluateText($task, $answer['text'] ?? null),
        };
    }

    private function evaluateMeasurement(array $expected, array $measurements): array
    {
        $prefix = (string) ($expected['measurement'] ?? '');
        $needle = $this->normalizeName($prefix);

        $match = null;
        foreach ($this->validEntries($measurements, 'value') as $m) {
            if ($needle === '' || str_starts_with($this->normalizeName((string) ($m['name'] ?? '')), $needle)) {
                $match = $m;
            }
        }

        if ($match === null) {
            return $this->verdict(false, false, $prefix !== ''
                ? "Bu vazifa uchun «{$prefix}» o'lchovi kerak. Vazifa shartidagi sozlamalarni o'rnatib, simulyatsiyada o'lchovni bajaring."
                : "Bu vazifa uchun simulyatsiyada o'lchov bajaring.", null);
        }

        $submitted = [
            'name' => mb_substr((string) ($match['name'] ?? ''), 0, 100),
            'value' => (float) $match['value'],
            'unit' => mb_substr((string) ($match['unit'] ?? ''), 0, 20),
        ];

        return $this->compare($expected, $submitted['value'], $submitted['unit'], $submitted,
            "To'g'ri! O'lchangan qiymat ({$this->fmt($submitted['value'])} {$submitted['unit']}) kutilgan natijaga mos keladi.",
            "O'lchangan qiymat kutilgan natijadan farq qiladi (ruxsat etilgan xatolik ±{$this->fmt((float) $expected['tolerance_percent'])}%). Vazifa shartidagi sozlamalarni tekshirib, o'lchovni qaytadan bajaring.");
    }

    private function evaluateCalculation(array $expected, array $calculations): array
    {
        $expectedUnit = (string) ($expected['unit'] ?? '');

        $match = null;
        $unitMismatch = null;
        foreach ($this->validEntries($calculations, 'result') as $c) {
            $unit = array_key_exists('unit', $c) && $c['unit'] !== null ? (string) $c['unit'] : $expectedUnit;
            if ($this->unitFactor($unit, $expectedUnit) !== null) {
                $match = ['unit' => $unit] + $c;
                $match['unit'] = $unit;
            } else {
                $unitMismatch = $unit;
            }
        }

        if ($match === null) {
            if ($unitMismatch !== null) {
                return $this->verdict(false, true, "Hisob-kitob natijasining birligi («{$unitMismatch}») bu vazifaga mos emas — natija {$this->unitLabel($expectedUnit)} bo'lishi kerak. Vazifada ko'rsatilgan formulani tanlang.", null);
            }

            return $this->verdict(false, false, "Bu vazifa uchun kalkulyatorda vazifada ko'rsatilgan formula bo'yicha hisob-kitob bajaring.", null);
        }

        $submitted = [
            'formula_id' => isset($match['formula_id']) ? mb_substr((string) $match['formula_id'], 0, 100) : null,
            'value' => (float) $match['result'],
            'unit' => mb_substr((string) $match['unit'], 0, 20),
        ];

        return $this->compare($expected, $submitted['value'], $submitted['unit'], $submitted,
            "To'g'ri! Hisoblangan qiymat ({$this->fmt($submitted['value'])} {$submitted['unit']}) kutilgan natijaga mos keladi.",
            "Hisob-kitob natijasi noto'g'ri (ruxsat etilgan xatolik ±{$this->fmt((float) $expected['tolerance_percent'])}%). Formulaga qo'yilgan qiymatlar va ularning birliklarini tekshiring.");
    }

    private function compare(array $expected, float $value, string $unit, array $submitted, string $ok, string $wrong): array
    {
        $expectedUnit = (string) ($expected['unit'] ?? '');
        $factor = $this->unitFactor($unit, $expectedUnit);

        if ($factor === null) {
            return $this->verdict(false, true, "O'lchov birligi («{$unit}») mos kelmadi — natija {$this->unitLabel($expectedUnit)} bo'lishi kerak.", $submitted);
        }

        $correct = $this->withinTolerance($value * $factor, (float) $expected['value'], (float) ($expected['tolerance_percent'] ?? 0));

        return $this->verdict($correct, true, $correct ? $ok : $wrong, $submitted);
    }

    /** Relative tolerance check: |actual − expected| ≤ tolerance% · |expected|. */
    public function withinTolerance(float $actual, float $expected, float $tolerancePercent): bool
    {
        if (!is_finite($actual) || !is_finite($expected)) {
            return false;
        }
        $allowed = abs($expected) * max(0.0, $tolerancePercent) / 100;
        if ($expected == 0.0) {
            $allowed = max($allowed, 1e-9);
        }

        // tiny slack for binary floating point at the exact edge (e.g. 1 % of 2.0147)
        return abs($actual - $expected) <= $allowed * (1 + 1e-9) + 1e-12;
    }

    private function evaluateAnyMeasurement(array $measurements): array
    {
        $entries = $this->validEntries($measurements, 'value');
        if ($entries === []) {
            return $this->verdict(false, false, "Bu vazifa uchun simulyatsiyada o'lchov bajaring.", null);
        }
        $m = end($entries);

        return $this->verdict(true, true, "O'lchov qabul qilindi.", [
            'name' => mb_substr((string) ($m['name'] ?? ''), 0, 100),
            'value' => (float) $m['value'],
            'unit' => mb_substr((string) ($m['unit'] ?? ''), 0, 20),
        ]);
    }

    private function evaluateAnyCalculation(array $calculations): array
    {
        $entries = $this->validEntries($calculations, 'result');
        if ($entries === []) {
            return $this->verdict(false, false, 'Bu vazifa uchun kalkulyatorda hisob-kitob bajaring.', null);
        }
        $c = end($entries);

        return $this->verdict(true, true, 'Hisob-kitob qabul qilindi.', [
            'formula_id' => isset($c['formula_id']) ? (string) $c['formula_id'] : null,
            'value' => (float) $c['result'],
            'unit' => isset($c['unit']) ? (string) $c['unit'] : null,
        ]);
    }

    private function evaluateText(array $task, mixed $text): array
    {
        $text = is_string($text) ? trim(preg_replace('/\s+/u', ' ', $text) ?? '') : '';
        if ($text === '') {
            return $this->verdict(false, false, "Javobingizni yozing: kuzatishlaringiz va xulosangizni o'z so'zlaringiz bilan bayon qiling.", null);
        }
        $text = mb_substr($text, 0, self::MAX_TEXT_LENGTH);

        $letters = preg_match_all('/\p{L}/u', $text);
        $words = array_unique(array_filter(
            preg_split('/[^\p{L}\p{N}\'ʻʼ’‘`]+/u', mb_strtolower($text)) ?: [],
            fn ($w) => preg_match_all('/\p{L}/u', $w) >= 2
        ));

        if ($letters < self::MIN_TEXT_LETTERS || count($words) < self::MIN_TEXT_WORDS) {
            return $this->verdict(false, true, 'Javob juda qisqa. Kamida ' . self::MIN_TEXT_WORDS . " ta turli so'z va " . self::MIN_TEXT_LETTERS . " ta harfdan iborat to'liq fikr yozing.", $text);
        }

        $own = $this->normalizeName($text);
        foreach (['description', 'title'] as $key) {
            $source = $this->normalizeName((string) ($task[$key] ?? ''));
            if ($source !== '' && str_contains($source, $own)) {
                return $this->verdict(false, true, "Vazifa matnini ko'chirmang — o'z kuzatishlaringiz va xulosangizni yozing.", $text);
            }
        }

        return $this->verdict(true, true, 'Javobingiz qabul qilindi. Rahmat!', $text);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function verdict(bool $correct, bool $relevant, string $feedback, mixed $submitted): array
    {
        return compact('correct', 'relevant', 'feedback', 'submitted');
    }

    /** Array entries whose $valueKey is a finite number. */
    private function validEntries(mixed $list, string $valueKey): array
    {
        if (!is_array($list)) {
            return [];
        }

        return array_values(array_filter($list, fn ($e) => is_array($e)
            && isset($e[$valueKey])
            && is_numeric($e[$valueKey])
            && is_finite((float) $e[$valueKey])));
    }

    /**
     * Multiplier converting a value in $from into $to (null when the units are incompatible).
     * unitFactor('mA', 'A') = 0.001; unitFactor('cm', 'mm') = 10; unitFactor('V', 'A') = null.
     */
    public function unitFactor(string $from, string $to): ?float
    {
        $from = $this->normalizeUnit($from);
        $to = $this->normalizeUnit($to);

        if ($from === $to) {
            return 1.0;
        }

        $a = $this->parseUnit($from);
        $b = $this->parseUnit($to);
        if ($a === null || $b === null || $a[0] !== $b[0]) {
            return null;
        }

        return $a[1] / $b[1];
    }

    /** @return array{0:string,1:float}|null [base unit, prefix multiplier] */
    private function parseUnit(string $unit): ?array
    {
        foreach (self::BASE_UNITS as $base) {
            if (!str_ends_with($unit, $base)) {
                continue;
            }
            $prefix = substr($unit, 0, strlen($unit) - strlen($base));
            if (!array_key_exists($prefix, self::PREFIXES)) {
                continue;
            }
            if ($prefix === 'c' && !in_array($base, self::CENTI_BASES, true)) {
                continue;
            }

            return [$base, self::PREFIXES[$prefix]];
        }

        return null;
    }

    private function normalizeUnit(string $unit): string
    {
        $unit = trim(preg_replace('/\s+/u', '', $unit) ?? '');
        $unit = str_replace(['μ', '*', '^2', '^3', 'Ohm', 'ohm'], ['µ', '·', '²', '³', 'Ω', 'Ω'], $unit);
        // ASCII micro: "uA", "us", "uT" → µ…
        if (preg_match('/^u(V\/m|N·m|Hz|eV|Bq|Pa|Ω|W|J|N|V|A|C|T|s|m|g|L)$/u', $unit)) {
            $unit = 'µ' . substr($unit, 1);
        }

        return $unit;
    }

    /** Case-, whitespace- and apostrophe-insensitive form of a measurement name. */
    private function normalizeName(string $name): string
    {
        $name = str_replace(['ʻ', 'ʼ', '’', '‘', '`', '´'], "'", $name);

        return trim(mb_strtolower(preg_replace('/\s+/u', ' ', $name) ?? ''));
    }

    private function unitLabel(string $unit): string
    {
        return $unit === '' ? "o'lchamsiz son" : "«{$unit}» da";
    }

    private function fmt(float $v): string
    {
        $s = rtrim(rtrim(number_format($v, 6, '.', ''), '0'), '.');

        return $s === '-0' ? '0' : $s;
    }
}
