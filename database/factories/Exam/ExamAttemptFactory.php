<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    public function definition(): array
    {
        return [
            'variant_id' => ExamVariant::factory(),
            'session_id' => fn (array $attrs) => ExamVariant::find($attrs['variant_id'])?->session_id,
            'user_id' => fn (array $attrs) => ExamVariant::find($attrs['variant_id'])?->user_id,
            'session_token' => Str::random(64),
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(90),
            'max_score' => 0,
        ];
    }

    public function graded(float $score, float $max): static
    {
        return $this->state([
            'status' => 'graded',
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => $score,
            'max_score' => $max,
            'score_percent' => $max > 0 ? round($score / $max * 100, 2) : 0,
        ]);
    }
}
