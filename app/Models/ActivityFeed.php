<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityFeed extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'type', 'title', 'description',
        'activityable_type', 'activityable_id',
        'is_public', 'occurred_at',
    ];
    
    protected $casts = [
        'is_public' => 'boolean',
        'occurred_at' => 'datetime',
    ];
    
    protected $appends = ['icon', 'color'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function activityable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'course_enrolled' => '📚',
            'course_completed' => '🎓',
            'lesson_completed' => '✅',
            'test_passed' => '📝',
            'achievement_unlocked' => '🏆',
            'battle_won' => '⚔️',
            'tournament_joined' => '🏅',
            'level_up' => '⬆️',
            'streak_milestone' => '🔥',
            'certificate_earned' => '📜',
            default => '📌',
        };
    }
    
    public function getColorAttribute(): string
    {
        return match($this->type) {
            'course_completed', 'test_passed', 'battle_won' => 'green',
            'achievement_unlocked', 'certificate_earned' => 'yellow',
            'level_up' => 'purple',
            'streak_milestone' => 'orange',
            default => 'blue',
        };
    }
}
