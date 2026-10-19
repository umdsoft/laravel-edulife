<?php

namespace App\Enums\Exam;

/**
 * Milliy sertifikat style blueprint parts: closed (with options) and open (written answer).
 */
enum BlueprintPart: string
{
    case Closed = 'closed';
    case Open = 'open';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
