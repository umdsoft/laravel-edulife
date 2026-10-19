<?php

namespace App\Services;

use App\Models\User;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Course;

class XPRewardService
{
    public function __construct(
        protected RewardSettings $rewards,
        protected CoinRewardService $coins,
    ) {}

    /**
     * ========================================
     * XP ALGORITHM - REALISTIC VALUES
     * ========================================
     * O'rtacha o'quvchi kuniga ~50-100 XP to'playdi
     * Level 2 ga yetish: 1-2 hafta (500 XP)
     * Level 10 ga yetish: 2 yil (55,000 XP)
     * ========================================
     */
    
    // The constants below are the defaults; lesson, test and battle-win XP and
    // the premium/VIP multipliers come from the admin settings (RewardSettings).

    // LESSON XP: 5-15 XP based on lesson type
    const XP_LESSON_VIDEO = 5;      // Video dars
    const XP_LESSON_TEXT = 8;       // Matnli dars
    const XP_LESSON_PRACTICAL = 15; // Amaliy dars
    const XP_LESSON_DEFAULT = 7;    // Default
    
    // COURSE XP: 100-300 XP based on course size
    const XP_COURSE_SMALL = 100;    // < 20 dars
    const XP_COURSE_MEDIUM = 200;   // 20-50 dars
    const XP_COURSE_LARGE = 300;    // > 50 dars
    
    // TEST XP: 10-40 XP based on score
    const XP_TEST_LOW = 10;         // 50-69%
    const XP_TEST_MEDIUM = 25;      // 70-89%
    const XP_TEST_HIGH = 40;        // 90-100%
    
    // STREAK XP: 5-25 XP based on streak days
    const XP_STREAK_WEEK = 5;       // 1-7 kun
    const XP_STREAK_MONTH = 15;     // 8-30 kun
    const XP_STREAK_LONG = 25;      // 30+ kun
    
    // BATTLE/TOURNAMENT XP: 20-100 XP
    const XP_BATTLE_PARTICIPATE = 20;
    const XP_BATTLE_TOP10 = 50;
    const XP_BATTLE_WIN = 100;
    const XP_BATTLE_LOSE = 10;
    
    // OLYMPIAD XP: 50-200 XP
    const XP_OLYMPIAD_PARTICIPATE = 50;
    const XP_OLYMPIAD_BRONZE = 100;
    const XP_OLYMPIAD_SILVER = 150;
    const XP_OLYMPIAD_GOLD = 200;
    
    // LAB XP: 15-50 XP based on difficulty
    const XP_LAB_BEGINNER = 15;
    const XP_LAB_INTERMEDIATE = 30;
    const XP_LAB_ADVANCED = 50;
    
    // WEEKLY GOAL XP: 30-60 XP
    const XP_WEEKLY_BASIC = 30;
    const XP_WEEKLY_BONUS = 45;
    const XP_WEEKLY_ALL = 60;
    
    // OTHER XP
    const XP_REVIEW_WRITE = 15;     // Review yozish
    const XP_QUESTION_ASK = 3;      // Savol berish
    const XP_QUESTION_ANSWER = 5;   // Javob berish
    const XP_ENROLLMENT = 5;        // Kursga yozilish
    
    /**
     * Award XP for lesson completion
     * Dars turiga qarab 5-15 XP
     */
    public function awardLessonXP(LessonProgress $progress): int
    {
        if ($progress->xp_awarded) {
            return 0;
        }
        
        $user = $progress->user;
        $lesson = $progress->lesson;
        
        // Determine XP based on lesson type
        $xpAmount = $this->calculateLessonXP($lesson);
        
        // Add streak bonus if applicable
        $xpAmount += $this->calculateStreakBonus($user);
        
        $xpAmount = $this->grant($user, $xpAmount);
        
        $progress->update([
            'xp_awarded' => true,
            'xp_amount' => $xpAmount,
        ]);

        // the lesson's coins, once with its XP ("Dars uchun coin")
        $this->coins->awardLessonCoins($user, $lesson);
        
        return $xpAmount;
    }
    
    /**
     * Calculate XP for a lesson based on its type
     */
    public function calculateLessonXP(Lesson $lesson): int
    {
        // "Dars uchun XP" is a text lesson; video ×5/8, practical ×15/8, other ×7/8
        return $this->rewards->lessonXp($lesson->type);
    }
    
    /**
     * Calculate streak bonus
     * Based on consecutive days
     */
    public function calculateStreakBonus(User $user): int
    {
        $streak = $user->studentProfile->streak_days ?? 0;
        
        if ($streak >= 30) {
            return self::XP_STREAK_LONG;
        } elseif ($streak >= 8) {
            return self::XP_STREAK_MONTH;
        } elseif ($streak >= 1) {
            return self::XP_STREAK_WEEK;
        }
        
        return 0;
    }
    
    /**
     * Award XP for course completion
     * Kurs hajmiga qarab 100-300 XP
     */
    public function awardCourseXP(Enrollment $enrollment): int
    {
        $user = $enrollment->user;
        $course = $enrollment->course;
        
        $xpAmount = $this->grant($user, $this->calculateCourseXP($course));
        $user->studentProfile->increment('courses_completed');
        
        return $xpAmount;
    }
    
    /**
     * Calculate XP for course based on size
     */
    public function calculateCourseXP(Course $course): int
    {
        $lessonCount = $course->lessons()->count();
        
        if ($lessonCount > 50) {
            return self::XP_COURSE_LARGE;
        } elseif ($lessonCount >= 20) {
            return self::XP_COURSE_MEDIUM;
        }
        
        return self::XP_COURSE_SMALL;
    }
    
