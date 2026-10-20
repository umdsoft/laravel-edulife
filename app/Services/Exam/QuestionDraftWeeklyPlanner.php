<?php

namespace App\Services\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\QuestionDraftRun;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\Log;

/**
 * Weekly AI drafting plan (spec: at least 100 new problems a week).
 *
 * exam.ai.weekly.target drafts are split over the active subjects that have
 * source documents. Inside a subject the topics covered by the teacher's
 * sources with the fewest questions in the bank go first; difficulties rotate
 * over exam.ai.weekly.difficulties. Runs already created by the schedule this
 * week count towards the target, so running the command twice is harmless.
 */
class QuestionDraftWeeklyPlanner
{
    public function __construct(private readonly QuestionDraftService $drafts) {}

    /** @return list<QuestionDraftRun> the runs that were started */
    public function run(): array
    {
        if (! config('exam.ai.weekly.enabled', true)) {
            Log::info('exam.ai_drafts: weekly plan disabled');

            return [];
        }
        if (! $this->drafts->isConfigured()) {
            Log::info('exam.ai_drafts: weekly plan skipped, ANTHROPIC_API_KEY is not set');

            return [];
        }
        if ($this->drafts->weeklyBudgetReached()) {
            Log::info('exam.ai_drafts: weekly plan skipped, budget reached');

            return [];
        }

        $subjects = Subject::query()->active()
            ->whereIn('id', ExamSourceDocument::query()->active()->select('subject_id'))
            ->orderBy('sort_order')->get();
        if ($subjects->isEmpty()) {
            Log::info('exam.ai_drafts: weekly plan skipped, no source documents');

            return [];
        }

        $target = (int) config('exam.ai.weekly.target', 100);
        $batch = max(1, min((int) config('exam.ai.weekly.batch_size', 10), (int) config('exam.ai.max_count_per_run', 20)));
        $difficulties = array_values(config('exam.ai.weekly.difficulties', [2, 3, 4])) ?: [3];
        $perSubject = (int) ceil($target / $subjects->count());

        $started = [];
        foreach ($subjects as $subject) {
            $already = (int) QuestionDraftRun::query()->since(now()->startOfWeek())
                ->where('trigger', QuestionDraftRun::TRIGGER_SCHEDULE)
                ->where('subject_id', $subject->id)
                ->whereNotIn('status', [QuestionDraftRun::STATUS_SKIPPED, QuestionDraftRun::STATUS_FAILED])
                ->sum('count_requested');
            $remaining = $perSubject - $already;

            $topics = $this->topicsByNeed($subject);
            if ($topics === []) {
                continue;
            }

            $i = 0;
            while ($remaining > 0) {
                $count = min($batch, $remaining);
                $started[] = $this->drafts->start([
                    'subject_id' => $subject->id,
                    'topic_id' => $topics[$i % count($topics)],
                    'difficulty_level' => $difficulties[$i % count($difficulties)],
                    'count' => $count,
                ], null, QuestionDraftRun::TRIGGER_SCHEDULE);
                $remaining -= $count;
                $i++;
            }
        }

        Log::info('exam.ai_drafts: weekly plan started runs', ['runs' => count($started)]);

        return $started;
    }

    /**
     * Topic ids covered by the subject's sources, fewest bank questions first.
     *
     * @return list<string>
     */
    private function topicsByNeed(Subject $subject): array
    {
        $docTopics = ExamSourceDocument::query()->active()->where('subject_id', $subject->id)->pluck('topic_id');
        $all = Topic::query()->where('subject_id', $subject->id)->where('is_active', true)->get(['id', 'parent_id', 'level']);

        if ($docTopics->contains(null)) {
            // a whole-subject manual: every topic (leaf topics preferred)
            $parents = $all->pluck('parent_id')->filter()->unique();
            $candidates = $all->reject(fn ($t) => $parents->contains($t->id))->pluck('id')->all();
        } else {
            $candidates = [];
            foreach ($all->whereIn('id', $docTopics->filter()->unique()->all()) as $topic) {
                $candidates = [...$candidates, ...Topic::query()->find($topic->id)->descendantAndSelfIds()];
            }
            $candidates = array_values(array_unique($candidates));
        }

        if ($candidates === []) {
            return [];
        }

        $counts = QuestionBank::query()
            ->whereIn('topic_id', $candidates)
            ->whereIn('review_status', [ReviewStatus::InReview->value, ReviewStatus::Approved->value])
            ->selectRaw('topic_id, count(*) as c')->groupBy('topic_id')->pluck('c', 'topic_id');

        usort($candidates, fn ($a, $b) => ((int) ($counts[$a] ?? 0)) <=> ((int) ($counts[$b] ?? 0)) ?: strcmp($a, $b));

        return $candidates;
    }
}
