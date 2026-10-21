<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An online purchase: one product, one provider, an amount fixed by the server.
 * The numeric id is the order number given to Payme (ac.order_id) and Click (merchant_trans_id).
 */
class PaymentOrder extends Model
{
    public const TYPE_COURSE = 'course';
    public const TYPE_COIN_PACKAGE = 'coin_package';
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_OLYMPIAD = 'olympiad_registration';
    /** paid exams: entry to one Saturday exam session (exam_session_purchases) */
    public const TYPE_EXAM_SESSION = 'exam_session';

    public const TYPES = [self::TYPE_COURSE, self::TYPE_COIN_PACKAGE, self::TYPE_SUBSCRIPTION, self::TYPE_OLYMPIAD, self::TYPE_EXAM_SESSION];

    public const PROVIDER_PAYME = 'payme';
    public const PROVIDER_CLICK = 'click';
    public const PROVIDER_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    /** payments.type for each product */
    public const PAYMENT_TYPES = [
        self::TYPE_COURSE => 'course_purchase',
        self::TYPE_COIN_PACKAGE => 'coin_package',
        self::TYPE_SUBSCRIPTION => 'subscription',
        self::TYPE_OLYMPIAD => 'olympiad_registration',
        self::TYPE_EXAM_SESSION => 'exam_session',
    ];

    protected $fillable = [
        'uuid', 'user_id', 'product_type', 'product_id', 'title', 'provider',
        'original_amount', 'discount_amount', 'amount', 'amount_tiyin', 'currency',
        'promo_code_id', 'payment_id', 'status',
        'paid_at', 'fulfilled_at', 'refunded_at', 'cancelled_at', 'fulfilment', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'amount_tiyin' => 'integer',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'fulfilment' => 'array',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentOrder $order) {
            $order->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * What the student is told: paid | refunded | cancelled | processing (provider transaction open) | pending.
     * The callback may arrive a few seconds after the student returns.
     */
    public function publicStatus(): string
    {
        $latest = $this->transactions->sortByDesc('id')->first();

        return match (true) {
            $this->status === self::STATUS_PAID => 'paid',
            $this->status === self::STATUS_REFUNDED => 'refunded',
            $this->status === self::STATUS_CANCELLED => 'cancelled',
            $latest?->state === PaymentTransaction::STATE_CREATED => 'processing',
            $latest !== null && $latest->state < 0 => 'cancelled',
            default => 'pending',
        };
    }

    /** "12 000.00" as Click expects (format N.NN) */
    public function amountForClick(): string
    {
        return number_format($this->amount_tiyin / 100, 2, '.', '');
    }
}
