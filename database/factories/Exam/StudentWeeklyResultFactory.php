<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentWeeklyResult>
 */
class StudentWeeklyResultFactory extends Factory
{
    protected $model = StudentWeeklyResult::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => ExamSession::factory(),
            'subject_id' => fn (array $attrs) => ExamSession::find($attrs['session_id'])?->subject_id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'score' => 18,
            'max_score' => 30,
            'score_percent' => 60,
            'delta_prev' => null,
        ];
    }
}
