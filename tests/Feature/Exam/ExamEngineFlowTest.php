<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Events\Exam\ExamAttemptGraded;
use App\Events\Exam\ExamSessionOpened;
use App\Exceptions\Exam\ExamUnavailable;
use App\Jobs\Exam\AutoSubmitExpiredExamAttemptsJob;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentWeeklyResult;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamSessionLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsExams;
use Tests\TestCase;

class ExamEngineFlowTest extends TestCase
{
    use BuildsExams, RefreshDatabase;

    private function weeklySetup(array $sessionAttrs = []): array
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 4, 2);
        $this->approvedQuestions($a, 2, 3, 'numeric');
        $blueprint = $this->examBlueprint($subject, [[$section, null, 'closed', 3, 1], [$section, null, 'open', 1, 2]], ['duration_min' => 30]);

        return [$subject, $blueprint, $this->openWeeklySession($blueprint, $sessionAttrs)];
    }

    /** Correct answers for every question of an attempt (single choice: option marked correct; numeric: 20). */
    private function correctItems(ExamAttempt $attempt): array
    {
        return $attempt->answers()->with('question.answerOptions')->get()->map(function (ExamAnswer $a) {
            $q = $a->question;

            return $q->examType()->isChoice()
                ? ['question_id' => $q->id, 'selected_option_id' => $q->answerOptions->firstWhere('is_correct', true)->id]
                : ['question_id' => $q->id, 'answer_text' => '20'];
        })->all();
    }

    public function test_start_autosave_submit_and_grade(): void
    {
        Event::fake([ExamAttemptGraded::class]);
        [$subject, , $session] = $this->weeklySetup();
        $student = $this->examStudent($subject);
        $service = app(ExamAttemptService::class);

        $attempt = $service->start($session, $student, ['ip' => '127.0.0.1', 'user_agent' => 'phpunit']);
        $this->assertSame(AttemptStatus::InProgress, $attempt->status);
        $this->assertSame(4, $attempt->answers()->count(), 'one answer row per variant question');
        $this->assertEquals(5, (float) $attempt->max_score, '3 x 1 + 1 x 2 points');
        $this->assertTrue($attempt->expires_at->between(now()->addMinutes(29), now()->addMinutes(31)), 'duration 30 min');

        // the same student gets the same attempt back (one attempt per session)
        $this->assertTrue($service->start($session, $student)->is($attempt));

        $items = $this->correctItems($attempt);
        $items[0]['selected_option_id'] = $attempt->answers()->first()->question->answerOptions()->where('is_correct', false)->value('id');
        $result = $service->saveAnswers($attempt, [...$items, ['question_id' => 'not-in-variant', 'answer_text' => '1']]);
        $this->assertSame(4, $result['saved']);
        $this->assertGreaterThan(0, $result['remaining_seconds']);

        // a second batch only touches what changed
        $this->assertSame(0, $service->saveAnswers($attempt, $items)['saved']);

        $service->submit($attempt);
        $attempt->refresh();
        $this->assertSame(AttemptStatus::Graded, $attempt->status);
        $this->assertEquals(4, (float) $attempt->score);
        $this->assertEquals(80, (float) $attempt->score_percent);
        $this->assertSame([3, 1, 0], [$attempt->correct_count, $attempt->wrong_count, $attempt->skipped_count]);
        Event::assertDispatched(ExamAttemptGraded::class, fn ($e) => $e->attempt->is($attempt) && ! $e->regraded);

        $weekly = StudentWeeklyResult::query()->where('attempt_id', $attempt->id)->firstOrFail();
        $this->assertEquals(80, (float) $weekly->score_percent);
        $this->assertSame(now()->startOfWeek()->toDateString(), $weekly->week_start->toDateString());

        // finished: no more answers, no second attempt
        $this->expectException(ExamUnavailable::class);
        $service->saveAnswers($attempt->fresh(), $items);
    }

    public function test_second_start_after_submit_is_refused(): void
    {
        [$subject, , $session] = $this->weeklySetup();
        $student = $this->examStudent($subject);
        $service = app(ExamAttemptService::class);
        $service->submit($service->start($session, $student));

        try {
            $service->start($session, $student);
            $this->fail('second attempt allowed');
        } catch (ExamUnavailable $e) {
            $this->assertSame('already_done', $e->reason);
        }
        $this->assertSame(1, ExamAttempt::query()->where('session_id', $session->id)->count());
    }

    public function test_session_window_and_audience(): void
    {
        [$subject, $blueprint] = $this->weeklySetup();
        $student = $this->examStudent($subject);
        $service = app(ExamAttemptService::class);

        $future = $this->openWeeklySession($blueprint, ['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3), 'status' => 'scheduled']);
        $past = $this->openWeeklySession($blueprint, ['starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3), 'status' => 'graded']);
        $open = $this->openWeeklySession($blueprint);

        foreach ([[$future, 'not_open'], [$past, 'closed']] as [$session, $reason]) {
            try {
                $service->start($session, $student);
                $this->fail("started {$reason}");
            } catch (ExamUnavailable $e) {
                $this->assertSame($reason, $e->reason);
            }
        }

        // a student whose profile does not list the subject
        try {
            $service->start($open, $this->examStudent(['physics']));
            $this->fail('outsider started');
        } catch (ExamUnavailable $e) {
            $this->assertSame('not_allowed', $e->reason);
        }

        // expires at the session end when the duration would run past it
        $late = $this->openWeeklySession($blueprint, ['starts_at' => now()->subHours(2), 'ends_at' => now()->addMinutes(10)]);
        $attempt = $service->start($late, $student);
        $this->assertTrue($attempt->expires_at->equalTo($late->ends_at));
    }

    public function test_schedule_opens_and_closes_sessions_and_auto_submits(): void
    {
        Event::fake([ExamSessionOpened::class]);
        [$subject, $blueprint] = $this->weeklySetup();
        $session = ExamSession::factory()->create([
            'subject_id' => $subject->id, 'blueprint_id' => $blueprint->id,
            'starts_at' => now()->addMinutes(5), 'ends_at' => now()->addHours(2), 'status' => 'scheduled',
        ]);
        $students = [$this->examStudent($subject), $this->examStudent($subject)];
        $lifecycle = app(ExamSessionLifecycle::class);
        $service = app(ExamAttemptService::class);

        $this->travel(6)->minutes();
        $this->artisan('exam:tick')->assertSuccessful();
        $this->assertSame(ExamSessionStatus::Open, $session->fresh()->status);
        Event::assertDispatchedTimes(ExamSessionOpened::class, 1);
        $lifecycle->sync($session->fresh());
        Event::assertDispatchedTimes(ExamSessionOpened::class, 1);

        $first = $service->start($session->fresh(), $students[0]);
        $service->saveAnswers($first, $this->correctItems($first));
        $second = $service->start($session->fresh(), $students[1]);

        // the first attempt runs out of time (30 min): the scheduler submits it with the saved answers
        $this->travel(31)->minutes();
        (new AutoSubmitExpiredExamAttemptsJob)->handle($service);
        $first->refresh();
        $this->assertSame(AttemptStatus::Graded, $first->status);
        $this->assertEquals(100, (float) $first->score_percent);
        $this->assertTrue($first->submitted_at->equalTo($first->expires_at), 'submitted at the deadline');
        $this->assertSame(AttemptStatus::Graded, $second->fresh()->status, 'also expired, graded with nothing answered');
        $this->assertEquals(0, (float) $second->fresh()->score);

        // late answers are refused
        try {
            $service->saveAnswers($first, $this->correctItems($first));
            $this->fail('saved after the deadline');
        } catch (ExamUnavailable $e) {
            $this->assertSame('attempt_closed', $e->reason);
        }

        // session end: closed, ranked, published
        $third = $this->examStudent($subject);
        $this->travel(2)->hours();
        $this->artisan('exam:tick')->assertSuccessful();
        $session->refresh();
        $this->assertSame(ExamSessionStatus::Graded, $session->status);
        $this->assertNotNull($session->results_published_at);
        $ranks = StudentWeeklyResult::query()->where('session_id', $session->id)->orderBy('rank')->get();
        $this->assertSame([1, 2], $ranks->pluck('rank')->all());
        $this->assertSame($students[0]->id, $ranks->first()->user_id);
        $this->assertSame(2, $ranks->first()->participants_count);
        $this->assertEquals(50, (float) $ranks->first()->percentile);

        try {
            $service->start($session, $third);
            $this->fail('started after the end');
        } catch (ExamUnavailable $e) {
            $this->assertSame('closed', $e->reason);
        }
    }

    public function test_closing_auto_submits_attempts_still_running(): void
    {
        [$subject, $blueprint] = $this->weeklySetup();
        $session = $this->openWeeklySession($blueprint, ['starts_at' => now()->subHours(1), 'ends_at' => now()->addMinutes(20)]);
        $attempt = app(ExamAttemptService::class)->start($session, $this->examStudent($subject));

        $this->travel(21)->minutes();
        app(ExamSessionLifecycle::class)->sync($session);

        $this->assertSame(AttemptStatus::Graded, $attempt->fresh()->status);
        $this->assertSame(ExamSessionStatus::Graded, $session->fresh()->status);
        $this->assertSame(1, StudentWeeklyResult::query()->where('session_id', $session->id)->value('rank'));
    }

    public function test_tab_switches_are_logged_without_blocking(): void
    {
        [$subject, , $session] = $this->weeklySetup();
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($session, $this->examStudent($subject));

        $service->logEvent($attempt, 'tab_switch');
        $service->logEvent($attempt, 'tab_switch', ['hidden_ms' => 4000]);

        $attempt->refresh();
        $this->assertSame(2, $attempt->violations_count);
        $this->assertSame('tab_switch', $attempt->violation_log[1]['type']);
        $this->assertSame(['hidden_ms' => 4000], $attempt->violation_log[1]['meta']);
        $this->assertSame(AttemptStatus::InProgress, $attempt->status);
    }
}
