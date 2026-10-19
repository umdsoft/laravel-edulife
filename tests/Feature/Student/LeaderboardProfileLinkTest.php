<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA2-S-09: the friends page says "find students in the leaderboard", but the
 * leaderboard rows did not open the student's profile (where "Kuzatish" is).
 */
class LeaderboardProfileLinkTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_leaderboard_rows_open_the_students_profile(): void
    {
        $leader = $this->studentUser();
        $leader->studentProfile->update(['xp' => 500]);
        $viewer = $this->studentUser();

        $userId = $this->actingAs($viewer)->get(route('student.leaderboard.index'))
            ->assertInertia(fn (Assert $p) => $p->where('leaderboard.0.user_id', $leader->id))
            ->viewData('page')['props']['leaderboard'][0]['user_id'];

        $this->actingAs($viewer)->get(route('student.users.show', $userId))->assertOk();

        $vue = file_get_contents(resource_path('js/Pages/Student/Leaderboard/Index.vue'));
        $this->assertStringContainsString("route('student.users.show', player.user_id)", $vue);
    }
}
