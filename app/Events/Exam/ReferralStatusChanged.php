<?php

namespace App\Events\Exam;

use App\Enums\Exam\ReferralStatus;
use App\Models\Exam\Referral;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A referral moved forward in the funnel (joined -> placement_done -> subscribed).
 * Fired once per transition; $previous is null for a freshly created referral.
 */
class ReferralStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Referral $referral,
        public ?ReferralStatus $previous = null,
    ) {}
}
