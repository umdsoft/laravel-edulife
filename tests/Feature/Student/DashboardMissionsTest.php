<?php

namespace Tests\Feature\Student;

use App\Models\DailyMission;
use App\Models\UserMission;
use App\Services\MissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** QA-S-09: the dashboard and /student/missions showed different missions. */
class DashboardMissionsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_dashboard_shows_the_same_missions_and_progress_as_the_missions_page(): void
    {
        foreach (['complete_lessons' => 'Dars tugating', 'pass_tests' => "Testdan o'ting", 'daily_login' => 'Kiring'] as $type => $title) {
            DailyMission::forceCreate(['title' => $title, 'description' => $title, 'type' => $type, 'code' => $type, 'target_count' => 2, 'xp_reward' => 10, 'coin_reward' => 5, 'is_active' => true]);
        }
        $student = $this->studentUser();

        $this->actingAs($student)->get(route('student.missions.index'));
        $missionIds = UserMission::where('user_id', $student->id)->pluck('id')->sort()->values()->all();

        app(MissionService::class)->updateProgress($student, 'complete_lessons');

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('dailyMissions.missions', fn ($missions) => collect($missions)->pluck('id')->sort()->values()->all() === $missionIds
                    && collect($missions)->firstWhere('title', 'Dars tugating')['current'] === 1));
    }
}
