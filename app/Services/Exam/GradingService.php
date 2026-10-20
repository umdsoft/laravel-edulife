<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionType;
use App\Enums\Exam\ReviewStatus;
use App\Events\Exam\ExamAttemptGraded;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentExamProfile;
use App\Models\QuestionBank;
use App\Services\Exam\Grading\AnswerChecker;
use Illuminate\Support\Facades\DB;

/**
 * Automatic grading of exam attempts.
 *
 * Writes exam_answers (is_correct, unit_correct, points) and the attempt
 * totals, then the derived data: topic mastery (MasteryService), the weekly
 * result row (ExamResultService) and fires ExamAttemptGraded.
 *
 * A RETIRED question is voided: 0 points out of 0 for everybody, not counted
 * as correct, wrong or skipped. A fixed question (new answer key) is simply
 * graded again with its current key by regradeQuestion().
 */
class GradingService
{
    public function __construct(
        private AnswerChecker $checker,
        private MasteryService $mastery,
        private ExamResultService $results,
    ) {}

    /** Grade a submitted attempt (idempotent; re-grading recomputes everything). */
    public function grade(ExamAttempt $attempt, bool $regrade = false): ExamAttempt
    {
        $wasGraded = $attempt->status === AttemptStatus::Graded;

        DB::transaction(function () use ($attempt, $regrade, $wasGraded) {
            $this->scoreAttempt($attempt, $regrade && $wasGraded);
            if ($regrade && $wasGraded) {
                $this->mastery->rebuild($attempt->user_id, $attempt->session->subject_id);
            } else {
                $this->mastery->applyAttempt($attempt);
            }
            $this->results->record($attempt);
            $this->markPlacementDone($attempt);
        });

        ExamAttemptGraded::dispatch($attempt->fresh(), $regrade && $wasGraded);

        return $attempt;
    }

    /**
     * Re-grade every finished attempt that contains $question (after the
     * teacher fixed or retired it) and refresh mastery and weekly results.
     *
     * @return array{attempts: int, changed: int}
     */
    public function regradeQuestion(QuestionBank $question): array
    {
        $attemptIds = ExamAnswer::query()->where('question_id', $question->id)->distinct()->pluck('attempt_id');
        $attempts = ExamAttempt::query()
            ->whereIn('id', $attemptIds)
            ->whereIn('status', [AttemptStatus::Graded->value, AttemptStatus::Submitted->value, AttemptStatus::AutoSubmitted->value])
            ->orderBy('submitted_at')
            ->get();

        $changed = 0;
        $pairs = [];
        $sessions = [];
        $regraded = [];

        DB::transaction(function () use ($attempts, &$changed, &$pairs, &$sessions, &$regraded) {
            foreach ($attempts as $attempt) {
                $before = [(string) $attempt->score, (string) $attempt->max_score];
                $wasGraded = $attempt->status === AttemptStatus::Graded;
                $this->scoreAttempt($attempt, $wasGraded);
                if ($before !== [(string) $attempt->score, (string) $attempt->max_score]) {
                    $changed++;
                }
                $pairs[$attempt->user_id.'|'.$attempt->session->subject_id] = [$attempt->user_id, $attempt->session->subject_id];
                $sessions[$attempt->session_id] = $attempt->session;
                $regraded[] = [$attempt, $wasGraded];
            }

            foreach ($pairs as [$userId, $subjectId]) {
                $this->mastery->rebuild($userId, $subjectId);
            }
            foreach ($regraded as [$attempt]) {
                $this->results->record($attempt);
                $this->markPlacementDone($attempt);
            }
        });

        foreach ($regraded as [$attempt, $wasGraded]) {
            ExamAttemptGraded::dispatch($attempt->fresh(), $wasGraded);
        }

        return ['attempts' => $attempts->count(), 'changed' => $changed];
    }

    /** Grade the answers and store the attempt totals (no derived data). */
    private function scoreAttempt(ExamAttempt $attempt, bool $isRegrade): void
    {
        $attempt->load([
            'session' => fn ($q) => $q->withTrashed(),
            'answers.question' => fn ($q) => $q->withTrashed(),
            'answers.question.answerOptions',
            'answers.question.numericAnswers',
        ]);
        $blueprint = ExamBlueprint::withTrashed()->find($attempt->session?->blueprint_id);
        $ratio = (float) ($blueprint?->wrong_unit_ratio ?? 0);

        $score = 0.0;
        $max = 0.0;
        $correct = 0;
        $wrong = 0;
        $skipped = 0;

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            if (! $question || $question->review_status === ReviewStatus::Retired) {
                $answer->fill(['is_correct' => null, 'unit_correct' => null, 'points' => 0, 'max_points' => 0]);
            } else {
                $result = $this->checker->check($question, $answer, (float) $answer->max_points, $ratio);
                $answer->fill($result);
            }
            if ($answer->isDirty()) {
                $answer->save();
            }

            $answerMax = (float) $answer->max_points;
            if ($answerMax <= 0) {
                continue;
            }
            $max += $answerMax;
            $score += (float) $answer->points;
            match ($answer->is_correct) {
                true => $correct++,
                false => $wrong++,
                null => $skipped++,
            };
        }

        $attempt->forceFill([
            'status' => AttemptStatus::Graded,
            'score' => round($score, 2),
            'max_score' => round($max, 2),
            'score_percent' => $max > 0 ? round($score / $max * 100, 2) : 0,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'skipped_count' => $skipped,
            'graded_at' => $attempt->graded_at ?? now(),
            'regraded_at' => $isRegrade ? now() : $attempt->regraded_at,
        ])->save();
    }

    private function markPlacementDone(ExamAttempt $attempt): void
    {
        $session = $attempt->session ?? ExamSession::withTrashed()->find($attempt->session_id);
        if ($session?->type !== ExamSessionType::Placement) {
            return;
        }

        StudentExamProfile::query()
            ->where('user_id', $attempt->user_id)
            ->whereNull('placement_completed_at')
            ->update(['placement_completed_at' => $attempt->submitted_at ?? now()]);
    }
}
