<?php

namespace Database\Factories\Exam;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'parent_id' => null,
            'code' => 'topic.'.fake()->unique()->lexify('????????'),
            'name_uz' => fake()->words(3, true),
            'level' => Topic::LEVEL_SECTION,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /** Child of $parent (subject and level are derived on save). */
    public function childOf(Topic $parent): static
    {
        return $this->state([
            'subject_id' => $parent->subject_id,
            'parent_id' => $parent->id,
        ]);
    }
}
