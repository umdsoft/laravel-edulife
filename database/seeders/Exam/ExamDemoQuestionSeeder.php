<?php

namespace Database\Seeders\Exam;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Services\Exam\QuestionReviewService;
use App\Support\Exam\QuestionText;
use Database\Seeders\Exam\Support\DemoQuestionVerifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Approved demo questions (is_demo = true), 5 per section, answers verified
 * by DemoQuestionVerifier before anything is written. The teacher deletes
 * or replaces them later. Idempotent: a demo question whose stem already
 * exists (normalized hash) is skipped.
 */
class ExamDemoQuestionSeeder extends Seeder
{
    public const SOURCE_REF = 'EduLife demo to\'plami (avtomatik tekshirilgan)';

    /** @return array<string, list<array>> subject code => question definitions */
    public static function definitions(): array
    {
        return [
            'math' => require __DIR__.'/data/demo_questions_math.php',
            'physics' => require __DIR__.'/data/demo_questions_physics.php',
        ];
    }

    public function run(): void
    {
        $definitions = self::definitions();
        $codes = ExamTopicSeeder::codes();

        $errors = [];
        foreach ($definitions as $questions) {
            foreach ($questions as $q) {
                array_push($errors, ...DemoQuestionVerifier::errors($q, $codes));
            }
        }
        if ($errors !== []) {
            throw new RuntimeException("Demo questions failed verification:\n - ".implode("\n - ", $errors));
        }

        $topics = Topic::query()->whereIn('code', $codes)->get()->keyBy('code');
        $review = app(QuestionReviewService::class);

        DB::transaction(function () use ($definitions, $topics, $review) {
            foreach ($definitions as $questions) {
                foreach ($questions as $q) {
                    $this->seedQuestion($q, $topics[$q['topic']], $review);
                }
            }
        });
    }

    private function seedQuestion(array $q, Topic $topic, QuestionReviewService $review): void
    {
        $exists = QuestionBank::withTrashed()
            ->where('is_demo', true)
            ->where('normalized_hash', QuestionText::hash($q['stem']))
            ->exists();
        if ($exists) {
            return;
        }

        $type = ExamQuestionType::from($q['type']);
        $question = QuestionBank::create([
            'subject_id' => $topic->subject_id,
            'topic_id' => $topic->id,
            'question_type' => $type->value,
            'question_text' => $q['stem'],
            'difficulty' => self::legacyDifficulty($q['difficulty']),
            'difficulty_level' => $q['difficulty'],
            'exam_formats' => $q['formats'] ?? ['milliy_sertifikat', 'abituriyent'],
            'source_ref' => self::SOURCE_REF,
            'origin' => 'manual',
            'review_status' => ReviewStatus::InReview,
            'content_format' => 'latex_markdown',
            'units_note' => isset($q['constants']['g']) ? 'g = 10 m/s²' : null,
            'constants' => $q['constants'] ?? null,
            'solution_text' => $q['solution'] ?? null,
            'base_points' => 1,
            'estimated_time_seconds' => 60 + 30 * $q['difficulty'],
            'is_demo' => true,
            'status' => QuestionBank::STATUS_DRAFT,
        ]);

        if ($type->isChoice()) {
            $this->seedOptions($question, $q);
        } else {
            QuestionBankAnswer::create([
                'question_id' => $question->id,
                'value' => self::decimal((float) $q['answer']),
                'tolerance_type' => $q['tolerance_type'] ?? 'abs',
                'tolerance' => self::decimal((float) ($q['tolerance'] ?? 0)),
                'unit' => $q['unit'] ?? null,
                'accepted_units' => $q['accepted_units'] ?? null,
                'note' => $q['note'] ?? null,
            ]);
        }

        $review->approve($question, null);
    }

    private function seedOptions(QuestionBank $question, array $q): void
    {
        $options = $q['options'];
        // Deterministic rotation so the correct option is not always "A".
        $shift = crc32($q['stem']) % count($options);
        $options = [...array_slice($options, $shift), ...array_slice($options, 0, $shift)];

        foreach ($options as $i => $option) {
            QuestionBankOption::create([
                'question_id' => $question->id,
                'label' => QuestionBankOption::LABELS[$i],
                'text' => self::optionText($option, $q['option_unit'] ?? null),
                'is_correct' => DemoQuestionVerifier::same(DemoQuestionVerifier::optionValue($option), (float) $q['answer']),
                'sort_order' => $i,
            ]);
        }
    }

    public static function optionText(int|float|array $option, ?string $unit): string
    {
        $tex = is_array($option) ? $option[0] : self::formatNumber($option);

        return '$'.$tex.($unit !== null ? '\ \mathrm{'.$unit.'}' : '').'$';
    }

    /** LaTeX number: decimal comma, thin-space thousands for >= 10 000. */
    public static function formatNumber(int|float $value): string
    {
        if (abs($value - round($value)) < 1e-9) {
            $int = (int) round($value);

            return abs($int) >= 10000 ? number_format($int, 0, '', '\,') : (string) $int;
        }

        $s = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');

        return str_replace('.', '{,}', $s);
    }

    private static function decimal(float $value): string
    {
        // Exact decimal string for DECIMAL(30,10) columns.
        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.') ?: '0';
    }

    private static function legacyDifficulty(int $level): string
    {
        return match (true) {
            $level <= 2 => QuestionBank::DIFFICULTY_EASY,
            $level === 3 => QuestionBank::DIFFICULTY_MEDIUM,
            $level === 4 => QuestionBank::DIFFICULTY_HARD,
            default => QuestionBank::DIFFICULTY_EXPERT,
        };
    }
}
