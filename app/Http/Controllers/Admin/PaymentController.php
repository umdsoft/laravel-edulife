<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /** Payme / Click transaction states (Payme codes; Click uses 1, 2, -1). */
    private const STATE_LABELS = [
        PaymentTransaction::STATE_CREATED => 'Yaratilgan',
        PaymentTransaction::STATE_PERFORMED => "O'tkazilgan",
        PaymentTransaction::STATE_CANCELLED => 'Bekor qilingan',
        PaymentTransaction::STATE_CANCELLED_AFTER_PERFORM => "O'tkazilgandan keyin bekor qilingan",
    ];

    private const REASON_LABELS = [
        1 => 'Qabul qiluvchi topilmadi',
        2 => 'Debet operatsiyasida xato',
        3 => 'Tranzaksiyani bajarishda xato',
        4 => "Vaqt tugadi (12 soat)",
        5 => 'Pul qaytarildi',
        10 => "Noma'lum xato",
    ];

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        $query = Payment::with(['user', 'order']);

        // Search: user name, provider transaction id or order number
        if ($search = trim((string) $request->get('search'))) {
            $orderNumber = ltrim($search, '#№');
            $query->where(function ($q) use ($search, $orderNumber) {
                $q->where('provider_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                if (ctype_digit($orderNumber)) {
                    $q->orWhereHas('order', fn ($oq) => $oq->where('id', (int) $orderNumber));
                }
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        $payments = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment) => [
                'id' => $payment->id,
                'order_id' => $payment->order?->id,
                'title' => $payment->order?->title,
                'transaction_id' => $payment->provider_transaction_id,
                'provider' => $payment->provider,
                'user_name' => $payment->user ? $payment->user->first_name.' '.$payment->user->last_name : 'N/A',
                'amount' => $payment->amount,
                'type' => $payment->type,
                'status' => $payment->status,
                'created_at' => $payment->created_at->format('d.m.Y H:i'),
            ]);

        $summary = [
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            'total_count' => Payment::count(),
            'payme_amount' => Payment::where('status', 'completed')->where('provider', 'payme')->sum('amount'),
            'click_amount' => Payment::where('status', 'completed')->where('provider', 'click')->sum('amount'),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status', 'type', 'provider']),
            'examRefundsPending' => app(\App\Services\Exam\ExamRefunds::class)->pendingCount(),
        ]);
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): Response
    {
        $payment->load(['user', 'course', 'subscriptionPlan', 'promoCode', 'order.transactions']);
        $order = $payment->order;

        return Inertia::render('Admin/Payments/Show', [
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->provider_transaction_id,
                'amount' => $payment->amount,
                'original_amount' => $payment->original_amount,
                'discount_amount' => $payment->discount_amount,
                'type' => $payment->type,
                'status' => $payment->status,
                'payment_method' => $payment->provider,
                'paid_at' => $payment->paid_at?->format('d.m.Y H:i'),
                'refunded_at' => $payment->refunded_at?->format('d.m.Y H:i'),
                'refund_reason' => $payment->refund_reason,
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'full_name' => $payment->user->first_name.' '.$payment->user->last_name,
                    'phone' => $payment->user->phone,
                    'email' => $payment->user->email,
                ] : null,
                'course' => $payment->course ? [
                    'id' => $payment->course->id,
                    'title' => $payment->course->title,
                ] : null,
                'subscription_plan' => $payment->subscriptionPlan ? [
                    'id' => $payment->subscriptionPlan->id,
                    'name' => $payment->subscriptionPlan->name,
                ] : null,
                'promo_code' => $payment->promoCode ? [
                    'code' => $payment->promoCode->code,
                    'type' => $payment->promoCode->type,
                    'discount' => $payment->promoCode->value,
                ] : null,
                'created_at' => $payment->created_at->format('d.m.Y H:i'),
            ],
            'order' => $order ? [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'title' => $order->title,
                'product_type' => $order->product_type,
                'provider' => $order->provider,
                'status' => $order->status,
                'amount_tiyin' => $order->amount_tiyin,
                'paid_at' => $order->paid_at?->format('d.m.Y H:i:s'),
                'fulfilled_at' => $order->fulfilled_at?->format('d.m.Y H:i:s'),
                'refunded_at' => $order->refunded_at?->format('d.m.Y H:i:s'),
                'fulfilment' => $order->fulfilment,
                'transactions' => $order->transactions->sortByDesc('id')->values()->map(fn (PaymentTransaction $t) => [
                    'id' => $t->id,
                    'provider' => $t->provider,
                    'provider_transaction_id' => $t->provider_transaction_id,
                    'click_paydoc_id' => $t->click_paydoc_id,
                    'state' => $t->state,
                    'state_label' => self::STATE_LABELS[$t->state] ?? (string) $t->state,
                    'reason' => $t->reason,
                    'reason_label' => $t->reason !== null ? (self::REASON_LABELS[$t->reason] ?? (string) $t->reason) : ($t->meta['cancel_note'] ?? null),
                    'amount' => $t->amount_tiyin / 100,
                    'create_time' => $this->msDate($t->create_time),
                    'perform_time' => $this->msDate($t->perform_time),
                    'cancel_time' => $this->msDate($t->cancel_time),
                    'has_fiscal' => ! empty($t->meta['fiscal']),
                    'fiscal_url' => $t->meta['fiscal']['perform_data']['qr_code_url'] ?? null,
                ])->all(),
            ] : null,
        ]);
    }

    private function msDate(?int $ms): ?string
    {
        return $ms ? \Illuminate\Support\Carbon::createFromTimestampMs($ms)->timezone(config('app.timezone'))->format('d.m.Y H:i:s') : null;
    }
}
