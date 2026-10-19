<?php

namespace App\Enums\Exam;

/** courses.course_track */
enum CourseTrack: string
{
    case Abituriyent = 'abituriyent';
    case MilliySertifikat = 'milliy_sertifikat';
    case SatMath = 'sat_math';
    case School = 'school';

    public function label(): string
    {
        return match ($this) {
            self::Abituriyent => 'Abituriyent (OTM)',
            self::MilliySertifikat => 'Milliy sertifikat',
            self::SatMath => 'SAT Math',
            self::School => 'Maktab dasturi (5-11-sinf)',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
