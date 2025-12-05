<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OlympiadRegistration extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_DISQUALIFIED = 'disqualified';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_ADVANCED = 'advanced';
    public const STATUS_COMPLETED = 'completed';

    // Payment method constants
    public const PAYMENT_PAYME = 'payme';
    public const PAYMENT_CLICK = 'click';
    public const PAYMENT_COINS = 'coins';
    public const PAYMENT_FREE = 'free';
    public const PAYMENT_COUPON = 'coupon';
    public const PAYMENT_MIXED = 'mixed';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'status',
        'advanced_from_olympiad_id',
        'advanced_from_rank',
        'coupon_id',
        'discount_percent',
        'discount_amount',
        'original_price',
        'final_price',
        'payment_method',
        'payment_coins',
        'payment_cash',
        'payment_status',
        'payment_transaction_id',
        'paid_at',
        'school_id',
        'grade_level',
        'demo_purchased',
        'demo_attempts_used',
        'demo_best_score',
        'registered_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'advanced_from_rank' => 'integer',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'original_price' => 'decimal:2',
            'final_price' => 'decimal:2',
            'payment_coins' => 'integer',
            'payment_cash' => 'decimal:2',
            'grade_level' => 'integer',
            'demo_purchased' => 'boolean',
            'demo_attempts_used' => 'integer',
            'demo_best_score' => 'decimal:2',
            'paid_at' => 'datetime',
            'registered_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function advancedFromOlympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class, 'advanced_from_olympiad_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function attempt(): HasOne
    {
        return $this->hasOne(OlympiadAttempt::class, 'registration_id');
    }

    public function demoAttempts(): HasMany
    {
        return $this->hasMany(DemoAttempt::class, 'registration_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(OlympiadPaymentTransaction::class, 'registration_id');
    }

    // ==================== SCOPES ====================

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_CONFIRMED,
            self::STATUS_ADVANCED,
            self::STATUS_COMPLETED,
        ]);
    }

    public function scopeForOlympiad($query, string $olympiadId)
    {
        return $query->where('olympiad_id', $olympiadId);
    }

    // ==================== ACCESSORS ====================

    public function getIsConfirmedAttribute(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'completed' || $this->final_price == 0;
    }

    public function getCanStartExamAttribute(): bool
    {
        return $this->is_confirmed
            && $this->is_paid
            && !in_array($this->status, [self::STATUS_DISQUALIFIED, self::STATUS_NO_SHOW]);
    }

    public function getHasUsedDemoAttribute(): bool
    {
        return $this->demo_attempts_used > 0;
    }

    public function getCanUseDemoAttribute(): bool
    {
        if (!$this->demo_purchased && !$this->hasFreeDemo()) {
            return false;
        }

        $maxAttempts = $this->olympiad->demo_config['max_attempts'] ?? 3;
        return $this->demo_attempts_used < $maxAttempts;
    }

    public function getRemainingDemoAttemptsAttribute(): int
    {
        $maxAttempts = $this->olympiad->demo_config['max_attempts'] ?? 3;
        return max(0, $maxAttempts - $this->demo_attempts_used);
    }

    public function getIsAdvancedAttribute(): bool
    {
        return $this->advanced_from_olympiad_id !== null;
    }

    // ==================== METHODS ====================

    /**
     * Check if user has free demo attempt
     */
    public function hasFreeDemo(): bool
    {
        $freeAttempts = $this->olympiad->demo_config['free_attempts'] ?? 1;
        return $this->demo_attempts_used < $freeAttempts;
    }

    /**
     * Apply coupon to registration
     */
    public function applyCoupon(DiscountCoupon $coupon): bool
    {
        if (!$coupon->canBeUsedFor($this->olympiad_id, $this->user_id)) {
            return false;
        }

        $discount = $coupon->calculateDiscount($this->original_price);
        
        $this->coupon_id = $coupon->id;
        $this->discount_amount = $discount;
        $this->discount_percent = ($discount / $this->original_price) * 100;
        $this->final_price = max(0, $this->original_price - $discount);
        $this->save();

        return true;
    }

    /**
     * Confirm registration after payment
     */
    public function confirm(): void
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->payment_status = 'completed';
        $this->paid_at = now();
        $this->confirmed_at = now();
        $this->save();

        // Use coupon if applied
        if ($this->coupon) {
            $this->coupon->use();
        }
    }

    /**
     * Cancel registration
     */
    public function cancel(string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * Mark as disqualified
     */
    public function disqualify(): void
    {
        $this->status = self::STATUS_DISQUALIFIED;
        $this->save();
    }

    /**
     * Increment demo usage
     */
    public function useDemo(): bool
    {
        if (!$this->can_use_demo) {
            return false;
        }

        $this->demo_attempts_used++;
        $this->save();

        return true;
    }

    /**
     * Update best demo score
     */
    public function updateDemoBestScore(float $score): void
    {
        if ($this->demo_best_score === null || $score > $this->demo_best_score) {
            $this->demo_best_score = $score;
            $this->save();
        }
    }
}
