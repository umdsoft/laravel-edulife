<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\ExamSessionStatus;
use App\Events\Exam\ExamAttemptGraded;
use App\Exceptions\Exam\ExamUnavailable;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\QuestionBank;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamPlacementService;
use App\Services\Exam\ExamSessionLifecycle;
use App\Services\Exam\QuestionReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsExams;
use Tests\TestCase;

class ExamMasteryRegradeTest extends TestCase
{
    use BuildsExams, RefreshDatabase;

    /** Answer the attempt: the first $right questions (variant order) correctly, the rest wrong. */
    private function answer(ExamAttempt $attempt, int $right): void
    {
        $items = $attempt->answers()->with('question.answerOptions')->orderBy('position')->get()
            ->map(function (ExamAnswer $a, int $i) use ($right) {
                $correct = $a->question->answerOptions->firstWhere('is_correct', true);
                $wrong = $a->question->answerOptions->firstWhere('is_correct', false);

                return ['question_id' => $a->question_id, 'selected_option_id' => ($i < $right ? $correct : $wrong)->id];
            })->all();
        app(ExamAttemptService::class)->saveAnswers($attempt, $items);
    }

    public function test_placement_is_once_per_subject_seeds_mastery_and_a_retake_needs_the_teacher(): void
    {
        $subject = $this->examSubject();
        [$section, $a, $b] = $this->examTopics($subject);
        $this->approvedQuestions($a, 2, 2);
        $this->approvedQuestions($b, 2, 4);
        $this->examBlueprint($subject, [[$a, null, 'closed', 2], [$b, null, 'closed', 2]], ['purpose' => 'placement', 'duration_min' => 20]);
        $student = $this->examStudent($subject);
        $placement = app(ExamPlacementService::class);
        $service = app(ExamAttemptService::class);

        $session = $placement->sessionFor($student, $subject);
        $this->assertNotNull($session);
        $this->assertSame(ExamSessionStatus::Open, $session->status);
        $this->assertTrue($placement->publicSession($subject)->is($session), 'one public placement session per subject');

        $attempt = $service->start($session, $student);
        // topic A: both right, topic B: both wrong
        $items = $attempt->answers()->with('question.answerOptions')->get()->map(fn (ExamAnswer $x) => [
            'question_id' => $x->question_id,
            'selected_option_id' => $x->question->answerOptions->firstWhere('is_correct', $x->question->topic_id === $a->id)->id,
        ])->all();
        $service->saveAnswers($attempt, $items);
        $service->submit($attempt);

        $mastery = StudentTopicMastery::query()->where('user_id', $student->id)->get()->keyBy('topic_id');
        $this->assertEquals(100, (float) $mastery[$a->id]->score, 'alpha = 1: the placement result is the starting value');
        $this->assertEquals(0, (float) $mastery[$b->id]->score);
        $this->assertSame('placement', $mastery[$a->id]->source);
        $this->assertSame([1, 2, 2], [$mastery[$a->id]->attempts_count, $mastery[$a->id]->answered_count, $mastery[$a->id]->correct_count]);
        $this->assertNotNull(StudentExamProfile::query()->where('user_id', $student->id)->value('placement_completed_at'));
        $this->assertSame(0, StudentWeeklyResult::query()->count(), 'placement is not a weekly result');

        // once per subject
        $this->assertNull($placement->sessionFor($student, $subject));
        try {
            $service->start($placement->publicSession($subject), $student);
            $this->fail('second placement allowed');
        } catch (ExamUnavailable $e) {
            $this->assertSame('already_done', $e->reason);
        }

        // the teacher allows a retake: a private session for this student only
        $retake = $placement->allowRetake($student, $subject, $this->examTeacher());
        $this->assertSame($student->id, $retake->restricted_to_user_id);
        $this->assertTrue($placement->sessionFor($student, $subject)->is($retake));
        try {
            $service->start($retake, $this->examStudent($subject));
            $this->fail('someone else used the retake');
        } catch (ExamUnavailable $e) {
            $this->assertSame('not_allowed', $e->reason);
        }

        $again = $service->start($retake, $student);
        $this->answer($again, 4);
        $service->submit($again);
        $this->assertEquals(100, (float) StudentTopicMastery::query()->where('user_id', $student->id)->where('topic_id', $b->id)->value('score'), 'retake overwrites (alpha 1)');
        $this->assertNull($placement->sessionFor($student, $subject), 'retake used up');
    }

