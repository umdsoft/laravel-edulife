<?php

namespace App\Enums\Exam;

/**
 * Student goal chosen at registration.
 */
enum StudentGoal: string
{
    case OtmGrant = 'otm_grant';
    case MilliySertifikat = 'milliy_sertifikat';
    case Sat = 'sat';
    case School = 'school';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
