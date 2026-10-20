<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamAnswer;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read model of a student's exam progress: growth history, topic map,
 * weakest topics, recommendations and the Sunday report payload.
 *
 * Reads only what stage 5 writes (exam_student_weekly_results,
 * exam_student_topic_mastery, exam_answers); never writes.
 *
 * Section mastery (level 1) is not stored: it is the answered_count weighted
 * average of the mastery rows in its subtree (docs/exam/02_DATA_MODEL.md §6).
 */
class ReportBuilder
{
    /** Below this a topic counts as "weak" on the progress page. */
    public const WEAK_THRESHOLD = 60.0;

    /**
     * Subjects the student works in: chosen at onboarding, plus any subject
     * with results or mastery (a student may have switched).
     *
     * @return Collection<int, Subject>
     */
    public function subjectsFor(User $student): Collection
    {
        $codes = StudentExamProfile::query()->where('user_id', $student->id)->value('subjects');
        $codes = is_string($codes) ? (json_decode($codes, true) ?: []) : ($codes ?? []);

        $withData = StudentTopicMastery::query()->where('user_id', $student->id)->distinct()->pluck('subject_id')
            ->merge(StudentWeeklyResult::query()->where('user_id', $student->id)->distinct()->pluck('subject_id'))
            ->unique()->values()->all();

        return Subject::query()
            ->where(fn ($q) => $q->whereIn('code', $codes ?: ['__none__'])->orWhereIn('id', $withData ?: ['__none__']))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Weekly results, oldest first, one point per week per subject.
     *
     * @return list<array{subject: string, week_start: string, label: string, score: float, max_score: float, percent: float, delta: float|null, rank: int|null, participants: int|null, result_id: string}>
     */
    public function history(User $student, ?Subject $subject = null, int $weeks = 26): array
    {
        $subjects = Subject::query()->pluck('code', 'id');

        return StudentWeeklyResult::query()
            ->where('user_id', $student->id)
            ->when($subject, fn ($q) => $q->where('subject_id', $subject->id))
            ->orderByDesc('week_start')
            ->orderByDesc('created_at')
            ->limit($weeks * 2)
            ->get()
            ->unique(fn (StudentWeeklyResult $r) => $r->subject_id.'|'.$r->week_start->toDateString())
            ->sortBy(fn (StudentWeeklyResult $r) => $r->week_start->toDateString())
            ->values()
            ->map(fn (StudentWeeklyResult $r) => $this->resultRow($r, (string) ($subjects[$r->subject_id] ?? '')))
            ->all();
    }

    public function resultRow(StudentWeeklyResult $r, string $subjectCode = ''): array
    {
        return [
            'result_id' => $r->id,
            'subject' => $subjectCode,
            'week_start' => $r->week_start->toDateString(),
            'label' => $r->week_start->format('d.m'),
            'score' => round((float) $r->score, 2),
            'max_score' => round((float) $r->max_score, 2),
            'percent' => round((float) $r->score_percent, 1),
            'delta' => $r->delta_prev === null ? null : round((float) $r->delta_prev, 1),
            'rank' => $r->rank,
            'participants' => $r->participants_count,
        ];
    }

    /**
     * Topic map: subject -> sections -> topics with mastery (null = no data yet).
     *
     * @return list<array{code: string, name: string, score: float|null, sections: list<array>}>
     */
    public function topicMap(User $student, ?Collection $subjects = null): array
    {
        $subjects ??= $this->subjectsFor($student);
        if ($subjects->isEmpty()) {
            return [];
        }

        $mastery = StudentTopicMastery::query()
            ->where('user_id', $student->id)
            ->get()
            ->keyBy('topic_id');

        $topics = Topic::query()
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get(['id', 'subject_id', 'parent_id', 'code', 'name_uz', 'level', 'sort_order']);

        $children = $topics->groupBy(fn (Topic $t) => (string) $t->parent_id);

        $map = [];
        foreach ($subjects as $subject) {
            $sections = [];
            $subjectWeight = 0.0;
            $subjectSum = 0.0;
            foreach ($children->get('', collect())->where('subject_id', $subject->id) as $section) {
                $rows = [];
                $weight = 0.0;
                $sum = 0.0;
                foreach ($this->subtree($section, $children) as $node) {
                    $m = $mastery->get($node->id);
                    if ($m) {
                        $w = max(1, (int) $m->answered_count);
                        $weight += $w;
                        $sum += $w * (float) $m->score;
                    }
                    if ($node->id !== $section->id) {
                        $rows[] = [
                            'id' => $node->id,
                            'code' => $node->code,
                            'name' => $node->name_uz,
                            'score' => $m ? round((float) $m->score, 1) : null,
                            'answered' => $m ? (int) $m->answered_count : 0,
                        ];
                    }
                }
                $subjectWeight += $weight;
                $subjectSum += $sum;
                $sections[] = [
                    'id' => $section->id,
                    'code' => $section->code,
                    'name' => $section->name_uz,
                    'score' => $weight > 0 ? round($sum / $weight, 1) : null,
                    'topics' => $rows,
                ];
            }

            $map[] = [
                'code' => $subject->code,
                'name' => $subject->name_uz,
                'score' => $subjectWeight > 0 ? round($subjectSum / $subjectWeight, 1) : null,
                'sections' => $sections,
            ];
        }

        return $map;
    }

    /** @return list<Topic> section first, then descendants */
    private function subtree(Topic $root, Collection $children): array
    {
        $out = [$root];
        foreach ($children->get((string) $root->id, collect()) as $child) {
            array_push($out, ...$this->subtree($child, $children));
        }

        return $out;
    }

    /**
     * Topics with data, weakest first.
     *
     * @return list<array{id: string, code: string, name: string, subject: string, section: string|null, score: float, answered: int}>
     */
    public function weakestTopics(User $student, int $limit = 5, ?Subject $subject = null): array
    {
        $rows = StudentTopicMastery::query()
            ->with(['topic:id,parent_id,code,name_uz,subject_id,is_active', 'topic.parent:id,name_uz', 'subject:id,code'])
            ->where('user_id', $student->id)
            ->when($subject, fn ($q) => $q->where('subject_id', $subject->id))
            ->orderBy('score')
            ->orderByDesc('answered_count')
            ->limit($limit * 3)
            ->get()
            ->filter(fn (StudentTopicMastery $m) => $m->topic && $m->topic->is_active && (float) $m->score < 100)
            ->take($limit);

        return $rows->map(fn (StudentTopicMastery $m) => [
            'id' => $m->topic->id,
            'code' => $m->topic->code,
            'name' => $m->topic->name_uz,
            'subject' => $m->subject?->code,
            'section' => $m->topic->parent?->name_uz,
            'score' => round((float) $m->score, 1),
            'answered' => (int) $m->answered_count,
        ])->values()->all();
    }

    /**
     * Topics to work on next week: the weakest measured topics first; when
     * there are fewer than $count, the first not yet measured topics of the
     * student's subjects (grade appropriate) fill the list.
     *
     * @return list<array{id: string, code: string, name: string, subject: string|null, section: string|null, score: float|null, reason: string}>
     */
    public function recommendations(User $student, ?int $count = null): array
    {
        $count ??= (int) config('exam.report.recommendations', 3);

        $picked = array_map(fn (array $t) => $t + [
            'reason' => $t['score'] < self::WEAK_THRESHOLD ? 'weak' : 'improve',
        ], $this->weakestTopics($student, $count));

        if (count($picked) < $count) {
            $subjects = $this->subjectsFor($student);
            $grade = StudentExamProfile::query()->where('user_id', $student->id)->value('grade');
            $known = StudentTopicMastery::query()->where('user_id', $student->id)->pluck('topic_id')->all();

            $fill = Topic::query()
                ->with(['parent:id,name_uz,sort_order', 'subject:id,code,sort_order'])
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->where('is_active', true)
                ->where('level', '>', Topic::LEVEL_SECTION)
                ->whereNotIn('id', $known ?: ['__none__'])
                ->when($grade, fn ($q) => $q->forGrade((int) $grade))
                ->get()
                ->sortBy(fn (Topic $t) => sprintf('%03d-%05d-%05d', $t->subject?->sort_order ?? 0, $t->parent?->sort_order ?? 0, $t->sort_order))
                ->take($count - count($picked));

            foreach ($fill as $topic) {
                $picked[] = [
                    'id' => $topic->id,
                    'code' => $topic->code,
                    'name' => $topic->name_uz,
                    'subject' => $topic->subject?->code,
                    'section' => $topic->parent?->name_uz,
                    'score' => null,
                    'answered' => 0,
                    'reason' => 'new',
                ];
            }
        }

        return array_values($picked);
    }

    /**
     * The topic whose share of points grew most between this result's attempt
     * and the previous weekly attempt in the same subject (null when nothing grew).
     *
     * @return array{name: string, code: string, delta: float}|null
     */
    public function mostImprovedTopic(StudentWeeklyResult $result): ?array
    {
        if (! $result->attempt_id) {
            return null;
        }

        $previous = StudentWeeklyResult::query()
            ->where('user_id', $result->user_id)
            ->where('subject_id', $result->subject_id)
            ->where('week_start', '<', $result->week_start->toDateString())
            ->whereNotNull('attempt_id')
            ->orderByDesc('week_start')
            ->first();
        if (! $previous) {
            return null;
        }

        $now = $this->topicShares($result->attempt_id);
        $before = $this->topicShares($previous->attempt_id);

        $best = null;
        foreach ($now as $topicId => $share) {
            if (! isset($before[$topicId])) {
                continue;
            }
            $delta = $share - $before[$topicId];
            if ($delta > 0 && ($best === null || $delta > $best[1])) {
                $best = [$topicId, $delta];
            }
        }
        if ($best === null) {
            return null;
        }

        $topic = Topic::query()->find($best[0], ['id', 'code', 'name_uz']);

        return $topic ? ['name' => $topic->name_uz, 'code' => $topic->code, 'delta' => round($best[1] * 100, 1)] : null;
    }

    /** @return array<string, float> topic_id => points / max_points of one attempt */
    private function topicShares(string $attemptId): array
    {
        return ExamAnswer::query()
            ->join('question_bank', 'question_bank.id', '=', 'exam_answers.question_id')
            ->where('exam_answers.attempt_id', $attemptId)
            ->whereNotNull('question_bank.topic_id')
            ->groupBy('question_bank.topic_id')
            ->selectRaw('question_bank.topic_id as topic_id, SUM(COALESCE(exam_answers.points, 0)) as pts, SUM(COALESCE(exam_answers.max_points, 0)) as mx')
            ->get()
            ->filter(fn ($row) => (float) $row->mx > 0)
            ->mapWithKeys(fn ($row) => [(string) $row->topic_id => (float) $row->pts / (float) $row->mx])
            ->all();
    }

    /**
     * Sunday report payload for one child and one exam week.
     */
    public function weeklyReport(User $student, CarbonInterface $weekStart): array
    {
        $week = Carbon::parse($weekStart)->startOfWeek(CarbonInterface::MONDAY);
        $subjects = $this->subjectsFor($student);
        $map = collect($this->topicMap($student, $subjects))->keyBy('code');

        $results = StudentWeeklyResult::query()
            ->where('user_id', $student->id)
            ->whereDate('week_start', $week->toDateString())
            ->orderByDesc('score_percent')
            ->get()
            ->unique('subject_id')
            ->keyBy('subject_id');

        $rows = [];
        foreach ($subjects as $subject) {
            $r = $results->get($subject->id);
            $sections = collect($map->get($subject->code)['sections'] ?? [])
                ->map(fn (array $s) => ['code' => $s['code'], 'name' => $s['name'], 'score' => $s['score']])
                ->values()->all();
            $rows[] = [
                'code' => $subject->code,
                'name' => $subject->name_uz,
                'participated' => (bool) $r,
                'score' => $r ? round((float) $r->score, 2) : null,
                'max_score' => $r ? round((float) $r->max_score, 2) : null,
                'percent' => $r ? round((float) $r->score_percent, 1) : null,
                'delta' => $r && $r->delta_prev !== null ? round((float) $r->delta_prev, 1) : null,
                'rank' => $r?->rank,
                'participants' => $r?->participants_count,
                'topics' => $sections,
            ];
        }

        return [
            'week_start' => $week->toDateString(),
            'week_label' => $week->format('d.m.Y').' – '.$week->copy()->addDays(6)->format('d.m.Y'),
            'student' => ['id' => $student->id, 'first_name' => $student->first_name],
            'participated' => $results->isNotEmpty(),
            'subjects' => $rows,
            'recommendations' => array_map(fn (array $t) => [
                'code' => $t['code'], 'name' => $t['name'], 'subject' => $t['subject'],
                'section' => $t['section'], 'score' => $t['score'], 'reason' => $t['reason'],
            ], $this->recommendations($student)),
        ];
    }
}
