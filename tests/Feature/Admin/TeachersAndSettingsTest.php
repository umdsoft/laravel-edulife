<?php

namespace Tests\Feature\Admin;

use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeachersAndSettingsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** QA-A-10: courses_count counted the courses the teacher was *enrolled* in */
    public function test_teacher_list_and_page_count_the_teachers_own_courses(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $this->course($teacher, ['status' => 'draft']);
        foreach ([$this->studentUser(), $this->studentUser()] as $student) {
            Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        }

        $this->actingAs($this->admin())->get(route('admin.teachers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('teachers.data.0.total_courses', 2)
                ->where('teachers.data.0.total_students', 2));

        $this->actingAs($this->admin())->get(route('admin.teachers.show', $teacher))
            ->assertInertia(fn (Assert $page) => $page
                ->where('teacher.total_courses', 2)
                ->where('teacher.total_students', 2)
                ->has('courses', 2));
    }

    /** QA-A-13: the page sends "group.key" and nothing was updated */
    public function test_settings_are_saved_with_group_prefixed_keys(): void
    {
        Setting::forceCreate(['key' => 'xp_per_lesson', 'group' => 'xp', 'value' => '30', 'type' => 'integer']);

        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'settings' => [['key' => 'xp.xp_per_lesson', 'value' => 33]],
        ])->assertSessionHas('success');

        $this->assertSame('33', Setting::where('key', 'xp_per_lesson')->value('value'));

        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'settings' => [['key' => 'xp.mavjud_emas', 'value' => 1]],
        ])->assertSessionHas('error');
    }
}
