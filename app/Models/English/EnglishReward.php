<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishReward extends Model
{
    use HasUuids;

    protected $table = 'english_rewards';

    protected $fillable = [
        'slug',
        'code',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'reward_type',
        'xp_amount',
        'coin_amount',
        'gem_amount',
        'streak_freeze_amount',
        'bonus_items',
        'conditions',
        'available_from',
        'available_until',
        'is_recurring',
        'recurrence_period',
        'icon',
        'image',
        'color',
        'max_claims_per_user',
        'total_available',
        'total_claimed',
        'is_active',
        'order_number',
    ];

    protected $casts = [
        'xp_amount' => 'integer',
        'coin_amount' => 'integer',
        'gem_amount' => 'integer',
        'streak_freeze_amount' => 'integer',
        'bonus_items' => 'array',
        'conditions' => 'array',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'is_recurring' => 'boolean',
        'max_claims_per_user' => 'integer',
        'total_available' => 'integer',
        'total_claimed' => 'integer',
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(EnglishRewardClaim::class, 'reward_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('available_from')->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')->orWhere('available_until', '>=', now());
            });
    }
}
