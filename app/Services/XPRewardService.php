<?php

namespace App\Services;

use App\Models\User;
use App\Models\LessonProgress;
use App\Models\Enrollment;

class XPRewardService
{
    // XP amounts
    const XP_LESSON_COMPLETE = 20;
    const XP_COURSE_COMPLETE = 100;
    const XP_TEST_PASS = 30;
    const XP_REVIEW_WRITE = 25;
    const XP_QUESTION_ASK = 5;
    const XP_STREAK_BONUS = 10; // per day
    
    /**
     * Award XP for lesson completion
     */
    public function awardLessonXP(LessonProgress $progress): void
    {
        if ($progress->xp_awarded) {
            return;
        }
        
        $user = $progress->user;
        $xpAmount = self::XP_LESSON_COMPLETE;
        
        // Streak bonus
        $streak = $user->studentProfile->streak_days;
        if ($streak >= 7) {
            $xpAmount += self::XP_STREAK_BONUS;
        }
        
        $user->studentProfile->addXp($xpAmount);
        
        $progress->update([
            'xp_awarded' => true,
            'xp_amount' => $xpAmount,
        ]);
    }
    
    /**
     * Award XP for course completion
     */
    public function awardCourseXP(Enrollment $enrollment): void
    {
        $user = $enrollment->user;
        $user->studentProfile->addXp(self::XP_COURSE_COMPLETE);
        $user->studentProfile->increment('courses_completed');
    }
}
