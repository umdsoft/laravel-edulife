<?php

namespace App\Services\Payments;

use App\Models\CoinPackage;
use App\Models\Course;
use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Models\Enrollment;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamSessionPurchase;
use App\Models\Notification as AppNotification;
use App\Models\OlympiadPaymentTransaction;
use App\Models\OlympiadRegistration;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CoinService;
use App\Services\XPRewardService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Grants what an order paid for — exactly once — and takes it back on a refund.
 *
 * Every method runs inside a DB transaction and re-reads the order with a row lock
 * (SELECT … FOR UPDATE), so two callbacks racing for the same order (a repeated
 * PerformTransaction, Click Complete retries) fulfil it once: the second one sees
 * fulfilled_at and does nothing.
 */
class PaymentFulfillmentService
{
    public function __construct(
        private readonly CoinService $coins,
    ) {}

    /**
     * Mark the order paid and grant the product. Idempotent.
     */
    public function fulfil(PaymentOrder $order, ?PaymentTransaction $transaction = null): PaymentOrder
    {
        return DB::transaction(function () use ($order, $transaction) {
            /** @var PaymentOrder $order */
            $order = PaymentOrder::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->fulfilled_at !== null) {
                return $order; // already done (repeated callback)
            }

            $granted = match ($order->product_type) {
                PaymentOrder::TYPE_COURSE => $this->grantCourse($order),
                PaymentOrder::TYPE_COIN_PACKAGE => $this->grantCoins($order),
                PaymentOrder::TYPE_SUBSCRIPTION => $this->grantSubscription($order),
                PaymentOrder::TYPE_OLYMPIAD => $this->grantOlympiad($order, $transaction),
                PaymentOrder::TYPE_EXAM_SESSION => $this->grantExamSession($order),
                default => throw new \LogicException("Unknown product type {$order->product_type}"),
            };

            $this->recordPromoUsage($order);

            $order->forceFill([
                'status' => PaymentOrder::STATUS_PAID,
                'paid_at' => $order->paid_at ?? now(),
                'fulfilled_at' => now(),
                'fulfilment' => $granted,
            ])->save();

            if ($order->payment_id) {
                Payment::whereKey($order->payment_id)->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'provider_transaction_id' => $transaction?->provider_transaction_id,
                ]);
            }

            Log::info('payments.fulfilled', ['order' => $order->id, 'type' => $order->product_type, 'provider' => $order->provider]);

