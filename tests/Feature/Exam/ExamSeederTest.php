<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ExamSessionType;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Services\Exam\BlueprintCapacityChecker;
use App\Support\Exam\QuestionText;
use Database\Seeders\Exam\ExamDemoQuestionSeeder;
use Database\Seeders\Exam\ExamSeeder;
use Database\Seeders\Exam\ExamTopicSeeder;
use Database\Seeders\Exam\Support\DemoQuestionVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExamSeeder::class);
    }

    public function test_subjects_and_full_topic_tree(): void
    {
        $this->assertEqualsCanonicalizing(['math', 'physics'], Subject::pluck('code')->all());

        foreach (ExamTopicSeeder::tree() as $subjectCode => $sections) {
            $subject = Subject::where('code', $subjectCode)->firstOrFail();
            $this->assertSame(array_keys($sections), $subject->sections()->pluck('code')->all(), 'sections in order');

            foreach ($sections as $sectionCode => [$name, $from, $to, $topics]) {
                $section = Topic::where('code', $sectionCode)->firstOrFail();
                $this->assertSame(1, $section->level);
                $this->assertSame($name, $section->name_uz);
                $this->assertSame([$from, $to], [$section->grade_from, $section->grade_to]);
                $this->assertSame(array_keys($topics), $section->children()->pluck('code')->all());
                foreach ($section->children as $child) {
                    $this->assertSame(2, $child->level);
                    $this->assertSame($subject->id, $child->subject_id);
                }
            }
        }

        // prompt 4a.1: 15 math and 11 physics main topics
        $this->assertSame(15, Topic::sections()->whereHas('subject', fn ($q) => $q->where('code', 'math'))->count());
        $this->assertSame(11, Topic::sections()->whereHas('subject', fn ($q) => $q->where('code', 'physics'))->count());
    }

    public function test_demo_definitions_are_verified(): void
    {
        $codes = ExamTopicSeeder::codes();
        foreach (ExamDemoQuestionSeeder::definitions() as $questions) {
            foreach ($questions as $q) {
                $this->assertSame([], DemoQuestionVerifier::errors($q, $codes));
            }
        }
    }

    public function test_every_section_has_five_approved_demo_questions_of_mixed_types(): void
    {
        foreach (Topic::sections()->with('subject')->get() as $section) {
            $questions = QuestionBank::inTopicTree($section)->get();

            $this->assertCount(5, $questions, $section->code);
            foreach ($questions as $q) {
                $this->assertTrue($q->is_demo);
                $this->assertSame(ReviewStatus::Approved, $q->review_status);
                $this->assertSame($section->subject_id, $q->subject_id);
                $this->assertNotNull($q->difficulty_level);
            }

            $types = $questions->pluck('question_type')->unique()->values()->all();
            $this->assertContains('single_choice', $types, $section->code);
            $this->assertContains('numeric', $types, $section->code);
            if ($section->subject->code === Subject::PHYSICS) {
                $this->assertContains('numeric_with_unit', $types, $section->code);
            }
        }

        $this->assertSame(130, QuestionBank::examEligible()->demo()->count());
    }

    public function test_stored_answers_match_the_verified_definitions(): void
    {
        foreach (ExamDemoQuestionSeeder::definitions() as $questions) {
            foreach ($questions as $def) {
                $q = QuestionBank::where('normalized_hash', QuestionText::hash($def['stem']))->firstOrFail();
                $type = ExamQuestionType::from($def['type']);
                $expected = ($def['compute'])();

                if ($type->isChoice()) {
                    $correct = $q->answerOptions()->where('is_correct', true)->get();
                    $this->assertCount(1, $correct, $def['stem']);
                    $this->assertSame(0, $q->answerOptions()->whereNull('is_correct')->count());

                    $matching = array_values(array_filter(
                        $def['options'],
                        fn ($o) => DemoQuestionVerifier::same(DemoQuestionVerifier::optionValue($o), (float) $expected)
                    ));
                    $this->assertSame(
                        ExamDemoQuestionSeeder::optionText($matching[0], $def['option_unit'] ?? null),
                        $correct->first()->text,
                        'the option marked correct carries the computed value: '.$def['stem']
                    );
                    $this->assertSame(strtolower($correct->first()->label), $q->correct_answer);
                } else {
                    $answer = $q->numericAnswers()->sole();
                    $this->assertTrue($answer->accepts((float) $expected, $def['unit'] ?? null), $def['stem']);
                    $this->assertSame($def['unit'] ?? null, $answer->unit);
                    foreach ($def['accepted_units'] ?? [] as $unit => $factor) {
                        $this->assertTrue($answer->accepts((float) $expected / $factor, $unit), "{$def['stem']} in {$unit}");
                    }
                }
            }
        }
    }

    public function test_unit_conversion_on_seeded_physics_answers(): void
    {
        $echo = QuestionBank::where('question_text', 'like', '%aks-sadoni%')->firstOrFail()->numericAnswers()->sole();
        $this->assertTrue($echo->accepts(680, 'm'));
        $this->assertTrue($echo->accepts(0.68, 'km'));
        $this->assertFalse($echo->accepts(680, 'km'));

        $photon = QuestionBank::where('question_text', 'like', '%600 nm bo\'lgan fotonning%')->firstOrFail()->numericAnswers()->sole();
        $this->assertTrue($photon->accepts(2.06, 'eV'), 'rounded to 0.01 eV within relative tolerance');
        $this->assertTrue($photon->accepts(3.3e-19, 'J'));
        $this->assertFalse($photon->accepts(2.2, 'eV'));
    }

    public function test_placement_and_weekly_blueprints_are_satisfiable_by_the_demo_bank(): void
    {
        $checker = app(BlueprintCapacityChecker::class);

        foreach (Subject::all() as $subject) {
            foreach ([ExamSessionType::Placement, ExamSessionType::Weekly] as $purpose) {
                $blueprints = ExamBlueprint::where('subject_id', $subject->id)->purpose($purpose)->get();
                $this->assertCount(1, $blueprints, "{$subject->code} {$purpose->value}");

                $blueprint = $blueprints->first();
                $this->assertTrue($blueprint->isConsistent(), 'total_questions = sum(rule counts)');
                $report = $checker->check($blueprint);
                $this->assertTrue($report['satisfiable'], json_encode($report));
                $this->assertSame($blueprint->total_questions, $report['total_required']);
            }
        }

        $weekly = ExamBlueprint::purpose(ExamSessionType::Weekly)->first();
        $this->assertEqualsCanonicalizing(['closed', 'open'], $weekly->rules->pluck('part.value')->unique()->values()->all());
    }

    public function test_seeding_twice_changes_nothing(): void
    {
        $before = [QuestionBank::count(), Topic::count(), ExamBlueprint::count()];

        $this->seed(ExamSeeder::class);

        $this->assertSame($before, [QuestionBank::count(), Topic::count(), ExamBlueprint::count()]);
    }
}
