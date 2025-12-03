<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'xp_rewarded',
        'coin_rewarded',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }

    // Scopes
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('earned_at', '>=', now()->subDays($days));
    }
}
