<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSession>
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    public function definition(): array
    {
        $starts = now()->next('Saturday')->setTime(10, 0);

        return [
            'subject_id' => Subject::factory(),
            'blueprint_id' => fn (array $attrs) => ExamBlueprint::factory()->state(['subject_id' => $attrs['subject_id']]),
            'type' => 'weekly',
            'title' => 'Shanba imtihoni '.$starts->format('d.m.Y'),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHours(3),
            'registration_closes_at' => $starts->copy()->subHour(),
            'status' => 'scheduled',
        ];
    }

    public function placement(): static
    {
        return $this->state([
            'type' => 'placement',
            'title' => 'Qabul testi',
            'starts_at' => null,
            'ends_at' => null,
            'registration_closes_at' => null,
            'status' => 'open',
        ]);
    }
}
