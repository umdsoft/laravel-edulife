<?php

namespace Database\Factories\Exam;

use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentTopicMastery>
 */
class StudentTopicMasteryFactory extends Factory
{
    protected $model = StudentTopicMastery::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'subject_id' => fn (array $attrs) => Topic::find($attrs['topic_id'])?->subject_id,
            'score' => fake()->randomFloat(2, 0, 100),
            'attempts_count' => 1,
            'answered_count' => 5,
            'correct_count' => 3,
            'source' => 'placement',
            'last_attempt_at' => now(),
        ];
    }
}
