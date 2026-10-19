<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Events\CourseCompleted;

class ProgressTrackingService
{
    /**
     * A quiz-type lesson is completed by passing its test. Attempts passed
     * before that rule existed left those lessons "Boshlanmagan" and the
     * course stuck below 100% (QA2-S-05); complete them and recount.
     *
     * @return int how many lessons were completed now
     */
    public function completePassedQuizLessons(Enrollment $enrollment): int
    {
        $quizLessonIds = \App\Models\Lesson::where('course_id', $enrollment->course_id)
            ->where('type', 'quiz')
            ->pluck('id');
        if ($quizLessonIds->isEmpty()) {
            return 0;
        }

        $passedLessonIds = \App\Models\Test::where('testable_type', \App\Models\Lesson::class)
            ->whereIn('testable_id', $quizLessonIds)
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $enrollment->user_id)->where('is_passed', true))
            ->pluck('testable_id')
            ->unique();

        $completed = 0;
        foreach ($passedLessonIds as $lessonId) {
            $progress = LessonProgress::firstOrCreate(
                ['user_id' => $enrollment->user_id, 'lesson_id' => $lessonId],
                ['enrollment_id' => $enrollment->id, 'course_id' => $enrollment->course_id, 'total_duration' => 0],
            );
            if (! $progress->is_completed) {
                $progress->markAsCompleted();
                $completed++;
            }
        }

        if ($completed > 0) {
            $this->updateEnrollmentProgress($enrollment);
        }

        return $completed;
    }

    /**
     * Update enrollment progress based on lesson progresses
     */
    public function updateEnrollmentProgress(Enrollment $enrollment): void
    {
        $totalLessons = $enrollment->course->lessons()->count();
        $completedLessons = LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('is_completed', true)
            ->count();
        
        $progress = $totalLessons > 0 
            ? (int) round(($completedLessons / $totalLessons) * 100) 
            : 0;
        
        $enrollment->update([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress' => $progress,
            'last_activity_at' => now(),
        ]);
        
        // Check if course completed
        if ($progress >= 100 && !$enrollment->is_completed) {
            $enrollment->update([
                'is_completed' => true,
                'completed_at' => now(),
                'status' => 'completed',
            ]);
            
            event(new CourseCompleted($enrollment));
        }
    }
}
