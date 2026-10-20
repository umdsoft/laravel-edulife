<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentTopicMastery;

/**
 * Topic mastery ("mavzular xaritasi"), docs/exam/02_DATA_MODEL.md §6:
 *
 *   s   = Σ(wᵢ·pᵢ) / Σ(wᵢ·maxᵢ) · 100     over the attempt's questions of the topic,
 *         wᵢ = difficulty_level (1..5), voided (max = 0) questions skipped,
 *         skipped questions count as 0 points
 *   new = s                                  first observation of the topic
 *   new = (1 − α)·old + α·s                  otherwise; α = 1 placement, 0.3 weekly
 *
 * rebuild() replays every graded attempt of the student in the subject in
 * order, so a regrade gives exactly what incremental updates would have.
 */
class MasteryService
{
    public function alpha(ExamSessionType|string|null $type): float
    {
        $type = $type instanceof ExamSessionType ? $type->value : ($type ?: ExamSessionType::Weekly->value);
        $alphas = (array) config('exam.engine.mastery_alpha', []);

        return (float) ($alphas[$type] ?? ($type === ExamSessionType::Placement->value ? 1.0 : 0.3));
    }

    /**
     * Per topic observation of an attempt.
     *
     * @return array<string, array{score: float, answered: int, correct: int}>
     */
    public function observations(ExamAttempt $attempt): array
    {
        $attempt->loadMissing('answers.question:id,topic_id,difficulty_level');
        $sums = [];

        foreach ($attempt->answers as $answer) {
            $max = (float) $answer->max_points;
            $topicId = $answer->question?->topic_id;
            if ($max <= 0 || $topicId === null) {
                continue;
            }
            $w = max(1, (int) ($answer->question->difficulty_level ?? 1));
            $sums[$topicId] ??= ['num' => 0.0, 'den' => 0.0, 'answered' => 0, 'correct' => 0];
            $sums[$topicId]['num'] += $w * (float) $answer->points;
            $sums[$topicId]['den'] += $w * $max;
            $sums[$topicId]['answered']++;
            if ($answer->is_correct === true) {
                $sums[$topicId]['correct']++;
            }
        }

        $out = [];
        foreach ($sums as $topicId => $s) {
            if ($s['den'] <= 0) {
                continue;
            }
            $out[$topicId] = [
                'score' => max(0.0, min(100.0, $s['num'] / $s['den'] * 100)),
                'answered' => $s['answered'],
                'correct' => $s['correct'],
            ];
        }

        return $out;
    }

    public function applyAttempt(ExamAttempt $attempt): void
    {
        $attempt->loadMissing('session');
        $session = $attempt->session ?? ExamSession::withTrashed()->find($attempt->session_id);
        if (! $session || $attempt->status !== AttemptStatus::Graded) {
            return;
        }

        $alpha = $this->alpha($session->type);
        $at = $attempt->submitted_at ?? $attempt->graded_at ?? now();

        foreach ($this->observations($attempt) as $topicId => $obs) {
            $row = StudentTopicMastery::query()->firstOrNew(['user_id' => $attempt->user_id, 'topic_id' => $topicId]);
            $old = $row->exists ? (float) $row->score : null;
            $new = $old === null ? $obs['score'] : (1 - $alpha) * $old + $alpha * $obs['score'];

            $row->fill([
                'subject_id' => $session->subject_id,
                'score' => round(max(0.0, min(100.0, $new)), 2),
                'attempts_count' => (int) $row->attempts_count + 1,
                'answered_count' => (int) $row->answered_count + $obs['answered'],
                'correct_count' => (int) $row->correct_count + $obs['correct'],
                'source' => $session->type?->value,
                'last_attempt_at' => $at,
            ])->save();
        }
    }

    /** Recompute the student's mastery of a subject from all graded attempts. */
    public function rebuild(string $userId, string $subjectId): void
    {
        StudentTopicMastery::query()->where('user_id', $userId)->where('subject_id', $subjectId)->delete();

        $sessionIds = ExamSession::withTrashed()->where('subject_id', $subjectId)->select('id');

        ExamAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('session_id', $sessionIds)
            ->where('status', AttemptStatus::Graded->value)
            ->with(['session' => fn ($q) => $q->withTrashed(), 'answers.question:id,topic_id,difficulty_level'])
            ->orderBy('submitted_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->each(fn (ExamAttempt $attempt) => $this->applyAttempt($attempt));
    }
}
