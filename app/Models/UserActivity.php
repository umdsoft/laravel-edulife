<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'activity_date',
        'lessons_completed',
        'tests_taken',
        'time_spent',
        'videos_watched',
        'watch_time',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
        ];
    }

    // Accessors
    public function getFormattedTimeSpentAttribute(): string
    {
        $hours = floor($this->time_spent / 3600);
        $minutes = floor(($this->time_spent % 3600) / 60);
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    public function getFormattedWatchTimeAttribute(): string
    {
        $hours = floor($this->watch_time / 60);
        $minutes = $this->watch_time % 60;
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('activity_date', $date);
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('activity_date', now()->year)
            ->whereMonth('activity_date', now()->month);
    }

    // Helper Methods
    public static function recordActivity(User $user, array $data): void
    {
        static::updateOrCreate(
            [
                'user_id' => $user->id,
                'activity_date' => now()->toDateString(),
            ],
            $data
        );
    }
}
