<?php

namespace Tests\Feature\Admin;

use App\Models\DailyMission;
use App\Models\User;
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
            'task_type' => 'lessons',
            'target_value' => 3,
            'difficulty' => 'medium',
        ]);
    }

    public function test_admin_can_update_a_mission_and_index_exposes_form_keys(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.daily-missions.store'), $this->payload());
        $mission = DailyMission::firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.daily-missions.update', $mission), $this->payload(['type' => 'tests', 'target_count' => 5]))
            ->assertSessionHasNoErrors();

        $this->assertSame('tests', $mission->fresh()->task_type);
        $this->assertSame(5, $mission->fresh()->target_value);

        $this->actingAs($admin)
            ->get(route('admin.daily-missions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/DailyMissions/Index')
                ->has('missions', 1)
                ->where('missions.0.type', 'tests')
                ->where('missions.0.target_count', 5));
    }
}
