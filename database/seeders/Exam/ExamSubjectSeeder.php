<?php

namespace Database\Seeders\Exam;

use App\Models\Direction;
use App\Models\Exam\Subject;
use Illuminate\Database\Seeder;

/**
 * The two subjects of the platform. Linked to the LMS direction with the
 * same slug when it exists (math today; physics once 2b adds it).
 */
class ExamSubjectSeeder extends Seeder
{
    public const SUBJECTS = [
        Subject::MATH => ['name_uz' => 'Matematika', 'name_ru' => 'Математика', 'sort_order' => 1, 'slugs' => ['math', 'mathematics']],
        Subject::PHYSICS => ['name_uz' => 'Fizika', 'name_ru' => 'Физика', 'sort_order' => 2, 'slugs' => ['physics']],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $code => $data) {
            $direction = Direction::query()->whereIn('slug', $data['slugs'])->orderBy('slug')->first();

            Subject::query()->updateOrCreate(['code' => $code], [
                'name_uz' => $data['name_uz'],
                'name_ru' => $data['name_ru'],
                'sort_order' => $data['sort_order'],
                'direction_id' => $direction?->id,
                'is_active' => true,
            ]);
        }
    }
}
