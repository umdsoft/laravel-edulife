<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishRewardClaim extends Model
{
    use HasUuids;

    protected $table = 'english_reward_claims';

    protected $fillable = [
        'user_id',
        'reward_id',
        'xp_claimed',
        'coins_claimed',
        'gems_claimed',
        'items_claimed',
        'claim_source',
        'source_id',
        'claimed_at',
    ];

    protected $casts = [
        'xp_claimed' => 'integer',
        'coins_claimed' => 'integer',
        'gems_claimed' => 'integer',
        'items_claimed' => 'array',
        'claimed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(EnglishReward::class, 'reward_id');
    }
}
