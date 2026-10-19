<?php

namespace Database\Seeders\Exam;

use App\Enums\Exam\BlueprintPart;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use Illuminate\Database\Seeder;

/**
 * One placement (entrance) and one weekly blueprint per subject, sized so
 * the demo bank alone can fill them (5 demo questions per section:
 * 3 closed at difficulty 1-3, 2 open at difficulty 3-4).
 *
 *  placement: 1 closed question per section, any difficulty
 *  weekly:    closed  - per section 1 question of difficulty 2 + 1 of any
 *             open    - per section 1 open question, any difficulty
 *
 * Idempotent by (subject, purpose, name); rules are rebuilt only for a
 * newly created blueprint so teacher edits survive re-seeding.
 */
class ExamBlueprintSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Subject::query()->whereIn('code', [Subject::MATH, Subject::PHYSICS])->get() as $subject) {
            $sections = Topic::query()->where('subject_id', $subject->id)->whereNull('parent_id')
                ->orderBy('sort_order')->get();
            if ($sections->isEmpty()) {
                continue;
            }

            $placementRules = $sections->map(fn (Topic $s) => [$s->id, null, BlueprintPart::Closed, 1])->all();

            $weeklyRules = [];
            foreach ($sections as $s) {
                $weeklyRules[] = [$s->id, 2, BlueprintPart::Closed, 1];
                $weeklyRules[] = [$s->id, null, BlueprintPart::Closed, 1];
            }
            foreach ($sections as $s) {
                $weeklyRules[] = [$s->id, null, BlueprintPart::Open, 1];
            }

            $this->blueprint($subject, 'placement', "{$subject->name_uz}: qabul testi", 60, $placementRules,
                'Kirish imtihoni: har bo\'limdan bitta savol, natija mavzular xaritasining boshlang\'ich qiymati bo\'ladi.');
            $this->blueprint($subject, 'weekly', "{$subject->name_uz}: shanba imtihoni", 120, $weeklyRules,
                'Haftalik imtihon: yopiq qism (variantli) va ochiq qism (javob yoziladi).');
        }
    }

    /** @param list<array{0: string, 1: ?int, 2: BlueprintPart, 3: int}> $rules */
    private function blueprint(Subject $subject, string $purpose, string $name, int $minutes, array $rules, string $description): void
    {
        $blueprint = ExamBlueprint::query()->firstOrNew([
            'subject_id' => $subject->id,
            'purpose' => $purpose,
            'name' => $name,
        ]);
        if ($blueprint->exists) {
            return;
        }

        $blueprint->fill([
            'format' => 'milliy_sertifikat',
            'duration_min' => $minutes,
            'total_questions' => array_sum(array_column($rules, 3)),
            'wrong_unit_ratio' => 0.5,
            'avoid_repeat_weeks' => 4,
            'allow_adjacent_difficulty' => true,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'description' => $description,
            'is_active' => true,
        ])->save();

        foreach ($rules as $i => [$topicId, $difficulty, $part, $count]) {
            $blueprint->rules()->create([
                'topic_id' => $topicId,
                'difficulty_level' => $difficulty,
                'part' => $part,
                'count' => $count,
                'points_per_question' => $part === BlueprintPart::Open ? 2 : 1,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
