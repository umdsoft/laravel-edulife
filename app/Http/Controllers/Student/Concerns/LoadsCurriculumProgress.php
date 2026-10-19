<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\User;

/**
 * The curriculum sidebar (LearnFrame / LessonSidebar) needs the student's
 * lesson progress and per-module totals on every learn page, not only on the
 * course overview — otherwise it shows "0/5 dars" and "Boshlanmagan".
 */
trait LoadsCurriculumProgress
{
    /** @return array{lessonProgresses: \Illuminate\Support\Collection, moduleProgress: array} */
    protected function curriculumProgress(Course $course, User $user): array
    {
        $course->loadMissing([
            'modules' => fn ($q) => $q->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $lessonProgresses = LessonProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('lesson_id');

        $moduleProgress = [];
        foreach ($course->modules as $module) {
            $total = $module->lessons->count();
            $completed = $module->lessons
                ->filter(fn ($lesson) => $lessonProgresses->get($lesson->id)?->is_completed)
                ->count();

            $moduleProgress[$module->id] = [
                'total' => $total,
                'completed' => $completed,
                'percentage' => $total > 0 ? round($completed / $total * 100) : 0,
            ];
        }

        return ['lessonProgresses' => $lessonProgresses, 'moduleProgress' => $moduleProgress];
    }
}
