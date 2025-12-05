<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadPrize extends Model
{
    use HasFactory, HasUuids;

    // Prize type constants
    public const TYPE_CASH = 'cash';
    public const TYPE_COINS = 'coins';
    public const TYPE_DISCOUNT_COUPON = 'discount_coupon';
    public const TYPE_BADGE = 'badge';
    public const TYPE_CERTIFICATE = 'certificate';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'attempt_id',
        'rank',
        'prize_type',
        'cash_amount',
        'coins_amount',
        'coupon_id',
        'badge_id',
        'certificate_id',
        'status',
        'awarded_at',
        'delivered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'cash_amount' => 'decimal:2',
            'coins_amount' => 'integer',
            'awarded_at' => 'datetime',
            'delivered_at' => 'datetime',
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

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(OlympiadCertificate::class, 'certificate_id');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('prize_type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getIsDeliveredAttribute(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function getPrizeValueAttribute(): string
    {
        switch ($this->prize_type) {
            case self::TYPE_CASH:
                return number_format($this->cash_amount, 0, '', ' ') . ' UZS';
            case self::TYPE_COINS:
                return number_format($this->coins_amount) . ' COIN';
            case self::TYPE_DISCOUNT_COUPON:
                return $this->coupon?->formatted_discount ?? 'Chegirma kuponi';
            case self::TYPE_BADGE:
                return 'Yutuq nishoni';
            case self::TYPE_CERTIFICATE:
                return 'Sertifikat';
            default:
                return '-';
        }
    }

    public function getPrizeTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_CASH => 'Pul mukofoti',
            self::TYPE_COINS => 'Tanga mukofoti',
            self::TYPE_DISCOUNT_COUPON => 'Chegirma kuponi',
            self::TYPE_BADGE => 'Yutuq nishoni',
            self::TYPE_CERTIFICATE => 'Sertifikat',
        ];
        return $labels[$this->prize_type] ?? $this->prize_type;
    }

    // ==================== METHODS ====================

    /**
     * Deliver the prize
     */
    public function deliver(): bool
    {
        try {
            $this->status = self::STATUS_PROCESSING;
            $this->save();

            switch ($this->prize_type) {
                case self::TYPE_COINS:
                    $this->user->increment('coins', $this->coins_amount);
                    break;
                    
                case self::TYPE_CASH:
                    // Cash prizes need manual processing
                    // Just mark as delivered for now
                    break;
                    
                case self::TYPE_DISCOUNT_COUPON:
                    // Coupon already created
                    break;
            }

            $this->status = self::STATUS_DELIVERED;
            $this->delivered_at = now();
            $this->save();

            return true;
        } catch (\Exception $e) {
            $this->status = self::STATUS_FAILED;
            $this->notes = $e->getMessage();
            $this->save();
            
            return false;
        }
    }

    /**
     * Create prizes for olympiad winners
     */
    public static function createForWinners(Olympiad $olympiad): void
    {
        $stage = $olympiad->stage;
        $rewardConfig = $olympiad->reward_config ?? $stage?->getDefaultRewardConfig() ?? [];

        $leaderboard = OlympiadLiveLeaderboard::where('olympiad_id', $olympiad->id)
            ->where('is_disqualified', false)
            ->orderBy('rank')
            ->get();

        foreach ($leaderboard as $entry) {
            $reward = $stage?->getRewardForRank($entry->rank) ?? [];
            
            if (empty($reward)) {
                continue;
            }

            // Create coins prize
            if (isset($reward['bonus_coins']) && $reward['bonus_coins'] > 0) {
                self::create([
                    'olympiad_id' => $olympiad->id,
                    'user_id' => $entry->user_id,
                    'attempt_id' => $entry->attempt_id,
                    'rank' => $entry->rank,
                    'prize_type' => self::TYPE_COINS,
                    'coins_amount' => $reward['bonus_coins'],
                    'status' => self::STATUS_PENDING,
                    'awarded_at' => now(),
                ]);
            }

            // Create cash prize
            if (isset($reward['cash']) && $reward['cash'] > 0) {
                self::create([
                    'olympiad_id' => $olympiad->id,
                    'user_id' => $entry->user_id,
                    'attempt_id' => $entry->attempt_id,
                    'rank' => $entry->rank,
                    'prize_type' => self::TYPE_CASH,
                    'cash_amount' => $reward['cash'],
                    'status' => self::STATUS_PENDING,
                    'awarded_at' => now(),
                ]);
            }

            // Create discount coupon for next stage
            if (isset($reward['discount_percent']) && $reward['discount_percent'] > 0) {
                $coupon = DiscountCoupon::createAdvancementCoupon(
                    $entry->user_id,
                    $olympiad->id,
                    $entry->rank,
                    $reward['discount_percent'],
                    $olympiad->next_olympiad_id
                );

                self::create([
                    'olympiad_id' => $olympiad->id,
                    'user_id' => $entry->user_id,
                    'attempt_id' => $entry->attempt_id,
                    'rank' => $entry->rank,
                    'prize_type' => self::TYPE_DISCOUNT_COUPON,
                    'coupon_id' => $coupon->id,
                    'status' => self::STATUS_DELIVERED,
                    'awarded_at' => now(),
                    'delivered_at' => now(),
                ]);
            }
        }
    }
}
