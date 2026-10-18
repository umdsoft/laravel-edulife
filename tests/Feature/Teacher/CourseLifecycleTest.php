<?php

namespace Tests\Feature\Teacher;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class CourseLifecycleTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_course_fields_are_saved_to_real_columns(): void
    {
        $teacher = $this->teacher();
        $direction = $this->course($teacher)->direction_id;

        $this->actingAs($teacher)->post(route('teacher.courses.store'), [
            'direction_id' => $direction,
            'title' => 'Vue 3 kursi',
            'short_description' => 'Qisqa tavsif matni',
            'description' => str_repeat('Batafsil tavsif ', 10),
            'level' => 'advanced',
            'price' => 200000,
            'is_free' => false,
            'what_will_learn' => ['Composition API'],
            'who_is_for' => ['Frontend dasturchilar'],
        ])->assertSessionHasNoErrors();

        $course = Course::where('title', 'Vue 3 kursi')->firstOrFail();
        $this->assertSame('advanced', $course->difficulty);
        $this->assertSame(['Composition API'], $course->what_you_learn);
        $this->assertSame(['Frontend dasturchilar'], $course->target_audience);
        $this->assertSame('draft', $course->status);
    }

    public function test_course_with_thumbnail_and_lesson_can_be_submitted(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['status' => 'draft']);
        $this->lesson($this->module($course));

        $this->actingAs($teacher)
            ->post(route('teacher.courses.thumbnail', $course), ['thumbnail' => UploadedFile::fake()->image('cover.jpg')])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($course->fresh()->thumbnail);

        $this->actingAs($teacher)->patch(route('teacher.courses.submit', $course))->assertSessionHas('success');

        $this->assertSame('pending', $course->fresh()->status);
    }

    public function test_course_without_thumbnail_cannot_be_submitted(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['status' => 'draft']);
        $this->lesson($this->module($course));

        $this->actingAs($teacher)->patch(route('teacher.courses.submit', $course))->assertSessionHas('error');
        $this->assertSame('draft', $course->fresh()->status);
    }
    public function test_editing_text_lesson_keeps_and_updates_text_content(): void
    {
        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)));

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.edit', $lesson))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('lesson.text_content', 'Maxfiy pullik matn'));

        $this->actingAs($teacher)
            ->put(route('teacher.lessons.update', $lesson), ['title' => 'Yangi', 'type' => 'text', 'content' => 'Yangi matn'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Yangi matn', $lesson->fresh()->text_content);
    }

    public function test_course_update_without_language_keeps_existing_language(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['language' => 'ru']);

        $this->actingAs($teacher)->put(route('teacher.courses.update', $course), [
            'direction_id' => $course->direction_id,
            'title' => 'Yangilangan',
            'short_description' => 'Qisqa',
            'description' => str_repeat('Tavsif ', 20),
            'level' => 'beginner',
            'price' => 0,
            'is_free' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('ru', $course->fresh()->language);
        $this->assertSame('Yangilangan', $course->fresh()->title);
    }
}
