<?php

namespace App\Services;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\CoinTransaction;

class CoinRewardService
{
    // Coin Values (Ranges)
    public const COIN_LESSON_MIN = 10;
    public const COIN_LESSON_MAX = 50;
    
    public const COIN_TEST_PASS_LOW = 20;
    public const COIN_TEST_PASS_MEDIUM = 50;
    public const COIN_TEST_PASS_HIGH = 100;
    
    public const COIN_STREAK_MIN = 5;
    public const COIN_STREAK_MAX = 50;
    
    public const COIN_COURSE_MIN = 100;
    public const COIN_COURSE_MAX = 500;
    
    public const COIN_REFERRAL = 100;
    
    public const COIN_TOURNAMENT_WIN_MIN = 200;
    public const COIN_TOURNAMENT_WIN_MAX = 1000;
    
    public const COIN_OLYMPIAD_PARTICIPATION = 50; // Base participation
    public const COIN_OLYMPIAD_BRONZE = 500;
    public const COIN_OLYMPIAD_SILVER = 1000;
    public const COIN_OLYMPIAD_GOLD = 2000;

    /**
     * Award coins for lesson completion
     */
    public function awardLessonCoins(User $user, Lesson $lesson): int
    {
        // Random amount between min and max, maybe influenced by lesson duration/difficulty
        $amount = rand(self::COIN_LESSON_MIN, self::COIN_LESSON_MAX);
        
        $this->addCoins($user, $amount, 'lesson_complete', "Lesson completed: {$lesson->title}");
        
        return $amount;
    }

    /**
     * Award coins for test completion
     */
    public function awardTestCoins(User $user, float $scorePercentage): int
    {
        $amount = 0;
        if ($scorePercentage >= 90) {
            $amount = self::COIN_TEST_PASS_HIGH;
        } elseif ($scorePercentage >= 70) {
            $amount = self::COIN_TEST_PASS_MEDIUM;
        } elseif ($scorePercentage >= 50) {
            $amount = self::COIN_TEST_PASS_LOW;
        }
        
        if ($amount > 0) {
            $this->addCoins($user, $amount, 'test_pass', "Test passed with {$scorePercentage}%");
        }
        
        return $amount;
    }

    /**
     * Award coins for course completion
     */
    public function awardCourseCoins(User $user, Course $course): int
    {
        // Calculate based on course size (lessons count)
        $lessonCount = $course->lessons()->count();
        $amount = min(self::COIN_COURSE_MAX, max(self::COIN_COURSE_MIN, $lessonCount * 10));
        
        $this->addCoins($user, $amount, 'course_complete', "Course completed: {$course->title}");
        
        return $amount;
    }

    /**
     * Award coins for daily streak
     */
    public function awardStreakCoins(User $user, int $days): int
    {
        // More days = more coins, capped at MAX
        $amount = min(self::COIN_STREAK_MAX, max(self::COIN_STREAK_MIN, $days * 2));
        
        $this->addCoins($user, $amount, 'daily_streak', "Daily streak: {$days} days");
        
        return $amount;
    }

    /**
     * Award coins for referral
     */
    public function awardReferralCoins(User $referrer, User $referee): int
    {
        $amount = self::COIN_REFERRAL;
        $this->addCoins($referrer, $amount, 'referral', "Referred user: {$referee->name}");
        return $amount;
    }

    /**
     * Award coins for Olympiad
     */
    public function awardOlympiadCoins(User $user, string $medal): int
    {
        $amount = match ($medal) {
            'gold' => self::COIN_OLYMPIAD_GOLD,
            'silver' => self::COIN_OLYMPIAD_SILVER,
            'bronze' => self::COIN_OLYMPIAD_BRONZE,
            'participation' => self::COIN_OLYMPIAD_PARTICIPATION,
            default => 0,
        };
        
        if ($amount > 0) {
            $this->addCoins($user, $amount, 'olympiad_reward', "Olympiad reward: {$medal}");
        }
        
        return $amount;
    }

    /**
     * Helper to add coins and log transaction
     */
    protected function addCoins(User $user, int $amount, string $source, string $description): void
    {
        $user->studentProfile->addCoins($amount);
        
        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'earn',
            'amount' => $amount,
            'balance_after' => $user->studentProfile->coins,
            'source' => $source,
            'description' => $description,
        ]);
    }
}