    public function test_weekly_attempts_update_mastery_with_ema_and_weekly_delta(): void
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 12, 2);
        $blueprint = $this->examBlueprint($subject, [[$a, null, 'closed', 4]], ['avoid_repeat_weeks' => 0]);
        $student = $this->examStudent($subject);
        $service = app(ExamAttemptService::class);

        // week 1: 4/4 -> first observation, mastery = 100
        $week1 = $this->openWeeklySession($blueprint, ['starts_at' => now()->subDays(7)->subMinutes(10), 'ends_at' => now()->addHours(3)]);
        $attempt = $service->start($week1, $student);
        $this->answer($attempt, 4);
        $service->submit($attempt);
        $this->assertEquals(100, (float) StudentTopicMastery::query()->where('topic_id', $a->id)->value('score'));

        // week 2: 1/4 = 25 -> 0.7 * 100 + 0.3 * 25 = 77.5
        $week2 = $this->openWeeklySession($blueprint);
        $attempt2 = $service->start($week2, $student);
        $this->answer($attempt2, 1);
        $service->submit($attempt2);

        $row = StudentTopicMastery::query()->where('topic_id', $a->id)->first();
        $this->assertEquals(77.5, (float) $row->score);
        $this->assertSame('weekly', $row->source);
        $this->assertSame([2, 8, 5], [$row->attempts_count, $row->answered_count, $row->correct_count]);

        $results = StudentWeeklyResult::query()->where('user_id', $student->id)->orderBy('week_start')->get();
        $this->assertCount(2, $results);
        $this->assertNull($results[0]->delta_prev);
        $this->assertEquals(-75, (float) $results[1]->delta_prev, '25 % - 100 %');
    }

    public function test_regrade_after_a_fix_and_after_retiring(): void
    {
        Event::fake([ExamAttemptGraded::class]);
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $questions = $this->approvedQuestions($a, 2, 2);
        $blueprint = $this->examBlueprint($subject, [[$a, null, 'closed', 2]], ['shuffle_questions' => false]);
        $session = $this->openWeeklySession($blueprint);
        $student = $this->examStudent($subject);
        $other = $this->examStudent($subject);
        $service = app(ExamAttemptService::class);

        // both students choose option B for every question; A is the key -> 0 points
        foreach ([$student, $other] as $user) {
            $attempt = $service->start($session, $user);
            $items = $attempt->answers()->get()->map(fn ($x) => [
                'question_id' => $x->question_id,
                'selected_option_id' => QuestionBankOption::query()->where('question_id', $x->question_id)->where('label', 'B')->value('id'),
            ])->all();
            $service->saveAnswers($attempt, $items);
            $service->submit($attempt);
        }
        app(ExamSessionLifecycle::class)->close($session);
        $this->assertEquals(0, (float) ExamAttempt::query()->where('user_id', $student->id)->value('score'));

        // the teacher finds that B is the right answer of question 1 and fixes it
        $fixed = $questions[0];
        $teacher = $this->examTeacher();
        app(QuestionReviewService::class)->submitForReview($fixed);
        app(QuestionReviewService::class)->approve($fixed->fresh(), $teacher, $fixed->answerOptions()->where('label', 'B')->value('id'));

        $this->artisan('exam:regrade', ['question' => $fixed->id])
            ->expectsOutputToContain('2 ta urinish')
            ->assertSuccessful();

        $attempt = ExamAttempt::query()->where('user_id', $student->id)->first();
        $this->assertEquals(1, (float) $attempt->score);
        $this->assertEquals(50, (float) $attempt->score_percent);
        $this->assertNotNull($attempt->regraded_at);
        $this->assertEquals(50, (float) StudentTopicMastery::query()->where('user_id', $student->id)->value('score'), 'mastery rebuilt');
        $this->assertEquals(50, (float) StudentWeeklyResult::query()->where('attempt_id', $attempt->id)->value('score_percent'));
        Event::assertDispatched(ExamAttemptGraded::class, fn ($e) => $e->regraded && $e->attempt->is($attempt));

        // question 2 had an error and is retired: voided for everybody (0 of 0)
        app(QuestionReviewService::class)->retire($questions[1]->fresh(), $teacher, 'shartda xato');
        $this->artisan('exam:regrade', ['question' => $questions[1]->id])->assertSuccessful();

        $attempt->refresh();
        $this->assertEquals(1, (float) $attempt->score);
        $this->assertEquals(1, (float) $attempt->max_score);
        $this->assertEquals(100, (float) $attempt->score_percent);
        $this->assertSame([1, 0, 0], [$attempt->correct_count, $attempt->wrong_count, $attempt->skipped_count]);
        $voided = ExamAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $questions[1]->id)->first();
        $this->assertNull($voided->is_correct);
        $this->assertEquals(0, (float) $voided->max_points);
        $this->assertSame([1, 1], StudentWeeklyResult::query()->where('session_id', $session->id)->pluck('rank')->all(), 'tie after regrade');

        $this->artisan('exam:regrade', ['question' => 'missing'])->assertFailed();
        $this->assertSame(QuestionBank::query()->count(), 2);
    }
}
