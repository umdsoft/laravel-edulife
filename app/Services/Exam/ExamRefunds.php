<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamSessionPurchase;
use App\Models\Notification as AppNotification;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Paid exams: money to return by hand. Provider refund APIs are never called automatically
 * (QARORLAR: "Pullik imtihonlar"):
 *   - the teacher cancels a session  -> its paid entries become refund_pending,
 *   - an entry paid twice (both Payme and Click went through) -> the second order is listed too,
 *   - the admin returns the money (Payme / Click cabinet, cash, ...) and presses "Qaytarildi".
 * A Payme cancel after perform (CancelTransaction, state -2) also closes the entry, through
 * PaymentFulfillmentService::reverse().
 */
class ExamRefunds
{
    /** @return int entries marked */
    public function markSessionCancelled(ExamSession $session): int
    {
        $purchases = ExamSessionPurchase::query()->where('session_id', $session->id)->paid()->with('user')->get();
        if ($purchases->isEmpty()) {
            return 0;
        }

        ExamSessionPurchase::query()->whereKey($purchases->modelKeys())->paid()->update([
            'status' => ExamSessionPurchase::STATUS_REFUND_PENDING,
            'refund_requested_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($purchases as $purchase) {
            if ($purchase->user) {
                AppNotification::send($purchase->user, 'exam_refund', 'Imtihon bekor qilindi',
                    "«{$session->title}» bekor qilindi. To'lagan ".number_format($purchase->amount, 0, '', ' ')." so'mingiz qaytariladi.");
            }
        }
        Log::info('exam.refunds.session_cancelled', ['session' => $session->id, 'entries' => $purchases->count()]);

        return $purchases->count();
    }

    /**
     * What the admin has to return, oldest first.
     *
     * @return array{pending: Collection<int, array>, refunded: Collection<int, array>}
     */
    public function lists(int $refundedLimit = 50): array
    {
        $pending = ExamSessionPurchase::query()
            ->where('status', ExamSessionPurchase::STATUS_REFUND_PENDING)
            ->with(['user:id,first_name,last_name,phone', 'session:id,title,starts_at,status', 'order:id,uuid,provider,payment_id'])
            ->orderBy('refund_requested_at')
            ->get()
            ->map(fn (ExamSessionPurchase $p) => $this->purchaseRow($p));

        $duplicates = PaymentOrder::query()
            ->where('product_type', PaymentOrder::TYPE_EXAM_SESSION)
            ->where('status', PaymentOrder::STATUS_PAID)
            ->with('user:id,first_name,last_name,phone')
            ->orderBy('paid_at')
            ->get()
            ->filter(fn (PaymentOrder $o) => (bool) ($o->fulfilment['duplicate'] ?? false))
            ->map(fn (PaymentOrder $o) => $this->duplicateRow($o));

        $refunded = ExamSessionPurchase::query()
            ->where('status', ExamSessionPurchase::STATUS_REFUNDED)
            ->with(['user:id,first_name,last_name,phone', 'session:id,title,starts_at,status', 'order:id,uuid,provider,payment_id'])
            ->latest('refunded_at')
            ->limit($refundedLimit)
            ->get()
            ->map(fn (ExamSessionPurchase $p) => $this->purchaseRow($p));

        return ['pending' => $pending->concat($duplicates)->values(), 'refunded' => $refunded->values()];
    }

    public function pendingCount(): int
    {
        return ExamSessionPurchase::query()->where('status', ExamSessionPurchase::STATUS_REFUND_PENDING)->count();
    }

    /** "Qaytarildi" on an entry of a cancelled session. Idempotent. */
    public function markRefunded(ExamSessionPurchase $purchase, User $admin, ?string $note = null): bool
    {
        return DB::transaction(function () use ($purchase, $admin, $note) {
            $locked = ExamSessionPurchase::query()->whereKey($purchase->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== ExamSessionPurchase::STATUS_REFUND_PENDING) {
                return false;
            }

            $locked->forceFill([
                'status' => ExamSessionPurchase::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refunded_by' => $admin->id,
                'refund_note' => $note !== null ? mb_substr($note, 0, 255) : null,
            ])->save();

            if ($locked->payment_order_id) {
                $this->closeOrder(PaymentOrder::query()->find($locked->payment_order_id), $note);
            }

            return true;
        });
    }

    /** "Qaytarildi" on the second paid order of an entry. Idempotent. */
    public function markDuplicateRefunded(PaymentOrder $order, ?string $note = null): bool
    {
        if ($order->product_type !== PaymentOrder::TYPE_EXAM_SESSION || ! ($order->fulfilment['duplicate'] ?? false)) {
            return false;
        }

        return DB::transaction(fn () => $this->closeOrder(PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->first(), $note));
    }

    /** The order and its ledger row say "refunded" (money returned outside the provider callbacks). */
    private function closeOrder(?PaymentOrder $order, ?string $note): bool
    {
        if (! $order || $order->status !== PaymentOrder::STATUS_PAID) {
            return false;
        }

        $order->forceFill(['status' => PaymentOrder::STATUS_REFUNDED, 'refunded_at' => now()])->save();
        if ($order->payment_id) {
            Payment::whereKey($order->payment_id)->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_amount' => $order->amount,
                'refund_reason' => mb_substr('Qo\'lda qaytarildi'.($note ? ": {$note}" : ''), 0, 250),
            ]);
        }
        Log::info('exam.refunds.marked', ['order' => $order->id]);

        return true;
    }

    private function purchaseRow(ExamSessionPurchase $p): array
    {
        return [
            'key' => 'p-'.$p->id,
            'kind' => 'cancelled',
            'id' => $p->id,
            'student' => $this->name($p->user),
            'phone' => $p->user?->phone,
            'session' => $p->session?->title,
            'session_starts_at' => $p->session?->starts_at?->toIso8601String(),
            'amount' => (int) $p->amount,
            'provider' => $p->provider,
            'order_id' => $p->order?->id,
            'payment_id' => $p->order?->payment_id,
            'paid_at' => $p->paid_at?->toIso8601String(),
            'requested_at' => $p->refund_requested_at?->toIso8601String(),
            'refunded_at' => $p->refunded_at?->toIso8601String(),
            'note' => $p->refund_note,
        ];
    }

    private function duplicateRow(PaymentOrder $o): array
    {
        return [
            'key' => 'o-'.$o->id,
            'kind' => 'duplicate',
            'id' => $o->id,
            'student' => $this->name($o->user),
            'phone' => $o->user?->phone,
            'session' => preg_replace('/^Imtihon: /u', '', (string) $o->title),
            'session_starts_at' => null,
            'amount' => (int) round((float) $o->amount),
            'provider' => $o->provider,
            'order_id' => $o->id,
            'order_uuid' => $o->uuid,
            'payment_id' => $o->payment_id,
            'paid_at' => $o->paid_at?->toIso8601String(),
            'requested_at' => $o->fulfilled_at?->toIso8601String(),
            'refunded_at' => null,
            'note' => "Ikki marta to'langan",
        ];
    }

    private function name(?User $user): string
    {
        return $user ? (trim($user->first_name.' '.$user->last_name) ?: '—') : '—';
    }
}
