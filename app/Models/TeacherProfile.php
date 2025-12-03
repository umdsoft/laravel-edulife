<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'specializations',
        'website_url',
        'youtube_url',
        'telegram_url',
        'linkedin_url',
        'level',
        'commission_rate',
        'avg_rating',
        'total_reviews',
        'total_students',
        'total_courses',
        'is_verified',
        'verified_at',
        'expertise',
        'total_earnings',
        'pending_earnings',
        'monthly_earnings',
        'current_score',
        'last_month_score',
    ];

    protected function casts(): array
    {
        return [
            'specializations' => 'array',
            'commission_rate' => 'decimal:2',
            'avg_rating' => 'decimal:2',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id', 'user_id');
    }

    public function getLevelBadgeAttribute(): string
    {
        return match($this->level) {
            'top' => '🏆 Top',
            'featured' => '⭐ Tavsiya etilgan',
            'verified' => '✓ Tasdiqlangan',
            default => '🆕 Yangi',
        };
    }

    // Commission rates by level
    public static function getCommissionRate(string $level): float
    {
        return match($level) {
            'new' => 30.00,
            'verified' => 30.00,
            'featured' => 25.00,
            'top' => 20.00,
            default => 30.00,
        };
    }
}
