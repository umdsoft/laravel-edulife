<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Exceptions\Exam\QuestionNotApprovable;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Exam\Concerns\HandlesBankQuestions;
use App\Models\Exam\Subject;
use App\Models\QuestionBank;
use App\Services\Exam\QuestionEditorService;
use App\Services\Exam\QuestionReviewService;
use App\Services\Exam\TopicTreeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Tekshiruv navbati": one in_review question at a time. The teacher marks
 * the correct answer (click or keys 1-5), fixes the text inline and
 * approves (Enter) or rejects with a reason. Approval is decided by
 * QuestionApprovalGuard on the server; the screen only mirrors it.
 */
class ReviewController extends Controller
{
    use HandlesBankQuestions;

    public function __construct(
        private readonly QuestionEditorService $editor,
        private readonly QuestionReviewService $review,
    ) {}

    public function show(Request $request, TopicTreeService $topics): Response
    {
        $filters = $this->filters($request);
        $queue = $this->queue($filters);

        $current = null;
        if ($request->filled('question')) {
            $current = (clone $queue)->whereKey($request->query('question'))->first();
        }
        if ($current === null && $request->filled('after')) {
            $current = $this->nextAfter($queue, (string) $request->query('after'));
        }
        $current ??= (clone $queue)->first();

        $remaining = (clone $queue)->reorder()->count();
        $position = $current ? (clone $queue)->reorder()->where(fn (Builder $q) => $q
            ->where('created_at', '<', $current->created_at)
            ->orWhere(fn (Builder $w) => $w->where('created_at', $current->created_at)->where('id', '<', $current->id))
        )->count() + 1 : 0;

        return Inertia::render('Teacher/Exam/Review/Index', [
            'question' => $current ? $this->questionPayload($current) : null,
            'queue' => [
                'remaining' => $remaining,
                'position' => $position,
                'next_id' => $current ? $this->nextAfter($queue, $current->id)?->id : null,
            ],
            'progress' => $this->weeklyProgress(),
            'filters' => $filters,
            'subjects' => $this->subjectsPayload(),
            'topics' => $topics->options(),
            'vocabulary' => $this->vocabulary(),
        ]);
    }

    /** One click / key: choice options. single_choice: the only correct one; multiple_choice: toggle. */
    public function mark(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->ensureReviewable($question);
        $data = $request->validate(['option_id' => ['required', 'uuid']]);
        abort_unless($question->examType()?->isChoice(), 422, 'Bu savolda variant yo\'q.');
        abort_unless($question->answerOptions()->whereKey($data['option_id'])->exists(), 422, 'Variant bu savolga tegishli emas.');

        $this->editor->markOption($question, $data['option_id']);

        return back(303);
    }

    /** Expected numeric answer (value, tolerance, unit) from the review screen. */
    public function answer(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->ensureReviewable($question);
        abort_unless($question->examType()?->usesNumericAnswer(), 422, 'Bu savol son javobli emas.');
        $request->merge(['question_type' => $question->question_type, 'subject_id' => $question->subject_id]);
        $data = $this->validatedQuestion($request, true);

        $this->editor->setNumericAnswer($question, $data['answer'] ?? []);

        return back(303)->with('success', 'Javob saqlandi.');
    }

    /** Inline edit: stem, option texts (marks kept), difficulty, subject / topic, solution, source. */
    public function update(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->ensureReviewable($question);
        $request->merge(['subject_id' => $request->input('subject_id', $question->subject_id)]);
        $request->request->remove('question_type'); // the type is not changed here
        $data = $this->validatedQuestion($request, true);
        unset($data['answer']);

        try {
            $this->editor->update($question, $data, $request->user(), null, true);
        } catch (\DomainException $e) {
            return back(303)->with('error', $e->getMessage());
        }

        return back(303)->with('success', 'Saqlandi.');
    }

    public function approve(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->ensureReviewable($question);
        $data = $request->validate(['option_id' => ['nullable', 'uuid']]);
        if (! empty($data['option_id']) && ! $question->answerOptions()->whereKey($data['option_id'])->exists()) {
            return back(303)->withErrors(['approve' => 'Variant bu savolga tegishli emas.']);
        }

        try {
            $this->editor->approve($question, $request->user(), $data['option_id'] ?? null);
        } catch (QuestionNotApprovable $e) {
            return back(303)->withErrors(['approve' => implode(' ', $e->violations)]);
        }

        return redirect()->route('teacher.exam.review', $this->nextQuery($request, $question))
            ->with('success', 'Tasdiqlandi.');
    }

    public function reject(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->ensureReviewable($question);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']], [
            'reason.required' => 'Rad etish sababini yozing.',
        ]);
        abort_if($question->review_status === ReviewStatus::Approved, 422, 'Tasdiqlangan savolni muomaladan chiqaring.');
        $this->review->reject($question, $request->user(), $data['reason']);

        return redirect()->route('teacher.exam.review', $this->nextQuery($request, $question))
            ->with('success', 'Rad etildi.');
    }

    // ==================== helpers ====================

    private function filters(Request $request): array
    {
        $f = $request->validate([
            'subject' => ['nullable', 'string', 'max:40'],
            'origin' => ['nullable', Rule::in(['ai_draft', 'manual', 'import'])],
        ]);

        return array_filter($f, fn ($v) => $v !== null && $v !== '');
    }

    private function queue(array $filters): Builder
    {
        $subjectId = ! empty($filters['subject'])
            ? Subject::query()->where('code', $filters['subject'])->value('id')
            : null;

        return QuestionBank::query()
            ->where('review_status', ReviewStatus::InReview->value)
            ->when(! empty($filters['subject']), fn (Builder $q) => $q->where('subject_id', $subjectId))
            ->when(! empty($filters['origin']), fn (Builder $q) => $q->where('origin', $filters['origin']))
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /** Next question in queue order after $id; wraps around to the first one (never $id itself). */
    private function nextAfter(Builder $queue, string $id): ?QuestionBank
    {
        $ref = QuestionBank::withTrashed()->whereKey($id)->first(['id', 'created_at']);
        if ($ref) {
            $next = (clone $queue)->where(fn (Builder $q) => $q
                ->where('created_at', '>', $ref->created_at)
                ->orWhere(fn (Builder $w) => $w->where('created_at', $ref->created_at)->where('id', '>', $ref->id))
            )->first();
            if ($next) {
                return $next;
            }
        }

        return (clone $queue)->whereKeyNot($id)->first();
    }

    private function nextQuery(Request $request, QuestionBank $question): array
    {
        return array_merge($this->filters($request), ['after' => $question->id]);
    }

    private function ensureReviewable(QuestionBank $question): void
    {
        abort_if($question->subject_id === null, 404);
        abort_if(in_array($question->review_status, [ReviewStatus::Retired], true), 422, 'Muomaladan chiqarilgan savol.');
    }

    /** "bu hafta tasdiqlandi: N / target" (Monday 00:00, app timezone; demo seeds not counted). */
    private function weeklyProgress(): array
    {
        $approved = QuestionBank::query()
            ->where('review_status', ReviewStatus::Approved->value)
            ->whereNotNull('reviewed_by')
            ->where('is_demo', false)
            ->where('reviewed_at', '>=', now()->startOfWeek())
            ->count();

        return [
            'approved' => $approved,
            'target' => max(1, (int) config('exam.bank.weekly_review_target', 100)),
        ];
    }
}
