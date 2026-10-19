<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamVariant>
 */
class ExamVariantFactory extends Factory
{
    protected $model = ExamVariant::class;

    public function definition(): array
    {
        return [
            'session_id' => ExamSession::factory(),
            'user_id' => User::factory(),
            'seed' => fake()->numberBetween(1, PHP_INT_MAX),
            'question_ids' => [],
            'option_orders' => [],
            'used_fallback' => false,
            'generated_at' => now(),
        ];
    }
}
