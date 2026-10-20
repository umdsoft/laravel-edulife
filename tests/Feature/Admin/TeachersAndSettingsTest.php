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

    /** QA2-A-04: 13 fields of the settings page used keys that are not in the settings table */
    public function test_every_field_of_the_settings_page_is_a_seeded_setting(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $vue = file_get_contents(resource_path('js/Pages/Admin/Settings/Index.vue'));
        preg_match('/const FIELDS = \{(.*?)\n\};/s', $vue, $block);
        preg_match_all('/^    (\w+): \[(.*?)^    \],/ms', $block[1], $sections, PREG_SET_ORDER);
        $this->assertNotEmpty($sections);

        $payload = [];
        foreach ($sections as [, $section, $fields]) {
            $group = ['coinLimits' => 'coin', 'battleLimits' => 'battle', 'battleScoring' => 'battle'][$section] ?? $section;
            preg_match_all("/key: '([a-z0-9_]+)'/", $fields, $keys);
            foreach ($keys[1] as $key) {
                $payload[] = ['key' => "{$group}.{$key}", 'value' => 7];
            }
        }
        $this->assertCount(33, $payload);

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), ['settings' => $payload])
            ->assertSessionHas('success');
        $this->assertSame('7', Setting::where('key', 'battle_daily_limit_trial')->value('value'));
    }

    /** battle scoring parameters are edited on the settings page and read by the scoring code */
    public function test_battle_scoring_settings_are_editable_and_used(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class); // re-seeding is idempotent
        $this->assertSame(1, Setting::where('key', 'battle_scoring_k')->count());

        $scoring = app(\App\Services\BattleScoringSettings::class);
        $this->assertSame(100, $scoring->base());
        $this->assertSame(2.0, $scoring->k());

        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'settings' => [['key' => 'battle.battle_scoring_base', 'value' => 150], ['key' => 'battle.battle_scoring_k', 'value' => 1.5]],
        ])->assertSessionHas('success');

        $this->assertSame(150, $scoring->base());
        $this->assertSame(1.5, $scoring->k());
    }

    /** QA2-A-04: saving logged "Sozlama o'zgartirildi" for every untouched field too */
    public function test_only_changed_settings_are_written_to_the_system_log(): void
    {
        Setting::forceCreate(['key' => 'xp_per_lesson', 'group' => 'xp', 'value' => '30', 'type' => 'integer']);
        Setting::forceCreate(['key' => 'coin_per_lesson', 'group' => 'coin', 'value' => '5', 'type' => 'integer']);

        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'settings' => [['key' => 'xp.xp_per_lesson', 'value' => 33], ['key' => 'coin.coin_per_lesson', 'value' => 5]],
        ])->assertSessionHas('success');

        $logged = \App\Models\AuditLog::pluck('description')->implode('|');
        $this->assertStringContainsString('xp_per_lesson', $logged);
        $this->assertStringNotContainsString('coin_per_lesson', $logged);
    }
}
