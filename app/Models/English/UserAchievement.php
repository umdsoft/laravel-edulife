<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasUuids;

    protected $table = 'user_achievements';

    protected $fillable = [
        'user_id',
        'achievement_id',
        'current_progress',
        'required_progress',
        'progress_percentage',
        'status',
        'started_at',
        'completed_at',
        'claimed_at',
        'xp_claimed',
        'coins_claimed',
        'gems_claimed',
        'is_featured',
        'show_notification',
    ];

    protected $casts = [
        'current_progress' => 'integer',
        'required_progress' => 'integer',
        'progress_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'claimed_at' => 'datetime',
        'xp_claimed' => 'integer',
        'coins_claimed' => 'integer',
        'gems_claimed' => 'integer',
        'is_featured' => 'boolean',
        'show_notification' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(EnglishAchievement::class, 'achievement_id');
    }

    public function updateProgress(int $newProgress): void
    {
        $this->current_progress = min($newProgress, $this->required_progress);
        $this->progress_percentage = round(($this->current_progress / $this->required_progress) * 100, 2);

        if ($this->status === 'locked') {
            $this->status = 'in_progress';
            $this->started_at = now();
        }

        if ($this->current_progress >= $this->required_progress && $this->status !== 'completed') {
            $this->status = 'completed';
            $this->completed_at = now();
        }

        $this->save();
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeClaimable($query)
    {
        return $query->where('status', 'completed')->whereNull('claimed_at');
    }
}
