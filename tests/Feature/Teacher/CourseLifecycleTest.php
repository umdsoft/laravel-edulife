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

    public function test_discount_price_maps_to_price_and_original_price(): void
    {
        $teacher = $this->teacher();
        $direction = $this->course($teacher)->direction_id;

        $this->actingAs($teacher)->post(route('teacher.courses.store'), [
            'direction_id' => $direction,
            'title' => 'Chegirmali kurs',
            'short_description' => 'Qisqa tavsif matni',
            'description' => str_repeat('Batafsil tavsif ', 10),
            'level' => 'beginner',
            'price' => 200000,
            'discount_price' => 150000,
            'is_free' => false,
        ])->assertSessionHasNoErrors();

        $course = Course::where('title', 'Chegirmali kurs')->firstOrFail();
        $this->assertEquals(150000, $course->price);
        $this->assertEquals(200000, $course->original_price);
        $this->assertEquals(150000, $course->discount_price);
        $this->assertSame(25, $course->discount_percent);
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
}
