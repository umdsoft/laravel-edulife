<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam\Subject;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Test;
use App\Services\Exam\QuestionPicker;
use App\Services\Exam\TopicTreeService;
use App\Support\Difficulty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Course test → "Bankdan qo'shish" (docs/exam/03 §2.2): the teacher picks a
 * subject, a topic and a difficulty, sees how many approved questions each
 * level has, and either ticks questions by hand or asks for a distribution
 * ("oson 5 · o'rta 10 · qiyin 5") that QuestionPicker draws at random.
 *
 * The chosen bank questions are COPIED into the test (questions +
 * question_options) with the level mapped to the course band (1–2 easy,
 * 3 medium, 4–5 hard) and questions.bank_question_id pointing at the origin;
 * a bank question already in the test is never added again.
 *
 * Only choice questions are offered: the course test grades single / multiple
 * choice the same way as the bank, while a numeric answer with a tolerance
 * and units has no course test equivalent.
 */
class TestBankController extends Controller
{
    public const TYPES = ['single_choice', 'multiple_choice'];

    public const MAX_PER_ADD = 100;

    private const FILTER_RULES = [
        'subject_id' => ['required', 'uuid', 'exists:exam_subjects,id'],
        'topic_id' => ['nullable', 'uuid', 'exists:exam_topics,id'],
    ];

    public function __construct(
        private readonly QuestionPicker $picker,
        private readonly TopicTreeService $topics,
    ) {}

    public function create(Test $test): Response
    {
        $this->authorize('update', $test->course);

        $subjects = Subject::query()->orderBy('sort_order')->get(['id', 'code', 'name_uz']);

        return Inertia::render('Teacher/Courses/Tests/Questions/FromBank', [
            'course' => $test->course->only(['id', 'title', 'slug']),
            'test' => $test->only(['id', 'title', 'course_id']),
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name_uz' => $s->name_uz])->values(),
            'topics' => $this->topics->options(),
            'levels' => Difficulty::levelOptions(),
            'bands' => array_map(fn ($b) => $b + ['levels' => Difficulty::levelsOf($b['value'])], Difficulty::bandOptions()),
            'inTest' => $this->bankIdsInTest($test),
            'maxPerAdd' => self::MAX_PER_ADD,
        ]);
    }

