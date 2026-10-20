<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamFormat;
use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\QuestionOrigin;
use App\Enums\Exam\ToleranceType;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Support\Difficulty;
use App\Support\Exam\QuestionText;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Question import from CSV / Excel (xlsx, xls, ods via PhpSpreadsheet, which
 * maatwebsite/excel already brings) and JSON.
 *
 * Two steps:
 *   parse()  - file -> normalized rows with errors, warnings and duplicate
 *              flags (by QuestionText::hash of the stem) for the preview;
 *   import() - creates the valid, non-duplicate rows as origin = import,
 *              review_status = in_review.
 *
 * The answer written in the file is NOT trusted by default: options stay
 * is_correct = NULL and numeric values NULL, unless the teacher explicitly
 * confirms the file's answers on the preview ($acceptAnswers). Even then
 * the question stays in review; approval is a separate teacher action.
 */
class QuestionImportService
{
    public const COLUMNS = [
        'subject', 'topic', 'type', 'difficulty', 'question',
        'option_a', 'option_b', 'option_c', 'option_d', 'option_e',
        'correct', 'unit', 'tolerance', 'tolerance_type', 'accepted_units',
        'source_ref', 'exam_formats', 'solution_text', 'solution_video_url', 'units_note',
    ];

    /** Header spellings teachers use (Uzbek / short forms) -> column. */
    private const ALIASES = [
        'fan' => 'subject',
        'mavzu' => 'topic', 'topic_code' => 'topic',
        'tur' => 'type', 'savol_turi' => 'type', 'question_type' => 'type',
        'qiyinlik' => 'difficulty', 'difficulty_level' => 'difficulty', 'level' => 'difficulty',
        'shart' => 'question', 'savol' => 'question', 'stem' => 'question', 'question_text' => 'question', 'text' => 'question',
        'a' => 'option_a', 'b' => 'option_b', 'c' => 'option_c', 'd' => 'option_d', 'e' => 'option_e',
        'javob' => 'correct', 'togri' => 'correct', 'answer' => 'correct', 'correct_answer' => 'correct',
        'birlik' => 'unit', 'xatolik' => 'tolerance',
        'manba' => 'source_ref', 'source' => 'source_ref',
        'format' => 'exam_formats', 'formats' => 'exam_formats', 'formatlar' => 'exam_formats',
        'yechim' => 'solution_text', 'solution' => 'solution_text',
        'video' => 'solution_video_url', 'yechim_video' => 'solution_video_url',
    ];

    private const TYPE_ALIASES = [
        'single' => 'single_choice', 'bitta' => 'single_choice',
        'multiple' => 'multiple_choice', 'kop' => 'multiple_choice',
        'number' => 'numeric', 'son' => 'numeric',
        'unit' => 'numeric_with_unit', 'birlikli' => 'numeric_with_unit',
    ];

    /** Types the bank editor and import support. */
    public const TYPES = ['single_choice', 'multiple_choice', 'numeric', 'numeric_with_unit'];

    private array $subjects = [];

    private array $topics = [];

    // ==================== parse ====================

    /**
     * @return array{rows: list<array>, errors: list<string>}
     */
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        try {
            $raw = match (true) {
                $ext === 'json' => $this->readJson((string) file_get_contents($file->getRealPath())),
                in_array($ext, ['csv', 'txt'], true) => $this->readCsv((string) file_get_contents($file->getRealPath())),
                default => $this->readSpreadsheet($file->getRealPath()),
            };
        } catch (\JsonException) {
            return ['rows' => [], 'errors' => ['JSON fayl noto\'g\'ri: tuzilishini shablon bilan solishtiring.']];
        } catch (\Throwable $e) {
            report($e);

            return ['rows' => [], 'errors' => ['Faylni o\'qib bo\'lmadi. CSV, Excel (xlsx) yoki JSON shablonidan foydalaning.']];
        }

        if ($raw === []) {
            return ['rows' => [], 'errors' => ['Faylda savol topilmadi.']];
        }
        $max = (int) config('exam.bank.import.max_rows', 500);
        $errors = [];
        if (count($raw) > $max) {
            $errors[] = "Bir faylda ko'pi bilan {$max} ta savol bo'lishi mumkin; birinchi {$max} tasi olindi.";
            $raw = array_slice($raw, 0, $max, true);
        }

