<?php

namespace Database\Factories\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamBlueprintRule>
 */
class ExamBlueprintRuleFactory extends Factory
{
    protected $model = ExamBlueprintRule::class;

    public function definition(): array
    {
        return [
            'blueprint_id' => ExamBlueprint::factory(),
            'topic_id' => Topic::factory(),
            'difficulty_level' => null,
            'part' => 'closed',
            'count' => 1,
            'points_per_question' => 1,
            'sort_order' => 0,
        ];
    }
}
