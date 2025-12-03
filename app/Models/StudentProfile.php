<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id',
        'xp',
        'level',
        'streak_days',
        'last_activity_date',
        'longest_streak',
        'elo_rating',
        'rank',
        'battles_won',
        'battles_lost',
        'battles_draw',
        'coins',
        'total_coins_earned',
        'courses_completed',
        'lessons_completed',
        'tests_passed',
        'total_watch_time',
        'certificates_earned',
        'interests',
        'preferred_language',
        'email_notifications',
        'push_notifications',
    ];
    
    protected $casts = [
        'xp' => 'integer',
        'level' => 'integer',
        'streak_days' => 'integer',
        'last_activity_date' => 'date',
        'longest_streak' => 'integer',
        'elo_rating' => 'integer',
        'battles_won' => 'integer',
        'battles_lost' => 'integer',
        'battles_draw' => 'integer',
        'coins' => 'integer',
        'total_coins_earned' => 'integer',
        'courses_completed' => 'integer',
        'lessons_completed' => 'integer',
        'tests_passed' => 'integer',
        'total_watch_time' => 'integer',
        'certificates_earned' => 'integer',
        'interests' => 'array',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];
    
    protected $appends = ['rank_badge', 'level_progress', 'formatted_watch_time'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * XP dan Level hisoblash
     * Level 1: 0-99 XP
     * Level 2: 100-299 XP
     * Level 3: 300-599 XP
     * Formula: level = floor(sqrt(xp / 100)) + 1
     */
    public static function calculateLevel(int $xp): int
    {
        return (int) floor(sqrt($xp / 100)) + 1;
    }
    
    /**
     * Keyingi level uchun kerakli XP
     */
    public static function xpForLevel(int $level): int
    {
        return (int) pow($level - 1, 2) * 100;
    }
    
    /**
     * Keyingi levelgacha progress (0-100)
     */
    public function getLevelProgressAttribute(): int
    {
        $currentLevelXp = self::xpForLevel($this->level);
        $nextLevelXp = self::xpForLevel($this->level + 1);
        $xpInCurrentLevel = $this->xp - $currentLevelXp;
        $xpNeededForNext = $nextLevelXp - $currentLevelXp;
        
        return (int) min(100, ($xpInCurrentLevel / $xpNeededForNext) * 100);
    }
    
    /**
     * Rank badge emoji
     */
    public function getRankBadgeAttribute(): string
    {
        return match($this->rank) {
            'bronze' => '🥉',
            'silver' => '🥈',
            'gold' => '🥇',
            'platinum' => '💎',
            'diamond' => '💠',
            'master' => '👑',
            default => '🥉',
        };
    }
    
    /**
     * ELO dan Rank aniqlash
     */
    public static function calculateRank(int $elo): string
    {
        return match(true) {
            $elo >= 2400 => 'master',
            $elo >= 2000 => 'diamond',
            $elo >= 1600 => 'platinum',
            $elo >= 1200 => 'gold',
            $elo >= 800 => 'silver',
            default => 'bronze',
        };
    }
    
    /**
     * Formatted watch time
     */
    public function getFormattedWatchTimeAttribute(): string
    {
        $hours = floor($this->total_watch_time / 3600);
        $minutes = floor(($this->total_watch_time % 3600) / 60);
        
        if ($hours > 0) {
            return $hours . ' soat ' . $minutes . ' daqiqa';
        }
        return $minutes . ' daqiqa';
    }
    
    /**
     * XP qo'shish va level yangilash
     */
    public function addXp(int $amount): void
    {
        $this->xp += $amount;
        $newLevel = self::calculateLevel($this->xp);
        
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            // TODO: Level up notification
        }
        
        $this->save();
    }
    
    /**
     * Streak yangilash
     */
    public function updateStreak(): void
    {
        $today = now()->toDateString();
        $lastActivity = $this->last_activity_date?->toDateString();
        
        if ($lastActivity === $today) {
            // Bugun allaqachon faol
            return;
        }
        
        $yesterday = now()->subDay()->toDateString();
        
        if ($lastActivity === $yesterday) {
            // Ketma-ket kun
            $this->streak_days++;
            if ($this->streak_days > $this->longest_streak) {
                $this->longest_streak = $this->streak_days;
            }
        } else {
            // Streak uzildi
            $this->streak_days = 1;
        }
        
        $this->last_activity_date = $today;
        $this->save();
    }
    
    /**
     * COIN qo'shish
     */
    public function addCoins(int $amount): void
    {
        $this->coins += $amount;
        $this->total_coins_earned += $amount;
        $this->save();
    }
    
    /**
     * COIN sarflash
     */
    public function spendCoins(int $amount): bool
    {
        if ($this->coins < $amount) {
            return false;
        }
        
        $this->coins -= $amount;
        $this->save();
        return true;
    }
}