            return $order;
        });
    }

    /**
     * Why the granted product cannot be taken back (null = it can).
     */
    public function reverseProblem(PaymentOrder $order): ?string
    {
        if (! config('payments.reverse_on_refund', true)) {
            return 'refunds disabled';
        }

        $granted = $order->fulfilment ?? [];

        return match ($order->product_type) {
            PaymentOrder::TYPE_COIN_PACKAGE => ($order->user?->ensureStudentProfile()->coins ?? 0) < (int) ($granted['coins'] ?? 0)
                ? 'coins already spent' : null,
            PaymentOrder::TYPE_OLYMPIAD => $this->olympiadStarted($granted) ? 'olympiad attempt started' : null,
            PaymentOrder::TYPE_EXAM_SESSION => $this->examSessionStarted($order, $granted) ? 'exam attempt started' : null,
            default => null,
        };
    }

    /**
     * Take back what the order granted (refund after payment). Idempotent.
     *
     * @throws \DomainException when it cannot be taken back (see reverseProblem)
     */
    public function reverse(PaymentOrder $order, string $reason = 'refund'): PaymentOrder
    {
        return DB::transaction(function () use ($order, $reason) {
            /** @var PaymentOrder $order */
            $order = PaymentOrder::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status === PaymentOrder::STATUS_REFUNDED) {
                return $order;
            }
            if ($order->fulfilled_at === null) {
                throw new \LogicException('Order was not fulfilled');
            }
            if ($problem = $this->reverseProblem($order)) {
                throw new \DomainException($problem);
            }

            $granted = $order->fulfilment ?? [];

            match ($order->product_type) {
                PaymentOrder::TYPE_COURSE => $this->revokeCourse($granted),
                PaymentOrder::TYPE_COIN_PACKAGE => $this->revokeCoins($order, $granted),
                PaymentOrder::TYPE_SUBSCRIPTION => $this->revokeSubscription($granted),
                PaymentOrder::TYPE_OLYMPIAD => $this->revokeOlympiad($granted),
                PaymentOrder::TYPE_EXAM_SESSION => $this->revokeExamSession($granted, $reason),
                default => null,
            };

            if ($order->promo_code_id) {
                $deleted = PromoCodeUsage::where('promo_code_id', $order->promo_code_id)
                    ->where('payment_id', $order->payment_id)
                    ->delete();
                if ($deleted) {
                    PromoCode::whereKey($order->promo_code_id)->where('usage_count', '>', 0)->decrement('usage_count');
                }
            }

            $order->forceFill([
                'status' => PaymentOrder::STATUS_REFUNDED,
                'refunded_at' => now(),
            ])->save();

            if ($order->payment_id) {
                Payment::whereKey($order->payment_id)->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_amount' => $order->amount,
                    'refund_reason' => $reason,
                ]);
            }

            Log::info('payments.reversed', ['order' => $order->id, 'type' => $order->product_type]);

            return $order;
        });
    }

    // ------------------------------------------------------------------ grant

    private function grantCourse(PaymentOrder $order): array
    {
        $course = Course::findOrFail($order->product_id);
        $user = $order->user;

        $enrollment = Enrollment::withTrashed()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->lockForUpdate()
            ->first();

        if ($enrollment && ! $enrollment->trashed()) {
            // access was opened another way meanwhile (admin / second order): keep it, note it
            return ['enrollment_id' => $enrollment->id, 'already_had_access' => true];
        }

        $attributes = [
            'access_type' => 'purchase',
            'status' => 'active',
            'payment_id' => $order->payment_id,
            'paid_amount' => $order->amount,
            'payment_status' => 'paid',
            'total_lessons' => $course->lessons()->count(),
        ];

        if ($enrollment) {
            $enrollment->restore();
            $enrollment->forceFill($attributes)->save();
        } else {
            $enrollment = Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id] + $attributes);
        }

        app(XPRewardService::class)->awardEnrollmentXP($user);
        AppNotification::send($user, 'course_access', 'Kursga kirish ochildi', "To'lov qabul qilindi. «{$course->title}» kursi kurslaringizga qo'shildi.");

        return ['enrollment_id' => $enrollment->id, 'already_had_access' => false];
    }

    private function grantCoins(PaymentOrder $order): array
    {
        // the coins sold are fixed on the order; the package may have been edited or deleted since
        $package = CoinPackage::find($order->product_id);
        $coins = (int) ($order->meta['coins'] ?? ($package ? ($package->total_coins ?: ((int) $package->coins + (int) $package->bonus_coins)) : 0));
        $name = $package?->name ?? $order->title;

        // 'coin_package' is exempt from the monthly earning limit (CoinService)
        $added = $this->coins->addCoins($order->user, $coins, 'coin_package', "Coin paketi: {$name}", $order);
        AppNotification::send($order->user, 'coins_purchased', 'Coinlar qo\'shildi', "To'lov qabul qilindi. Hisobingizga {$added} coin qo'shildi.");

        return ['coins' => $added, 'coin_package_id' => $order->product_id];
    }

    private function grantSubscription(PaymentOrder $order): array
    {
        $plan = SubscriptionPlan::findOrFail($order->product_id);
        $user = $order->user;

        $active = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->lockForUpdate()
            ->get();

        $same = $active->firstWhere('plan_id', $plan->id);
        if ($same) {
            // renewal: the paid period is added after the current one
            $previousEnd = $same->ends_at;
            $from = $previousEnd && $previousEnd->isFuture() ? $previousEnd->copy() : now();
            $same->forceFill([
                'ends_at' => $from->add($plan->interval, (int) $plan->interval_count),
                'last_payment_id' => $order->payment_id,
                'auto_renew' => false,
            ])->save();
            $subscription = $same;
        } else {
            // switching plans: the new plan replaces the old one
            foreach ($active as $old) {
                $old->forceFill([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => "Yangi tarifga o'tildi",
                    'auto_renew' => false,
                ])->save();
            }
            $previousEnd = null;
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => now()->add($plan->interval, (int) $plan->interval_count),
                'auto_renew' => false,
                'last_payment_id' => $order->payment_id,
            ]);
        }

        AppNotification::send($user, 'subscription_activated', 'Obuna faollashtirildi',
            "«{$plan->name}» tarifi {$subscription->ends_at?->format('d.m.Y')} gacha faol.");

        return [
            'subscription_id' => $subscription->id,
            'renewal' => (bool) $same,
            'previous_ends_at' => $previousEnd?->toIso8601String(),
            'replaced' => $same ? [] : $active->pluck('id')->all(),
        ];
    }

    private function grantOlympiad(PaymentOrder $order, ?PaymentTransaction $transaction): array
    {
        /** @var OlympiadRegistration $registration */
        $registration = OlympiadRegistration::whereKey($order->product_id)->lockForUpdate()->firstOrFail();

        if ($registration->status !== OlympiadRegistration::STATUS_PENDING_PAYMENT) {
            return ['registration_id' => $registration->id, 'already_confirmed' => true];
        }

        $registration->forceFill([
            'payment_method' => $order->provider,
            'payment_cash' => $order->amount,
            'payment_transaction_id' => $transaction?->provider_transaction_id,
        ])->save();

        $olympiadPayment = OlympiadPaymentTransaction::create([
            'user_id' => $registration->user_id,
            'registration_id' => $registration->id,
            'payment_type' => OlympiadPaymentTransaction::TYPE_OLYMPIAD_REGISTRATION,
            'payment_method' => $order->provider,
            'amount' => $order->amount,
            'coins_amount' => 0,
            'status' => OlympiadPaymentTransaction::STATUS_PENDING,
        ]);
        // confirms the registration (status confirmed, payment_status completed, paid_at)
        $olympiadPayment->complete($transaction?->provider_transaction_id);

        AppNotification::send($order->user, 'olympiad_registration', "Olimpiadaga ro'yxatdan o'tildi",
            "To'lov qabul qilindi. «{$registration->olympiad?->title}» olimpiadasida ishtirokingiz tasdiqlandi.");

        return ['registration_id' => $registration->id, 'olympiad_payment_id' => $olympiadPayment->id, 'already_confirmed' => false];
    }

    /**
     * Paid exams: the student's entry to the session (one row per student and session).
     * A second paid order for the same entry (paid with both providers) is noted as a duplicate
     * and listed for a manual refund; a session cancelled meanwhile is listed for a refund at once.
     */
    private function grantExamSession(PaymentOrder $order): array
    {
        $user = $order->user;
        $session = ExamSession::withTrashed()->find($order->product_id);

        $purchase = ExamSessionPurchase::query()
            ->where('session_id', $order->product_id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($purchase && $purchase->status !== ExamSessionPurchase::STATUS_REFUNDED) {
            Log::warning('payments.exam_session.duplicate', ['order' => $order->id, 'purchase' => $purchase->id]);

            return ['purchase_id' => $purchase->id, 'session_id' => $order->product_id, 'duplicate' => true];
        }

        $cancelled = ! $session || $session->trashed() || $session->status === ExamSessionStatus::Cancelled;
        $attributes = [
            'payment_order_id' => $order->id,
            'amount' => (int) round((float) $order->amount),
            'provider' => $order->provider,
            'status' => $cancelled ? ExamSessionPurchase::STATUS_REFUND_PENDING : ExamSessionPurchase::STATUS_PAID,
            'paid_at' => now(),
            'refund_requested_at' => $cancelled ? now() : null,
            'refunded_at' => null,
            'refunded_by' => null,
            'refund_note' => null,
        ];

        if ($purchase) {
            $purchase->forceFill($attributes)->save();
        } else {
            $purchase = ExamSessionPurchase::create(['session_id' => $order->product_id, 'user_id' => $user->id] + $attributes);
        }

        $title = $session?->title ?? $order->title;
        AppNotification::send($user, 'exam_purchase', $cancelled ? "Imtihon bekor qilingan" : 'Imtihonga kirish ochildi', $cancelled
            ? "To'lov qabul qilindi, lekin «{$title}» bekor qilingan. Pul qaytariladi."
            : "To'lov qabul qilindi. «{$title}» imtihoniga kirish ochiq.");

        return ['purchase_id' => $purchase->id, 'session_id' => $order->product_id, 'duplicate' => false];
    }

    private function recordPromoUsage(PaymentOrder $order): void
    {
        if (! $order->promo_code_id || ! $order->payment_id) {
            return;
        }
        if (PromoCodeUsage::where('promo_code_id', $order->promo_code_id)->where('payment_id', $order->payment_id)->exists()) {
            return;
        }

        PromoCodeUsage::create([
            'promo_code_id' => $order->promo_code_id,
            'user_id' => $order->user_id,
            'payment_id' => $order->payment_id,
            'discount_amount' => $order->discount_amount,
        ]);
        PromoCode::whereKey($order->promo_code_id)->increment('usage_count');
    }

    // ----------------------------------------------------------------- revoke

    private function revokeCourse(array $granted): void
    {
        if (($granted['already_had_access'] ?? false) || empty($granted['enrollment_id'])) {
            return;
        }

        $enrollment = Enrollment::find($granted['enrollment_id']);
        if ($enrollment) {
            $enrollment->forceFill(['status' => 'refunded', 'payment_status' => 'refunded'])->save();
            $enrollment->delete(); // soft delete: access is closed, progress kept
        }
    }

    private function revokeCoins(PaymentOrder $order, array $granted): void
    {
        $coins = (int) ($granted['coins'] ?? 0);
        if ($coins > 0 && ! $this->coins->spendCoins($order->user, $coins, 'coin_package_refund', "Coin paketi qaytarildi (#{$order->id})", $order)) {
            throw new \DomainException('coins already spent');
        }
    }

    private function revokeSubscription(array $granted): void
    {
        $subscription = Subscription::find($granted['subscription_id'] ?? null);
        if (! $subscription) {
            return;
        }

        if (($granted['renewal'] ?? false) && ! empty($granted['previous_ends_at'])) {
            $subscription->forceFill(['ends_at' => \Illuminate\Support\Carbon::parse($granted['previous_ends_at'])])->save();

            return;
        }

        $subscription->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => now(),
            'cancellation_reason' => "To'lov qaytarildi",
            'auto_renew' => false,
        ])->save();
    }

    private function revokeOlympiad(array $granted): void
    {
        if (($granted['already_confirmed'] ?? false) || empty($granted['registration_id'])) {
            return;
        }

        OlympiadRegistration::whereKey($granted['registration_id'])->update([
            'status' => OlympiadRegistration::STATUS_REFUNDED,
            'payment_status' => 'refunded',
        ]);
        if (! empty($granted['olympiad_payment_id'])) {
            OlympiadPaymentTransaction::whereKey($granted['olympiad_payment_id'])->update([
                'status' => OlympiadPaymentTransaction::STATUS_REFUNDED,
                'status_message' => "To'lov qaytarildi",
            ]);
        }
    }

    private function revokeExamSession(array $granted, string $reason): void
    {
        if (($granted['duplicate'] ?? false) || empty($granted['purchase_id'])) {
            return; // the entry belongs to another order
        }

        ExamSessionPurchase::whereKey($granted['purchase_id'])
            ->where('status', '!=', ExamSessionPurchase::STATUS_REFUNDED)
            ->update([
                'status' => ExamSessionPurchase::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refund_note' => mb_substr($reason, 0, 255),
                'updated_at' => now(),
            ]);
    }

    /** The student started the session with this entry (an invalidated attempt — cancelled session — does not count). */
    private function examSessionStarted(PaymentOrder $order, array $granted): bool
    {
        if ($granted['duplicate'] ?? false) {
            return false;
        }

        return ExamAttempt::query()
            ->where('session_id', $order->product_id)
            ->where('user_id', $order->user_id)
            ->where('status', '!=', AttemptStatus::Invalidated->value)
            ->exists();
    }

    private function olympiadStarted(array $granted): bool
    {
        if (empty($granted['registration_id']) || ($granted['already_confirmed'] ?? false)) {
            return false;
        }

        $registration = OlympiadRegistration::find($granted['registration_id']);

        return $registration && $registration->attempt()->exists();
    }
}
