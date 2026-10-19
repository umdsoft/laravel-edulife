<?php

namespace Database\Factories\Exam;

use App\Models\Exam\StudentExamProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentExamProfile>
 */
class StudentExamProfileFactory extends Factory
{
    protected $model = StudentExamProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'grade' => fake()->numberBetween(9, 11),
            'is_graduate' => false,
            'subjects' => ['math'],
            'goal' => 'otm_grant',
            'show_name_publicly' => false,
            'public_alias' => null,
        ];
    }
}
