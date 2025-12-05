<?php

namespace App\Services;

use App\Models\User;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Course;

class XPRewardService
{
    /**
     * ========================================
     * XP ALGORITHM - REALISTIC VALUES
     * ========================================
     * O'rtacha o'quvchi kuniga ~50-100 XP to'playdi
     * Level 2 ga yetish: 1-2 hafta (500 XP)
     * Level 10 ga yetish: 2 yil (55,000 XP)
     * ========================================
     */
    
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
        
        $user->studentProfile->addXp($xpAmount);
        
        $progress->update([
            'xp_awarded' => true,
            'xp_amount' => $xpAmount,
        ]);
        
        return $xpAmount;
    }
    
    /**
     * Calculate XP for a lesson based on its type
     */
    public function calculateLessonXP(Lesson $lesson): int
    {
        $type = strtolower($lesson->type ?? 'video');
        
        return match ($type) {
            'video' => self::XP_LESSON_VIDEO,
            'text', 'article', 'reading' => self::XP_LESSON_TEXT,
            'practical', 'exercise', 'assignment', 'quiz' => self::XP_LESSON_PRACTICAL,
            default => self::XP_LESSON_DEFAULT,
        };
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
        
        $xpAmount = $this->calculateCourseXP($course);
        
        $user->studentProfile->addXp($xpAmount);
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
        $xpAmount = $this->calculateTestXP($scorePercentage);
        
        $user->studentProfile->addXp($xpAmount);
        
        return $xpAmount;
    }
    
    /**
     * Calculate XP based on test score percentage
     */
    public function calculateTestXP(float $scorePercentage): int
    {
        if ($scorePercentage >= 90) {
            return self::XP_TEST_HIGH;
        } elseif ($scorePercentage >= 70) {
            return self::XP_TEST_MEDIUM;
        } elseif ($scorePercentage >= 50) {
            return self::XP_TEST_LOW;
        }
        
        return 0; // Failed test, no XP
    }
    
    /**
     * Award XP for battle/tournament
     */
    public function awardBattleXP(User $user, string $result): int
    {
        $xpAmount = match ($result) {
            'win' => self::XP_BATTLE_WIN,
            'top10' => self::XP_BATTLE_TOP10,
            'participate' => self::XP_BATTLE_PARTICIPATE,
            'lose' => self::XP_BATTLE_LOSE,
            default => self::XP_BATTLE_PARTICIPATE,
        };
        
        $user->studentProfile->addXp($xpAmount);
        
        return $xpAmount;
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
        
        $user->studentProfile->addXp($xpAmount);
        
        return $xpAmount;
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
        
        $user->studentProfile->addXp($xpAmount);
        
        return $xpAmount;
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
        
        $user->studentProfile->addXp($xpAmount);
        
        return $xpAmount;
    }
    
    /**
     * Award XP for writing review
     */
    public function awardReviewXP(User $user): int
    {
        $user->studentProfile->addXp(self::XP_REVIEW_WRITE);
        return self::XP_REVIEW_WRITE;
    }
    
    /**
     * Award XP for asking question
     */
    public function awardQuestionXP(User $user): int
    {
        $user->studentProfile->addXp(self::XP_QUESTION_ASK);
        return self::XP_QUESTION_ASK;
    }
    
    /**
     * Award XP for enrollment
     */
    public function awardEnrollmentXP(User $user): int
    {
        $user->studentProfile->addXp(self::XP_ENROLLMENT);
        return self::XP_ENROLLMENT;
    }
    
    /**
     * Get XP reward info for display
     */
    public function getXPRewardInfo(): array
    {
        return [
            'lesson' => [
                'video' => self::XP_LESSON_VIDEO,
                'text' => self::XP_LESSON_TEXT,
                'practical' => self::XP_LESSON_PRACTICAL,
            ],
            'course' => [
                'small' => self::XP_COURSE_SMALL,
                'medium' => self::XP_COURSE_MEDIUM,
                'large' => self::XP_COURSE_LARGE,
            ],
            'test' => [
                'low' => self::XP_TEST_LOW,
                'medium' => self::XP_TEST_MEDIUM,
                'high' => self::XP_TEST_HIGH,
            ],
            'streak' => [
                'week' => self::XP_STREAK_WEEK,
                'month' => self::XP_STREAK_MONTH,
                'long' => self::XP_STREAK_LONG,
            ],
            'battle' => [
                'win' => self::XP_BATTLE_WIN,
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
}
