<?php

namespace App\Enums\Exam;

/**
 * Referral funnel: joined -> placement_done -> subscribed.
 */
enum ReferralStatus: string
{
    case Joined = 'joined';
    case PlacementDone = 'placement_done';
    case Subscribed = 'subscribed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
