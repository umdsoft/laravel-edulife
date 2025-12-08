<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishLeaderboard extends Model
{
    use HasUuids;

    protected $table = 'english_leaderboards';

    protected $fillable = [
        'slug',
        'code',
        'name',
        'name_uz',
        'description',
        'type',
        'period',
        'period_start',
        'period_end',
        'level_id',
        'country_code',
        'display_count',
        'show_position_change',
        'position_rewards',
        'last_reset_at',
        'next_reset_at',
        'icon',
        'color',
        'is_active',
        'order_number',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'display_count' => 'integer',
        'show_position_change' => 'boolean',
        'position_rewards' => 'array',
        'last_reset_at' => 'datetime',
        'next_reset_at' => 'datetime',
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(EnglishLeaderboardEntry::class, 'leaderboard_id')
            ->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
