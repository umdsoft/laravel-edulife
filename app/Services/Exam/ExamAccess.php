<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Exceptions\Exam\ExamUnavailable;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamSessionPurchase;
use App\Models\Exam\StudentExamProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * Paid exams (docs/exam/QARORLAR.md item 9) — the one place that decides who may sit a session.
 *
 * A student may start a session when
 *   (a) it is free (price 0) — placement tests are always free,
 *   (b) they have an active subscription whose plan includes exams (subscription_plans.includes_exams), or
 *   (c) they hold a paid purchase of that session (exam_session_purchases, bought with Payme / Click).
 * Teachers and admins are never limited. Who may sit the session at all (open window, subject in the
 * profile, private retake sessions) stays in ExamAttemptService::start().
 */
class ExamAccess
{
    public const VIA_FREE = 'free';
    public const VIA_SUBSCRIPTION = 'subscription';
    public const VIA_PURCHASE = 'purchase';
    public const VIA_PLACEMENT = 'placement';
    public const VIA_STAFF = 'staff';

    private const STAFF_ROLES = ['teacher', 'admin', 'super_admin'];

    /** The session costs money (placement never does). */
    public function isPaid(ExamSession $session): bool
    {
        return $session->type !== ExamSessionType::Placement && (int) $session->price > 0;
    }

    /**
     * @return array{allowed: bool, via: ?string, reason: ?string, message: ?string}
     */
    public function check(ExamSession $session, User $user): array
    {
        $via = match (true) {
            in_array($user->role, self::STAFF_ROLES, true) => self::VIA_STAFF,
            $session->type === ExamSessionType::Placement => self::VIA_PLACEMENT,
            ! $this->isPaid($session) => self::VIA_FREE,
            $this->subscriptionWithExams($user) !== null => self::VIA_SUBSCRIPTION,
            $this->purchase($session, $user) !== null => self::VIA_PURCHASE,
            default => null,
        };

        if ($via !== null) {
            return ['allowed' => true, 'via' => $via, 'reason' => null, 'message' => null];
        }

        $e = ExamUnavailable::paymentRequired((int) $session->price);

        return ['allowed' => false, 'via' => null, 'reason' => $e->reason, 'message' => $e->getMessage()];
    }

    /**
     * @return string how the student got in (stored on the attempt)
     *
     * @throws ExamUnavailable payment_required
     */
    public function assertCanStart(ExamSession $session, User $user): string
    {
        $decision = $this->check($session, $user);
        if (! $decision['allowed']) {
            throw ExamUnavailable::paymentRequired((int) $session->price);
        }

        return $decision['via'];
    }

    /**
     * Safety net for the Take page and autosave: an attempt exists only after start() let the
     * student in (access_via is set then); attempts from before paid exams are checked live.
     */
    public function allowsAttempt(ExamAttempt $attempt, ExamSession $session): bool
    {
        if ($attempt->access_via !== null) {
            return true;
        }

        $user = $attempt->user ?? User::query()->find($attempt->user_id);

        return $user !== null && $this->check($session, $user)['allowed'];
    }

    /** The student's active subscription whose plan includes exams (latest end first). */
    public function subscriptionWithExams(User $user): ?Subscription
    {
        return Subscription::query()
            ->with('plan:id,name,includes_exams')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('plan', fn ($q) => $q->where('includes_exams', true))
            ->orderByRaw('ends_at IS NULL DESC')
            ->orderByDesc('ends_at')
            ->first();
    }

    public function purchase(ExamSession $session, User $user): ?ExamSessionPurchase
    {
        return ExamSessionPurchase::query()
            ->where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->paid()
            ->first();
    }

    /** Why $user can not buy $session right now (null = they can). Used by the payment product. */
    public function buyProblem(ExamSession $session, User $user): ?string
    {
        if ($session->type === ExamSessionType::Placement) {
            return 'Qabul testi bepul.';
        }
        if (! $this->isPaid($session)) {
            return "Bu imtihon bepul — to'lovsiz qatnashish mumkin.";
        }
        if (! in_array($session->status, [ExamSessionStatus::Scheduled, ExamSessionStatus::Open], true)
            || ($session->ends_at !== null && $session->ends_at->lte(now()))) {
            return "Bu imtihon uchun to'lov qabul qilinmaydi: imtihon yakunlangan, bekor qilingan yoki hali e'lon qilinmagan.";
        }
        if ($session->restricted_to_user_id !== null && (string) $session->restricted_to_user_id !== (string) $user->id) {
            return 'Bu imtihon siz uchun emas.';
        }
        if ($session->restricted_to_user_id === null) {
            $code = (string) $session->subject()->value('code');
            $profile = StudentExamProfile::query()->where('user_id', $user->id)->first();
            if (! $profile || ! $profile->studies($code)) {
                return "Bu imtihon siz uchun emas: profilingizda bu fan tanlanmagan.";
            }
        }
        if (ExamAttempt::query()->where('session_id', $session->id)->where('user_id', $user->id)->exists()) {
            return 'Siz bu imtihonni allaqachon boshlagansiz.';
        }
        if ($this->purchase($session, $user) !== null) {
            return "Siz bu imtihon uchun to'lagansiz.";
        }
        if ($this->subscriptionWithExams($user) !== null) {
            return "Obunangiz bilan bu imtihonga kirish ochiq — to'lash shart emas.";
        }

        return null;
    }

    /**
     * What the student's exam page shows for a session:
     *   free | subscription | purchased | pay
     *
     * @return array{state: string, price: int, can_buy: bool, buy_problem: ?string}
     */
    public function state(ExamSession $session, User $user): array
    {
        $decision = $this->check($session, $user);
        $state = match ($decision['via']) {
            self::VIA_SUBSCRIPTION => 'subscription',
            self::VIA_PURCHASE => 'purchased',
            null => 'pay',
            default => 'free',
        };
        $problem = $state === 'pay' ? $this->buyProblem($session, $user) : null;

        return [
            'state' => $state,
            'price' => $this->isPaid($session) ? (int) $session->price : 0,
            'can_buy' => $state === 'pay' && $problem === null,
            'buy_problem' => $problem,
        ];
    }

    /** Names of the active plans that include exams (for the hint on the exam page). */
    public function examPlanNames(): array
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->where('includes_exams', true)
            ->where('price', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->pluck('name')
            ->all();
    }
}
