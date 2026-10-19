<?php

namespace App\Enums\Exam;

/**
 * Question types the exam engine understands (question_bank.question_type).
 * Legacy olympiad types (essay, matching, ...) stay valid for the olympiad
 * module but are not selected by exam blueprints.
 */
enum ExamQuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case Numeric = 'numeric';
    case NumericWithUnit = 'numeric_with_unit';
    /** Reserved for a later stage (expression equivalence); not gradable yet. */
    case Expression = 'expression';

    /** Closed (option based) question. */
    public function isChoice(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice, self::TrueFalse], true);
    }

    public function usesNumericAnswer(): bool
    {
        return $this === self::Numeric || $this === self::NumericWithUnit;
    }

    public function requiresUnit(): bool
    {
        return $this === self::NumericWithUnit;
    }

    /** Blueprint part (closed / open) this type belongs to. */
    public function part(): BlueprintPart
    {
        return $this->isChoice() ? BlueprintPart::Closed : BlueprintPart::Open;
    }

    /** Gradable types of a blueprint part (Expression excluded until supported). */
    public static function valuesForPart(BlueprintPart $part): array
    {
        $types = array_filter(
            self::cases(),
            fn (self $t) => $t !== self::Expression && $t->part() === $part
        );

        return array_values(array_map(fn (self $t) => $t->value, $types));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
