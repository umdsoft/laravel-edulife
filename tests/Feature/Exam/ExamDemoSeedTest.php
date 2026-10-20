<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\Subject;
use App\Models\User;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\ExamPlacementService;
use Database\Seeders\Exam\ExamDemoSessionSeeder;
use Database\Seeders\Exam\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The seeded demo bank drives the real engine: a demo student can take the
 * placement test of each subject, and an upcoming Saturday session exists.
 */
class ExamDemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_student_takes_the_seeded_placement_test_and_a_saturday_session_is_scheduled(): void
    {
        $student = User::factory()->create(['email' => ExamDemoSessionSeeder::DEMO_STUDENT_EMAIL, 'role' => 'student', 'status' => 'active']);
        $this->seed(ExamSeeder::class);
        $this->seed(ExamSeeder::class); // idempotent

        $profile = StudentExamProfile::query()->where('user_id', $student->id)->firstOrFail();
        $this->assertTrue($profile->isComplete());

        $saturday = ExamSession::query()->where('type', 'weekly')->get();
        $this->assertCount(1, $saturday);
        $this->assertSame(ExamSessionStatus::Scheduled, $saturday[0]->status);
        $this->assertTrue($saturday[0]->starts_at->isSaturday() && $saturday[0]->starts_at->isFuture());
        $this->assertSame(1, ExamVariant::query()->where('session_id', $saturday[0]->id)->count(), 'variant of the demo student pre-generated');
        $this->assertCount(45, ExamVariant::query()->where('session_id', $saturday[0]->id)->first()->question_ids, 'math weekly: 15 sections x 3');

        foreach ([Subject::MATH => 15, Subject::PHYSICS => 11] as $code => $questions) {
            $subject = Subject::query()->where('code', $code)->firstOrFail();
            $session = app(ExamPlacementService::class)->sessionFor($student, $subject);
            $this->assertNotNull($session, $code);

            $attempt = app(ExamAttemptService::class)->start($session, $student);
            $this->assertSame($questions, $attempt->answers()->count(), $code);

            // answer everything correctly
            $items = ExamAnswer::query()->where('attempt_id', $attempt->id)->with('question.answerOptions')->get()
                ->map(fn ($a) => ['question_id' => $a->question_id, 'selected_option_id' => $a->question->answerOptions->firstWhere('is_correct', true)->id])
                ->all();
            app(ExamAttemptService::class)->saveAnswers($attempt, $items);
            app(ExamAttemptService::class)->submit($attempt);

            $attempt->refresh();
            $this->assertSame(AttemptStatus::Graded, $attempt->status);
            $this->assertEquals(100, (float) $attempt->score_percent, $code);
            $this->assertSame($questions, StudentTopicMastery::query()->where('user_id', $student->id)->where('subject_id', $subject->id)->count(),
                'one topic per section question');
        }
    }
}
