<?php

namespace Database\Seeders\Exam;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Services\Exam\QuestionReviewService;
use App\Services\Media\QuestionImageStore;
use App\Services\Media\QuestionZipTemplate;
use App\Support\Exam\QuestionText;
use App\Support\RichText\GraphExpression;
use App\Support\RichText\RichTextMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Demo questions with a graph or a picture (approved, is_demo = true), so the
 * teacher sees how media looks in the bank, the exam and the result:
 * a parabola, "which graph matches", a v–t graph, two force vectors and a
 * triangle drawn with GD and stored like an upload.
 *
 * Every answer is computed independently ("compute") and checked against the
 * written answer, the options and the graph itself before anything is saved.
 * Idempotent: a question whose stem (without media) already exists is skipped.
 *
 *   php artisan db:seed --class="Database\Seeders\Exam\ExamMediaDemoSeeder"
 */
class ExamMediaDemoSeeder extends Seeder
{
    public const SOURCE_REF = 'EduLife demo to\'plami — rasm va grafiklar';

    public const IMAGE_PLACEHOLDER = '{{triangle_image}}';

    /** @return list<array> */
    public static function definitions(): array
    {
        $graph = fn (array $spec) => "```graph\n".json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n```";
        $line = fn (string $expr) => $graph(['x' => [-3, 3], 'y' => [-4, 4], 'grid' => true, 'functions' => [['expr' => $expr]]]);

        return [
            [
                'topic' => 'math.functions.quadratic', 'type' => 'single_choice', 'difficulty' => 2,
                'stem' => "Rasmda \$y = x^2 - 2x - 3\$ funksiyaning grafigi berilgan. Grafik \$Ox\$ o'qini kesib o'tgan nuqtalar abssissalarining yig'indisini toping.\n\n"
                    .$graph(['x' => [-3, 5], 'y' => [-5, 6], 'grid' => true,
                        'functions' => [['expr' => 'x^2-2x-3', 'label' => 'y = x² − 2x − 3']],
                        'points' => [['x' => -1, 'y' => 0, 'label' => 'A'], ['x' => 3, 'y' => 0, 'label' => 'B'], ['x' => 1, 'y' => -4, 'label' => 'C']]]),
                'answer' => 2,
                'compute' => fn () => (-(-2) + sqrt(4 + 12)) / 2 + (-(-2) - sqrt(4 + 12)) / 2,
                // the drawn points really lie on the curve
                'graph_checks' => [['x^2-2x-3', -1, 0], ['x^2-2x-3', 3, 0], ['x^2-2x-3', 1, -4]],
                'options' => [['$2$', 2], ['$-2$', -2], ['$3$', 3], ['$-4$', -4]],
                'solution' => 'Viyet teoremasi: $x_1 + x_2 = -\frac{b}{a} = 2$. Grafikda ham: $A(-1;\,0)$ va $B(3;\,0)$, $-1 + 3 = 2$.',
            ],
            [
                'topic' => 'math.functions.linear', 'type' => 'single_choice', 'difficulty' => 2,
                'stem' => 'Qaysi grafik $y = 2x - 1$ funksiyaga mos keladi?',
                'answer' => 1,
                'compute' => fn () => 1,
                // option value 1 = matches y = 2x - 1 at every checked x, 0 = does not
                'options' => [[$line('2x-1'), 1], [$line('-2x-1'), 0], [$line('2x+1'), 0], [$line('x/2-1'), 0]],
                'option_match' => '2x-1',
                'solution' => "\$y = 2x - 1\$: \$x = 0\$ da \$y = -1\$, \$x = 1\$ da \$y = 1\$ — grafik o'sadi va \$Oy\$ o'qini \$-1\$ da kesadi.",
            ],
            [
                'topic' => 'phys.kinematics.accelerated', 'type' => 'numeric_with_unit', 'difficulty' => 3,
                'stem' => "Jism tezligining vaqtga bog'liqlik grafigi berilgan. Jism 0 dan 8 s gacha bosib o'tgan yo'lni toping.\n\n"
                    .$graph(['x' => [0, 9], 'y' => [0, 5], 'ticks' => [1, 1], 'axes' => ['x' => 't, s', 'y' => 'v, m/s'],
                        'polylines' => [['points' => [[0, 0], [2, 4], [6, 4], [8, 0]], 'label' => 'v(t)']],
                        'segments' => [['from' => [2, 0], 'to' => [2, 4], 'dashed' => true], ['from' => [6, 0], 'to' => [6, 4], 'dashed' => true]]]),
                'answer' => 24, 'unit' => 'm',
                'compute' => fn () => self::areaUnder([[0, 0], [2, 4], [6, 4], [8, 0]]),
                'solution' => "Yo'l — grafik ostidagi yuza: $\\frac{1}{2}\\cdot 2\\cdot 4 + 4\\cdot 4 + \\frac{1}{2}\\cdot 2\\cdot 4 = 4 + 16 + 4 = 24$ m.",
            ],
            [
                'topic' => 'phys.dynamics.newton', 'type' => 'single_choice', 'difficulty' => 3,
                'stem' => "Jismga o'zaro perpendikular ikki kuch ta'sir qiladi: \$F_1 = 3\\ \\mathrm{N}\$ va \$F_2 = 4\\ \\mathrm{N}\$ (rasmda). Teng ta'sir etuvchi kuchning modulini toping.\n\n"
                    .$graph(['x' => [-1, 5], 'y' => [-1, 5], 'equal' => true, 'ticks' => [1, 1], 'axes' => ['x' => 'x, N', 'y' => 'y, N'],
                        'vectors' => [['from' => [0, 0], 'to' => [3, 0], 'label' => 'F₁', 'color' => 'blue'], ['from' => [0, 0], 'to' => [0, 4], 'label' => 'F₂', 'color' => 'red']],
                        'angles' => [['vertex' => [0, 0], 'from' => [3, 0], 'to' => [0, 4], 'right' => true]]]),
                'answer' => 5,
                'compute' => fn () => hypot(3, 4),
                'options' => [['$5\ \mathrm{N}$', 5], ['$7\ \mathrm{N}$', 7], ['$1\ \mathrm{N}$', 1], ['$12\ \mathrm{N}$', 12]],
                'solution' => 'Kuchlar perpendikular: $F = \sqrt{F_1^2 + F_2^2} = \sqrt{9 + 16} = 5$ N.',
            ],
            [
                'topic' => 'math.planimetry.triangles', 'type' => 'single_choice', 'difficulty' => 2,
                'stem' => "Rasmdagi to'g'ri burchakli uchburchakda \$AC = 3\$, \$BC = 4\$. Gipotenuza \$AB\$ ni toping.\n\n![To'g'ri burchakli uchburchak ABC, C burchak to'g'ri](".self::IMAGE_PLACEHOLDER.'){width=60%}',
                'answer' => 5,
                'compute' => fn () => sqrt(3 ** 2 + 4 ** 2),
                'options' => [['$5$', 5], ['$7$', 7], ['$\sqrt{7}$', sqrt(7)], ['$12$', 12]],
                'solution' => 'Pifagor teoremasi: $AB = \sqrt{AC^2 + BC^2} = \sqrt{9 + 16} = 5$.',
            ],
        ];
    }

    /** Area under a v–t polyline (trapezoids) = distance. */
    public static function areaUnder(array $points): float
    {
        $s = 0.0;
        for ($i = 1; $i < count($points); $i++) {
            $s += ($points[$i][0] - $points[$i - 1][0]) * ($points[$i][1] + $points[$i - 1][1]) / 2;
        }

        return $s;
    }

    /** Problems of one definition (empty = consistent). */
    public static function errors(array $q, array $topicCodes): array
    {
        $stem = mb_substr(strip_tags($q['stem']), 0, 40);
        $errors = [];
        if (! in_array($q['topic'], $topicCodes, true)) {
            $errors[] = "{$stem}: unknown topic {$q['topic']}";
        }
        $computed = ($q['compute'])();
        if (abs($computed - $q['answer']) > 1e-9) {
            $errors[] = "{$stem}: answer {$q['answer']} != computed {$computed}";
        }
        $texts = [str_replace(self::IMAGE_PLACEHOLDER, '/storage/questions/2026/01/demo.webp', $q['stem']), $q['solution'] ?? ''];
        if (isset($q['options'])) {
            $matching = array_filter($q['options'], fn ($o) => abs($o[1] - $q['answer']) < 1e-9);
            if (count($matching) !== 1) {
                $errors[] = "{$stem}: exactly one option must equal the answer";
            }
            foreach ($q['options'] as $o) {
                $texts[] = $o[0];
            }
        }
        foreach ($q['graph_checks'] ?? [] as [$expr, $x, $y]) {
            if (abs(GraphExpression::evaluate(GraphExpression::parse($expr), $x) - $y) > 1e-9) {
                $errors[] = "{$stem}: point ({$x}; {$y}) is not on y = {$expr}";
            }
        }
        if (isset($q['option_match'])) {
            // an option is correct exactly when its drawn function equals the asked one
            $want = GraphExpression::parse($q['option_match']);
            foreach ($q['options'] as [$text, $value]) {
                $json = RichTextMedia::graphs($text)[0] ?? null;
                $expr = $json ? (json_decode($json, true)['functions'][0]['expr'] ?? null) : null;
                $same = $expr !== null && collect([-2, -0.5, 0, 1, 2.5])->every(
                    fn ($x) => abs(GraphExpression::evaluate(GraphExpression::parse($expr), $x) - GraphExpression::evaluate($want, $x)) < 1e-9
                );
                if ($same !== ($value == 1)) {
                    $errors[] = "{$stem}: option graph {$expr} marked ".($value == 1 ? 'correct' : 'wrong').' but it '.($same ? 'matches' : 'does not match');
                }
            }
        }
        foreach ($texts as $t) {
            foreach (RichTextMedia::errors($t) as $e) {
                $errors[] = "{$stem}: {$e}";
            }
        }

        return $errors;
    }

    public function run(): void
    {
        $topics = Topic::query()->get()->keyBy('code');
        $definitions = self::definitions();
        $errors = [];
        foreach ($definitions as $q) {
            array_push($errors, ...self::errors($q, $topics->keys()->all()));
        }
        if ($errors !== []) {
            throw new RuntimeException("Media demo questions failed verification:\n - ".implode("\n - ", $errors));
        }

        $review = app(QuestionReviewService::class);
        foreach ($definitions as $q) {
            if (QuestionBank::withTrashed()->where('is_demo', true)->where('normalized_hash', QuestionText::hash($q['stem']))->exists()) {
                continue;
            }
            $stem = $q['stem'];
            if (str_contains($stem, self::IMAGE_PLACEHOLDER)) {
                // drawn with GD and stored exactly like a teacher's upload (WebP on the public disk)
                $url = app(QuestionImageStore::class)->storeBytes(QuestionZipTemplate::trianglePng())['url'];
                $stem = str_replace(self::IMAGE_PLACEHOLDER, $url, $stem);
            }
            DB::transaction(fn () => $this->seedQuestion($q, $stem, $topics[$q['topic']], $review));
        }
    }

    private function seedQuestion(array $q, string $stem, Topic $topic, QuestionReviewService $review): void
    {
        $type = ExamQuestionType::from($q['type']);
        $question = QuestionBank::create([
            'subject_id' => $topic->subject_id,
            'topic_id' => $topic->id,
            'question_type' => $type->value,
            'question_text' => $stem,
            'difficulty' => match (true) {
                $q['difficulty'] <= 2 => QuestionBank::DIFFICULTY_EASY,
                $q['difficulty'] === 3 => QuestionBank::DIFFICULTY_MEDIUM,
                default => QuestionBank::DIFFICULTY_HARD,
            },
            'difficulty_level' => $q['difficulty'],
            'exam_formats' => ['milliy_sertifikat', 'abituriyent'],
            'source_ref' => self::SOURCE_REF,
            'origin' => 'manual',
            'review_status' => ReviewStatus::InReview,
            'content_format' => 'latex_markdown',
            'solution_text' => $q['solution'] ?? null,
            'explanation' => $q['solution'] ?? null,
            'base_points' => 1,
            'estimated_time_seconds' => 60 + 30 * $q['difficulty'],
            'is_demo' => true,
            'status' => QuestionBank::STATUS_DRAFT,
        ]);

        if ($type->isChoice()) {
            // deterministic rotation so the correct option is not always "A"
            $options = $q['options'];
            $shift = crc32(QuestionText::normalize($q['stem'])) % count($options);
            $options = [...array_slice($options, $shift), ...array_slice($options, 0, $shift)];
            foreach ($options as $i => [$text, $value]) {
                QuestionBankOption::create([
                    'question_id' => $question->id,
                    'label' => QuestionBankOption::LABELS[$i],
                    'text' => $text,
                    'is_correct' => abs($value - $q['answer']) < 1e-9,
                    'sort_order' => $i,
                ]);
            }
        } else {
            QuestionBankAnswer::create([
                'question_id' => $question->id,
                'value' => (string) $q['answer'],
                'tolerance_type' => 'abs',
                'tolerance' => '0',
                'unit' => $q['unit'] ?? null,
            ]);
        }

        $review->approve($question, null);
    }
}
