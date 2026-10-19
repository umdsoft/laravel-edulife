<?php

namespace Tests\Feature\Teacher;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class BulkLessonTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    /** QA-T-06: it redirected to a non-existent edit tab, hiding the new lessons */
    public function test_bulk_lessons_are_created_and_the_curriculum_is_shown(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $module = $this->module($course);

        $this->actingAs($teacher)->post(route('teacher.courses.modules.bulk-lessons.store', [$course, $module]), [
            'lessons' => [
                ['title' => 'Kasrlar', 'type' => 'text'],
                ['title' => 'Tenglamalar', 'type' => 'video'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('teacher.courses.modules.index', $course));

        $this->assertSame(['Kasrlar', 'Tenglamalar'], $module->lessons()->pluck('title')->all());
    }

    public function test_an_empty_row_gets_a_readable_message(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $module = $this->module($course);

        $this->actingAs($teacher)->post(route('teacher.courses.modules.bulk-lessons.store', [$course, $module]), [
            'lessons' => [['title' => 'Kasrlar', 'type' => 'text'], ['title' => '', 'type' => 'text']],
        ])->assertSessionHasErrors(['lessons.1.title' => "Har bir darsning nomi to'ldirilishi shart."]);
    }
}
