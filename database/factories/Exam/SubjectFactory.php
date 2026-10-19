<?php

namespace Database\Factories\Exam;

use App\Models\Exam\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'code' => 'subj_'.fake()->unique()->lexify('??????'),
            'name_uz' => fake()->words(2, true),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function math(): static
    {
        return $this->state(['code' => Subject::MATH, 'name_uz' => 'Matematika', 'sort_order' => 1]);
    }

    public function physics(): static
    {
        return $this->state(['code' => Subject::PHYSICS, 'name_uz' => 'Fizika', 'sort_order' => 2]);
    }
}
