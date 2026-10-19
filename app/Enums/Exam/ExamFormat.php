<?php

namespace App\Enums\Exam;

/**
 * Exam formats a question suits (question_bank.exam_formats JSON) and the
 * format of a blueprint.
 */
enum ExamFormat: string
{
    case MilliySertifikat = 'milliy_sertifikat';
    case Abituriyent = 'abituriyent';
    case Sat = 'sat';

    public function label(): string
    {
        return match ($this) {
            self::MilliySertifikat => 'Milliy sertifikat',
            self::Abituriyent => 'Abituriyent (OTM)',
            self::Sat => 'SAT',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