    /** Counts per level and one page of matching questions (JSON for the page). */
    public function search(Request $request, Test $test): JsonResponse
    {
        $this->authorize('update', $test->course);
        $data = $request->validate([
            ...self::FILTER_RULES,
            'difficulty' => ['nullable', Rule::in([...array_map('strval', Difficulty::LEVELS), ...Difficulty::BANDS])],
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $filters = $this->pickerFilters($data, $test);
        $levels = $this->picker->levelCounts($filters);

        $page = $this->picker->query($filters)
            ->when(! empty($data['difficulty']), fn (Builder $q) => $q->whereIn('difficulty_level', $this->levelsFor($data['difficulty'])))
            ->when(! empty($data['q']), fn (Builder $q) => $q->where('question_text', 'like', '%'.addcslashes($data['q'], '%_\\').'%'))
            ->with(['answerOptions:id,question_id,label,text,is_correct,sort_order', 'topic:id,name_uz'])
            ->orderBy('difficulty_level')->orderByDesc('created_at')->orderBy('id')
            ->paginate(20, ['id', 'question_text', 'question_type', 'difficulty_level', 'topic_id', 'source_ref', 'created_at']);

        return response()->json([
            'levels' => $levels,
            'bands' => $this->picker->bandCounts($filters, $levels),
            'total' => $page->total(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'items' => collect($page->items())->map(fn (QuestionBank $q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_type' => $q->question_type,
                'difficulty_level' => $q->difficulty_level,
                'band' => Difficulty::bandOf($q->difficulty_level),
                'topic' => $q->topic?->name_uz,
                'source_ref' => $q->source_ref,
                'options' => $q->answerOptions->map(fn ($o) => ['label' => $o->label, 'text' => $o->text, 'is_correct' => (bool) $o->is_correct])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request, Test $test): RedirectResponse
    {
        $this->authorize('update', $test->course);
        $data = $request->validate([
            ...self::FILTER_RULES,
            'mode' => ['required', Rule::in(['pick', 'distribution'])],
            'question_ids' => ['exclude_unless:mode,pick', 'required', 'array', 'min:1', 'max:'.self::MAX_PER_ADD],
            'question_ids.*' => ['uuid', 'distinct'],
            'distribution' => ['exclude_unless:mode,distribution', 'required', 'array'],
            'distribution.easy' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_PER_ADD],
            'distribution.medium' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_PER_ADD],
            'distribution.hard' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_PER_ADD],
            'seed' => ['nullable', 'integer', 'min:1'],
        ], [
            'question_ids.required' => "Bankdan kamida bitta savolni belgilang.",
            'distribution.required' => "Har daraja uchun nechta savol kerakligini yozing.",
        ], ['subject_id' => 'fan', 'topic_id' => 'mavzu']);

        $filters = $this->pickerFilters($data, $test);
        $shortNote = '';

        if ($data['mode'] === 'pick') {
            // only approved choice questions of the chosen subject, not yet in the test
            $ids = $this->picker->query($filters)->whereIn('id', $data['question_ids'])->pluck('id')->map(fn ($id) => (string) $id)->all();
            $order = array_flip($data['question_ids']);
            usort($ids, fn ($a, $b) => $order[$a] <=> $order[$b]);
            $skipped = count($data['question_ids']) - count($ids);
            if ($skipped > 0) {
                $shortNote = " {$skipped} tasi qo'shilmadi (allaqachon testda yoki bankda mos emas).";
            }
        } else {
            $distribution = array_filter(array_map('intval', array_intersect_key($data['distribution'], array_flip(Difficulty::BANDS))));
            if ($distribution === []) {
                throw ValidationException::withMessages(['distribution' => "Kamida bitta darajaga savollar sonini yozing."]);
            }
            if (array_sum($distribution) > self::MAX_PER_ADD) {
                throw ValidationException::withMessages(['distribution' => 'Bir martada ko\'pi bilan '.self::MAX_PER_ADD.' ta savol.']);
            }
            $result = $this->picker->pick($filters, $distribution, $data['seed'] ?? null);
            $ids = $result['ids'];
            $short = collect($result['lines'])->where('shortfall', '>', 0)
                ->map(fn ($l) => mb_strtolower(Difficulty::bandLabel($l['key']) ?? $l['key']).": {$l['shortfall']} ta")->implode(', ');
            if ($short !== '') {
                $shortNote = " Bankda yetmadi — {$short}.";
            }
        }

        if ($ids === []) {
            return back()->with('warning', 'Bankdan qo\'shiladigan savol topilmadi.'.$shortNote);
        }

        $created = $this->copyIntoTest($test, $ids);
        $summary = collect($created)->countBy('difficulty');
        $parts = collect(Difficulty::BANDS)->filter(fn ($b) => $summary->get($b))
            ->map(fn ($b) => mb_strtolower(Difficulty::bandLabel($b)).' '.$summary->get($b))->implode(' · ');

        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', count($created)." ta savol bankdan qo'shildi ({$parts}).".$shortNote);
    }

    // ==================== helpers ====================

    private function pickerFilters(array $f, Test $test): array
    {
        return [
            'subject_id' => $f['subject_id'],
            'topic_id' => $f['topic_id'] ?? null,
            'types' => self::TYPES,
            'approved' => true,
            'exclude_ids' => $this->bankIdsInTest($test),
            // a choice question with no option rows cannot be copied
            'where' => fn (Builder $q) => $q->whereNotNull('difficulty_level')->whereHas('answerOptions'),
        ];
    }

    /** @return list<int> */
    private function levelsFor(string $difficulty): array
    {
        return Difficulty::isBand($difficulty) ? Difficulty::levelsOf($difficulty) : [(int) $difficulty];
    }

    /** @return list<string> */
    private function bankIdsInTest(Test $test): array
    {
        return $test->questions()->whereNotNull('bank_question_id')->pluck('bank_question_id')
            ->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    /**
     * @param  list<string>  $ids  bank question ids, in the order they are added
     * @return list<Question>
     */
    private function copyIntoTest(Test $test, array $ids): array
    {
        return DB::transaction(function () use ($test, $ids) {
            // re-read inside the transaction: never the same bank question twice in one test
            $already = array_flip($test->questions()->whereNotNull('bank_question_id')->lockForUpdate()->pluck('bank_question_id')->map(fn ($id) => (string) $id)->all());
            $bank = QuestionBank::query()->whereIn('id', $ids)->with('answerOptions')->get()->keyBy(fn ($q) => (string) $q->id);
            $order = (int) ($test->questions()->max('sort_order') ?? 0);
            $created = [];

            foreach ($ids as $id) {
                $source = $bank->get($id);
                if (! $source || isset($already[$id])) {
                    continue;
                }
                $already[$id] = true;

                $question = $test->questions()->create([
                    'course_id' => $test->course_id,
                    'bank_question_id' => $source->id,
                    'type' => $source->question_type,
                    'difficulty' => Difficulty::bandOf($source->difficulty_level) ?? Difficulty::MEDIUM,
                    'question_text' => $source->question_text,
                    'explanation' => $source->solution_text ?: $source->explanation,
                    'points' => 1,
                    'sort_order' => ++$order,
                ]);
                foreach ($source->answerOptions->values() as $i => $option) {
                    $question->options()->create([
                        'option_text' => $option->text,
                        'is_correct' => (bool) $option->is_correct,
                        'sort_order' => $i,
                    ]);
                }
                $question->syncAnswerKey();
                $created[] = $question;
            }

            return $created;
        });
    }
}
