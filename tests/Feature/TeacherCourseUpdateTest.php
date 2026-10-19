<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Direction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TeacherCourseUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_update_without_language_keeps_existing_language(): void
    {
        // This test covers the language fallback only; course ownership policies are tested elsewhere.
        Gate::before(fn () => true);

        $direction = Direction::factory()->create();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'direction_id' => $direction->id,
            'language' => 'ru',
        ]);

        $this->actingAs($teacher)->put(route('teacher.courses.update', $course), [
            'direction_id' => $direction->id,
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
