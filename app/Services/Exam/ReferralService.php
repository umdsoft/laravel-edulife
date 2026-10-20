<?php

namespace App\Services\Exam;

use App\Enums\Exam\ReferralStatus;
use App\Events\Exam\ReferralStatusChanged;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\Referral;
use App\Models\Exam\ReferralCode;
use App\Models\User;
use App\Services\CoinRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Invite links and the referral funnel joined -> placement_done -> subscribed
 * (spec section 9).
 *
 * Web: /r/{code} remembers the code (session + cookie) and opens the
 * registration page; RegisterController calls captureForNewUser(). Telegram
 * (/start ref_<code>, stage 6) calls recordJoin() directly.
 *
 * Reward: when the referral reaches config('exam.referral.reward_stage')
 * (default placement_done: the invited student really took the free
 * placement test), the referrer gets the existing referral coin bonus
 * (CoinRewardService::awardReferralCoins) exactly once. Coins are the
 * platform's in-app bonus currency, not money, which is what the spec asks.
 */
class ReferralService
{
    public const SESSION_KEY = 'exam_referral_code';

    public const COOKIE = 'edulife_ref';

    private const ORDER = ['joined' => 0, 'placement_done' => 1, 'subscribed' => 2];

    public function __construct(private readonly CoinRewardService $coins) {}

    public function codeFor(User $user): ReferralCode
    {
        return ReferralCode::query()->firstOrCreate(['owner_id' => $user->id], ['uses_count' => 0, 'is_active' => true]);
    }

    public function linkFor(User $user): string
    {
        return route('referral.landing', ['code' => $this->codeFor($user)->code]);
    }

    public static function normalize(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if (str_starts_with($code, 'REF_')) {
            $code = substr($code, 4);
        }

        return preg_match('/^[A-Z0-9]{4,32}$/', $code) ? $code : null;
    }

    /** /r/{code}: keep the code until the visitor registers. */
    public function remember(Request $request, string $code): void
    {
        $code = self::normalize($code);
        if ($code === null) {
            return;
        }
        $request->session()->put(self::SESSION_KEY, $code);
        Cookie::queue(self::COOKIE, $code, 60 * 24 * (int) config('exam.referral.cookie_days', 30));
    }

    /** Called right after a web registration. */
    public function captureForNewUser(User $user, Request $request): ?Referral
    {
        $code = $request->input('ref')
            ?: $request->session()->pull(self::SESSION_KEY)
            ?: $request->cookie(self::COOKIE);

        $referral = $this->recordJoin($user, is_string($code) ? $code : null, 'web');
        Cookie::queue(Cookie::forget(self::COOKIE));

        return $referral;
    }

    /**
     * Link $referred to the owner of $code. Silently ignores unknown or
     * inactive codes, self invites and users who were already invited.
     */
    public function recordJoin(User $referred, ?string $code, string $source = 'web'): ?Referral
    {
        $code = self::normalize($code);
        if ($code === null) {
            return null;
        }

        $referral = DB::transaction(function () use ($referred, $code) {
            $owner = ReferralCode::query()->where('code', $code)->where('is_active', true)->lockForUpdate()->first();
            if (! $owner || $owner->owner_id === $referred->id) {
                return null;
            }
            if (Referral::query()->where('referred_id', $referred->id)->exists()) {
                return null;
            }

            $referral = Referral::create([
                'referral_code_id' => $owner->id,
                'referrer_id' => $owner->owner_id,
                'referred_id' => $referred->id,
                'status' => ReferralStatus::Joined,
                'joined_at' => now(),
            ]);
            $owner->increment('uses_count');

            return $referral;
        });

        if ($referral) {
            // ids only: no personal data in logs (spec section 11)
            Log::info('exam.referral.joined', ['referral' => $referral->id, 'source' => $source]);
            ReferralStatusChanged::dispatch($referral, null);
            $this->rewardIfDue($referral);
        }

        return $referral;
    }

    /** Move the referral of $referred forward (never back); fires the event once per step. */
    public function advance(User|string $referred, ReferralStatus $status): ?Referral
    {
        $referredId = $referred instanceof User ? $referred->id : $referred;

        $result = DB::transaction(function () use ($referredId, $status) {
            $referral = Referral::query()->where('referred_id', $referredId)->lockForUpdate()->first();
            if (! $referral || self::ORDER[$status->value] <= self::ORDER[$referral->status->value]) {
                return null;
            }
            $previous = $referral->status;
            $referral->advanceTo($status);

            return [$referral, $previous];
        });

        if ($result === null) {
            return null;
        }

        [$referral, $previous] = $result;
        ReferralStatusChanged::dispatch($referral, $previous);
        $this->rewardIfDue($referral);

        return $referral;
    }

    /** ExamAttemptGraded: a graded placement attempt = placement_done. */
    public function onAttemptGraded(ExamAttempt $attempt): void
    {
        $attempt->loadMissing('session:id,type');
        $type = $attempt->session?->type;
        $type = $type instanceof \BackedEnum ? $type->value : $type;
        if ($type !== 'placement') {
            return;
        }
        $this->advance($attempt->user_id, ReferralStatus::PlacementDone);
    }

    private function rewardIfDue(Referral $referral): void
    {
        $stage = (string) config('exam.referral.reward_stage', 'placement_done');
        $stage = array_key_exists($stage, self::ORDER) ? $stage : 'placement_done';
        if ($referral->reward_granted_at !== null || self::ORDER[$referral->status->value] < self::ORDER[$stage]) {
            return;
        }

        // claim the reward atomically: a repeated event never pays twice
        $claimed = Referral::query()->whereKey($referral->id)->whereNull('reward_granted_at')
            ->update(['reward_granted_at' => now()]);
        if ($claimed !== 1) {
            return;
        }
        $referral->refresh();

        $referrer = User::query()->find($referral->referrer_id);
        $referred = User::query()->find($referral->referred_id);
        // coins live on the student profile; guardians and staff get no coins
        if ($referrer && $referred && $referrer->role === 'student') {
            $this->coins->awardReferralCoins($referrer, $referred);
        }
    }
}
