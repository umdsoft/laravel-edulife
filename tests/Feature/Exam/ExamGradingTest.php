<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamAnswer;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\QuestionBank;
use App\Services\Exam\Grading\AnswerChecker;
use App\Services\Exam\QuestionReviewService;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsExams;
use Tests\TestCase;

class ExamGradingTest extends TestCase
{
    use BuildsExams, RefreshDatabase;

    private function check(QuestionBank $q, array $answer, float $max = 2, float $ratio = 0.5): array
    {
        $q->load('answerOptions', 'numericAnswers');

        return app(AnswerChecker::class)->check($q, new ExamAnswer($answer), $max, $ratio);
    }

    private function option(QuestionBank $q, string $label): string
    {
        return $q->answerOptions()->where('label', $label)->value('id');
    }

    public function test_single_choice(): void
    {
        [, $topic] = $this->examTopics($this->examSubject());
        $q = $this->approvedQuestions($topic, 1)->first(); // A is correct

        $this->assertSame(['is_correct' => true, 'unit_correct' => null, 'points' => 2.0], $this->check($q, ['selected_option_id' => $this->option($q, 'A')]));
        $this->assertSame(['is_correct' => false, 'unit_correct' => null, 'points' => 0.0], $this->check($q, ['selected_option_id' => $this->option($q, 'C')]));
        $this->assertNull($this->check($q, [])['is_correct'], 'skipped');
    }

    public function test_true_false(): void
    {
        [, $topic] = $this->examTopics($this->examSubject());
        $q = ExamQuestionFactory::new()->forTopic($topic)->inReview()->create(['question_type' => 'true_false']);
        foreach (['A' => 'To\'g\'ri', 'B' => 'Noto\'g\'ri'] as $label => $text) {
            QuestionBankOption::create(['question_id' => $q->id, 'label' => $label, 'text' => $text, 'is_correct' => $label === 'B', 'sort_order' => ord($label)]);
        }
        app(QuestionReviewService::class)->approve($q, $this->examTeacher());

        $this->assertTrue($this->check($q, ['selected_option_id' => $this->option($q, 'B')])['is_correct']);
        $this->assertFalse($this->check($q, ['selected_option_id' => $this->option($q, 'A')])['is_correct']);
    }

    public function test_multiple_choice_is_all_or_nothing(): void
    {
        [, $topic] = $this->examTopics($this->examSubject());
        $q = $this->approvedQuestions($topic, 1, 2, 'multiple_choice')->first();
        QuestionBankOption::query()->whereKey($this->option($q, 'C'))->update(['is_correct' => true]); // A and C

        $a = $this->option($q, 'A');
        $c = $this->option($q, 'C');
        $this->assertTrue($this->check($q, ['selected_option_ids' => [$c, $a]])['is_correct']);
        $this->assertFalse($this->check($q, ['selected_option_ids' => [$a]])['is_correct'], 'partial selection earns nothing');
        $this->assertFalse($this->check($q, ['selected_option_ids' => [$a, $c, $this->option($q, 'B')]])['is_correct']);
        $this->assertNull($this->check($q, ['selected_option_ids' => []])['is_correct']);
    }

    public function test_numeric_with_tolerance_and_student_notations(): void
    {
        [, $topic] = $this->examTopics($this->examSubject());
        $q = $this->approvedQuestions($topic, 1, 3, 'numeric')->first(); // 20, exact
        foreach (['20', '20,0', '40/2', '2·10^1', '2e1'] as $text) {
            $this->assertTrue($this->check($q, ['answer_text' => $text])['is_correct'], $text);
        }
        $this->assertFalse($this->check($q, ['answer_text' => '20.01'])['is_correct']);
        $this->assertFalse($this->check($q, ['answer_text' => 'yigirma'])['is_correct'], 'unparseable is wrong');
        $this->assertNull($this->check($q, ['answer_text' => '  '])['is_correct'], 'blank is skipped');

        QuestionBankAnswer::query()->where('question_id', $q->id)->update(['tolerance_type' => 'abs', 'tolerance' => 0.05]);
        $this->assertTrue($this->check($q->fresh(), ['answer_text' => '20,04'])['is_correct']);
        $this->assertFalse($this->check($q->fresh(), ['answer_text' => '20,06'])['is_correct']);

        QuestionBankAnswer::query()->where('question_id', $q->id)->update(['tolerance_type' => 'rel', 'tolerance' => 0.01]); // 1 %
        $this->assertTrue($this->check($q->fresh(), ['answer_text' => '20.19'])['is_correct']);
        $this->assertFalse($this->check($q->fresh(), ['answer_text' => '20.3'])['is_correct']);

        // a second accepted answer (e.g. both roots)
        QuestionBankAnswer::create(['question_id' => $q->id, 'value' => -5, 'tolerance_type' => 'abs', 'tolerance' => 0, 'sort_order' => 2]);
        $this->assertTrue($this->check($q->fresh(), ['answer_text' => '-5'])['is_correct']);
    }

    public function test_numeric_with_unit_normalises_converts_and_gives_partial_credit(): void
    {
        [, $topic] = $this->examTopics($this->examSubject());
        $q = $this->approvedQuestions($topic, 1, 3, 'numeric_with_unit')->first(); // 20 m/s, km/soat = 1/3.6

        $this->assertSame(['is_correct' => true, 'unit_correct' => true, 'points' => 2.0], $this->check($q, ['answer_text' => '20', 'unit' => 'm/s']));
        $this->assertTrue($this->check($q, ['answer_text' => '20', 'unit' => ' m / s '])['is_correct'], 'spelling normalised');
        $this->assertTrue($this->check($q, ['answer_text' => '72', 'unit' => 'km/soat'])['is_correct'], 'accepted unit converted');
        $this->assertTrue($this->check($q, ['answer_text' => '72', 'unit' => 'km / soat'])['is_correct']);

        // right number, wrong / missing unit -> wrong_unit_ratio of the points
        $this->assertSame(['is_correct' => false, 'unit_correct' => false, 'points' => 1.0], $this->check($q, ['answer_text' => '20', 'unit' => 'km/soat']));
        $this->assertSame(['is_correct' => false, 'unit_correct' => false, 'points' => 1.0], $this->check($q, ['answer_text' => '20']));
        $this->assertSame(0.0, $this->check($q, ['answer_text' => '20', 'unit' => 'N'], 2, 0.0)['points'], 'ratio 0 = no points');

        // known unit, wrong number
        $this->assertSame(['is_correct' => false, 'unit_correct' => true, 'points' => 0.0], $this->check($q, ['answer_text' => '19', 'unit' => 'm/s']));
        $this->assertSame(0.0, $this->check($q, ['answer_text' => '19', 'unit' => 'N'])['points']);

        $choices = AnswerChecker::unitChoices($q->load('numericAnswers'));
        $this->assertContains('m/s', $choices);
        $this->assertContains('km/soat', $choices);
        $this->assertContains('N', $choices, 'catalog distractors');
        $this->assertSame(count($choices), count(array_unique($choices)));
    }
}
