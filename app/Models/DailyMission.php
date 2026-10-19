<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMission extends Model
{
    use HasUuids;
    
    /**
     * daily_missions.type enum values.
     */
    public const TYPES = ['complete_lessons', 'pass_tests', 'win_battles', 'play_battles', 'watch_time', 'daily_login'];

    // Only real daily_missions columns (task_type/target_value/weight never existed)
    protected $fillable = [
        'code', 'title', 'description', 'icon', 'type', 'target_count',
        'xp_reward', 'coin_reward', 'difficulty', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'target_count' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
    
    public function userMissions(): HasMany
    {
        return $this->hasMany(UserMission::class, 'mission_id');
    }
    
    public function getTargetAttribute(): int
    {
        return $this->target_count ?? 1;
    }

    public function getTaskTypeAttribute(): ?string
    {
        return $this->attributes['type'] ?? null;
    }
}
