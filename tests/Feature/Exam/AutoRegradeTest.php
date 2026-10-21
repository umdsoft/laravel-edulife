<?php

namespace Tests\Feature\Exam;

use App\Jobs\Exam\RegradeQuestionJob;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\QuestionBankOption;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamSessionLifecycle;
use App\Services\Exam\QuestionReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\BuildsExams;
use Tests\TestCase;

/** A fixed or retired question re-grades its attempts without `exam:regrade`. */
class AutoRegradeTest extends TestCase
{
    use BuildsExams, RefreshDatabase;

    /** Two questions, one student answering B everywhere (A is the key): 0 of 2. */
    private function gradedAttempt(): array
    {
        $subject = $this->examSubject();
        [, $topic] = $this->examTopics($subject);
        $questions = $this->approvedQuestions($topic, 2, 2);
        $blueprint = $this->examBlueprint($subject, [[$topic, null, 'closed', 2]], ['shuffle_questions' => false]);
        $session = $this->openWeeklySession($blueprint);
        $service = app(ExamAttemptService::class);

        $attempt = $service->start($session, $this->examStudent($subject));
        $service->saveAnswers($attempt, $attempt->answers()->get()->map(fn ($x) => [
            'question_id' => $x->question_id,
            'selected_option_id' => QuestionBankOption::query()->where('question_id', $x->question_id)->where('label', 'B')->value('id'),
        ])->all());
        $service->submit($attempt);
        app(ExamSessionLifecycle::class)->close($session);

        return [$questions, $attempt->fresh()];
    }

    public function test_re_approving_a_fixed_question_regrades_its_attempts(): void
    {
        [$questions, $attempt] = $this->gradedAttempt();
        $this->assertEquals(0, (float) $attempt->score);

        $review = app(QuestionReviewService::class);
        $review->submitForReview($questions[0]);
        $review->approve($questions[0]->fresh(), $this->examTeacher(), $questions[0]->answerOptions()->where('label', 'B')->value('id'));

        $attempt->refresh();
        $this->assertEquals(1, (float) $attempt->score);
        $this->assertNotNull($attempt->regraded_at);
    }

    public function test_retiring_an_answered_question_voids_it_and_tells_the_teacher(): void
    {
        [$questions, $attempt] = $this->gradedAttempt();
        $this->assertEquals(2, (float) $attempt->max_score);

        $this->actingAs($this->examTeacher())
            ->post(route('teacher.exam.questions.retire', $questions[1]), ['reason' => 'shartda xato'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'qayta hisoblanmoqda'));

        $this->assertEquals(1, (float) $attempt->fresh()->max_score);
    }

    public function test_only_answered_questions_queue_a_regrade(): void
    {
        [$questions] = $this->gradedAttempt();
        Queue::fake();
        $review = app(QuestionReviewService::class);
        $teacher = $this->examTeacher();

        // a new question nobody has answered: approving it queues nothing
        $subject = $questions[0]->subject;
        [, $topic] = $this->examTopics($subject);
        $fresh = $this->approvedQuestions($topic, 1, 2)[0];
        $review->submitForReview($fresh);
        $review->approve($fresh->fresh(), $teacher);
        Queue::assertNotPushed(RegradeQuestionJob::class);

        $review->retire($questions[1]->fresh(), $teacher, 'xato');
        Queue::assertPushed(RegradeQuestionJob::class, fn ($job) => $job->questionId === $questions[1]->id);
    }
}
