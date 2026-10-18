<?php

namespace Tests\Feature\Teacher;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class CourseOwnershipTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_teacher_can_update_module_of_own_course_via_shallow_route(): void
    {
        $teacher = $this->teacher();
        $module = $this->module($this->course($teacher));

        $this->actingAs($teacher)
            ->put(route('teacher.modules.update', $module), ['title' => 'Yangi nom'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Yangi nom', $module->fresh()->title);
    }

    public function test_teacher_cannot_update_module_of_another_teachers_course(): void
    {
        $owner = $this->teacher();
        $module = $this->module($this->course($owner));

        $this->actingAs($this->teacher())
            ->put(route('teacher.modules.update', $module), ['title' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('Kirish', $module->fresh()->title);
    }

    public function test_teacher_cannot_delete_foreign_lesson(): void
    {
        $owner = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($owner)));

        $this->actingAs($this->teacher())
            ->delete(route('teacher.lessons.destroy', $lesson))
            ->assertForbidden();

        $this->assertNotSoftDeleted($lesson);
    }

    public function test_teacher_can_delete_own_lesson(): void
    {
        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)));

        $this->actingAs($teacher)
            ->delete(route('teacher.lessons.destroy', $lesson))
            ->assertRedirect();

        $this->assertSoftDeleted($lesson);
    }

    public function test_nested_child_must_belong_to_parent_course(): void
    {
        $attacker = $this->teacher();
        $ownCourse = $this->course($attacker);
        $foreignModule = $this->module($this->course($this->teacher()));

        // Own course id + another teacher's module id must not resolve
        $this->actingAs($attacker)
            ->post(route('teacher.courses.modules.lessons.store', [$ownCourse, $foreignModule]), [
                'title' => 'Injected', 'type' => 'text',
            ])
            ->assertNotFound();
    }

    public function test_teacher_cannot_edit_foreign_course(): void
    {
        $course = $this->course($this->teacher());

        $this->actingAs($this->teacher())
            ->get(route('teacher.courses.edit', $course))
            ->assertForbidden();
    }
}
