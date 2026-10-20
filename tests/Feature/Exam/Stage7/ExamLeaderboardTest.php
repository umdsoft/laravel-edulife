<?php

namespace Tests\Feature\Exam\Stage7;

use App\Models\Exam\StudentExamProfile;
use App\Services\Exam\LeaderboardNames;
use App\Services\Exam\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamLeaderboardTest extends TestCase
{
    use BuildsExamPeople, RefreshDatabase;

    private function board(?string $week = null): array
    {
        LeaderboardService::flush();

        return app(LeaderboardService::class)->board($this->math(), $week ?? $this->lastMonday());
    }

    public function test_names_only_with_consent_otherwise_a_stable_nickname(): void
    {
        $week = $this->lastMonday();
        $public = $this->student(['show_name_publicly' => true], ['first_name' => 'Ali', 'last_name' => 'Valiyev']);
        $hidden = $this->student(['show_name_publicly' => false], ['first_name' => 'Madina', 'last_name' => 'Rahimova']);
        $noProfile = $this->student([], ['first_name' => 'Sardor', 'last_name' => 'Karimov']);
        $this->weeklyResult($public, $this->math(), $week, 90);
        $this->weeklyResult($hidden, $this->math(), $week, 80);
        $this->weeklyResult($noProfile, $this->math(), $week, 70);

        $top = collect($this->board()['top']);

        $this->assertSame('Ali V.', $top[0]['name']);
        $this->assertSame(LeaderboardNames::nickname($hidden->id), $top[1]['name']);
        $this->assertSame(LeaderboardNames::nickname($noProfile->id), $top[2]['name']);
        $this->assertStringNotContainsString('Madina', $top[1]['name']);
        $this->assertStringNotContainsString('Sardor', $top[2]['name']);
        $this->assertMatchesRegularExpression('/^\S+ \S+ \d{2}$/u', $top[1]['name']);
        // stable across calls and weeks
        $this->assertSame(LeaderboardNames::nickname($hidden->id), LeaderboardNames::nickname($hidden->id));
        $this->assertNotSame(LeaderboardNames::nickname($hidden->id), LeaderboardNames::nickname($noProfile->id));
    }

    public function test_ranking_handles_ties_and_growth_lists_only_positive_deltas(): void
    {
        $week = $this->lastMonday();
        $a = $this->student(['show_name_publicly' => true], ['first_name' => 'A']);
        $b = $this->student(['show_name_publicly' => true], ['first_name' => 'B']);
        $c = $this->student(['show_name_publicly' => true], ['first_name' => 'C']);
        $d = $this->student(['show_name_publicly' => true], ['first_name' => 'D']);
        $this->weeklyResult($a, $this->math(), $week, 90, 2);
        $this->weeklyResult($b, $this->math(), $week, 80, 15);
        $this->weeklyResult($c, $this->math(), $week, 80, -5);
        $this->weeklyResult($d, $this->math(), $week, 60, null);

        $board = $this->board();

        $this->assertSame([1, 2, 2, 4], array_column($board['top'], 'rank'));
        $this->assertSame(4, $board['participants']);
        $this->assertSame([15.0, 2.0], array_column($board['growth'], 'delta'));
        $this->assertSame([1, 2], array_column($board['growth'], 'rank'));

        $service = app(LeaderboardService::class);
        $this->assertSame(2, $service->positionOf($board, $c)['top']['rank']);
        $this->assertNull($service->positionOf($board, $c)['growth']);
        $this->assertSame(1, $service->positionOf($board, $b)['growth']['rank']);
        $this->assertNull($service->positionOf($board, $this->student()));
    }

    public function test_schools_need_the_minimum_number_of_participants(): void
    {
        config(['exam.leaderboard.school_min_participants' => 3]);
        $week = $this->lastMonday();
        foreach ([70, 80, 90] as $p) {
            $this->weeklyResult($this->student(['school_name' => ' 14-maktab ']), $this->math(), $week, $p);
        }
        foreach ([100, 100] as $p) {
            $this->weeklyResult($this->student(['school_name' => 'Prezident maktabi']), $this->math(), $week, $p);
        }

        $schools = $this->board()['schools'];

        $this->assertCount(1, $schools);
        $this->assertSame('14-maktab', $schools[0]['name']);
        $this->assertSame(80.0, $schools[0]['average']);
        $this->assertSame(3, $schools[0]['participants']);
        $this->assertSame(3, $this->board()['school_min']);
        $this->assertArrayNotHasKey('members', $schools[0]);
    }

    public function test_the_board_is_cached_until_flushed(): void
    {
        $week = $this->lastMonday();
        $this->weeklyResult($this->student(), $this->math(), $week, 50);

        $service = app(LeaderboardService::class);
        LeaderboardService::flush();
        $this->assertSame(1, $service->board($this->math(), $week)['participants']);

        $this->weeklyResult($this->student(), $this->math(), $week, 60);
        $this->assertSame(1, $service->board($this->math(), $week)['participants'], 'served from cache');

        LeaderboardService::flush();
        $this->assertSame(2, $service->board($this->math(), $week)['participants']);
    }

    public function test_a_consent_change_flushes_the_cache(): void
    {
        $week = $this->lastMonday();
        $student = $this->student(['show_name_publicly' => false], ['first_name' => 'Ali', 'last_name' => 'Valiyev']);
        $this->weeklyResult($student, $this->math(), $week, 50);
        $service = app(LeaderboardService::class);
        LeaderboardService::flush();
        $this->assertNotSame('Ali V.', $service->board($this->math(), $week)['top'][0]['name']);

        StudentExamProfile::where('user_id', $student->id)->first()->update(['show_name_publicly' => true]);

        $this->assertSame('Ali V.', $service->board($this->math(), $week)['top'][0]['name']);
    }

    public function test_the_page_shows_boards_and_the_viewers_position_without_user_ids(): void
    {
        $week = $this->lastMonday();
        $me = $this->student(['subjects' => ['math']]);
        $other = $this->student(['show_name_publicly' => false]);
        $this->weeklyResult($other, $this->math(), $week, 95, 4);
        $this->weeklyResult($me, $this->math(), $week, 75, 8);
        LeaderboardService::flush();

        $response = $this->actingAs($me)->get(route('student.exam.leaderboard'))->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Student/Exam/Leaderboard')
            ->where('subject', 'math')
            ->where('week', $week)
            ->has('board.top', 2)
            ->where('me.top.rank', 2)
            ->where('me.growth.rank', 1)
            ->where('me.uid', LeaderboardService::uid($me->id)));

        $this->assertStringNotContainsString($other->id, $response->getContent());
    }

    public function test_the_page_works_without_any_results(): void
    {
        $this->math();
        $this->actingAs($this->student())->get(route('student.exam.leaderboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('board', null)->where('weeks', []));
    }
}
