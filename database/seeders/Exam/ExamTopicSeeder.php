<?php

namespace Database\Seeders\Exam;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use Illuminate\Database\Seeder;

/**
 * Topic tree from data/topics.php (idempotent: matched by code, existing
 * rows keep teacher edits of fields the data file does not own).
 */
class ExamTopicSeeder extends Seeder
{
    public static function tree(): array
    {
        return require __DIR__.'/data/topics.php';
    }

    /** @return list<string> every section and topic code */
    public static function codes(): array
    {
        $codes = [];
        foreach (self::tree() as $sections) {
            foreach ($sections as $sectionCode => [, , , $topics]) {
                $codes[] = $sectionCode;
                array_push($codes, ...array_keys($topics));
            }
        }

        return $codes;
    }

    public function run(): void
    {
        foreach (self::tree() as $subjectCode => $sections) {
            $subject = Subject::query()->where('code', $subjectCode)->firstOrFail();

            $sectionOrder = 0;
            foreach ($sections as $sectionCode => [$name, $gradeFrom, $gradeTo, $topics]) {
                $section = Topic::withTrashed()->firstOrNew(['code' => $sectionCode]);
                if (! $section->exists) {
                    $section->fill([
                        'subject_id' => $subject->id,
                        'name_uz' => $name,
                        'grade_from' => $gradeFrom,
                        'grade_to' => $gradeTo,
                        'sort_order' => ++$sectionOrder,
                        'is_active' => true,
                    ])->save();
                } else {
                    $sectionOrder++;
                }

                $topicOrder = 0;
                foreach ($topics as $topicCode => $topicName) {
                    $topic = Topic::withTrashed()->firstOrNew(['code' => $topicCode]);
                    $topicOrder++;
                    if ($topic->exists) {
                        continue;
                    }
                    $topic->fill([
                        'subject_id' => $subject->id,
                        'parent_id' => $section->id,
                        'name_uz' => $topicName,
                        'grade_from' => $gradeFrom,
                        'grade_to' => $gradeTo,
                        'sort_order' => $topicOrder,
                        'is_active' => true,
                    ])->save();
                }
            }
        }
    }
}
