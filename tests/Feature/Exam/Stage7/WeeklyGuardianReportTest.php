<?php

namespace Tests\Feature\Exam\Stage7;

use App\Events\Exam\WeeklyReportReady;
use App\Jobs\Exam\SendWeeklyGuardianReportsJob;
use App\Models\Exam\GuardianReport;
use App\Models\Notification as AppNotification;
use App\Services\Exam\ReportBuilder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WeeklyGuardianReportTest extends TestCase
{
    use BuildsExamPeople, RefreshDatabase;

    private function runJob(?string $week = null): int
    {
        return (new SendWeeklyGuardianReportsJob($week))->handle(app(ReportBuilder::class));
    }

    public function test_the_report_has_score_delta_topic_map_and_three_recommendations(): void
    {
        Event::fake([WeeklyReportReady::class]);
        $week = $this->lastMonday();
        $guardian = $this->guardianUser();
        $child = $this->student(['subjects' => ['math'], 'grade' => 10], ['first_name' => 'Ali']);
        $link = $this->link($guardian, $child);

        [$algebra, $quadratic] = $this->topicPair($this->math(), 'Algebra', 'Kvadrat tenglamalar', 1);
        [$geometry, $triangles] = $this->topicPair($this->math(), 'Geometriya', 'Uchburchaklar', 2);
        [$trig, $identities] = $this->topicPair($this->math(), 'Trigonometriya', 'Ayniyatlar', 3);
        [, $logs] = $this->topicPair($this->math(), 'Logarifmlar', 'Logarifm xossalari', 4);
        $this->mastery($child, $quadratic, 35);
        $this->mastery($child, $triangles, 82);
        $this->mastery($child, $identities, 55);
        $this->mastery($child, $logs, 95);

        $this->weeklyResult($child, $this->math(), $week, 71.1, 6.7, ['score' => 32, 'max_score' => 45, 'rank' => 3, 'participants_count' => 40]);

        $this->assertSame(1, $this->runJob());

        $report = GuardianReport::where('guardian_student_id', $link->id)->firstOrFail();
        $p = $report->payload;
        $this->assertSame($week, $p['week_start']);
        $this->assertTrue($p['participated']);
        $this->assertSame('Ali', $p['student']['first_name']);
        $this->assertCount(1, $p['subjects']);
        $math = $p['subjects'][0];
        $this->assertSame('math', $math['code']);
        $this->assertEquals(32, $math['score']);
        $this->assertEquals(45, $math['max_score']);
        $this->assertEquals(71.1, $math['percent']);
        $this->assertEquals(6.7, $math['delta']);
        $this->assertSame(3, $math['rank']);
        $this->assertEquals(['Algebra' => 35.0, 'Geometriya' => 82.0, 'Trigonometriya' => 55.0, 'Logarifmlar' => 95.0],
            collect($math['topics'])->pluck('score', 'name')->all());

        // weakest first: 35, 55, 82
        $this->assertSame(['Kvadrat tenglamalar', 'Ayniyatlar', 'Uchburchaklar'], array_column($p['recommendations'], 'name'));
        $this->assertSame(['weak', 'weak', 'improve'], array_column($p['recommendations'], 'reason'));

        // in-app notification
        $note = AppNotification::where('user_id', $guardian->id)->where('type', 'exam_weekly_report')->firstOrFail();
        $this->assertStringContainsString('Ali', $note->title);
        $this->assertStringContainsString('71,1% (+6,7)', $note->body);
        $this->assertSame($report->id, $note->data['report_id']);
        $this->assertSame(route('guardian.reports.show', $report, false), $note->action_url);

        Event::assertDispatched(WeeklyReportReady::class, fn (WeeklyReportReady $e) => $e->link->is($link)
            && $e->report['report_id'] === $report->id
            && count($e->report['recommendations']) === 3
            && $e->report['subjects'][0]['delta'] == 6.7);
    }

    public function test_recommendations_are_filled_with_new_topics_when_little_is_measured(): void
    {
        $child = $this->student(['subjects' => ['math'], 'grade' => 9]);
        [, $a] = $this->topicPair($this->math(), 'A', 'A1', 1);
        [, $b] = $this->topicPair($this->math(), 'B', 'B1', 2);
        [, $c] = $this->topicPair($this->math(), 'C', 'C1', 3);
        $this->mastery($child, $a, 40);

        $recs = app(ReportBuilder::class)->recommendations($child);

        $this->assertSame(['A1', 'B1', 'C1'], array_column($recs, 'name'));
        $this->assertSame(['weak', 'new', 'new'], array_column($recs, 'reason'));
    }

    public function test_only_approved_consented_links_that_want_reports_get_one(): void
    {
        Event::fake([WeeklyReportReady::class]);
        $guardian = $this->guardianUser();
        $this->link($guardian, $this->student(), verified: false);                       // pending
        $this->link($guardian, $this->student(), attrs: ['consent_at' => null]);         // no consent
        $this->link($guardian, $this->student(), attrs: ['receives_reports' => false]);  // opted out
        $this->link($guardian, $this->student(), attrs: ['rejected_at' => now()]);       // rejected
        $this->link($this->guardianUser(['status' => 'blocked']), $this->student());     // blocked guardian
        $ok = $this->link($guardian, $this->student());

        $this->assertSame(1, $this->runJob());

        $this->assertSame([$ok->id], GuardianReport::pluck('guardian_student_id')->all());
        Event::assertDispatchedTimes(WeeklyReportReady::class, 1);
    }

    public function test_running_twice_sends_a_week_only_once(): void
    {
        Event::fake([WeeklyReportReady::class]);
        $guardian = $this->guardianUser();
        $this->link($guardian, $this->student(['subjects' => ['math']]));

        $this->assertSame(1, $this->runJob());
        $this->assertSame(0, $this->runJob());

        $this->assertSame(1, GuardianReport::count());
        $this->assertSame(1, AppNotification::where('user_id', $guardian->id)->count());
        Event::assertDispatchedTimes(WeeklyReportReady::class, 1);
    }

    public function test_a_week_without_an_exam_is_reported_as_missed(): void
    {
        $guardian = $this->guardianUser();
        $child = $this->student(['subjects' => ['math']]);
        $this->link($guardian, $child);
        $this->weeklyResult($child, $this->math(), now()->subWeeks(2)->startOfWeek()->toDateString(), 60);

        $this->runJob();

        $p = GuardianReport::firstOrFail()->payload;
        $this->assertFalse($p['participated']);
        $this->assertFalse($p['subjects'][0]['participated']);
        $this->assertNull($p['subjects'][0]['percent']);
        $this->assertStringContainsString('topshirilmadi', AppNotification::where('user_id', $guardian->id)->value('body'));
    }

    public function test_the_command_and_the_sunday_schedule(): void
    {
        $guardian = $this->guardianUser();
        $this->link($guardian, $this->student(['subjects' => ['math']]));

        $this->artisan('exam:weekly-reports', ['--week' => now()->toDateString()])->assertSuccessful();
        $this->assertSame(1, GuardianReport::count());

        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'exam:weekly-reports'));
        $this->assertNotNull($event, 'exam:weekly-reports is scheduled');
        $this->assertSame('0 9 * * 0', $event->expression);
        $this->assertSame('Asia/Tashkent', $event->timezone);
    }
}
