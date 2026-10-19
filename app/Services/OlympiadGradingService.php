<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAnswer;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadLiveLeaderboard;
use App\Models\QuestionBank;
use App\Models\SectionAttempt;
use App\Models\UserOlympiadHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OlympiadGradingService
{
    /**
     * Auto-grade all answers for an attempt
     */
    public function autoGradeAttempt(OlympiadAttempt $attempt): void
    {
        $answers = $attempt->answers()
            ->where('requires_manual_grading', false)
            ->where('is_graded', false)
            ->with('question')
            ->get();

        foreach ($answers as $answer) {
            $this->autoGradeAnswer($answer);
        }

        // Recalculate scores
        $this->recalculateAttemptScores($attempt);
    }

    /**
     * Auto-grade a single answer
     */
    public function autoGradeAnswer(OlympiadAnswer $answer): void
    {
        $question = $answer->question;

        if ($question->requires_manual_grading) {
            return;
        }

        $isCorrect = $question->checkAnswer($answer->user_answer);
        $points = $question->calculatePoints($answer->user_answer);

        $answer->is_correct = $isCorrect;
        $answer->points_earned = $points;
        $answer->is_graded = true;
        $answer->save();

        // Update question statistics
        $question->recordUsage($isCorrect);
    }

    /**
     * Get manual grading queue for an olympiad
     */
    public function getManualGradingQueue(string $olympiadId): Collection
    {
        return OlympiadAnswer::whereHas('attempt', fn($q) => $q->where('olympiad_id', $olympiadId))
            ->where('requires_manual_grading', true)
            ->where('is_graded', false)
            ->with([
                'attempt.user',
                'question',
                'sectionAttempt.section',
            ])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Grade an answer manually
     */
    public function gradeManually(
        OlympiadAnswer $answer,
        float $points,
        string $graderId,
        ?array $gradingDetails = null,
        ?string $feedback = null
    ): void {
        $answer->manualGrade($points, $gradingDetails ?? [], $feedback);

        // Update grading details with grader info
        $details = $answer->grading_details ?? [];
        $details['grader_id'] = $graderId;
        $details['graded_at'] = now()->toIso8601String();
        $answer->grading_details = $details;
        $answer->save();

        // Check if all manual grading is complete
        $this->checkManualGradingComplete($answer->attempt);
    }

    /**
     * Check if all manual grading is complete for an attempt
     */
    private function checkManualGradingComplete(OlympiadAttempt $attempt): void
    {
        $pendingCount = $attempt->answers()
            ->where('requires_manual_grading', true)
            ->where('is_graded', false)
            ->count();

        if ($pendingCount === 0) {
            $attempt->manual_grading_complete = true;
            $attempt->graded_at = now();
            $attempt->status = OlympiadAttempt::STATUS_GRADED;
            $attempt->save();

            // Recalculate final scores
            $this->recalculateAttemptScores($attempt);
        }
    }

    /**
     * Recalculate all scores for an attempt. Uses the same formula as submitting
     * (SectionAttempt::calculateScore + OlympiadAttempt::calculateScores).
     */
    public function recalculateAttemptScores(OlympiadAttempt $attempt): void
    {
        $attempt->load(['sectionAttempts.section', 'sectionAttempts.answers']);
        $attempt->calculateScores();
        $attempt->save();

        $attempt->updateLeaderboard();
    }

    /**
     * Get grading statistics for olympiad
     */
    public function getGradingStats(Olympiad $olympiad): array
    {
        $totalAttempts = $olympiad->attempts()->completed()->count();
        $fullyGraded = $olympiad->attempts()
            ->where('status', OlympiadAttempt::STATUS_GRADED)
            ->count();

        $pendingManualGrading = OlympiadAnswer::whereHas('attempt', fn($q) => $q->where('olympiad_id', $olympiad->id))
            ->where('requires_manual_grading', true)
            ->where('is_graded', false)
            ->count();

        $gradedManually = OlympiadAnswer::whereHas('attempt', fn($q) => $q->where('olympiad_id', $olympiad->id))
            ->where('requires_manual_grading', true)
            ->where('is_graded', true)
            ->count();

        return [
            'total_attempts' => $totalAttempts,
            'fully_graded' => $fullyGraded,
            'pending_grading' => $totalAttempts - $fullyGraded,
            'pending_manual' => $pendingManualGrading,
            'graded_manually' => $gradedManually,
            'completion_percent' => $totalAttempts > 0 
                ? round(($fullyGraded / $totalAttempts) * 100)
                : 0,
        ];
    }

    /**
     * Get detailed answer analysis for a question
     */
    public function getQuestionAnalysis(string $olympiadId, string $questionId): array
    {
        $answers = OlympiadAnswer::whereHas('attempt', fn($q) => $q->where('olympiad_id', $olympiadId))
            ->where('question_id', $questionId)
            ->where('is_graded', true)
            ->get();

        $question = QuestionBank::find($questionId);

        $distribution = [];
        foreach ($answers as $answer) {
            $key = is_array($answer->user_answer) 
                ? json_encode($answer->user_answer) 
                : (string) $answer->user_answer;
            
            if (!isset($distribution[$key])) {
                $distribution[$key] = 0;
            }
            $distribution[$key]++;
        }

        return [
            'total_responses' => $answers->count(),
            'correct_responses' => $answers->where('is_correct', true)->count(),
            'average_time' => $answers->avg('time_spent_seconds'),
            'average_score' => $answers->avg('points_earned'),
            'answer_distribution' => $distribution,
            'difficulty_rating' => $this->calculateDifficultyRating($answers),
        ];
    }

    /**
     * Calculate difficulty rating based on responses
     */
    private function calculateDifficultyRating(Collection $answers): string
    {
        if ($answers->isEmpty()) {
            return 'unknown';
        }

        $correctRate = $answers->where('is_correct', true)->count() / $answers->count();

        if ($correctRate >= 0.8) {
            return 'easy';
        } elseif ($correctRate >= 0.5) {
            return 'medium';
        } elseif ($correctRate >= 0.2) {
            return 'hard';
        }

        return 'expert';
    }

    /**
     * Bulk grade by rubric for writing/speaking sections
     */
    public function bulkGradeByRubric(
        array $answerIds,
        array $rubricScores,
        string $graderId,
        ?string $feedback = null
    ): int {
        $graded = 0;

        foreach ($answerIds as $answerId) {
            $answer = OlympiadAnswer::find($answerId);
            if (!$answer) continue;

            $question = $answer->question;
            $rubric = $question->grading_rubric ?? [];
            $criteria = $rubric['criteria'] ?? [];

            $totalPoints = 0;
            $maxPoints = 0;
            $gradingDetails = ['criteria' => []];

            foreach ($criteria as $criterion) {
                $criterionName = $criterion['name'];
                $criterionMax = $criterion['max_points'];
                $score = $rubricScores[$answerId][$criterionName] ?? 0;

                $totalPoints += min($score, $criterionMax);
                $maxPoints += $criterionMax;

                $gradingDetails['criteria'][$criterionName] = [
                    'score' => $score,
                    'max' => $criterionMax,
                ];
            }

            // Calculate final points proportionally
            $finalPoints = $answer->max_points * ($totalPoints / max($maxPoints, 1));

            $this->gradeManually($answer, $finalPoints, $graderId, $gradingDetails, $feedback);
            $graded++;
        }

        return $graded;
    }

    /**
     * Finalize the olympiad: final ranks, coin rewards (olympiad reward_config:
     * coins_per_correct, top_10_coins), the participants' olympiad history and
     * the completed status. Safe to run again: a participant who already has a
     * history row for this olympiad is not rewarded twice.
     *
     * @throws \DomainException with an Uzbek message the admin can act on
     */
    public function finalizeOlympiadGrading(Olympiad $olympiad): void
    {
        if (!in_array($olympiad->status, [Olympiad::STATUS_LIVE, Olympiad::STATUS_GRADING, Olympiad::STATUS_RESULTS_READY, Olympiad::STATUS_COMPLETED], true)) {
            throw new \DomainException("Natijalarni faqat jonli yoki baholanayotgan olimpiada uchun yakunlash mumkin.");
        }

        $stats = $this->getGradingStats($olympiad);
        if ($stats['pending_manual'] > 0) {
            throw new \DomainException("Qo'lda baholanmagan javoblar bor ({$stats['pending_manual']} ta). Avval ularni baholang.");
        }

        DB::transaction(function () use ($olympiad) {
            // submitted attempts get their final scores with the current grading
            $attempts = OlympiadAttempt::where('olympiad_id', $olympiad->id)
                ->completed()
                ->with(['sectionAttempts.section', 'sectionAttempts.answers'])
                ->get();
            foreach ($attempts as $attempt) {
                $attempt->calculateScores();
                $attempt->save();
                app(\App\Services\LeaderboardService::class)->updateEntry($attempt);
            }

            OlympiadLiveLeaderboard::recalculateRanks($olympiad->id);

            $leaderboard = OlympiadLiveLeaderboard::with('attempt')
                ->where('olympiad_id', $olympiad->id)
                ->orderBy('rank')
                ->get();
            $participants = $leaderboard->where('is_disqualified', false)->count();

            $config = (array) ($olympiad->reward_config ?? []);
            $perCorrect = max(0, (int) ($config['coins_per_correct'] ?? 0));
            $top10 = max(0, (int) ($config['top_10_coins'] ?? 0));

            foreach ($leaderboard as $entry) {
                $attempt = $entry->attempt;
                if (!$attempt) {
                    continue;
                }

                // a "participated" row is written on submit; rewards come with the final status
                $alreadyFinalized = UserOlympiadHistory::where('user_id', $entry->user_id)
                    ->where('olympiad_id', $olympiad->id)
                    ->where('status', '!=', UserOlympiadHistory::STATUS_PARTICIPATED)
                    ->exists();

                $coins = 0;
                if (!$entry->is_disqualified) {
                    $coins = $perCorrect * (int) $entry->questions_correct
                        + ($entry->rank >= 1 && $entry->rank <= 10 ? $top10 : 0);
                }

                $history = UserOlympiadHistory::firstOrNew([
                    'user_id' => $entry->user_id,
                    'olympiad_id' => $olympiad->id,
                ]);
                $history->fill([
                    'olympiad_type_id' => $olympiad->olympiad_type_id,
                    'stage_id' => $olympiad->stage_id,
                    'series_id' => $olympiad->series_id,
                    'attempt_id' => $attempt->id,
                    'status' => $entry->is_disqualified ? UserOlympiadHistory::STATUS_DISQUALIFIED : UserOlympiadHistory::STATUS_COMPLETED,
                    'rank' => $entry->is_disqualified ? null : $entry->rank,
                    'total_participants' => $participants,
                    'percentile' => $entry->is_disqualified ? null : OlympiadLiveLeaderboard::percentileFor((int) $entry->rank, $participants),
                    'score' => $attempt->total_raw_score,
                    'max_score' => $attempt->total_max_score,
                    'score_percent' => $attempt->score_percent,
                    'section_scores' => $attempt->sections_results,
                ]);
                if (!$alreadyFinalized) {
                    $history->coins_earned = $coins;
                }
                $history->save();

                if (!$alreadyFinalized && $coins > 0) {
                    app(\App\Services\CoinService::class)->addCoins(
                        $attempt->user()->first(),
                        $coins,
                        'olympiad_reward',
                        "Olimpiada natijasi: {$olympiad->title}",
                        $olympiad
                    );
                }
            }

            $olympiad->status = Olympiad::STATUS_COMPLETED;
            $olympiad->save();
        });
    }
}
