<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAnswer;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadQuestion;
use App\Models\OlympiadRegistration;
use App\Models\OlympiadSection;
use App\Models\SectionAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OlympiadExamService
{
    protected AntiCheatService $antiCheatService;
    protected OlympiadGradingService $gradingService;

    public function __construct(AntiCheatService $antiCheatService, OlympiadGradingService $gradingService)
    {
        $this->antiCheatService = $antiCheatService;
        $this->gradingService = $gradingService;
    }

    /**
     * Check if user can start exam
     */
    public function canStartExam(OlympiadRegistration $registration): array
    {
        $olympiad = $registration->olympiad;

        // Check registration status
        if (!$registration->is_confirmed) {
            return ['can_start' => false, 'reason' => 'registration_not_confirmed'];
        }

        // Check if already started
        $existingAttempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', $registration->user_id)
            ->first();

        if ($existingAttempt && $existingAttempt->is_completed) {
            return ['can_start' => false, 'reason' => 'already_completed'];
        }

        if ($existingAttempt && $existingAttempt->is_disqualified) {
            return ['can_start' => false, 'reason' => 'disqualified'];
        }

        if ($existingAttempt && $existingAttempt->is_in_progress) {
            return ['can_start' => true, 'can_continue' => true, 'attempt' => $existingAttempt];
        }

        // Check olympiad timing
        if ($olympiad->status !== Olympiad::STATUS_LIVE) {
            return ['can_start' => false, 'reason' => 'olympiad_not_live'];
        }

        if ($olympiad->olympiad_start_at && now()->lt($olympiad->olympiad_start_at)) {
            return ['can_start' => false, 'reason' => 'olympiad_not_started'];
        }

        if ($olympiad->olympiad_end_at && now()->gt($olympiad->olympiad_end_at)) {
            return ['can_start' => false, 'reason' => 'olympiad_ended'];
        }

        return ['can_start' => true];
    }

    /**
     * Start exam attempt
     */
    public function startExam(OlympiadRegistration $registration, string $deviceFingerprint): OlympiadAttempt
    {
        $checkResult = $this->canStartExam($registration);
        
        if (!$checkResult['can_start']) {
            throw new \Exception("Cannot start exam: {$checkResult['reason']}");
        }

        // If continuing existing attempt
        if (!empty($checkResult['can_continue'])) {
            return $checkResult['attempt'];
        }

        return DB::transaction(function () use ($registration, $deviceFingerprint) {
            $olympiad = $registration->olympiad;

            // Verify and lock device
            $deviceLock = $this->antiCheatService->lockDevice(
                $olympiad->id,
                $registration->user_id,
                $deviceFingerprint
            );

            // Create attempt
            $attempt = OlympiadAttempt::create([
                'olympiad_id' => $olympiad->id,
                'user_id' => $registration->user_id,
                'registration_id' => $registration->id,
                'session_token' => Str::random(64),
                'device_lock_id' => $deviceLock->id,
                'total_max_score' => $olympiad->sections->sum('max_points'),
                'status' => OlympiadAttempt::STATUS_NOT_STARTED,
            ]);

            // Create section attempts
            foreach ($olympiad->sections()->ordered()->get() as $section) {
                SectionAttempt::create([
                    'attempt_id' => $attempt->id,
                    'section_id' => $section->id,
                    'max_score' => $section->max_points,
                    'requires_manual_grading' => $section->requires_manual_grading,
                    'status' => SectionAttempt::STATUS_NOT_STARTED,
                ]);
            }

            $attempt->start();

            return $attempt->load(['sectionAttempts.section', 'olympiad']);
        });
    }

    /**
     * Get current exam state
     */
    public function getExamState(OlympiadAttempt $attempt): array
    {
        $sectionAttempts = $attempt->sectionAttempts()
            ->with(['section', 'answers'])
            ->get();

        $currentSectionAttempt = $sectionAttempts
            ->where('section_id', $attempt->current_section_id)
            ->first();

        return [
            'attempt' => $attempt,
            'olympiad' => $attempt->olympiad,
            'sections' => $sectionAttempts,
            'current_section' => $currentSectionAttempt,
            'remaining_time' => $attempt->remaining_seconds,
            'total_progress' => $this->calculateProgress($attempt),
        ];
    }

    /**
     * Get questions for a section
     */
    public function getSectionQuestions(OlympiadAttempt $attempt, string $sectionId): array
    {
        $sectionAttempt = $attempt->sectionAttempts()
            ->where('section_id', $sectionId)
            ->first();

        if (!$sectionAttempt) {
            throw new \Exception('Section not found');
        }

        // Get questions with user answers if any
        $questions = OlympiadQuestion::where('section_id', $sectionId)
            ->with(['question'])
            ->ordered()
            ->get();

        $answers = OlympiadAnswer::where('section_attempt_id', $sectionAttempt->id)
            ->pluck('user_answer', 'olympiad_question_id');

        $flagged = OlympiadAnswer::where('section_attempt_id', $sectionAttempt->id)
            ->where('is_flagged', true)
            ->pluck('olympiad_question_id')
            ->toArray();

        return [
            'section_attempt' => $sectionAttempt,
            'questions' => $questions->map(function ($q) use ($answers, $flagged) {
                return [
                    'id' => $q->id,
                    'question_id' => $q->question_id,
                    'order_number' => $q->order_number,
                    'points' => $q->points,
                    'question_type' => $q->question->question_type,
                    'question_text' => $q->question->question_text,
                    'question_html' => $q->question->question_html,
                    'question_media' => $q->question->question_media,
                    'options' => $q->question->options,
                    'user_answer' => $answers[$q->id] ?? null,
                    'is_flagged' => in_array($q->id, $flagged),
                ];
            }),
            'remaining_time' => $sectionAttempt->time_remaining,
        ];
    }

    /**
     * Submit answer for a question
     */
    public function submitAnswer(
        OlympiadAttempt $attempt,
        string $sectionId,
        string $questionId,
        $answer,
        int $timeSpent = 0
    ): OlympiadAnswer {
        if (!$attempt->can_continue) {
            throw new \Exception('Cannot submit answer: exam not active');
        }

        $sectionAttempt = $attempt->sectionAttempts()
            ->where('section_id', $sectionId)
            ->first();

        if (!$sectionAttempt || !$sectionAttempt->is_in_progress) {
            throw new \Exception('Section not active');
        }

        $olympiadQuestion = OlympiadQuestion::with('question')
            ->where('id', $questionId)
            ->first();

        if (!$olympiadQuestion) {
            throw new \Exception('Question not found');
        }

        $existingAnswer = OlympiadAnswer::where('attempt_id', $attempt->id)
            ->where('olympiad_question_id', $questionId)
            ->first();

        if ($existingAnswer) {
            $existingAnswer->user_answer = $answer;
            $existingAnswer->time_spent_seconds += $timeSpent;
            $existingAnswer->save();
            
            // Re-grade if auto-gradable
            if (!$existingAnswer->requires_manual_grading) {
                $existingAnswer->autoGrade();
            }

            return $existingAnswer;
        }

        $answerRecord = OlympiadAnswer::create([
            'attempt_id' => $attempt->id,
            'section_attempt_id' => $sectionAttempt->id,
            'olympiad_question_id' => $questionId,
            'question_id' => $olympiadQuestion->question_id,
            'user_answer' => $answer,
            'max_points' => $olympiadQuestion->points,
            'time_spent_seconds' => $timeSpent,
            'requires_manual_grading' => $olympiadQuestion->question->requires_manual_grading,
        ]);

        // Auto-grade if possible
        if (!$answerRecord->requires_manual_grading) {
            $answerRecord->autoGrade();
        }

        return $answerRecord;
    }

    /**
     * Start a section
     */
    public function startSection(OlympiadAttempt $attempt, string $sectionId): SectionAttempt
    {
        $sectionAttempt = $attempt->sectionAttempts()
            ->where('section_id', $sectionId)
            ->first();

        if (!$sectionAttempt) {
            throw new \Exception('Section not found');
        }

        // Check section order
        $section = $sectionAttempt->section;
        $previousSections = $attempt->sectionAttempts()
            ->whereHas('section', fn($q) => $q->where('order_number', '<', $section->order_number))
            ->where('status', '!=', SectionAttempt::STATUS_COMPLETED)
            ->exists();

        if ($previousSections && !$attempt->olympiad->allow_section_skip) {
            throw new \Exception('Must complete previous sections first');
        }

        if ($sectionAttempt->status === SectionAttempt::STATUS_NOT_STARTED) {
            $sectionAttempt->start();
        }

        $attempt->current_section_id = $sectionId;
        $attempt->save();

        return $sectionAttempt;
    }

    /**
     * Complete a section
     */
    public function completeSection(OlympiadAttempt $attempt, string $sectionId): SectionAttempt
    {
        $sectionAttempt = $attempt->sectionAttempts()
            ->where('section_id', $sectionId)
            ->first();

        if (!$sectionAttempt) {
            throw new \Exception('Section not found');
        }

        $sectionAttempt->complete();

        // Move to next section
        $nextSection = $attempt->olympiad->sections()
            ->where('order_number', '>', $sectionAttempt->section->order_number)
            ->ordered()
            ->first();

        if ($nextSection) {
            $attempt->current_section_id = $nextSection->id;
            $attempt->save();
        }

        return $sectionAttempt;
    }

    /**
     * Submit entire exam
     */
    public function submitExam(OlympiadAttempt $attempt): OlympiadAttempt
    {
        if ($attempt->is_completed) {
            throw new \Exception('Exam already submitted');
        }

        return DB::transaction(function () use ($attempt) {
            // Complete any in-progress sections
            foreach ($attempt->sectionAttempts as $sectionAttempt) {
                if ($sectionAttempt->status === SectionAttempt::STATUS_IN_PROGRESS) {
                    $sectionAttempt->complete();
                }
            }

            // Submit the attempt
            $attempt->submit();

            // Release device lock
            if ($attempt->deviceLock) {
                $attempt->deviceLock->release();
            }

            // Update leaderboard
            $attempt->updateLeaderboard();

            return $attempt->fresh(['sectionAttempts', 'olympiad']);
        });
    }

    /**
     * Flag/unflag a question
     */
    public function toggleFlag(OlympiadAttempt $attempt, string $questionId): bool
    {
        $answer = OlympiadAnswer::where('attempt_id', $attempt->id)
            ->where('olympiad_question_id', $questionId)
            ->first();

        if (!$answer) {
            // Create empty answer with flag
            $olympiadQuestion = OlympiadQuestion::find($questionId);
            $sectionAttempt = $attempt->sectionAttempts()
                ->where('section_id', $olympiadQuestion->section_id)
                ->first();

            $answer = OlympiadAnswer::create([
                'attempt_id' => $attempt->id,
                'section_attempt_id' => $sectionAttempt->id,
                'olympiad_question_id' => $questionId,
                'question_id' => $olympiadQuestion->question_id,
                'max_points' => $olympiadQuestion->points,
                'is_flagged' => true,
            ]);

            return true;
        }

        $answer->toggleFlag();
        return $answer->is_flagged;
    }

    /**
     * Calculate exam progress
     */
    private function calculateProgress(OlympiadAttempt $attempt): array
    {
        $totalQuestions = 0;
        $answeredQuestions = 0;
        $sectionProgress = [];

        foreach ($attempt->sectionAttempts as $sectionAttempt) {
            $sectionQuestions = OlympiadQuestion::where('section_id', $sectionAttempt->section_id)->count();
            $sectionAnswered = $sectionAttempt->answers()->whereNotNull('user_answer')->count();

            $totalQuestions += $sectionQuestions;
            $answeredQuestions += $sectionAnswered;

            $sectionProgress[$sectionAttempt->section_id] = [
                'total' => $sectionQuestions,
                'answered' => $sectionAnswered,
                'percent' => $sectionQuestions > 0 ? round(($sectionAnswered / $sectionQuestions) * 100) : 0,
                'status' => $sectionAttempt->status,
            ];
        }

        return [
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'overall_percent' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100) : 0,
            'sections' => $sectionProgress,
        ];
    }

    /**
     * Validate session token
     */
    public function validateSession(string $sessionToken): ?OlympiadAttempt
    {
        return OlympiadAttempt::where('session_token', $sessionToken)
            ->where('status', OlympiadAttempt::STATUS_IN_PROGRESS)
            ->first();
    }
}
