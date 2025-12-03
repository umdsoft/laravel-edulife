<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStreak extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'streak_date',
        'streak_count',
        'multiplier',
        'bonus_xp',
        'bonus_coins',
        'was_claimed',
    ];

    protected function casts(): array
    {
        return [
            'streak_date' => 'date',
            'multiplier' => 'decimal:2',
            'was_claimed' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('streak_date', $date);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('streak_date', '>=', now()->subDays($days));
    }

    // Static helper to calculate multiplier
    public static function calculateMultiplier(int $streakCount): float
    {
        return match(true) {
            $streakCount >= 30 => 2.0,
            $streakCount >= 14 => 1.5,
            $streakCount >= 7 => 1.25,
            default => 1.0,
        };
    }
}
