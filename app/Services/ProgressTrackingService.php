<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Events\CourseCompleted;

class ProgressTrackingService
{
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
