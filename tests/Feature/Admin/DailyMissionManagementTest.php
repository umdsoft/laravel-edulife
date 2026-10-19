<?php

namespace Tests\Feature\Admin;

use App\Models\DailyMission;
use App\Models\User;
use App\Models\UserMission;
use App\Services\MissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyMissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** Payload exactly as the Admin/DailyMissions/Index form posts it. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'lessons_3',
            'title' => '3 ta dars',
            'description' => 'Bugun 3 ta darsni tugating',
            'icon' => '📚',
            'type' => 'lessons',
            'target_count' => 3,
            'xp_reward' => 50,
            'coin_reward' => 10,
            'difficulty' => 'medium',
            'is_active' => true,
        ], $overrides);
    }

    public function test_admin_can_create_a_mission_from_the_form_payload(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.daily-missions.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('daily_missions', [
            'code' => 'lessons_3',
            'icon' => '📚',
            'type' => 'complete_lessons',
            'target_count' => 3,
            'difficulty' => 'medium',
        ]);
    }

    public function test_admin_can_update_a_mission_and_index_exposes_form_keys(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.daily-missions.store'), $this->payload());
        $mission = DailyMission::firstOrFail();

        // same code must be accepted for the mission being edited
        $this->actingAs($admin)
            ->put(route('admin.daily-missions.update', $mission), $this->payload(['type' => 'tests', 'target_count' => 5]))
            ->assertSessionHasNoErrors();

        $this->assertSame('pass_tests', $mission->fresh()->type);
        $this->assertSame(5, $mission->fresh()->target_count);

        $this->actingAs($admin)
            ->get(route('admin.daily-missions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/DailyMissions/Index')
                ->has('missions', 1)
                ->where('missions.0.type', 'tests')
                ->where('missions.0.target_count', 5)
                ->where('missions.0.code', 'lessons_3'));
    }

    public function test_mission_code_must_be_unique(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.daily-missions.store'), $this->payload());

        $this->actingAs($admin)
            ->post(route('admin.daily-missions.store'), $this->payload(['title' => 'Boshqa']))
            ->assertSessionHasErrors('code');
    }

    public function test_battle_events_advance_matching_missions(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $mission = DailyMission::create([
            'title' => '1 ta battle yuting',
            'description' => 'Bugun 1 ta battle yuting',
            'type' => 'win_battles',
            'target_count' => 1,
        ]);
        $userMission = UserMission::create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
            'target_value' => 1,
            'assigned_date' => today(),
        ]);

        $service = app(MissionService::class);
        $service->updateProgress($student, 'battle_complete'); // play_battles: no match
        $this->assertFalse($userMission->fresh()->is_completed);

        $service->updateProgress($student, 'battle_win');
        $this->assertTrue($userMission->fresh()->is_completed);
    }
}