    /**
     * Award XP for test completion
     * Natijaga qarab 10-40 XP
     */
    public function awardTestXP(User $user, float $scorePercentage): int
    {
        return $this->grant($user, $this->calculateTestXP($scorePercentage));
    }
    
    /**
     * Calculate XP based on test score percentage
     */
    public function calculateTestXP(float $scorePercentage): int
    {
        // "Test uchun XP" is the 90%+ band; 70-89% ×25/40, 50-69% ×10/40, below: none
        return $this->rewards->testXp($scorePercentage);
    }
    
    /**
     * Award XP for battle/tournament
     */
    public function awardBattleXP(User $user, string $result): int
    {
        $xpAmount = match ($result) {
            'win' => $this->rewards->battleWinXp(),
            'top10' => self::XP_BATTLE_TOP10,
            'participate' => self::XP_BATTLE_PARTICIPATE,
            'lose' => self::XP_BATTLE_LOSE,
            default => self::XP_BATTLE_PARTICIPATE,
        };
        
        return $this->grant($user, $xpAmount);
    }
    
    /**
     * Award XP for olympiad
     */
    public function awardOlympiadXP(User $user, ?string $medal = null): int
    {
        $xpAmount = match ($medal) {
            'gold' => self::XP_OLYMPIAD_GOLD,
            'silver' => self::XP_OLYMPIAD_SILVER,
            'bronze' => self::XP_OLYMPIAD_BRONZE,
            default => self::XP_OLYMPIAD_PARTICIPATE,
        };
        
        return $this->grant($user, $xpAmount);
    }
    
    /**
     * Award XP for lab experiment
     */
    public function awardLabXP(User $user, string $difficulty): int
    {
        $xpAmount = match ($difficulty) {
            'advanced', 'hard' => self::XP_LAB_ADVANCED,
            'intermediate', 'medium' => self::XP_LAB_INTERMEDIATE,
            default => self::XP_LAB_BEGINNER,
        };
        
        return $this->grant($user, $xpAmount);
    }
    
    /**
     * Award XP for weekly goals
     */
    public function awardWeeklyGoalXP(User $user, string $type): int
    {
        $xpAmount = match ($type) {
            'all' => self::XP_WEEKLY_ALL,
            'bonus' => self::XP_WEEKLY_BONUS,
            default => self::XP_WEEKLY_BASIC,
        };
        
        return $this->grant($user, $xpAmount);
    }
    
    /**
     * Award XP for writing review
     */
    public function awardReviewXP(User $user): int
    {
        return $this->grant($user, self::XP_REVIEW_WRITE);
    }
    
    /**
     * Award XP for asking question
     */
    public function awardQuestionXP(User $user): int
    {
        return $this->grant($user, self::XP_QUESTION_ASK);
    }
    
    /**
     * Award XP for enrollment
     */
    public function awardEnrollmentXP(User $user): int
    {
        return $this->grant($user, self::XP_ENROLLMENT);
    }
    
    /**
     * Get XP reward info for display
     */
    public function getXPRewardInfo(): array
    {
        return [
            'lesson' => [
                'video' => $this->rewards->lessonXp('video'),
                'text' => $this->rewards->lessonXp('text'),
                'practical' => $this->rewards->lessonXp('practical'),
            ],
            'course' => [
                'small' => self::XP_COURSE_SMALL,
                'medium' => self::XP_COURSE_MEDIUM,
                'large' => self::XP_COURSE_LARGE,
            ],
            'test' => [
                'low' => $this->rewards->testXp(50),
                'medium' => $this->rewards->testXp(70),
                'high' => $this->rewards->testXp(90),
            ],
            'streak' => [
                'week' => self::XP_STREAK_WEEK,
                'month' => self::XP_STREAK_MONTH,
                'long' => self::XP_STREAK_LONG,
            ],
            'battle' => [
                'win' => $this->rewards->battleWinXp(),
                'top10' => self::XP_BATTLE_TOP10,
                'participate' => self::XP_BATTLE_PARTICIPATE,
                'lose' => self::XP_BATTLE_LOSE,
            ],
            'olympiad' => [
                'gold' => self::XP_OLYMPIAD_GOLD,
                'silver' => self::XP_OLYMPIAD_SILVER,
                'bronze' => self::XP_OLYMPIAD_BRONZE,
                'participate' => self::XP_OLYMPIAD_PARTICIPATE,
            ],
            'lab' => [
                'beginner' => self::XP_LAB_BEGINNER,
                'intermediate' => self::XP_LAB_INTERMEDIATE,
                'advanced' => self::XP_LAB_ADVANCED,
            ],
            'weekly' => [
                'basic' => self::XP_WEEKLY_BASIC,
                'bonus' => self::XP_WEEKLY_BONUS,
                'all' => self::XP_WEEKLY_ALL,
            ],
        ];
    }

    /**
     * Add activity XP to the student, with the premium / VIP multiplier from
     * the settings (×1 for everyone else). Returns the XP actually added.
     */
    protected function grant(User $user, int $xpAmount): int
    {
        if ($xpAmount <= 0) {
            return 0;
        }

        $xpAmount = (int) round($xpAmount * $this->rewards->xpMultiplier($user));
        $user->ensureStudentProfile()->addXp($xpAmount);

        return $xpAmount;
    }
}
