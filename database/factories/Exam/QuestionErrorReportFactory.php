<?php

namespace Database\Factories\Exam;

use App\Models\Exam\QuestionErrorReport;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionErrorReport>
 */
class QuestionErrorReportFactory extends Factory
{
    protected $model = QuestionErrorReport::class;

    public function definition(): array
    {
        return [
            'question_id' => ExamQuestionFactory::new(),
            'user_id' => User::factory(),
            'message' => fake()->sentence(),
            'status' => 'open',
        ];
    }
}
