<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserEnglishProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_english_profiles';

    protected $fillable = [
        'user_id',
        'current_level_id',
        'current_unit_id',
        'current_lesson_id',
        'total_xp',
        'current_level_xp',
        'coins',
        'gems',
        'elo_rating',
        'battles_played',
        'battles_won',
        'battles_lost',
        'win_streak',
        'best_win_streak',
        'words_learned',
        'words_mastered',
        'lessons_completed',
        'units_completed',
        'games_played',
        'tests_passed',
        'ai_conversations',
        'total_study_minutes',
        'today_study_minutes',
        'last_study_date',
        'current_streak',
        'longest_streak',
        'streak_start_date',
        'streak_protected_today',
        'streak_freezes_available',
        'placement_test_completed',
        'placement_test_date',
        'placement_test_results',
        'daily_xp_goal',
        'today_xp_earned',
        'daily_challenges_completed',
        'preferences',
        'target_ielts_band',
        'estimated_ielts_band',
    ];

    protected $casts = [
        'total_xp' => 'integer',
        'current_level_xp' => 'integer',
        'coins' => 'integer',
        'gems' => 'integer',
        'elo_rating' => 'integer',
        'battles_played' => 'integer',
        'battles_won' => 'integer',
        'battles_lost' => 'integer',
        'win_streak' => 'integer',
        'best_win_streak' => 'integer',
        'words_learned' => 'integer',
        'words_mastered' => 'integer',
        'lessons_completed' => 'integer',
        'units_completed' => 'integer',
        'games_played' => 'integer',
        'tests_passed' => 'integer',
        'ai_conversations' => 'integer',
        'total_study_minutes' => 'integer',
        'today_study_minutes' => 'integer',
        'last_study_date' => 'date',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'streak_start_date' => 'date',
        'streak_protected_today' => 'boolean',
        'streak_freezes_available' => 'integer',
        'placement_test_completed' => 'boolean',
        'placement_test_date' => 'datetime',
        'placement_test_results' => 'array',
        'daily_xp_goal' => 'integer',
        'today_xp_earned' => 'integer',
        'daily_challenges_completed' => 'array',
        'preferences' => 'array',
        'target_ielts_band' => 'decimal:1',
        'estimated_ielts_band' => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'current_level_id');
    }

    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(EnglishUnit::class, 'current_unit_id');
    }

    public function currentLesson(): BelongsTo
    {
        return $this->belongsTo(EnglishLesson::class, 'current_lesson_id');
    }

    public function vocabulary(): HasMany
    {
        return $this->hasMany(UserVocabulary::class, 'user_id', 'user_id');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(UserLessonProgress::class, 'user_id', 'user_id');
    }

    public function skillLevels(): HasMany
    {
        return $this->hasMany(UserSkillLevel::class, 'user_id', 'user_id');
    }

    public function addXp(int $amount): void
    {
        // Update English profile XP
        $this->total_xp += $amount;
        $this->current_level_xp += $amount;
        $this->today_xp_earned += $amount;
        $this->save();
        
        // Sync to main StudentProfile for unified display
        if ($this->user && $this->user->studentProfile) {
            $this->user->studentProfile->addXp($amount);
        }
    }

    public function addCoins(int $amount): void
    {
        // Update English profile coins
        $this->increment('coins', $amount);
        
        // Sync to main StudentProfile for unified display
        if ($this->user && $this->user->studentProfile) {
            $this->user->studentProfile->addCoins($amount);
        }
    }

    public function spendCoins(int $amount): bool
    {
        if ($this->coins >= $amount) {
            $this->decrement('coins', $amount);
            return true;
        }
        return false;
    }

    public function getWinRateAttribute(): float
    {
        if ($this->battles_played === 0)
            return 0;
        return round(($this->battles_won / $this->battles_played) * 100, 1);
    }

    public function getEloTierAttribute(): string
    {
        return match (true) {
            $this->elo_rating >= 1800 => 'Master',
            $this->elo_rating >= 1600 => 'Diamond',
            $this->elo_rating >= 1400 => 'Platinum',
            $this->elo_rating >= 1200 => 'Gold',
            $this->elo_rating >= 1000 => 'Silver',
            default => 'Bronze',
        };
    }
}
