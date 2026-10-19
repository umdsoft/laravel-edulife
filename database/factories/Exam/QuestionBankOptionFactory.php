<?php

namespace Database\Factories\Exam;

use App\Models\Exam\QuestionBankOption;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionBankOption>
 */
class QuestionBankOptionFactory extends Factory
{
    protected $model = QuestionBankOption::class;

    public function definition(): array
    {
        return [
            'question_id' => ExamQuestionFactory::new(),
            'label' => 'A',
            'text' => '$'.fake()->numberBetween(1, 99).'$',
            'is_correct' => null,
            'sort_order' => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
