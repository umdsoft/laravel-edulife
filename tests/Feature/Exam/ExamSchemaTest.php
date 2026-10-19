<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamVariant;
use App\Models\QuestionBank;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migration_creates_all_exam_tables(): void
    {
        foreach ([
            'exam_subjects', 'exam_topics', 'question_bank_options', 'question_bank_answers',
            'question_error_reports', 'exam_blueprints', 'exam_blueprint_rules', 'exam_sessions',
            'exam_variants', 'exam_attempts', 'exam_answers', 'exam_student_topic_mastery',
            'exam_student_weekly_results', 'exam_student_profiles', 'guardian_student',
            'telegram_accounts', 'referral_codes', 'referrals',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table {$table}");
        }

        $this->assertTrue(Schema::hasColumns('question_bank', [
            'subject_id', 'topic_id', 'difficulty_level', 'exam_formats', 'source_ref', 'origin',
            'review_status', 'reviewed_by', 'reviewed_at', 'reject_reason', 'solution_video_url',
            'solution_text', 'content_format', 'units_note', 'constants', 'error_reports_count',
            'is_demo', 'normalized_hash',
        ]));

        foreach (['courses', 'lessons', 'tests', 'lab_experiments'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'exam_topic_id'), "{$table}.exam_topic_id missing");
        }
        $this->assertTrue(Schema::hasColumn('courses', 'course_track'));
    }

    public function test_question_bank_exam_selection_index_exists(): void
    {
        $indexes = collect(Schema::getIndexes('question_bank'))->keyBy('name');

        $this->assertSame(
            ['subject_id', 'topic_id', 'difficulty_level', 'review_status'],
            $indexes['qb_exam_selection_idx']['columns']
        );
    }

    public function test_legacy_columns_are_nullable_and_defaults_are_safe(): void
    {
        // An exam question needs neither an olympiad section nor the JSON answer.
        $id = (string) \Illuminate\Support\Str::uuid();
        DB::table('question_bank')->insert([
            'id' => $id,
            'question_type' => 'numeric',
            'question_text' => '2 + 2 = ?',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('question_bank')->find($id);
        $this->assertNull($row->section_type);
        $this->assertNull($row->correct_answer);
        $this->assertSame('draft', $row->review_status, 'new rows must never default to approved');
        $this->assertSame('manual', $row->origin);
        $this->assertSame('latex_markdown', $row->content_format);
        $this->assertEquals(0, $row->is_demo);
    }

    public function test_olympiad_style_question_still_saves(): void
    {
        $q = QuestionBank::create([
            'section_type' => 'test',
            'question_type' => 'single_choice',
            'question_text' => 'Legacy olympiad question',
            'options' => [['id' => 'a', 'text' => '1', 'is_correct' => true]],
            'correct_answer' => 'a',
        ]);

        $this->assertTrue($q->fresh()->checkAnswer('a'));
        $this->assertNotNull($q->normalized_hash);
    }

    public function test_variant_is_unique_per_session_and_user(): void
    {
        $variant = ExamVariant::factory()->create();

        $this->expectException(QueryException::class);
        ExamVariant::factory()->create([
            'session_id' => $variant->session_id,
            'user_id' => $variant->user_id,
        ]);
    }

    public function test_guardian_role_is_storable(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);

        $this->assertTrue($guardian->fresh()->isGuardian());
    }

    public function test_option_label_is_unique_per_question(): void
    {
        $q = ExamQuestionFactory::new()->withUnmarkedOptions(2)->create();

        $this->expectException(QueryException::class);
        $q->answerOptions()->create(['label' => 'A', 'text' => 'dup']);
    }
}
