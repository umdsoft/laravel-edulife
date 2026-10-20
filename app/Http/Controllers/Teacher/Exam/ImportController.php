<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Services\Exam\QuestionImportService;
use App\Support\Difficulty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Import: upload (CSV / Excel / JSON) -> preview (errors, duplicates, the
 * file's answers) -> confirm. Parsed rows wait in the cache, keyed by the
 * teacher and a random token, until confirmed.
 */
class ImportController extends Controller
{
    private const EXTENSIONS = ['csv', 'txt', 'xlsx', 'xls', 'ods', 'json'];

    public function __construct(private readonly QuestionImportService $import) {}

    public function index(): Response
    {
        return Inertia::render('Teacher/Exam/Import/Index', [
            'columns' => QuestionImportService::COLUMNS,
            'maxRows' => (int) config('exam.bank.import.max_rows', 500),
            'maxFileKb' => (int) config('exam.bank.import.max_file_kb', 4096),
            'importedInReview' => QuestionBank::query()
                ->where('origin', 'import')
                ->where('review_status', ReviewStatus::InReview->value)
                ->count(),
        ]);
    }

    public function template(Request $request): HttpResponse
    {
        $format = $request->query('format', 'csv');

        return match ($format) {
            'xlsx' => response($this->import->templateXlsx(), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="savollar-shablon.xlsx"',
            ]),
            'json' => response($this->import->templateJson(), 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="savollar-shablon.json"',
            ]),
            default => response($this->import->templateCsv(), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="savollar-shablon.csv"',
            ]),
        };
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('exam.bank.import.max_file_kb', 4096)],
        ], [], ['file' => 'fayl']);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => 'Fayl turi CSV, Excel (xlsx, xls, ods) yoki JSON bo\'lishi kerak.']);
        }

        $parsed = $this->import->parse($file);
        if ($parsed['rows'] === []) {
            throw ValidationException::withMessages(['file' => $parsed['errors'][0] ?? 'Faylda savol topilmadi.']);
        }

        $token = Str::random(32);
        Cache::put($this->key($request, $token), [
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 120),
            'rows' => $parsed['rows'],
            'errors' => $parsed['errors'],
            'uploaded_at' => now()->toIso8601String(),
        ], now()->addMinutes((int) config('exam.bank.import.preview_ttl_minutes', 120)));

        return redirect()->route('teacher.exam.import.preview', ['token' => $token]);
    }

    public function preview(Request $request): Response|RedirectResponse
    {
        $token = (string) $request->query('token');
        $batch = $token !== '' ? Cache::get($this->key($request, $token)) : null;
        if (! $batch) {
            return redirect()->route('teacher.exam.import.index')
                ->with('error', 'Yuklangan fayl muddati o\'tdi yoki topilmadi. Faylni qayta yuklang.');
        }

        $rows = collect($batch['rows']);
        $valid = $rows->filter(fn ($r) => $r['errors'] === [] && $r['duplicate'] === null);

        return Inertia::render('Teacher/Exam/Import/Preview', [
            'token' => $token,
            'fileName' => $batch['file_name'],
            'fileErrors' => $batch['errors'],
            'rows' => $rows->map(fn ($r) => [
                'row' => $r['row'],
                'subject' => $r['subject'],
                'topic' => $r['topic'],
                'type' => $r['data']['question_type'],
                'difficulty' => $r['data']['difficulty_level'],
                // missing / invalid in the file: the preview asks for a level (docs/exam/03 §2.1)
                'needs_difficulty' => $r['data']['difficulty_level'] === null,
                'question_text' => $r['data']['question_text'],
                'options' => collect($r['data']['options'] ?? [])->pluck('text')->all(),
                'unit' => $r['data']['answer']['unit'] ?? null,
                'source_ref' => $r['data']['source_ref'],
                'answer_label' => $r['answer_label'],
                'has_file_answer' => $r['file_answer'] !== null,
                'errors' => $r['errors'],
                'warnings' => $r['warnings'],
                'duplicate' => $r['duplicate'],
                'duplicate_of' => $r['duplicate_of'],
            ])->values(),
            'summary' => [
                'total' => $rows->count(),
                'valid' => $valid->count(),
                'errors' => $rows->filter(fn ($r) => $r['errors'] !== [])->count(),
                'duplicates' => $rows->filter(fn ($r) => $r['errors'] === [] && $r['duplicate'] !== null)->count(),
                'with_answers' => $valid->filter(fn ($r) => $r['file_answer'] !== null)->count(),
                'needs_difficulty' => $valid->filter(fn ($r) => $r['data']['difficulty_level'] === null)->count(),
            ],
            'levels' => Difficulty::levelOptions(),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:32'],
            'accept_answers' => ['boolean'],
            'exclude_rows' => ['nullable', 'array'],
            'exclude_rows.*' => ['integer'],
            // file row number => level picked on the preview (rows without a valid one in the file)
            'difficulties' => ['nullable', 'array'],
            'difficulties.*' => ['nullable', 'integer', 'between:1,5'],
        ]);
        $key = $this->key($request, $data['token']);
        $batch = Cache::get($key);
        if (! $batch) {
            return redirect()->route('teacher.exam.import.index')
                ->with('error', 'Yuklangan fayl muddati o\'tdi yoki allaqachon import qilingan.');
        }

        // no row enters the bank without a level; the upload stays in the cache so the teacher can pick them
        $difficulties = array_filter($data['difficulties'] ?? [], fn ($v) => $v !== null);
        $missing = $this->import->rowsMissingDifficulty($batch['rows'], $data['exclude_rows'] ?? [], $difficulties);
        if ($missing !== []) {
            throw ValidationException::withMessages(['difficulties' => count($missing).' ta qatorda qiyinlik tanlanmagan ('
                .implode(', ', array_slice($missing, 0, 10)).(count($missing) > 10 ? ', …' : '').'-qator). Darajani tanlang yoki qatorni olib tashlang.']);
        }

        if (! Cache::pull($key)) { // one confirmation per upload
            return redirect()->route('teacher.exam.import.index')
                ->with('error', 'Yuklangan fayl muddati o\'tdi yoki allaqachon import qilingan.');
        }

        $stats = $this->import->import($batch['rows'], $request->user(), (bool) ($data['accept_answers'] ?? false), $data['exclude_rows'] ?? [], $difficulties);

        $message = "{$stats['created']} ta savol tekshiruv navbatiga qo'shildi";
        if ($stats['with_answers'] > 0) {
            $message .= " ({$stats['with_answers']} tasida fayldagi javob belgilandi)";
        }
        $message .= '.';
        if ($stats['duplicates'] + $stats['skipped'] > 0) {
            $message .= " O'tkazib yuborildi: {$stats['duplicates']} ta dublikat, {$stats['skipped']} ta xato yoki tanlanmagan.";
        }

        return redirect()->route('teacher.exam.review', ['origin' => 'import'])->with('success', $message);
    }

    private function key(Request $request, string $token): string
    {
        return 'exam_import:'.$request->user()->id.':'.$token;
    }
}
