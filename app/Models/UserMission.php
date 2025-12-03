<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMission extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'mission_id',
        'current_value', 'target_value',
        'is_completed', 'is_claimed',
        'assigned_date', 'completed_at', 'claimed_at',
    ];
    
    protected $casts = [
        'current_value' => 'integer',
        'target_value' => 'integer',
        'is_completed' => 'boolean',
        'is_claimed' => 'boolean',
        'assigned_date' => 'date',
        'completed_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];
    
    protected $appends = ['progress_percentage'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function mission(): BelongsTo
    {
        return $this->belongsTo(DailyMission::class, 'mission_id');
    }
    
    public function getProgressPercentageAttribute(): int
    {
        if ($this->target_value === 0) return 100;
        return min(100, (int) round(($this->current_value / $this->target_value) * 100));
    }
    
    /**
     * Progress qo'shish
     */
    public function addProgress(int $amount = 1): void
    {
        if ($this->is_completed) return;
        
        $this->current_value = min($this->target_value, $this->current_value + $amount);
        
        if ($this->current_value >= $this->target_value) {
            $this->is_completed = true;
            $this->completed_at = now();
        }
        
        $this->save();
    }
}
