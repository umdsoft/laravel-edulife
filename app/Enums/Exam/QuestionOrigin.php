<?php

namespace App\Enums\Exam;

/**
 * Where a question_bank row came from.
 */
enum QuestionOrigin: string
{
    case AiDraft = 'ai_draft';
    case Manual = 'manual';
    case Import = 'import';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
