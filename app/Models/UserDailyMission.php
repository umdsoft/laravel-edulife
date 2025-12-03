<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyMission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'daily_mission_id',
        'mission_date',
        'current_count',
        'target_count',
        'is_completed',
        'is_claimed',
        'xp_rewarded',
        'coin_rewarded',
        'completed_at',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'mission_date' => 'date',
            'is_completed' => 'boolean',
            'is_claimed' => 'boolean',
            'completed_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    // Accessors
    public function getProgressPercentAttribute(): float
    {
        if ($this->target_count == 0) return 100;
        return min(($this->current_count / $this->target_count) * 100, 100);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dailyMission()
    {
        return $this->belongsTo(DailyMission::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->where('mission_date', now()->toDateString());
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeUnclaimed($query)
    {
        return $query->where('is_completed', true)->where('is_claimed', false);
    }

    // Helper Methods
    public function incrementProgress(int $amount = 1): void
    {
        $this->increment('current_count', $amount);
        
        if ($this->current_count >= $this->target_count && !$this->is_completed) {
            $this->markAsCompleted();
        }
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function claim(): void
    {
        if (!$this->is_completed || $this->is_claimed) return;

        $this->update([
            'is_claimed' => true,
            'claimed_at' => now(),
            'xp_rewarded' => $this->dailyMission->xp_reward,
            'coin_rewarded' => $this->dailyMission->coin_reward,
        ]);

        // Award rewards
        $this->user->addXp($this->dailyMission->xp_reward);
        $this->user->addCoins(
            $this->dailyMission->coin_reward,
            'daily_mission',
            "Daily Mission: {$this->dailyMission->title}"
        );
    }
}
