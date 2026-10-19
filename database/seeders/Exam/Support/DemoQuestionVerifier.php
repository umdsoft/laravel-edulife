<?php

namespace Database\Seeders\Exam\Support;

use App\Enums\Exam\ExamQuestionType;

/**
 * Checks a demo question definition (data/demo_questions_*.php) before it
 * is seeded: the hand-written answer must equal the independent
 * computation, a closed question must have exactly one option with that
 * value, units must be declared for numeric_with_unit, ...
 */
final class DemoQuestionVerifier
{
    /**
     * Relative closeness used for computed vs expected values. Purely
     * relative so physical constants like 8e-14 and 8e-20 stay different.
     */
    public static function same(float $a, float $b): bool
    {
        $scale = max(abs($a), abs($b));

        return $scale < 1e-30 || abs($a - $b) <= 1e-6 * $scale;
    }

    /** Value of an option spec: number or [latex, value]. */
    public static function optionValue(int|float|array $option): float
    {
        return (float) (is_array($option) ? $option[1] : $option);
    }

    /**
     * @param  list<string>  $topicCodes  known topic codes
     * @return list<string> errors (empty = valid)
     */
    public static function errors(array $q, array $topicCodes): array
    {
        $e = [];
        $label = $q['topic'] ?? '?';
        $label .= ': '.mb_substr((string) ($q['stem'] ?? ''), 0, 60);

        foreach (['topic', 'type', 'difficulty', 'stem', 'answer', 'compute'] as $key) {
            if (! array_key_exists($key, $q)) {
                $e[] = "{$label} - missing [{$key}]";
            }
        }
        if ($e !== []) {
            return $e;
        }

        if (! in_array($q['topic'], $topicCodes, true)) {
            $e[] = "{$label} - unknown topic";
        }
        $type = ExamQuestionType::tryFrom($q['type']);
        if ($type === null || $type === ExamQuestionType::Expression) {
            return [...$e, "{$label} - unsupported type [{$q['type']}]"];
        }
        if (! is_int($q['difficulty']) || $q['difficulty'] < 1 || $q['difficulty'] > 5) {
            $e[] = "{$label} - difficulty must be 1..5";
        }
        if (trim($q['stem']) === '') {
            $e[] = "{$label} - empty stem";
        }

        $computed = ($q['compute'])();
        if (! is_numeric($computed) || ! self::same((float) $computed, (float) $q['answer'])) {
            $e[] = sprintf('%s - computed %s differs from expected %s', $label, var_export($computed, true), var_export($q['answer'], true));
        }

        if ($type->isChoice()) {
            $options = $q['options'] ?? [];
            if (count($options) < 2 || count($options) > 5) {
                $e[] = "{$label} - needs 2..5 options";
            }
            $values = array_map([self::class, 'optionValue'], $options);
            $matches = array_filter($values, fn ($v) => self::same($v, (float) $q['answer']));
            if (count($matches) !== 1) {
                $e[] = "{$label} - exactly one option must equal the answer, found ".count($matches);
            }
            foreach ($values as $i => $a) {
                foreach (array_slice($values, $i + 1) as $b) {
                    if (self::same($a, $b)) {
                        $e[] = "{$label} - duplicate option value {$a}";
                    }
                }
            }
        } else {
            if (isset($q['options'])) {
                $e[] = "{$label} - open question must not have options";
            }
            if (($q['tolerance'] ?? 0) < 0) {
                $e[] = "{$label} - negative tolerance";
            }
            if ($type->requiresUnit()) {
                if (trim((string) ($q['unit'] ?? '')) === '') {
                    $e[] = "{$label} - numeric_with_unit needs a unit";
                }
                foreach ($q['accepted_units'] ?? [] as $unit => $factor) {
                    if (! is_numeric($factor) || $factor <= 0) {
                        $e[] = "{$label} - bad factor for unit {$unit}";
                    }
                }
            } elseif (isset($q['unit'])) {
                $e[] = "{$label} - plain numeric question must not have a unit";
            }
        }

        return $e;
    }
}
