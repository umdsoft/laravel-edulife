<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishLeaderboardEntry extends Model
{
    use HasUuids;

    protected $table = 'english_leaderboard_entries';

    protected $fillable = [
        'leaderboard_id',
        'user_id',
        'score',
        'previous_score',
        'position',
        'previous_position',
        'position_change',
        'period_date',
        'stats',
        'reward_claimed',
        'reward_claimed_at',
        'last_updated_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'previous_score' => 'integer',
        'position' => 'integer',
        'previous_position' => 'integer',
        'position_change' => 'integer',
        'period_date' => 'date',
        'stats' => 'array',
        'reward_claimed' => 'boolean',
        'reward_claimed_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(EnglishLeaderboard::class, 'leaderboard_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updateScore(int $newScore): void
    {
        $this->previous_score = $this->score;
        $this->score = $newScore;
        $this->last_updated_at = now();
        $this->save();
    }
}
