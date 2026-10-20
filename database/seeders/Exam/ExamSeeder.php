<?php

namespace Database\Seeders\Exam;

use Illuminate\Database\Seeder;

/**
 * Exam module seed: subjects, topic tree, verified demo questions,
 * placement + weekly blueprints. Safe to run repeatedly.
 *
 *   php artisan db:seed --class="Database\Seeders\Exam\ExamSeeder"
 */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ExamSubjectSeeder::class,
            ExamTopicSeeder::class,
            ExamDemoQuestionSeeder::class,
            ExamBlueprintSeeder::class,
            ExamDemoSessionSeeder::class, // stage 5: placement windows, demo student profile, upcoming Saturday session
        ]);
    }
}
