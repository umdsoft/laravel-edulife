<?php

namespace App\Listeners\Exam;

use App\Enums\Exam\ReferralStatus;
use App\Models\Subscription;
use App\Services\Exam\ReferralService;
use Illuminate\Support\Facades\DB;

/**
 * An active subscription (Payme/Click fulfilment, PaymentFulfillmentService,
 * or one an admin created) moves the subscriber's referral to "subscribed".
 *
 * Hooked to the Subscription model's saved event (the payment flow fires no
 * event of its own). Runs after the surrounding transaction commits and never
 * throws into the payment flow.
 */
class AdvanceReferralOnSubscription
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function __invoke(Subscription $subscription): void
    {
        if ($subscription->status !== 'active' || ! $subscription->user_id) {
            return;
        }
        if ($subscription->ends_at !== null && $subscription->ends_at->isPast()) {
            return;
        }

        $userId = $subscription->user_id;
        DB::afterCommit(function () use ($userId) {
            try {
                $this->referrals->advance($userId, ReferralStatus::Subscribed);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
