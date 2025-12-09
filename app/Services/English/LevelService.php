<?php

namespace App\Services\English;

use App\Models\English\EnglishLevel;
use App\Models\English\UserEnglishProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LevelService
{
    /**
     * Get all levels with user progress
     */
    public function getAllLevelsWithProgress(User $user): Collection
    {
        $profile = $this->getOrCreateProfile($user);

        $levels = EnglishLevel::with([
            'topics' => function ($query) {
                $query->where('is_active', true)->orderBy('order_number');
            }
        ])
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get();

        return $levels->map(function ($level) use ($profile) {
            $level->is_current = $level->id === $profile->current_level_id;
            $level->is_unlocked = $this->isLevelUnlocked($level, $profile);
            $level->progress = $this->calculateLevelProgress($level, $profile);
            return $level;
        });
    }

    /**
     * Get single level with full details
     */
    public function getLevelWithDetails(string $levelId, User $user): ?EnglishLevel
    {
        $profile = $this->getOrCreateProfile($user);

        $level = EnglishLevel::with([
            'topics.units.lessons',
            'vocabulary' => fn($q) => $q->limit(20),
            'grammarRules' => fn($q) => $q->limit(10),
        ])->find($levelId);

        if (!$level)
            return null;

        $level->is_unlocked = $this->isLevelUnlocked($level, $profile);
        $level->progress = $this->calculateLevelProgress($level, $profile);
        $level->stats = $this->getLevelStats($level, $user);

        return $level;
    }

    /**
     * Check if level is unlocked for user
     */
    public function isLevelUnlocked(EnglishLevel $level, UserEnglishProfile $profile): bool
    {
        // Test mode - all levels unlocked
        if (\App\Models\Setting::get('english_test_mode', false)) {
            return true;
        }

        if ($level->order_number === 1)
            return true;

        $previousLevel = EnglishLevel::where('order_number', $level->order_number - 1)->first();

        if (!$previousLevel)
            return true;

        return $this->isLevelCompleted($previousLevel, $profile);
    }

    /**
     * Check if level is completed
     */
    public function isLevelCompleted(EnglishLevel $level, UserEnglishProfile $profile): bool
    {
        $progress = $this->calculateLevelProgress($level, $profile);
        return $progress >= 100;
    }

    /**
     * Calculate level progress percentage
     */
    public function calculateLevelProgress(EnglishLevel $level, UserEnglishProfile $profile): float
    {
        $totalLessons = 0;
        $completedLessons = 0;

        $level->loadMissing('topics.units.lessons');

        foreach ($level->topics as $topic) {
            foreach ($topic->units as $unit) {
                $totalLessons += $unit->lessons->count();
                $completedLessons += $unit->lessons->filter(function ($lesson) use ($profile) {
                    return $lesson->userProgress()
                        ->where('user_id', $profile->user_id)
                        ->where('status', 'completed')
                        ->exists();
                })->count();
            }
        }

        return $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;
    }

    /**
     * Get level statistics
     */
    public function getLevelStats(EnglishLevel $level, User $user): array
    {
        $level->loadMissing('topics.units.lessons');

        return [
            'total_topics' => $level->topics->count(),
            'total_units' => $level->topics->sum(fn($t) => $t->units->count()),
            'total_lessons' => $level->topics->sum(fn($t) => $t->units->sum(fn($u) => $u->lessons->count())),
            'total_vocabulary' => $level->vocabulary()->count(),
            'total_grammar_rules' => $level->grammarRules()->count(),
            'vocabulary_learned' => $level->vocabulary()
                ->whereHas('userVocabulary', fn($q) => $q->where('user_id', $user->id)->where('status', 'mastered'))
                ->count(),
            'estimated_hours' => $level->total_lessons * 0.5,
        ];
    }

    /**
     * Get or create user profile
     */
    public function getOrCreateProfile(User $user): UserEnglishProfile
    {
        return UserEnglishProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_level_id' => EnglishLevel::where('code', 'A1')->first()?->id,
                'total_xp' => 0,
                'coins' => 100,
                'gems' => 10,
                'elo_rating' => 1000,
                'current_streak' => 0,
                'longest_streak' => 0,
                'battles_played' => 0,
                'battles_won' => 0,
                'win_streak' => 0,
                'best_win_streak' => 0,
                'streak_freezes_available' => 2,
            ]
        );
    }

    /**
     * Advance user to next level
     */
    public function advanceToNextLevel(User $user): ?EnglishLevel
    {
        $profile = $this->getOrCreateProfile($user);
        $currentLevel = EnglishLevel::find($profile->current_level_id);

        if (!$currentLevel)
            return null;

        $nextLevel = EnglishLevel::where('order_number', $currentLevel->order_number + 1)->first();

        if (!$nextLevel)
            return null;

        $profile->current_level_id = $nextLevel->id;
        $profile->save();

        $profile->addXp($currentLevel->completion_xp ?? 500);
        $profile->addCoins($currentLevel->completion_coins ?? 100);

        return $nextLevel;
    }
}
