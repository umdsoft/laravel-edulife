<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DailyMission extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'code', 'title', 'description', 'icon',
        'task_type', 'target_value',
        'xp_reward', 'coin_reward',
        'difficulty', 'weight', 'is_active',
    ];
    
    protected $casts = [
        'target_value' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];
}
