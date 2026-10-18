<?php

namespace Tests\Concerns;

use App\Models\Course;
use App\Models\Direction;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesCourses
{
    protected function teacher(): User
    {
        return User::factory()->create(['role' => 'teacher', 'status' => 'active']);
    }

    protected function studentUser(): User
    {
        return User::factory()->create(['role' => 'student', 'status' => 'active']);
    }

    protected function course(User $teacher, array $attributes = []): Course
    {
        $direction = Direction::forceCreate([
            'name_uz' => 'Dasturlash',
            'slug' => 'dir-'.Str::random(8),
        ]);

        return Course::forceCreate(array_merge([
            'teacher_id' => $teacher->id,
            'direction_id' => $direction->id,
            'title' => 'Laravel asoslari',
            'slug' => 'course-'.Str::random(8),
            'short_description' => 'Qisqa tavsif',
            'description' => str_repeat('Tavsif ', 20),
            'status' => 'published',
            'is_free' => true,
        ], $attributes));
    }

    protected function module(Course $course): Module
    {
        return Module::forceCreate([
            'course_id' => $course->id,
            'title' => 'Kirish',
            'sort_order' => 1,
        ]);
    }

    protected function lesson(Module $module, array $attributes = []): Lesson
    {
        return Lesson::forceCreate(array_merge([
            'module_id' => $module->id,
            'course_id' => $module->course_id,
            'title' => 'Birinchi dars',
            'type' => 'text',
            'sort_order' => 1,
            'text_content' => 'Maxfiy pullik matn',
            'video_url' => '/storage/videos/secret.mp4',
        ], $attributes));
    }
}
