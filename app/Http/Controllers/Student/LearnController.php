<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LearnController extends Controller
{
    /**
     * Course learning dashboard
     */
    public function course(Course $course)
    {
        $user = Auth::user();
        
        // Check enrollment
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
        
        // Load course with modules and lessons
        $course->load([
            'teacher',
            'modules' => fn($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn($q) => $q->orderBy('sort_order'),
        ]);
        
        // Get user's progress for all lessons
        $lessonProgresses = LessonProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('lesson_id');
        
        // Calculate module progress
        $moduleProgress = [];
        foreach ($course->modules as $module) {
            $totalLessons = $module->lessons->count();
            $completedLessons = $module->lessons->filter(function ($lesson) use ($lessonProgresses) {
                $progress = $lessonProgresses->get($lesson->id);
                return $progress && $progress->is_completed;
            })->count();
            
            $moduleProgress[$module->id] = [
                'total' => $totalLessons,
                'completed' => $completedLessons,
                'percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
            ];
        }
        
        // Find next lesson to continue
        $nextLesson = null;
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $progress = $lessonProgresses->get($lesson->id);
                if (!$progress || !$progress->is_completed) {
                    $nextLesson = $lesson;
                    break 2;
                }
            }
        }
        
        // Announcements count
        $announcementsCount = $course->announcements()->published()->count();
        
        // Unanswered Q&A count
        $qnaCount = $course->questions()->questions()->count();
        
        return Inertia::render('Student/Learn/Course', [
            'course' => $course,
            'enrollment' => $enrollment,
            'lessonProgresses' => $lessonProgresses,
            'moduleProgress' => $moduleProgress,
            'nextLesson' => $nextLesson,
            'announcementsCount' => $announcementsCount,
            'qnaCount' => $qnaCount,
        ]);
    }
    
    /**
     * Single lesson view
     */
    public function lesson(Course $course, Lesson $lesson)
    {
        $user = Auth::user();
        
        // Check enrollment
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
        
        // Check if lesson belongs to course
        if ($lesson->course_id !== $course->id) {
            abort(404);
        }
        
        // Load lesson with relations
        $lesson->load(['video.chapters', 'attachments', 'module']);
        
        // Get or create progress
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'total_duration' => $lesson->duration,
            ]
        );
        
        // Update enrollment last activity
        $enrollment->update([
            'last_activity_at' => now(),
            'last_lesson_id' => $lesson->id,
        ]);
        
        // Update streak
        $user->studentProfile->updateStreak();
        
        // Get user notes for this lesson
        $notes = $user->lessonNotes()
            ->where('lesson_id', $lesson->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();
        
        // Navigation: prev/next lessons
        $allLessons = $course->lessons()
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->orderBy('modules.sort_order')
            ->orderBy('lessons.sort_order')
            ->select('lessons.*')
            ->get();
        
        $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
        $prevLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $nextLesson = $currentIndex < $allLessons->count() - 1 ? $allLessons[$currentIndex + 1] : null;
        
        // Course sidebar data
        $course->load([
            'modules' => fn($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn($q) => $q->orderBy('sort_order'),
        ]);
        
        $lessonProgresses = LessonProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('lesson_id');
        
        return Inertia::render('Student/Learn/Lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'enrollment' => $enrollment,
            'progress' => $progress,
            'notes' => $notes,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'lessonProgresses' => $lessonProgresses,
        ]);
    }
}
