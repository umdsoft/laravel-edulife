<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseCloneService
{
    /**
     * Clone a course with all its content
     */
    public function clone(Course $course, array $options = []): Course
    {
        return DB::transaction(function () use ($course, $options) {
            // Clone course
            $newCourse = $course->replicate([
                'slug',
                'status',
                'submitted_at',
                'approved_at',
                'rejection_reason',
                'views_count',
            ]);
            
            $newCourse->title = $options['title'] ?? $course->title . ' (Copy)';
            $newCourse->slug = Str::slug($newCourse->title) . '-' . Str::random(6);
            $newCourse->status = 'draft';
            $newCourse->save();
            
            // Clone thumbnail
            if ($course->thumbnail && ($options['clone_media'] ?? true)) {
                $newPath = $this->cloneFile($course->thumbnail, 'courses/thumbnails');
                $newCourse->update(['thumbnail' => $newPath]);
            }
            
            // Clone tags
            $newCourse->tags()->attach($course->tags->pluck('id'));
            
            // Clone modules and lessons
            if ($options['clone_curriculum'] ?? true) {
                $this->cloneModules($course, $newCourse, $options);
            }
            
            // Clone tests and questions
            if ($options['clone_tests'] ?? true) {
                $this->cloneTests($course, $newCourse);
            }
            
            return $newCourse;
        });
    }
    
    private function cloneModules(Course $source, Course $target, array $options): void
    {
        foreach ($source->modules as $module) {
            $newModule = $module->replicate();
            $newModule->course_id = $target->id;
            $newModule->save();
            
            // Clone lessons
            foreach ($module->lessons as $lesson) {
                $newLesson = $lesson->replicate([
                    'video_id',
                ]);
                $newLesson->course_id = $target->id;
                $newLesson->module_id = $newModule->id;
                $newLesson->video_duration = 0; // Reset duration (no video yet)
                $newLesson->save();
                
                // Clone attachments
                if ($options['clone_attachments'] ?? false) {
                    $this->cloneAttachments($lesson, $newLesson);
                }
                
                // Note: Videos are NOT cloned - teacher needs to upload again
            }
        }
    }
    
    private function cloneTests(Course $source, Course $target): void
    {
        // Create mapping for modules and lessons
        $moduleMapping = [];
        $lessonMapping = [];
        
        foreach ($source->modules as $oldModule) {
            $newModule = $target->modules()->where('sort_order', $oldModule->sort_order)->first();
            if ($newModule) {
                $moduleMapping[$oldModule->id] = $newModule->id;
                
                foreach ($oldModule->lessons as $oldLesson) {
                    $newLesson = $newModule->lessons()->where('sort_order', $oldLesson->sort_order)->first();
                    if ($newLesson) {
                        $lessonMapping[$oldLesson->id] = $newLesson->id;
                    }
                }
            }
        }
        
        // Clone tests
        foreach ($source->tests as $test) {
            $newTest = $test->replicate();
            $newTest->course_id = $target->id;
            $newTest->module_id = $moduleMapping[$test->module_id] ?? null;
            $newTest->lesson_id = $lessonMapping[$test->lesson_id] ?? null;
            $newTest->is_active = false; // Deactivate initially
            $newTest->save();
            
            // Clone questions
            foreach ($test->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->test_id = $newTest->id;
                $newQuestion->course_id = $target->id;
                $newQuestion->save();
                
                // Clone options
                foreach ($question->options as $option) {
                    $newOption = $option->replicate();
                    $newOption->question_id = $newQuestion->id;
                    $newOption->save();
                }
            }
        }
    }
    
    private function cloneAttachments(Lesson $source, Lesson $target): void
    {
        foreach ($source->attachments as $attachment) {
            $newPath = $this->cloneFile($attachment->file_path, 'lessons/' . $target->id . '/attachments');
            
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
        if (!Storage::disk('public')->exists($sourcePath)) {
            return null;
        }
        
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $newFileName = Str::uuid() . '.' . $extension;
        $newPath = $directory . '/' . $newFileName;
        
        Storage::disk('public')->copy($sourcePath, $newPath);
        
        return $newPath;
    }
}
