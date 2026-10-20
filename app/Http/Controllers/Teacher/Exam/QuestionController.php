<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Exceptions\Exam\QuestionNotApprovable;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Exam\Concerns\HandlesBankQuestions;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Services\Exam\DifficultyCalibration;
use App\Services\Exam\QuestionEditorService;
use App\Services\Exam\QuestionReviewService;
use App\Services\Exam\TopicTreeService;
use App\Support\Difficulty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Savollar banki": list with filters, create / edit form for exam questions.
 */
class QuestionController extends Controller
{
    use HandlesBankQuestions;

    public function __construct(
        private readonly QuestionEditorService $editor,
        private readonly TopicTreeService $topics,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'subject' => ['nullable', 'string', 'max:40'],
            'topic' => ['nullable', 'uuid'],
            'difficulty' => ['nullable', 'integer', 'between:1,5'],
            'format' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(ReviewStatus::values())],
            'origin' => ['nullable', Rule::in(['ai_draft', 'manual', 'import'])],
            'type' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:200'],
            'demo' => ['nullable', 'in:0,1'],
            // difficulty (wave 2): "belgilangan va amaldagi qiyinlik farq qiladi"
            'calibration' => ['nullable', Rule::in(['mismatch'])],
        ]);

        $subject = ! empty($filters['subject'])
            ? Subject::query()->where('code', $filters['subject'])->orWhere('id', $filters['subject'])->first()
            : null;

        $base = QuestionBank::query()
            ->whereNotNull('subject_id') // olympiad-only rows stay out of the exam bank
            ->when($subject, fn (Builder $q) => $q->where('subject_id', $subject->id));
        $this->applyFilters($base, $filters);

        $statusCounts = (clone $base)->reorder()
            ->selectRaw('review_status, count(*) as c')->groupBy('review_status')->pluck('c', 'review_status');

        $listed = (clone $base)
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('review_status', $filters['status']))
            ->with(['answerOptions:id,question_id,label,text,is_correct,sort_order', 'numericAnswers:id,question_id,value,unit',
                'topic:id,name_uz', 'subject:id,code,name_uz'])
            ->latest()
            ->orderByDesc('id')
            ->paginate((int) config('exam.bank.per_page', 20))
            ->withQueryString();

        $calibration = app(DifficultyCalibration::class)->forBank(
            $listed->getCollection()->mapWithKeys(fn (QuestionBank $q) => [(string) $q->id => $q->difficulty_level])->all()
        );
        $listed->getCollection()->transform(fn (QuestionBank $q) => [
            'id' => $q->id,
            'calibration' => $calibration[(string) $q->id] ?? null,
            'question_text' => $q->question_text,
            'question_type' => $q->question_type,
            'difficulty_level' => $q->difficulty_level,
            'review_status' => $q->review_status?->value,
            'origin' => $q->origin?->value,
            'source_ref' => $q->source_ref,
            'exam_formats' => $q->exam_formats ?? [],
            'subject' => $q->subject?->only(['code', 'name_uz']),
            'topic' => $q->topic?->name_uz,
            'is_demo' => (bool) $q->is_demo,
            'error_reports_count' => (int) $q->error_reports_count,
            'reject_reason' => $q->reject_reason,
            'options' => $q->answerOptions->map(fn ($o) => ['label' => $o->label, 'text' => $o->text, 'is_correct' => $o->is_correct])->all(),
            'answer' => ($a = $q->numericAnswers->first()) ? [
                'value' => $a->value === null ? null : rtrim(rtrim((string) $a->value, '0'), '.'),
                'unit' => $a->unit,
            ] : null,
            'created_at' => $q->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Teacher/Exam/Questions/Index', [
            'questions' => $listed,
            'filters' => array_merge(['subject' => $subject?->code], array_filter($filters, fn ($v) => $v !== null && $v !== '')),
            'statusCounts' => $statusCounts,
            'subjects' => $this->subjectsPayload(),
            'topics' => $this->topics->options(),
            'vocabulary' => $this->vocabulary(),
            'reviewPending' => QuestionBank::query()->where('review_status', ReviewStatus::InReview->value)->count(),
        ]);
    }

    public function create(Request $request): Response
    {
        $subject = Subject::query()->where('code', $request->query('subject'))->first()
            ?? Subject::query()->orderBy('sort_order')->first();
        $topicId = $request->query('topic');

        return Inertia::render('Teacher/Exam/Questions/Create', [
            'question' => null,
            'defaults' => [
                'subject_id' => $subject?->id,
                'topic_id' => $topicId && Topic::query()->whereKey($topicId)->exists() ? $topicId : null,
                'question_type' => in_array($request->query('type'), ['single_choice', 'multiple_choice', 'numeric', 'numeric_with_unit'], true)
                    ? $request->query('type') : 'single_choice',
                // no preselected level; "Saqlash va yana qo'shish" carries the level the teacher just chose
                'difficulty_level' => Difficulty::parseLevel($request->query('difficulty')),
            ],
            'subjects' => $this->subjectsPayload(),
            'topics' => $this->topics->options(),
            'vocabulary' => $this->vocabulary(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $intent = $this->intent($request);
        $data = $this->validatedQuestion($request);

        $approve = $intent === QuestionEditorService::INTENT_APPROVE;
        $question = $this->editor->create($data, $request->user(), $approve ? QuestionEditorService::INTENT_REVIEW : $intent);
        if ($approve) {
            try {
                $this->editor->approve($question, $request->user());
            } catch (QuestionNotApprovable $e) {
                return redirect()->route('teacher.exam.questions.edit', $question)
                    ->with('warning', 'Savol saqlandi, lekin tasdiqlanmadi: '.implode(' ', $e->violations));
            }
            $question->refresh();
        }

        if ($request->input('after') === 'create') {
            return redirect()->route('teacher.exam.questions.create', [
                'subject' => $question->subject?->code,
                'topic' => $question->topic_id,
                'type' => $question->question_type,
                'difficulty' => $question->difficulty_level,
            ])->with('success', $this->savedMessage($question).' Keyingisini yozing.');
        }

        return redirect()->route('teacher.exam.questions.index', ['subject' => $question->subject?->code])
            ->with('success', $this->savedMessage($question));
    }

    public function edit(QuestionBank $question): Response
    {
        abort_if($question->subject_id === null, 404);

        return Inertia::render('Teacher/Exam/Questions/Edit', [
            'question' => $this->questionPayload($question),
            'subjects' => $this->subjectsPayload(),
            'topics' => $this->topics->options(),
            'vocabulary' => $this->vocabulary(),
            'usedInExams' => $this->usedInExams($question),
        ]);
    }

    public function update(Request $request, QuestionBank $question): RedirectResponse
    {
        abort_if($question->subject_id === null, 404);
        $intent = $request->filled('intent') ? $this->intent($request) : null;
        $data = $this->validatedQuestion($request);

        try {
            $this->editor->update($question, $data, $request->user(), $intent);
        } catch (QuestionNotApprovable $e) {
            return back()->with('warning', 'O\'zgarishlar saqlandi, lekin tasdiqlanmadi: '.implode(' ', $e->violations));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('teacher.exam.questions.edit', $question)
            ->with('success', $this->savedMessage($question->refresh()));
    }

    public function destroy(QuestionBank $question): RedirectResponse
    {
        if ($question->review_status === ReviewStatus::Approved || $this->usedInExams($question)) {
            return back()->with('error', 'Tasdiqlangan yoki imtihonda ishlatilgan savol o\'chirilmaydi — uni muomaladan chiqaring.');
        }
        $question->delete();

        return redirect()->route('teacher.exam.questions.index')->with('success', 'Savol o\'chirildi.');
    }

    public function duplicate(Request $request, QuestionBank $question): RedirectResponse
    {
        $copy = $this->editor->duplicate($question, $request->user());

        return redirect()->route('teacher.exam.questions.edit', $copy)->with('success', 'Nusxa yaratildi (qoralama).');
    }

    public function retire(Request $request, QuestionBank $question, QuestionReviewService $review): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        if ($question->review_status !== ReviewStatus::Approved) {
            return back()->with('error', 'Faqat tasdiqlangan savolni muomaladan chiqarish mumkin.');
        }
        $review->retire($question, $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Savol muomaladan chiqarildi: endi imtihonlarga tushmaydi.');
    }

    // ==================== helpers ====================

    private function applyFilters(Builder $q, array $f): void
    {
        if (! empty($f['topic'])) {
            $q->whereIn('topic_id', $this->topics->subtreeIds($f['topic']) ?: [$f['topic']]);
        }
        if (! empty($f['difficulty'])) {
            $q->where('difficulty_level', (int) $f['difficulty']);
        }
        if (! empty($f['format'])) {
            $q->whereJsonContains('exam_formats', $f['format']);
        }
        if (! empty($f['origin'])) {
            $q->where('origin', $f['origin']);
        }
        if (! empty($f['type'])) {
            $q->where('question_type', $f['type']);
        }
        if (! empty($f['source'])) {
            $q->where('source_ref', 'like', '%'.$f['source'].'%');
        }
        if (! empty($f['q'])) {
            $term = '%'.$f['q'].'%';
            $q->where(fn (Builder $w) => $w->where('question_text', 'like', $term)->orWhere('source_ref', 'like', $term));
        }
        if (isset($f['demo']) && $f['demo'] !== '') {
            $q->where('is_demo', (bool) $f['demo']);
        }
        if (($f['calibration'] ?? null) === 'mismatch') {
            app(DifficultyCalibration::class)->scopeBankMismatch($q);
        }
    }

    private function intent(Request $request): string
    {
        $intent = $request->input('intent', QuestionEditorService::INTENT_REVIEW);

        return in_array($intent, [QuestionEditorService::INTENT_DRAFT, QuestionEditorService::INTENT_REVIEW, QuestionEditorService::INTENT_APPROVE], true)
            ? $intent : QuestionEditorService::INTENT_REVIEW;
    }

    private function savedMessage(QuestionBank $q): string
    {
        return match ($q->review_status) {
            ReviewStatus::Approved => 'Savol saqlandi va tasdiqlandi.',
            ReviewStatus::InReview => 'Savol saqlandi va tekshiruv navbatida.',
            ReviewStatus::Draft => 'Savol qoralama sifatida saqlandi.',
            default => 'Savol saqlandi.',
        };
    }

    private function usedInExams(QuestionBank $q): bool
    {
        return DB::table('exam_answers')->where('question_id', $q->id)->exists();
    }
}
