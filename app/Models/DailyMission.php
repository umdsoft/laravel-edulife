<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMission extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'title', 'description', 'type', 'target_count',
        'xp_reward', 'coin_reward', 'is_active', 'sort_order',
        // Also support new schema fields if they exist
        'code', 'icon', 'task_type', 'target_value', 'difficulty', 'weight',
    ];
    
    protected $casts = [
        'target_count' => 'integer',
        'target_value' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'weight' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
    
    public function userMissions(): HasMany
    {
        return $this->hasMany(UserMission::class, 'mission_id');
    }
    
    // Helper to get target value regardless of column name
    public function getTargetAttribute(): int
    {
        return $this->target_value ?? $this->target_count ?? 1;
    }
    
    // Helper to get task type regardless of column name
    public function getTaskTypeAttribute(): ?string
    {
        return $this->attributes['task_type'] ?? $this->attributes['type'] ?? null;
    }
}
