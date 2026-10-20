<?php

namespace Tests\Feature\Exam;

use App\Models\BattleQuestionStat;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Test;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * "ustoz: qiyin · amalda: 82% yechgan": the observed solve rate next to the
 * teacher's level, and the bank filter "belgilangan va amaldagi qiyinlik farq
 * qiladi". A suggestion only — the level never changes by itself.
 */
class DifficultyCalibrationTest extends TestCase
{
    use CreatesCourses, RefreshDatabase;

    private User $teacher;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        config(['exam.calibration.min_answers' => 3]);
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $math = Subject::factory()->math()->create();
        $this->topic = Topic::factory()->create(['subject_id' => $math->id]);
    }

    /** One finished attempt per answer (an attempt answers a question once). */
    private function answers(QuestionBank $q, int $correct, int $wrong, string $status = 'graded'): void
    {
        foreach ([...array_fill(0, $correct, true), ...array_fill(0, $wrong, false)] as $ok) {
            $attempt = ExamAttempt::factory()->create(['status' => $status]);
            ExamAnswer::factory()->create(['attempt_id' => $attempt->id, 'question_id' => $q->id, 'is_correct' => $ok]);
        }
    }

    public function test_bank_hint_and_mismatch_filter(): void
    {
        $hardButEasy = ExamQuestionFactory::new()->forTopic($this->topic)->approved()->create(['difficulty_level' => 5, 'question_text' => 'Hamma yechadi']);
        $this->answers($hardButEasy, 4, 0);
        $tooFew = ExamQuestionFactory::new()->forTopic($this->topic)->approved()->create(['difficulty_level' => 1, 'question_text' => 'Kam javob']);
        $this->answers($tooFew, 0, 2);
        $easyAndEasy = ExamQuestionFactory::new()->forTopic($this->topic)->approved()->create(['difficulty_level' => 2, 'question_text' => 'Mos']);
        $this->answers($easyAndEasy, 3, 0);
        $this->answers($easyAndEasy, 0, 5, 'invalidated'); // never counted
        $this->answers($easyAndEasy, 0, 5, 'in_progress');
        $mediumButHard = ExamQuestionFactory::new()->forTopic($this->topic)->approved()->create(['difficulty_level' => 3, 'question_text' => "Hech kim yechmaydi"]);
        $this->answers($mediumButHard, 0, 3);

        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.index'))
            ->assertOk()->assertInertia(fn (Assert $p) => $p->has('questions.data', 4)
                ->where('questions.data', fn ($rows) => collect($rows)->firstWhere('id', $tooFew->id)['calibration'] === null
                    && collect($rows)->firstWhere('id', $easyAndEasy->id)['calibration']['mismatch'] === false
                    && collect($rows)->firstWhere('id', $easyAndEasy->id)['calibration']['attempts'] === 3
                    && collect($rows)->firstWhere('id', $hardButEasy->id)['calibration']['rate'] == 1
                    && collect($rows)->firstWhere('id', $hardButEasy->id)['calibration']['band'] === 'easy'
                    && collect($rows)->firstWhere('id', $hardButEasy->id)['calibration']['level'] === 1));

        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.index', ['calibration' => 'mismatch']))
            ->assertInertia(fn (Assert $p) => $p->has('questions.data', 2)
                ->where('questions.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
                    === collect([$hardButEasy->id, $mediumButHard->id])->sort()->values()->all())
                ->where('filters.calibration', 'mismatch'));

        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.edit', $hardButEasy))
            ->assertInertia(fn (Assert $p) => $p->where('question.calibration.mismatch', true)->where('question.difficulty_level', 5));

        // suggestion only
        $this->assertSame(5, $hardButEasy->fresh()->difficulty_level);
        $this->assertSame(3, $mediumButHard->fresh()->difficulty_level);
    }

    public function test_course_question_hint_uses_the_battle_estimate(): void
    {
        $course = $this->course($this->teacher);
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'T', 'is_active' => true]);
        $easyLabel = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'difficulty' => 'easy', 'question_text' => 'A', 'sort_order' => 1]);
        $fresh = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'difficulty' => 'hard', 'question_text' => 'B', 'sort_order' => 2]);
        BattleQuestionStat::query()->create(['question_id' => $easyLabel->id, 'attempts' => 40, 'correct' => 4]);
        BattleQuestionStat::query()->create(['question_id' => $fresh->id, 'attempts' => 2, 'correct' => 0]);

        $this->actingAs($this->teacher)->get(route('teacher.tests.questions.index', $test))
            ->assertOk()->assertInertia(fn (Assert $p) => $p
                ->where("calibration.{$easyLabel->id}.band", 'hard')
                ->where("calibration.{$easyLabel->id}.set_band", 'easy')
                ->where("calibration.{$easyLabel->id}.mismatch", true)
                ->missing("calibration.{$fresh->id}"));
        $this->actingAs($this->teacher)->get(route('teacher.questions.edit', $easyLabel))
            ->assertInertia(fn (Assert $p) => $p->where('calibration.mismatch', true)->where('question.difficulty', 'easy'));
        $this->assertSame('easy', $easyLabel->fresh()->difficulty);
    }
}
