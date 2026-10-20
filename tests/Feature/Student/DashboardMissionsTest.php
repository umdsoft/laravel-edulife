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

    /** QA3-S-06: "30 daqiqa video tomosha qiling" showed "0 / 1800" (seconds) */
    public function test_watch_time_progress_reads_in_minutes(): void
    {
        DailyMission::forceCreate(['title' => '30 daqiqa video tomosha qiling', 'description' => 'Video', 'type' => 'watch_time', 'code' => 'watch_time', 'target_count' => 1800, 'xp_reward' => 10, 'coin_reward' => 2, 'is_active' => true]);
        DailyMission::forceCreate(['title' => 'Dars tugating', 'description' => 'Dars', 'type' => 'complete_lessons', 'code' => 'lessons', 'target_count' => 3, 'xp_reward' => 10, 'coin_reward' => 2, 'is_active' => true]);
        $student = $this->studentUser();
        $this->actingAs($student)->get(route('student.missions.index'));
        app(MissionService::class)->updateProgress($student, 'watch_time', 750);

        $this->actingAs($student)->get(route('student.missions.index'))
            ->assertInertia(fn (Assert $p) => $p->where('missions', fn ($missions) => collect($missions)->pluck('progress_label')->sort()->values()->all() === ['0 / 3', '12 / 30 daqiqa']));
        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn (Assert $p) => $p->where('dailyMissions.missions', fn ($missions) => collect($missions)->firstWhere('title', '30 daqiqa video tomosha qiling')['progress_label'] === '12 / 30 daqiqa'));
    }
}
