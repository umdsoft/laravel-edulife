<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class CourseVisibilityTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_course_page_does_not_leak_paid_lesson_content_or_teacher_pii(): void
    {
        $course = $this->course($this->teacher(), ['is_free' => false, 'price' => 100000]);
        $this->lesson($this->module($course));

        $this->actingAs($this->studentUser())
            ->get(route('student.courses.show', $course->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Courses/Show')
                ->has('course.modules.0.lessons.0', fn (Assert $lesson) => $lesson
                    ->where('title', 'Birinchi dars')
                    ->missing('video_url')
                    ->missing('text_content')
                    ->missing('source_url')
                    ->etc())
                ->missing('course.teacher.phone')
                ->missing('course.teacher.email'));

        $this->assertSame(1, $course->fresh()->views_count);
    }

    public function test_draft_course_is_hidden_from_students(): void
    {
        $course = $this->course($this->teacher(), ['status' => 'draft']);

        $this->actingAs($this->studentUser())
            ->get(route('student.courses.show', $course->slug))
            ->assertNotFound();
    }

    public function test_preview_lesson_must_belong_to_course(): void
    {
        $course = $this->course($this->teacher());
        $foreignLesson = $this->lesson($this->module($this->course($this->teacher())), ['is_preview' => true]);

        $this->actingAs($this->studentUser())
            ->get(route('student.courses.preview', [$course->slug, $foreignLesson]))
            ->assertNotFound();
    }

    public function test_non_preview_lesson_is_forbidden(): void
    {
        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course), ['is_preview' => false, 'is_free' => false]);

        $this->actingAs($this->studentUser())
            ->get(route('student.courses.preview', [$course->slug, $lesson]))
            ->assertForbidden();
    }
}
