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

    /** QA-T-04: the form always sends is_free=false, which made the price optional */
    public function test_paid_course_needs_a_price_and_free_course_costs_nothing(): void
    {
        $teacher = $this->teacher();
        $direction = $this->course($teacher)->direction_id;
        $payload = [
            'direction_id' => $direction,
            'title' => 'Narxsiz kurs',
            'short_description' => 'Qisqa tavsif matni',
            'description' => str_repeat('Batafsil tavsif ', 10),
            'level' => 'beginner',
            'is_free' => false,
            'price' => '',
        ];

        $this->actingAs($teacher)->post(route('teacher.courses.store'), $payload)->assertSessionHasErrors('price');
        $this->actingAs($teacher)->post(route('teacher.courses.store'), [...$payload, 'price' => 0])->assertSessionHasErrors('price');
        $this->assertFalse(Course::where('title', 'Narxsiz kurs')->exists());

        $this->actingAs($teacher)->post(route('teacher.courses.store'), [...$payload, 'is_free' => true, 'price' => 50000])
            ->assertSessionHasNoErrors();
        $free = Course::where('title', 'Narxsiz kurs')->firstOrFail();
        $this->assertTrue((bool) $free->is_free);
        $this->assertEquals(0, $free->price);

        $this->actingAs($teacher)->put(route('teacher.courses.update', $free), [...$payload, 'is_free' => false, 'price' => ''])
            ->assertSessionHasErrors('price');
    }
}
