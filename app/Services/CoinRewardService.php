<?php

namespace App\Services;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Course;

/**
 * Coin rewards for learning activity. Lesson, test and battle-win coins come
 * from the admin settings (RewardSettings); the constants are for the
 * remaining, not yet configurable rewards.
 */
class CoinRewardService
{
    public function __construct(
        protected RewardSettings $rewards,
        protected CoinService $coinService,
    ) {}

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
        // "Dars uchun coin" (0 = lessons give no coins)
        return $this->addCoins($user, $this->rewards->lessonCoins(), 'lesson_complete', "Dars tugatildi: {$lesson->title}", $lesson);
    }

    /**
     * Award coins for test completion
     */
    public function awardTestCoins(User $user, float $scorePercentage, $test = null): int
    {
        // "Test uchun coin" for 90%+; 70-89% ×25/40, 50-69% ×10/40
        $score = rtrim(rtrim(number_format($scorePercentage, 1, '.', ''), '0'), '.');

        return $this->addCoins($user, $this->rewards->testCoins($scorePercentage), 'test_pass', "Testdan o'tildi: {$score}%", $test);
    }

    /**
     * Award coins for a won battle ("Battle g'alabasi uchun coin").
     */
    public function awardBattleWinCoins(User $user, $battle = null): int
    {
        return $this->addCoins($user, $this->rewards->battleWinCoins(), 'battle_win', "Jangda g'alaba", $battle);
    }

    /**
     * Award coins for course completion
     */
    public function awardCourseCoins(User $user, Course $course): int
    {
        // Calculate based on course size (lessons count)
        $lessonCount = $course->lessons()->count();
        $amount = min(self::COIN_COURSE_MAX, max(self::COIN_COURSE_MIN, $lessonCount * 10));
        
        $this->addCoins($user, $amount, 'course_complete', "Kurs tugatildi: {$course->title}");
        
        return $amount;
    }

    /**
     * Award coins for daily streak
     */
    public function awardStreakCoins(User $user, int $days): int
    {
        // More days = more coins, capped at MAX
        $amount = min(self::COIN_STREAK_MAX, max(self::COIN_STREAK_MIN, $days * 2));
        
        $this->addCoins($user, $amount, 'daily_streak', "Kunlik seriya: {$days} kun");
        
        return $amount;
    }

    /**
     * Award coins for referral
     */
    public function awardReferralCoins(User $referrer, User $referee): int
    {
        $amount = self::COIN_REFERRAL;
        // first name only: the invited user may be a child (exam spec section 11)
        return $this->addCoins($referrer, $amount, 'referral', "Taklif mukofoti: {$referee->first_name}");
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
            $this->addCoins($user, $amount, 'olympiad_reward', "Olimpiada mukofoti: {$medal}");
        }
        
        return $amount;
    }

    /**
     * Helper to add coins and log transaction
     */
    protected function addCoins(User $user, int $amount, string $source, string $description, $reference = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        // the one balance (student_profiles) and its history; returns what was added
        return $this->coinService->addCoins($user, $amount, $source, $description, $reference);
    }
}
