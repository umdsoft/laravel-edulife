<?php

namespace App\Enums\Exam;

/**
 * draft -> scheduled (variants may be generated) -> open -> closed -> graded; cancelled at any point before graded.
 */
enum ExamSessionStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Closed = 'closed';
    case Graded = 'graded';
    case Cancelled = 'cancelled';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
