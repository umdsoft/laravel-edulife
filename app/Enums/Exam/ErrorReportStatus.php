<?php

namespace App\Enums\Exam;

/**
 * "Savolda xato bor" report status.
 */
enum ErrorReportStatus: string
{
    case Open = 'open';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
