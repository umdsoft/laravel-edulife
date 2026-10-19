<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseCloneService
{
    /** Course columns that describe the original's life, not its content. */
    private const COURSE_RESET = [
        'slug', 'status', 'submitted_at', 'approved_at', 'published_at', 'rejection_reason',
        'views_count', 'students_count', 'avg_rating', 'reviews_count', 'is_featured',
    ];

    /** @var array<string, string> old module id => new module id */
    private array $moduleMap = [];

    /** @var array<string, string> old lesson id => new lesson id */
    private array $lessonMap = [];

    /**
     * Clone a course with its curriculum and tests into a new draft.
     */
    public function clone(Course $course, array $options = []): Course
    {
        $this->moduleMap = [];
        $this->lessonMap = [];

        // lazy loading is disabled outside production: load everything up front
        $course->load([
            'tags',
            'modules.lessons.attachments',
            'tests.questions.options',
        ]);

        return DB::transaction(function () use ($course, $options) {
            $newCourse = $course->replicate(self::COURSE_RESET);
            $newCourse->title = $options['title'] ?? $course->title.' (nusxa)';
            $newCourse->slug = Str::slug($newCourse->title).'-'.Str::random(6);
            $newCourse->status = 'draft';
            $newCourse->students_count = 0;
            $newCourse->reviews_count = 0;
            $newCourse->avg_rating = 0;
            $newCourse->views_count = 0;
            $newCourse->is_featured = false;
            $newCourse->thumbnail = null;
            $newCourse->save();

            if ($course->thumbnail && ($options['clone_media'] ?? true)) {
                $newCourse->update(['thumbnail' => $this->cloneFile($course->thumbnail, 'courses/thumbnails')]);
            }

            $newCourse->tags()->attach($course->tags->pluck('id'));

            if ($options['clone_curriculum'] ?? true) {
                $this->cloneModules($course, $newCourse, $options);
            }

            if ($options['clone_tests'] ?? true) {
                $this->cloneTests($course, $newCourse);
            }

            return $newCourse;
        });
    }

    private function cloneModules(Course $source, Course $target, array $options): void
    {
        foreach ($source->modules as $module) {
            $newModule = $module->replicate(['lessons_count']);
            $newModule->course_id = $target->id;
            $newModule->save();
            $this->moduleMap[$module->id] = $newModule->id;

            foreach ($module->lessons as $lesson) {
                // Videos are not copied (the teacher uploads again); sharing the file would
                // let deleting one lesson's video break the other course
                $newLesson = $lesson->replicate();
                $newLesson->course_id = $target->id;
                $newLesson->module_id = $newModule->id;
                $newLesson->video_url = null;
                $newLesson->source_url = null;
                $newLesson->video_qualities = null;
                $newLesson->video_duration = 0;
                $newLesson->video_status = 'pending';
                $newLesson->save();
                $this->lessonMap[$lesson->id] = $newLesson->id;

                if ($options['clone_attachments'] ?? false) {
                    $this->cloneAttachments($lesson, $newLesson);
                }
            }
        }
    }

    private function cloneTests(Course $source, Course $target): void
    {
        foreach ($source->tests as $test) {
            $newTest = $test->replicate();
            $newTest->course_id = $target->id;
            $newTest->is_active = false;

            // re-point lesson/module tests at the copies (or make them course-wide)
            [$newTest->testable_type, $newTest->testable_id] = match ($test->testable_type) {
                Lesson::class => isset($this->lessonMap[$test->testable_id]) ? [Lesson::class, $this->lessonMap[$test->testable_id]] : [null, null],
                Module::class => isset($this->moduleMap[$test->testable_id]) ? [Module::class, $this->moduleMap[$test->testable_id]] : [null, null],
                Course::class => [Course::class, $target->id],
                default => [null, null],
            };
            $newTest->save();

            foreach ($test->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->test_id = $newTest->id;
                $newQuestion->course_id = $target->id;
                $newQuestion->save();

                foreach ($question->options as $option) {
                    $newOption = $option->replicate();
                    $newOption->question_id = $newQuestion->id;
                    $newOption->save();
                }

                // matching_pairs / ordering_items refer to option ids — rebuild for the copies
                $newQuestion->syncAnswerKey();
            }
        }
    }

    private function cloneAttachments(Lesson $source, Lesson $target): void
    {
        foreach ($source->attachments as $attachment) {
            $newPath = $this->cloneFile($attachment->file_path, 'lessons/'.$target->id.'/attachments');

            if ($newPath) {
                $newAttachment = $attachment->replicate(['download_count']);
                $newAttachment->lesson_id = $target->id;
                $newAttachment->file_path = $newPath;
                $newAttachment->download_count = 0;
                $newAttachment->save();
            }
        }
    }

    private function cloneFile(string $sourcePath, string $directory): ?string
    {
        if (! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $newPath = $directory.'/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->copy($sourcePath, $newPath);

        return $newPath;
    }
}