        return ['rows' => $this->normalizeRows($raw), 'errors' => $errors];
    }

    /** @param array<int, array<string, mixed>> $raw file row number => column => value */
    public function normalizeRows(array $raw): array
    {
        $this->loadLookups();
        $rows = [];
        foreach ($raw as $line => $cols) {
            $rows[] = $this->normalizeRow((int) $line, $cols);
        }

        // duplicates: against the bank, then inside the file
        $hashes = array_values(array_filter(array_column($rows, 'hash')));
        $inBank = $hashes === [] ? [] : array_flip(
            QuestionBank::query()->whereIn('normalized_hash', $hashes)->pluck('normalized_hash')->all()
        );
        $seen = [];
        foreach ($rows as &$row) {
            if ($row['hash'] === null) {
                continue;
            }
            if (isset($inBank[$row['hash']])) {
                $row['duplicate'] = 'bank';
            } elseif (isset($seen[$row['hash']])) {
                $row['duplicate'] = 'file';
                $row['duplicate_of'] = $seen[$row['hash']];
            } else {
                $seen[$row['hash']] = $row['row'];
            }
        }
        unset($row);

        return $rows;
    }

    private function normalizeRow(int $line, array $cols): array
    {
        $errors = [];
        $warnings = [];
        $get = fn (string $k) => trim((string) ($cols[$k] ?? ''));

        // subject
        $subjectKey = mb_strtolower($get('subject'));
        $subject = $this->subjects[$subjectKey] ?? null;
        if ($subject === null) {
            $errors[] = $subjectKey === '' ? 'Fan ko\'rsatilmagan (math yoki physics).' : "Fan topilmadi: «{$get('subject')}».";
        }

        // topic (code or name inside the subject)
        $topicId = null;
        $topicLabel = $get('topic');
        if ($subject && $topicLabel !== '') {
            $topic = $this->topics[$subject['id']][mb_strtolower($topicLabel)] ?? null;
            if ($topic === null) {
                $warnings[] = "Mavzu topilmadi: «{$topicLabel}» — tekshiruvda tanlanadi.";
            } else {
                $topicId = $topic['id'];
                $topicLabel = $topic['name_uz'];
                if (! $topic['is_active']) {
                    $warnings[] = 'Mavzu faol emas.';
                }
            }
        } elseif ($subject) {
            $warnings[] = 'Mavzu ko\'rsatilmagan — tekshiruvda tanlanadi.';
        }

        // type
        $typeRaw = mb_strtolower($get('type'));
        $type = $typeRaw === '' ? 'single_choice' : (self::TYPE_ALIASES[$typeRaw] ?? $typeRaw);
        if (! in_array($type, self::TYPES, true)) {
            $errors[] = "Savol turi noto'g'ri: «{$get('type')}». Mumkin: ".implode(', ', self::TYPES).'.';
            $type = null;
        }
        $examType = $type ? ExamQuestionType::from($type) : null;

        // difficulty: missing or invalid is never silently "medium" — the teacher
        // picks a level per row on the preview, and confirmation waits for it
        $difficulty = null;
        if ($get('difficulty') !== '') {
            $difficulty = Difficulty::parseLevel($get('difficulty'));
            if ($difficulty === null) {
                $warnings[] = "Qiyinlik «{$get('difficulty')}» noto'g'ri (1 dan 5 gacha bo'lishi kerak) — darajani tanlang.";
            }
        } else {
            $warnings[] = "Qiyinlik ko'rsatilmagan — darajani tanlang.";
        }

        // stem
        $stem = $get('question');
        if ($stem === '') {
            $errors[] = 'Savol sharti bo\'sh.';
        } elseif (mb_strlen($stem) > 10000) {
            $errors[] = 'Savol sharti juda uzun (10 000 belgidan ko\'p).';
        }

        $data = [
            'subject_id' => $subject['id'] ?? null,
            'topic_id' => $topicId,
            'question_type' => $type,
            'question_text' => $stem,
            'difficulty_level' => $difficulty,
            'source_ref' => mb_substr($get('source_ref'), 0, 255) ?: null,
            'solution_text' => $get('solution_text') ?: null,
            'solution_video_url' => null,
            'units_note' => mb_substr($get('units_note'), 0, 500) ?: null,
            'exam_formats' => [],
        ];

        // exam formats
        foreach (preg_split('/[,;|]+/', $get('exam_formats')) ?: [] as $f) {
            $f = mb_strtolower(trim($f));
            if ($f === '') {
                continue;
            }
            if (ExamFormat::tryFrom($f)) {
                $data['exam_formats'][] = $f;
            } else {
                $warnings[] = "Noma'lum format «{$f}» tashlab ketildi.";
            }
        }

        $video = $get('solution_video_url');
        if ($video !== '') {
            if (filter_var($video, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $video) && mb_strlen($video) <= 500) {
                $data['solution_video_url'] = $video;
            } else {
                $warnings[] = 'Yechim video havolasi noto\'g\'ri — tashlab ketildi.';
            }
        }

        // answers
        $fileAnswer = null;
        $answerLabel = null;
        $correctRaw = $get('correct');
        if ($examType?->isChoice()) {
            $options = [];
            foreach (QuestionBankOption::LABELS as $label) {
                $text = $get('option_'.strtolower($label));
                if ($text !== '') {
                    $options[$label] = $text;
                }
            }
            if (array_keys($options) !== array_slice(QuestionBankOption::LABELS, 0, count($options))) {
                $errors[] = 'Variantlar ketma-ket bo\'lishi kerak (A, B, C, D[, E]).';
            }
            if (count($options) < 4) {
                $errors[] = 'Kamida 4 ta variant kerak (A–D, ixtiyoriy E).';
            }
            $data['options'] = array_map(fn ($t) => ['text' => $t, 'is_correct' => null], array_values($options));

            if ($correctRaw !== '') {
                $labels = $this->parseLabels($correctRaw, count($options));
                if ($labels === null) {
                    $warnings[] = "Javob «{$correctRaw}» tushunarsiz — tekshiruvda belgilanadi.";
                } elseif ($examType !== ExamQuestionType::MultipleChoice && count($labels) !== 1) {
                    $warnings[] = 'Bitta javobli savolda faylda bir nechta javob — tekshiruvda belgilanadi.';
                } else {
                    $fileAnswer = ['labels' => $labels];
                    $answerLabel = implode(', ', $labels);
                }
            }
        } elseif ($examType?->usesNumericAnswer()) {
            $unit = $get('unit');
            $tolType = mb_strtolower($get('tolerance_type')) ?: 'abs';
            if (! ToleranceType::tryFrom($tolType)) {
                $errors[] = 'Xatolik turi abs yoki rel bo\'lishi kerak.';
                $tolType = 'abs';
            }
            $tolerance = $get('tolerance') === '' ? 0.0 : $this->parseNumber($get('tolerance'));
            if ($tolerance === null || $tolerance < 0) {
                $errors[] = 'Ruxsat etilgan xatolik manfiy bo\'lmagan son bo\'lishi kerak.';
                $tolerance = 0.0;
            }
            $accepted = $this->parseUnits($get('accepted_units'));
            if ($accepted === null) {
                $warnings[] = 'Qo\'shimcha birliklar tushunarsiz — tashlab ketildi (masalan: km/soat=0.2777777778).';
                $accepted = [];
            }
            if ($examType->requiresUnit() && $unit === '') {
                $warnings[] = 'O\'lchov birligi ko\'rsatilmagan — tasdiqlashdan oldin kiriting.';
            }
            $data['answer'] = [
                'value' => null,
                'tolerance_type' => $tolType,
                'tolerance' => $tolerance,
                'unit' => $examType->requiresUnit() ? mb_substr($unit, 0, 30) : null,
                'accepted_units' => $examType->requiresUnit() ? $accepted : [],
                'note' => null,
            ];
            if ($correctRaw !== '') {
                $value = $this->parseNumber($correctRaw);
                if ($value === null) {
                    $warnings[] = "Javob «{$correctRaw}» son emas — tekshiruvda kiritiladi.";
                } else {
                    $fileAnswer = ['value' => $value];
                    $answerLabel = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.').($unit !== '' && $examType->requiresUnit() ? ' '.$unit : '');
                }
            }
        }

        return [
            'row' => $line,
            'data' => $data,
            'subject' => $subject['name_uz'] ?? $get('subject'),
            'topic' => $topicLabel,
            'file_answer' => $fileAnswer,
            'answer_label' => $answerLabel,
            'errors' => $errors,
            'warnings' => $warnings,
            'needs_difficulty' => $difficulty === null,
            'duplicate' => null,
            'duplicate_of' => null,
            'hash' => $stem !== '' ? QuestionText::hash($stem) : null,
        ];
    }

    // ==================== import ====================

    /**
     * File rows that would be imported (no errors, not unticked, not a
     * duplicate on the preview) but still have no difficulty level, neither
     * from the file nor chosen on the preview.
     *
     * @param  list<int>  $excludeRows
     * @param  array<int|string, mixed>  $difficulties  file row number => level chosen on the preview
     * @return list<int> file row numbers
     */
    public function rowsMissingDifficulty(array $rows, array $excludeRows = [], array $difficulties = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeRows));
        $missing = [];
        foreach ($rows as $row) {
            if ($row['errors'] !== [] || isset($exclude[$row['row']]) || $row['duplicate'] !== null) {
                continue;
            }
            if ($this->levelFor($row, $difficulties) === null) {
                $missing[] = (int) $row['row'];
            }
        }

        return $missing;
    }

    /** The level chosen on the preview wins over the file's; null when there is none. */
    private function levelFor(array $row, array $difficulties): ?int
    {
        return Difficulty::parseLevel($difficulties[$row['row']] ?? null)
            ?? Difficulty::parseLevel($row['data']['difficulty_level'] ?? null);
    }

    /**
     * @param  list<array>  $rows  rows from parse()
     * @param  list<int>  $excludeRows  file row numbers the teacher unticked
     * @param  array<int|string, mixed>  $difficulties  file row number => level chosen on the preview
     * @return array{created: int, skipped: int, duplicates: int, with_answers: int}
     *
     * @throws \DomainException when a row to import has no difficulty level
     */
    public function import(array $rows, User $teacher, bool $acceptAnswers, array $excludeRows = [], array $difficulties = []): array
    {
        if (($missing = $this->rowsMissingDifficulty($rows, $excludeRows, $difficulties)) !== []) {
            throw new \DomainException('Qiyinlik tanlanmagan qatorlar: '.implode(', ', $missing).'.');
        }

        $editor = app(QuestionEditorService::class);
        $exclude = array_flip(array_map('intval', $excludeRows));
        $stats = ['created' => 0, 'skipped' => 0, 'duplicates' => 0, 'with_answers' => 0];
        $seen = [];

        foreach ($rows as $row) {
            if ($row['errors'] !== [] || isset($exclude[$row['row']])) {
                $stats['skipped']++;

                continue;
            }
            // the bank may have changed since the preview: check again
            if ($row['duplicate'] !== null || isset($seen[$row['hash']])
                || QuestionBank::query()->where('normalized_hash', $row['hash'])->exists()) {
                $stats['duplicates']++;

                continue;
            }
            $seen[$row['hash']] = true;

            $data = $row['data'];
            $data['difficulty_level'] = $this->levelFor($row, $difficulties);
            if ($acceptAnswers && $row['file_answer'] !== null) {
                $data = $this->applyFileAnswer($data, $row['file_answer']);
                $stats['with_answers']++;
            }

            $editor->create($data, $teacher, QuestionEditorService::INTENT_REVIEW, QuestionOrigin::Import);
            $stats['created']++;
        }

        return $stats;
    }

    private function applyFileAnswer(array $data, array $answer): array
    {
        if (isset($answer['labels']) && isset($data['options'])) {
            foreach ($data['options'] as $i => &$o) {
                $o['is_correct'] = in_array(QuestionBankOption::LABELS[$i], $answer['labels'], true);
            }
            unset($o);
        }
        if (array_key_exists('value', $answer) && isset($data['answer'])) {
            $data['answer']['value'] = $answer['value'];
        }

        return $data;
    }

    // ==================== readers ====================

    /** @return array<int, array<string, string>> */
    public function readCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($firstLine, $d))->first();

        $h = fopen('php://temp', 'r+');
        fwrite($h, $content);
        rewind($h);
        $table = [];
        while (($cells = fgetcsv($h, null, $delimiter, '"', '')) !== false) {
            $table[] = $cells;
        }
        fclose($h);

        return $this->tableToRows($table);
    }

    /** @return array<int, array<string, string>> */
    private function readSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getSheet(0);
        $table = $sheet->toArray(null, true, false, false);

        return $this->tableToRows($table);
    }

    /** @return array<int, array<string, mixed>> */
    public function readJson(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $json = json_decode($content, true, 64, JSON_THROW_ON_ERROR);
        $list = is_array($json) && array_is_list($json) ? $json : ($json['questions'] ?? null);
        if (! is_array($list)) {
            throw new \JsonException('questions list missing');
        }

        $rows = [];
        foreach (array_values($list) as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $rows[$i + 1] = $this->flattenJsonItem($item);
        }

        return $rows;
    }

    /** JSON item (nested options / answer) -> the flat CSV columns. */
    private function flattenJsonItem(array $item): array
    {
        $flat = [];
        foreach ($item as $key => $value) {
            $col = $this->column((string) $key);
            if ($col !== null && ! is_array($value)) {
                $flat[$col] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            }
        }

        $options = $item['options'] ?? $item['variantlar'] ?? null;
        if (is_array($options)) {
            // ["...", "..."] / [{"text": "...", "correct": true}] (labels by position) or {"A": "...", "B": "..."}
            $correct = [];
            $i = 0;
            foreach ($options as $key => $o) {
                $label = array_is_list($options) ? (QuestionBankOption::LABELS[$i] ?? null) : strtoupper(trim((string) $key));
                $i++;
                if (! in_array($label, QuestionBankOption::LABELS, true)) {
                    continue;
                }
                $text = is_array($o) ? ($o['text'] ?? '') : $o;
                if (is_array($o) && ($o['correct'] ?? $o['is_correct'] ?? false) === true) {
                    $correct[] = $label;
                }
                $flat['option_'.strtolower($label)] = is_scalar($text) ? (string) $text : '';
            }
            if ($correct !== [] && ! isset($flat['correct'])) {
                $flat['correct'] = implode(',', $correct);
            }
        }
        if (isset($item['correct']) && is_array($item['correct'])) {
            $flat['correct'] = implode(',', array_map('strval', $item['correct']));
        }

        $answer = $item['answer'] ?? null;
        if (is_array($answer)) {
            foreach (['value' => 'correct', 'unit' => 'unit', 'tolerance' => 'tolerance', 'tolerance_type' => 'tolerance_type'] as $k => $col) {
                if (isset($answer[$k]) && ! is_array($answer[$k])) {
                    $flat[$col] = (string) $answer[$k];
                }
            }
            if (isset($answer['accepted_units']) && is_array($answer['accepted_units'])) {
                $flat['accepted_units'] = json_encode($answer['accepted_units']);
            }
        }
        foreach (['exam_formats', 'formats'] as $k) {
            if (isset($item[$k]) && is_array($item[$k])) {
                $flat['exam_formats'] = implode(',', $item[$k]);
            }
        }
        if (isset($item['accepted_units']) && is_array($item['accepted_units'])) {
            $flat['accepted_units'] = json_encode($item['accepted_units']);
        }

        return $flat;
    }

    /**
     * First non-empty row is the header; returns file line => column => value,
     * blank rows dropped.
     *
     * @param  list<array>  $table
     * @return array<int, array<string, string>>
     */
    private function tableToRows(array $table): array
    {
        $headerAt = null;
        foreach ($table as $i => $cells) {
            if (collect($cells)->filter(fn ($c) => trim((string) $c) !== '')->isNotEmpty()) {
                $headerAt = $i;
                break;
            }
        }
        if ($headerAt === null) {
            return [];
        }
        $columns = array_map(fn ($h) => $this->column((string) $h), $table[$headerAt]);
        if (! in_array('question', $columns, true)) {
            throw new \RuntimeException('header without a question column');
        }

        $rows = [];
        foreach (array_slice($table, $headerAt + 1, null, true) as $i => $cells) {
            $row = [];
            foreach ($columns as $c => $col) {
                if ($col !== null) {
                    $value = $cells[$c] ?? '';
                    $row[$col] = is_float($value) ? $this->floatToString($value) : trim((string) $value);
                }
            }
            if (implode('', $row) !== '') {
                $rows[$i + 1] = $row; // 1-based line in the file
            }
        }

        return $rows;
    }

    private function column(string $header): ?string
    {
        $h = mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header)));
        $h = str_replace(["'", '`', "\u{2018}", "\u{2019}", "\u{02BB}", "\u{02BC}"], '', $h);
        $h = preg_replace('/[\s\-]+/', '_', $h);
        if (in_array($h, self::COLUMNS, true)) {
            return $h;
        }

        return self::ALIASES[$h] ?? null;
    }

    // ==================== value parsing ====================

    /** "0,5" / "0.5" / "1/2" / "-3" / "2.5e3" -> float, NULL when not a number. */
    public function parseNumber(string $text): ?float
    {
        $t = str_replace([' ', "\u{00A0}", "\u{2212}"], ['', '', '-'], trim($text));
        $t = preg_replace('/(?<=\d),(?=\d)/', '.', $t);
        if (preg_match('/^(-?\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)$/', $t, $m)) {
            return (float) $m[2] == 0.0 ? null : (float) $m[1] / (float) $m[2];
        }

        return is_numeric($t) ? (float) $t : null;
    }

    /** "B" / "a, c" / "AC" / "2" (1-based index) -> ["B"], NULL when unreadable. */
    private function parseLabels(string $text, int $optionCount): ?array
    {
        $t = strtoupper(trim($text));
        $parts = preg_match('/^[A-E]+$/', $t) ? str_split($t) : preg_split('/[\s,;|]+/', $t, -1, PREG_SPLIT_NO_EMPTY);
        $labels = [];
        foreach ($parts as $p) {
            if (ctype_digit($p)) {
                $p = QuestionBankOption::LABELS[(int) $p - 1] ?? '';
            }
            $index = array_search($p, QuestionBankOption::LABELS, true);
            if ($index === false || $index >= $optionCount) {
                return null;
            }
            $labels[] = $p;
        }
        $labels = array_values(array_unique($labels));

        return $labels === [] ? null : $labels;
    }

    /** "km/soat=0.2777; km/h=0.2777" or a JSON object -> [unit => factor], NULL when unreadable. */
    private function parseUnits(string $text): ?array
    {
        if (trim($text) === '') {
            return [];
        }
        if (str_starts_with(trim($text), '{')) {
            $json = json_decode($text, true);
            if (! is_array($json)) {
                return null;
            }
            $units = [];
            foreach ($json as $unit => $factor) {
                if (! is_numeric($factor) || (float) $factor <= 0) {
                    return null;
                }
                $units[trim((string) $unit)] = (float) $factor;
            }

            return $units;
        }
        $units = [];
        foreach (preg_split('/[;|\n]+/', $text) as $pair) {
            if (trim($pair) === '') {
                continue;
            }
            if (! str_contains($pair, '=')) {
                return null;
            }
            [$unit, $factor] = array_map('trim', explode('=', $pair, 2));
            $f = $this->parseNumber($factor);
            if ($unit === '' || $f === null || $f <= 0) {
                return null;
            }
            $units[$unit] = $f;
        }

        return $units;
    }

    private function floatToString(float $v): string
    {
        return floor($v) == $v && abs($v) < 1e15 ? (string) (int) $v : rtrim(rtrim(sprintf('%.10F', $v), '0'), '.');
    }

    private function loadLookups(): void
    {
        $this->subjects = [];
        foreach (Subject::query()->get(['id', 'code', 'name_uz']) as $s) {
            $entry = ['id' => $s->id, 'name_uz' => $s->name_uz];
            $this->subjects[mb_strtolower($s->code)] = $entry;
            $this->subjects[mb_strtolower($s->name_uz)] = $entry;
        }
        $this->subjects += [
            'matematika' => $this->subjects['math'] ?? null,
            'fizika' => $this->subjects['physics'] ?? null,
        ];
        $this->subjects = array_filter($this->subjects);

        $this->topics = [];
        foreach (Topic::query()->get(['id', 'subject_id', 'code', 'name_uz', 'is_active']) as $t) {
            $entry = ['id' => $t->id, 'name_uz' => $t->name_uz, 'is_active' => $t->is_active];
            $this->topics[$t->subject_id][mb_strtolower($t->code)] = $entry;
            $this->topics[$t->subject_id][mb_strtolower($t->name_uz)] ??= $entry;
        }
    }

    // ==================== templates ====================

    /** Example rows shared by all three template formats. */
    public function templateRows(): array
    {
        return [
            [
                'subject' => 'math', 'topic' => 'math.equations.quadratic', 'type' => 'single_choice', 'difficulty' => '2',
                'question' => '$x^2 - 5x + 6 = 0$ tenglamaning ildizlari yig\'indisini toping.',
                'option_a' => '$5$', 'option_b' => '$6$', 'option_c' => '$-5$', 'option_d' => '$1$', 'option_e' => '',
                'correct' => 'A', 'unit' => '', 'tolerance' => '', 'tolerance_type' => '', 'accepted_units' => '',
                'source_ref' => 'Algebra to\'plami, 12-bet, 45-masala', 'exam_formats' => 'milliy_sertifikat;abituriyent',
                'solution_text' => 'Viyet teoremasi: $x_1 + x_2 = 5$.', 'solution_video_url' => '', 'units_note' => '',
            ],
            [
                'subject' => 'math', 'topic' => 'math.percent.percent', 'type' => 'numeric', 'difficulty' => '3',
                'question' => '200 ning $15\%$ ini toping.',
                'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '', 'option_e' => '',
                'correct' => '30', 'unit' => '', 'tolerance' => '0', 'tolerance_type' => 'abs', 'accepted_units' => '',
                'source_ref' => '', 'exam_formats' => 'milliy_sertifikat',
                'solution_text' => '$200 \cdot 0{,}15 = 30$', 'solution_video_url' => '', 'units_note' => '',
            ],
            [
                'subject' => 'physics', 'topic' => 'phys.kinematics.uniform', 'type' => 'numeric_with_unit', 'difficulty' => '3',
                'question' => 'Avtomobil 2 soatda 144 km yo\'l bosdi. Uning o\'rtacha tezligini m/s da toping.',
                'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '', 'option_e' => '',
                'correct' => '20', 'unit' => 'm/s', 'tolerance' => '0.01', 'tolerance_type' => 'rel',
                'accepted_units' => 'km/soat=0.2777777778; km/h=0.2777777778',
                'source_ref' => 'Fizika masalalar to\'plami, 3-bet', 'exam_formats' => 'abituriyent',
                'solution_text' => '$v = \frac{s}{t} = \frac{144\ \text{km}}{2\ \text{soat}} = 72\ \text{km/soat} = 20\ \text{m/s}$',
                'solution_video_url' => '', 'units_note' => 'Javobni m/s da yozing.',
            ],
        ];
    }

    public function templateCsv(): string
    {
        $h = fopen('php://temp', 'r+');
        fputcsv($h, self::COLUMNS, ',', '"', '');
        foreach ($this->templateRows() as $row) {
            fputcsv($h, array_map(fn ($c) => $row[$c] ?? '', self::COLUMNS), ',', '"', '');
        }
        rewind($h);
        $csv = stream_get_contents($h);
        fclose($h);

        return "\xEF\xBB\xBF".$csv; // BOM: Excel opens UTF-8 correctly
    }

    public function templateJson(): string
    {
        $items = [];
        foreach ($this->templateRows() as $row) {
            $item = [
                'subject' => $row['subject'],
                'topic' => $row['topic'],
                'type' => $row['type'],
                'difficulty' => (int) $row['difficulty'],
                'question' => $row['question'],
            ];
            if (in_array($row['type'], ['single_choice', 'multiple_choice'], true)) {
                $item['options'] = array_values(array_filter([
                    $row['option_a'], $row['option_b'], $row['option_c'], $row['option_d'], $row['option_e'],
                ], fn ($t) => $t !== ''));
                $item['correct'] = $row['correct'];
            } else {
                $item['answer'] = array_filter([
                    'value' => (float) $row['correct'],
                    'unit' => $row['unit'] ?: null,
                    'tolerance' => (float) $row['tolerance'],
                    'tolerance_type' => $row['tolerance_type'],
                    'accepted_units' => $row['accepted_units'] !== ''
                        ? collect(explode(';', $row['accepted_units']))->mapWithKeys(function ($p) {
                            [$u, $f] = array_map('trim', explode('=', $p));

                            return [$u => (float) $f];
                        })->all()
                        : null,
                ], fn ($v) => $v !== null);
            }
            $item['source_ref'] = $row['source_ref'];
            $item['exam_formats'] = array_values(array_filter(explode(';', $row['exam_formats'])));
            $item['solution_text'] = $row['solution_text'];
            if ($row['units_note'] !== '') {
                $item['units_note'] = $row['units_note'];
            }
            $items[] = $item;
        }

        return json_encode(['questions' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }

    public function templateXlsx(): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Savollar');
        $sheet->fromArray(self::COLUMNS, null, 'A1');
        $r = 2;
        foreach ($this->templateRows() as $row) {
            // text cells: "30" stays "30", formulas starting with "=" are not evaluated
            foreach (array_values(array_map(fn ($c) => (string) ($row[$c] ?? ''), self::COLUMNS)) as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $r++;
        }
        $sheet->getStyle('A1:T1')->getFont()->setBold(true);
        foreach (range('A', 'T') as $col) {
            $sheet->getColumnDimension($col)->setWidth($col === 'E' ? 60 : 18);
        }

        $help = $book->createSheet();
        $help->setTitle("Yo'riqnoma");
        $help->fromArray([
            ['Ustun', 'Ma\'nosi'],
            ['subject', 'math yoki physics (Matematika / Fizika)'],
            ['topic', 'Mavzu kodi (masalan math.equations.quadratic) yoki mavzu nomi'],
            ['type', 'single_choice, multiple_choice, numeric, numeric_with_unit'],
            ['difficulty', "1 dan 5 gacha (1 — juda oson … 5 — juda qiyin). Bo'sh yoki noto'g'ri bo'lsa, oldindan ko'rishda tanlanadi"],
            ['question', 'Savol sharti. Formulalar: $x^2$, alohida qatorda $$...$$'],
            ['option_a … option_e', 'Variantlar (kamida A–D)'],
            ['correct', 'Variantli: harf (B) yoki bir nechta (A,C). Ochiq: son (20, 0,5, 1/2)'],
            ['unit', 'numeric_with_unit uchun asosiy birlik (m/s)'],
            ['tolerance / tolerance_type', 'Ruxsat etilgan xatolik; abs — absolyut, rel — nisbiy (0.01 = 1%)'],
            ['accepted_units', 'Boshqa birliklar: km/soat=0.2777777778; km/h=0.2777777778'],
            ['source_ref', 'Manba: to\'plam, bet, masala raqami'],
            ['exam_formats', 'milliy_sertifikat; abituriyent; sat'],
            ['solution_text / solution_video_url', 'Yechim matni va video havolasi (ixtiyoriy)'],
            ['', 'Fayldagi javob avtomatik qabul qilinmaydi: oldindan ko\'rish sahifasida alohida tasdiqlaysiz.'],
        ]);
        $help->getColumnDimension('A')->setWidth(32);
        $help->getColumnDimension('B')->setWidth(90);
        $help->getStyle('A1:B1')->getFont()->setBold(true);
        $book->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($book))->save($tmp);
        $binary = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $binary;
    }
}
