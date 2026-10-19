<?php

namespace Database\Factories\Exam;

use App\Models\Exam\GuardianStudent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuardianStudent>
 */
class GuardianStudentFactory extends Factory
{
    protected $model = GuardianStudent::class;

    public function definition(): array
    {
        return [
            'guardian_id' => User::factory()->state(['role' => 'guardian']),
            'student_id' => User::factory(),
            'relation' => 'mother',
            'consent_at' => now(),
            'consent_source' => 'telegram',
            'receives_reports' => true,
        ];
    }
}
