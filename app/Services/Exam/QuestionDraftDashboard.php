<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamFormat;
use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\QuestionDraftRun;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use Illuminate\Support\Facades\Route;

/**
 * Inertia props shared by the admin "AI qoralamalar" page and the teacher's
 * source page: run log, cost totals and the options of the start-run form.
 */
class QuestionDraftDashboard
{
    public function __construct(private readonly QuestionDraftService $drafts) {}

    public function runs(int $perPage = 20)
    {
        return QuestionDraftRun::query()
            ->with(['subject:id,code,name_uz', 'topic:id,code,name_uz', 'requester:id,first_name,last_name'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (QuestionDraftRun $run) => [
                'id' => $run->id,
                'created_at' => $run->created_at?->toIso8601String(),
                'finished_at' => $run->finished_at?->toIso8601String(),
                'subject' => $run->subject ? ['code' => $run->subject->code, 'name' => $run->subject->name_uz] : null,
                'topic' => $run->topic?->name_uz,
                'difficulty_level' => $run->difficulty_level,
                'exam_format' => $run->exam_format,
                'count_requested' => $run->count_requested,
                'trigger' => $run->trigger,
                'status' => $run->status,
                'model' => $run->response_model ?: $run->model,
                'prompt_version' => $run->prompt_name ? $run->prompt_name.'@'.$run->prompt_version : null,
                'input_tokens' => $run->input_tokens,
                'output_tokens' => $run->output_tokens,
                'estimated_cost_usd' => (float) $run->estimated_cost_usd,
                'drafts_created' => $run->drafts_created,
                'drafts_rejected' => $run->drafts_rejected,
                'duplicates_skipped' => $run->duplicates_skipped,
                'error' => $run->error,
                'requested_by' => $run->requester?->full_name,
            ]);
    }

    public function totals(): array
    {
        $sum = fn ($from) => QuestionDraftRun::query()->since($from)
            ->selectRaw('count(*) as runs, coalesce(sum(input_tokens),0) as input_tokens, coalesce(sum(output_tokens),0) as output_tokens, coalesce(sum(estimated_cost_usd),0) as cost, coalesce(sum(drafts_created),0) as drafts')
            ->first();

        $row = fn ($r) => [
            'runs' => (int) $r->runs,
            'input_tokens' => (int) $r->input_tokens,
            'output_tokens' => (int) $r->output_tokens,
            'cost_usd' => round((float) $r->cost, 4),
            'drafts' => (int) $r->drafts,
        ];

        return [
            'week' => $row($sum(now()->startOfWeek())),
            'month' => $row($sum(now()->startOfMonth())),
        ];
    }

    public function settings(): array
    {
        try {
            $prompt = QuestionDraftPrompt::load((string) config('exam.ai.prompt', 'question_draft'));
            $promptVersion = $prompt->name.'@'.$prompt->version;
        } catch (\Throwable) {
            $promptVersion = null;
        }

        return [
            'configured' => $this->drafts->isConfigured(),
            'model' => (string) config('exam.ai.model'),
            'effort' => (string) config('exam.ai.effort'),
            'prompt_version' => $promptVersion,
            'store_hint' => (bool) config('exam.ai.store_suggested_answer'),
            'max_count' => (int) config('exam.ai.max_count_per_run', 20),
            'weekly_enabled' => (bool) config('exam.ai.weekly.enabled'),
            'weekly_target' => (int) config('exam.ai.weekly.target'),
            'weekly_budget_usd' => (float) config('exam.ai.weekly.budget_usd'),
            'weekly_spent_usd' => round($this->drafts->weeklySpend(), 4),
            'review_url' => Route::has('teacher.exam.review') ? route('teacher.exam.review') : null,
        ];
    }

    /** Subjects with their topic trees (flat, indented by level) and source documents. */
    public function formOptions(): array
    {
        $subjects = Subject::query()->active()->orderBy('sort_order')->get(['id', 'code', 'name_uz']);
        $topics = Topic::query()->whereIn('subject_id', $subjects->pluck('id'))->where('is_active', true)
            ->orderBy('level')->orderBy('sort_order')->get(['id', 'subject_id', 'parent_id', 'code', 'name_uz', 'level']);

        return [
            'subjects' => $subjects->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name_uz,
                'topics' => $this->flatTree($topics->where('subject_id', $s->id)->values()),
            ])->values()->all(),
            'sources' => ExamSourceDocument::query()->active()->latest()
                ->get(['id', 'subject_id', 'topic_id', 'title', 'char_count'])
                ->map(fn ($d) => ['id' => $d->id, 'subject_id' => $d->subject_id, 'topic_id' => $d->topic_id, 'title' => $d->title, 'char_count' => $d->char_count])
                ->values()->all(),
            'formats' => array_map(fn (ExamFormat $f) => ['value' => $f->value, 'label' => $f->label()], ExamFormat::cases()),
        ];
    }

    private function flatTree($topics, ?string $parentId = null, int $depth = 0): array
    {
        $out = [];
        foreach ($topics->where('parent_id', $parentId) as $topic) {
            $out[] = ['id' => $topic->id, 'name' => $topic->name_uz, 'code' => $topic->code, 'depth' => $depth];
            if ($depth < 6) {
                $out = [...$out, ...$this->flatTree($topics, $topic->id, $depth + 1)];
            }
        }

        return $out;
    }
}
