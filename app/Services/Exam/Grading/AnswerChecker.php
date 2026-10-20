<?php

namespace App\Services\Exam\Grading;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ToleranceType;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\QuestionBank;

/**
 * Grades one answer against the question's CURRENT answer key (so a
 * regrade after the teacher fixes a question uses the fixed key).
 *
 *  single_choice / true_false  the selected option is marked correct
 *  multiple_choice             the selected set equals the correct set (all or nothing)
 *  numeric                     any expected answer accepts the number within its tolerance
 *  numeric_with_unit           number and unit (normalised, converted by accepted_units);
 *                              right number with a wrong / missing unit earns
 *                              max_points × wrong_unit_ratio (blueprint setting)
 *
 * Returns is_correct NULL for a skipped (empty) answer.
 *
 * @phpstan-type Result array{is_correct: ?bool, unit_correct: ?bool, points: float}
 */
class AnswerChecker
{
    /**
     * Expects $question->answerOptions and $question->numericAnswers loaded.
     *
     * @return array{is_correct: ?bool, unit_correct: ?bool, points: float}
     */
    public function check(QuestionBank $question, ExamAnswer $answer, float $maxPoints, float $wrongUnitRatio = 0.0): array
    {
        $type = $question->examType();
        $skipped = ['is_correct' => null, 'unit_correct' => null, 'points' => 0.0];
        $wrong = ['is_correct' => false, 'unit_correct' => null, 'points' => 0.0];
        $right = ['is_correct' => true, 'unit_correct' => null, 'points' => $maxPoints];

        if ($type === null) {
            return $skipped;
        }

        if ($type->isChoice()) {
            $correct = $question->answerOptions->where('is_correct', true)->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();

            if ($type === ExamQuestionType::MultipleChoice) {
                $selected = collect($answer->selected_option_ids ?? [])->map(fn ($id) => (string) $id)->unique()->sort()->values()->all();
                if ($selected === []) {
                    return $skipped;
                }

                return $correct !== [] && $selected === $correct ? $right : $wrong;
            }

            if ($answer->selected_option_id === null) {
                return $skipped;
            }

            return in_array((string) $answer->selected_option_id, $correct, true) ? $right : $wrong;
        }

        if (! $type->usesNumericAnswer()) {
            return $skipped; // expression: not gradable yet (never selected by blueprints)
        }

        $text = trim((string) $answer->answer_text);
        if ($text === '') {
            return $skipped;
        }

        $given = NumberParser::parse($text);
        if ($given === null) {
            return $wrong;
        }

        if ($type === ExamQuestionType::Numeric) {
            foreach ($question->numericAnswers as $expected) {
                if ($this->matches($expected, $given, 1.0)) {
                    return $right;
                }
            }

            return $wrong;
        }

        // numeric_with_unit
        $unitKnown = false;
        foreach ($question->numericAnswers as $expected) {
            $factor = $this->factor($expected, $answer->unit);
            if ($factor === null) {
                continue;
            }
            $unitKnown = true;
            if ($this->matches($expected, $given, $factor)) {
                return ['is_correct' => true, 'unit_correct' => true, 'points' => $maxPoints];
            }
        }

        // The number is right for some accepted unit but the unit written is
        // wrong or missing ("20 km/soat" for 20 m/s): partial credit.
        foreach ($question->numericAnswers as $expected) {
            foreach ($this->factors($expected) as $factor) {
                if ($this->matches($expected, $given, $factor)) {
                    return [
                        'is_correct' => false,
                        'unit_correct' => false,
                        'points' => round($maxPoints * max(0.0, min(1.0, $wrongUnitRatio)), 2),
                    ];
                }
            }
        }

        return ['is_correct' => false, 'unit_correct' => $unitKnown, 'points' => 0.0];
    }

    private function matches(QuestionBankAnswer $expected, float $given, float $factor): bool
    {
        if ($expected->value === null) {
            return false;
        }

        return ($expected->tolerance_type ?? ToleranceType::Absolute)
            ->accepts((float) $expected->value, $given * $factor, (float) $expected->tolerance);
    }

    /** Factor converting the student's unit into the canonical one, NULL when not accepted. */
    private function factor(QuestionBankAnswer $expected, ?string $unit): ?float
    {
        $given = UnitNormalizer::normalize($unit);
        if ($given === '') {
            return null;
        }
        if ($expected->unit !== null && UnitNormalizer::normalize($expected->unit) === $given) {
            return 1.0;
        }
        foreach (($expected->accepted_units ?? []) as $name => $factor) {
            if (UnitNormalizer::normalize((string) $name) === $given && is_numeric($factor) && (float) $factor > 0) {
                return (float) $factor;
            }
        }

        return null;
    }

    /** @return list<float> canonical (1) and every accepted unit factor */
    private function factors(QuestionBankAnswer $expected): array
    {
        $factors = [1.0];
        foreach (($expected->accepted_units ?? []) as $factor) {
            if (is_numeric($factor) && (float) $factor > 0) {
                $factors[] = (float) $factor;
            }
        }

        return $factors;
    }

    /** Units shown by the numeric_with_unit picker: catalog + the question's own, sorted. */
    public static function unitChoices(QuestionBank $question): array
    {
        $units = (array) config('exam.engine.unit_catalog', []);
        foreach ($question->numericAnswers as $expected) {
            if ($expected->unit) {
                $units[] = $expected->unit;
            }
            foreach (array_keys($expected->accepted_units ?? []) as $name) {
                $units[] = (string) $name;
            }
        }

        $byNorm = [];
        foreach ($units as $unit) {
            $byNorm[UnitNormalizer::normalize($unit)] ??= $unit;
        }
        $list = array_values($byNorm);
        usort($list, fn ($a, $b) => strcasecmp($a, $b) ?: strcmp($a, $b));

        return $list;
    }
}
