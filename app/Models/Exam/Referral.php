<?php

namespace App\Models\Exam;

use App\Enums\Exam\ReferralStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * referrer invited referred (a user can be referred only once).
 * Funnel: joined -> placement_done -> subscribed; status never moves back.
 */
class Referral extends Model
{
    use HasFactory, HasUuids;

    private const ORDER = [
        'joined' => 0,
        'placement_done' => 1,
        'subscribed' => 2,
    ];

    protected $table = 'referrals';

    protected $fillable = [
        'referral_code_id',
        'referrer_id',
        'referred_id',
        'status',
        'joined_at',
        'placement_done_at',
        'subscribed_at',
        'reward_granted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'joined_at' => 'datetime',
            'placement_done_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'reward_granted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Referral $referral) {
            if ($referral->referrer_id === $referral->referred_id) {
                throw new \InvalidArgumentException('A user can not refer themselves.');
            }
            $original = $referral->getOriginal('status');
            if ($referral->exists && $original !== null && $referral->status !== null) {
                $from = $original instanceof ReferralStatus ? $original->value : $original;
                if (self::ORDER[$referral->status->value] < self::ORDER[$from]) {
                    throw new \InvalidArgumentException('Referral status can not move backwards.');
                }
            }
        });
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /** Advance the funnel, stamping the matching timestamp. */
    public function advanceTo(ReferralStatus $status): void
    {
        if (self::ORDER[$status->value] <= self::ORDER[$this->status->value]) {
            return;
        }
        $this->status = $status;
        $column = match ($status) {
            ReferralStatus::Joined => 'joined_at',
            ReferralStatus::PlacementDone => 'placement_done_at',
            ReferralStatus::Subscribed => 'subscribed_at',
        };
        $this->{$column} ??= now();
        $this->save();
    }
}
