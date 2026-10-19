<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamBlueprint>
 */
class ExamBlueprintFactory extends Factory
{
    protected $model = ExamBlueprint::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'name' => 'Blueprint '.fake()->word(),
            'purpose' => 'weekly',
            'format' => 'milliy_sertifikat',
            'duration_min' => 90,
            'total_questions' => 1,
            'wrong_unit_ratio' => 0,
            'avoid_repeat_weeks' => 4,
            'allow_adjacent_difficulty' => true,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'is_active' => true,
        ];
    }

    public function placement(): static
    {
        return $this->state(['purpose' => 'placement', 'duration_min' => 60]);
    }
}
