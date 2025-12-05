<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiscountCoupon extends Model
{
    use HasFactory, HasUuids;

    // Discount type constants
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_COINS = 'coins';

    // Reason constants
    public const REASON_WINNER_ADVANCEMENT = 'winner_advancement';
    public const REASON_PARTICIPANT_REWARD = 'participant_reward';
    public const REASON_PROMO = 'promo';
    public const REASON_REFERRAL = 'referral';
    public const REASON_ADMIN = 'admin';
    public const REASON_SPECIAL = 'special';

    protected $fillable = [
        'code',
        'olympiad_id',
        'series_id',
        'user_id',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'reason',
        'source_olympiad_id',
        'source_rank',
        'valid_from',
        'valid_until',
        'max_uses',
        'times_used',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'source_rank' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(OlympiadSeries::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceOlympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class, 'source_olympiad_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(OlympiadRegistration::class, 'coupon_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now)
            ->whereRaw('times_used < max_uses');
    }

    public function scopeForOlympiad($query, string $olympiadId)
    {
        return $query->where(function ($q) use ($olympiadId) {
            $q->where('olympiad_id', $olympiadId)
              ->orWhereNull('olympiad_id');
        });
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id');
        });
    }

    // ==================== ACCESSORS ====================

    public function getIsValidAttribute(): bool
    {
        $now = now();
        return $this->is_active
            && $now->gte($this->valid_from)
            && $now->lte($this->valid_until)
            && $this->times_used < $this->max_uses;
    }

    public function getIsExpiredAttribute(): bool
    {
        return now()->gt($this->valid_until);
    }

    public function getIsExhaustedAttribute(): bool
    {
        return $this->times_used >= $this->max_uses;
    }

    public function getIsPersonalAttribute(): bool
    {
        return $this->user_id !== null;
    }

    public function getRemainingUsesAttribute(): int
    {
        return max(0, $this->max_uses - $this->times_used);
    }

    public function getFormattedDiscountAttribute(): string
    {
        switch ($this->discount_type) {
            case self::TYPE_PERCENT:
                return $this->discount_value . '%';
            case self::TYPE_FIXED:
                return number_format($this->discount_value, 0, '', ' ') . ' UZS';
            case self::TYPE_COINS:
                return number_format($this->discount_value) . ' COIN';
            default:
                return (string) $this->discount_value;
        }
    }

    // ==================== METHODS ====================

    /**
     * Generate unique coupon code
     */
    public static function generateCode(string $prefix = 'OLY'): string
    {
        $code = $prefix . '-' . strtoupper(Str::random(8));
        
        while (self::where('code', $code)->exists()) {
            $code = $prefix . '-' . strtoupper(Str::random(8));
        }
        
        return $code;
    }

    /**
     * Calculate discount amount for a price
     */
    public function calculateDiscount(float $originalPrice): float
    {
        switch ($this->discount_type) {
            case self::TYPE_PERCENT:
                $discount = $originalPrice * ($this->discount_value / 100);
                if ($this->max_discount_amount !== null) {
                    $discount = min($discount, $this->max_discount_amount);
                }
                return round($discount, 2);
                
            case self::TYPE_FIXED:
                return min($this->discount_value, $originalPrice);
                
            case self::TYPE_COINS:
                // Coins discount - calculate equivalent value
                // Assuming 1 coin = 100 UZS
                $coinsValue = $this->discount_value * 100;
                return min($coinsValue, $originalPrice);
                
            default:
                return 0;
        }
    }

    /**
     * Check if coupon can be used for an olympiad
     */
    public function canBeUsedFor(string $olympiadId, string $userId): bool
    {
        if (!$this->is_valid) {
            return false;
        }

        // Check olympiad restriction
        if ($this->olympiad_id !== null && $this->olympiad_id !== $olympiadId) {
            return false;
        }

        // Check user restriction
        if ($this->user_id !== null && $this->user_id !== $userId) {
            return false;
        }

        return true;
    }

    /**
     * Use the coupon
     */
    public function use(): bool
    {
        if (!$this->is_valid) {
            return false;
        }

        $this->times_used++;
        $this->save();

        return true;
    }

    /**
     * Create advancement coupon for a winner
     */
    public static function createAdvancementCoupon(
        string $userId,
        string $olympiadId,
        int $rank,
        float $discountPercent,
        ?string $nextOlympiadId = null
    ): self {
        return self::create([
            'code' => self::generateCode('WIN'),
            'olympiad_id' => $nextOlympiadId,
            'user_id' => $userId,
            'discount_type' => self::TYPE_PERCENT,
            'discount_value' => $discountPercent,
            'reason' => self::REASON_WINNER_ADVANCEMENT,
            'source_olympiad_id' => $olympiadId,
            'source_rank' => $rank,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(3),
            'max_uses' => 1,
            'is_active' => true,
        ]);
    }
}
