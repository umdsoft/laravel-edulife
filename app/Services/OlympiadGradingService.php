<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAnswer;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadLiveLeaderboard;
use App\Models\QuestionBank;
use App\Models\SectionAttempt;
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
     * Recalculate all scores for an attempt
     */
    public function recalculateAttemptScores(OlympiadAttempt $attempt): void
    {
        // Calculate section scores
        foreach ($attempt->sectionAttempts as $sectionAttempt) {
            $this->recalculateSectionScore($sectionAttempt);
        }

        // Calculate total scores
        $attempt->calculateScores();
        $attempt->save();

        // Update leaderboard
        $attempt->updateLeaderboard();
    }

    /**
     * Recalculate section score
     */
    private function recalculateSectionScore(SectionAttempt $sectionAttempt): void
    {
        $answers = $sectionAttempt->answers()->where('is_graded', true)->get();

        $rawScore = $answers->sum('points_earned');
        $maxScore = $answers->sum('max_points');
        $correctCount = $answers->where('is_correct', true)->count();

        $sectionAttempt->raw_score = $rawScore;
        $sectionAttempt->questions_answered = $answers->count();
        $sectionAttempt->questions_correct = $correctCount;

        // Calculate weighted score based on section weight
        $section = $sectionAttempt->section;
        $weight = $section->weight_percent / 100;
        $sectionAttempt->weighted_score = $rawScore * $weight;
        $sectionAttempt->score_percent = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;

        $sectionAttempt->save();
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
     * Finalize olympiad grading and calculate ranks
     */
    public function finalizeOlympiadGrading(Olympiad $olympiad): void
    {
        // Ensure all grading is complete
        $stats = $this->getGradingStats($olympiad);
        if ($stats['pending_manual'] > 0) {
            throw new \Exception('Manual grading not complete');
        }

        DB::transaction(function () use ($olympiad) {
            // Calculate final ranks
            OlympiadLiveLeaderboard::recalculateRanks($olympiad->id);

            // Update all attempts with final rank
            $leaderboard = OlympiadLiveLeaderboard::where('olympiad_id', $olympiad->id)
                ->get();

            foreach ($leaderboard as $entry) {
                $attempt = $entry->attempt;
                $attempt->rank = $entry->rank;
                $attempt->percentile = $entry->rank > 0 
                    ? round((1 - ($entry->rank / $leaderboard->count())) * 100)
                    : null;
                $attempt->save();
            }

            // Update olympiad status
            $olympiad->status = Olympiad::STATUS_COMPLETED;
            $olympiad->save();
        });
    }
}
