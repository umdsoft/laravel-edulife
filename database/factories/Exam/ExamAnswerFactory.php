<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAnswer>
 */
class ExamAnswerFactory extends Factory
{
    protected $model = ExamAnswer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => ExamAttempt::factory(),
            'question_id' => ExamQuestionFactory::new(),
            'position' => 1,
            'points' => 0,
            'max_points' => 1,
            'is_flagged' => false,
        ];
    }
}
