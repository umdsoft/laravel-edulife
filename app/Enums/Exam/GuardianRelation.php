<?php

namespace App\Enums\Exam;

/**
 * Relation of a guardian user to a student (guardian_student.relation).
 */
enum GuardianRelation: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Guardian = 'guardian';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
