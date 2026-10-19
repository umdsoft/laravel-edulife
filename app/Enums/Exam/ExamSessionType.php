<?php

namespace App\Enums\Exam;

/**
 * placement = entrance exam, weekly = the Saturday exam.
 */
enum ExamSessionType: string
{
    case Placement = 'placement';
    case Weekly = 'weekly';
    case OpenRegional = 'open_regional';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
