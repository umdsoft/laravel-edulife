<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\CourseQuestion;
use App\Models\CourseAnnouncement;
use App\Models\Video;
use App\Models\VideoChapter;
use App\Services\CourseCloneService;
use Illuminate\Support\Facades\DB;

class VerifyTeacherPanelPart3 extends Command
{
    protected $signature = 'verify:teacher-part3';
    protected $description = 'Verify Teacher Panel Part 3 features';

    public function handle()
    {
        $this->info('Starting verification...');

        DB::beginTransaction();

        try {
            // 1. Setup Data
            $teacher = User::factory()->create(['role' => 'teacher']);
            $student = User::factory()->create(['role' => 'student']);
            
            $direction = \App\Models\Direction::create([
                'name_uz' => 'IT',
                'name_ru' => 'IT',
                'name_en' => 'IT',
                'slug' => 'it',
                'is_active' => true,
            ]);

            $course = Course::create([
                'teacher_id' => $teacher->id,
                'direction_id' => $direction->id,
                'title' => 'Original Course',
                'slug' => 'original-course',
                'level' => 'beginner',
                'short_description' => 'Short desc',
                'description' => 'Long desc',
                'price' => 100000,
                'status' => 'draft',
            ]);

            $module = Module::create([
                'course_id' => $course->id,
                'title' => 'Module 1',
                'sort_order' => 1,
            ]);

            $lesson = Lesson::create([
                'course_id' => $course->id,
                'module_id' => $module->id,
                'title' => 'Lesson 1',
                'type' => 'video',
                'sort_order' => 1,
            ]);

            $this->info('✅ Setup data created.');

            // 2. Attachments
            $attachment = LessonAttachment::create([
                'lesson_id' => $lesson->id,
                'title' => 'Test Attachment',
                'file_name' => 'test.pdf',
                'file_path' => 'lessons/attachments/test.pdf',
                'file_type' => 'pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
            ]);

            if ($lesson->attachments()->count() === 1) {
                $this->info('✅ Attachments verified.');
            } else {
                $this->error('❌ Attachments failed.');
            }

            // 3. Video Chapters
            $video = Video::create([
                'lesson_id' => $lesson->id,
                'original_name' => 'video.mp4',
                'original_path' => 'videos/video.mp4',
                'mime_type' => 'video/mp4',
                'size' => 1024 * 1024,
                'duration' => 120,
                'status' => 'completed',
            ]);

            VideoChapter::create([
                'video_id' => $video->id,
                'lesson_id' => $lesson->id,
                'title' => 'Chapter 1',
                'timestamp' => 10,
            ]);

            if ($video->chapters()->count() === 1) {
                $this->info('✅ Video Chapters verified.');
            } else {
                $this->error('❌ Video Chapters failed.');
            }

            // 4. Q&A
            CourseQuestion::create([
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
                'user_id' => $student->id,
                'content' => 'Test Question',
            ]);

            if ($course->courseQuestions()->count() === 1) {
                $this->info('✅ Q&A verified.');
            } else {
                $this->error('❌ Q&A failed.');
            }

            // 5. Announcements
            CourseAnnouncement::create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'title' => 'Test Announcement',
                'content' => 'Content',
                'type' => 'info',
            ]);

            if ($course->announcements()->count() === 1) {
                $this->info('✅ Announcements verified.');
            } else {
                $this->error('❌ Announcements failed.');
            }

            // 6. Course Clone
            $cloneService = new CourseCloneService();
            $clonedCourse = $cloneService->clone($course, [
                'title' => 'Cloned Course',
                'clone_curriculum' => true,
                'clone_attachments' => true,
            ]);

            if ($clonedCourse->title === 'Cloned Course' && $clonedCourse->modules()->count() === 1) {
                $this->info('✅ Course Clone verified.');
            } else {
                $this->error('❌ Course Clone failed.');
            }

            DB::rollBack();
            $this->info('Verification completed successfully (Changes rolled back).');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Verification failed: ' . $e->getMessage());
            file_put_contents('verification_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
