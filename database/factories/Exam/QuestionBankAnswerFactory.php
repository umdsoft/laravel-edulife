<?php

namespace Database\Factories\Exam;

use App\Models\Exam\QuestionBankAnswer;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionBankAnswer>
 */
class QuestionBankAnswerFactory extends Factory
{
    protected $model = QuestionBankAnswer::class;

    public function definition(): array
    {
        return [
            'question_id' => ExamQuestionFactory::new()->numeric(),
            'value' => fake()->numberBetween(1, 100),
            'tolerance_type' => 'abs',
            'tolerance' => 0,
            'unit' => null,
            'accepted_units' => null,
            'sort_order' => 0,
        ];
    }

    public function withUnit(string $unit, array $acceptedUnits = []): static
    {
        return $this->state(['unit' => $unit, 'accepted_units' => $acceptedUnits ?: null]);
    }
}
