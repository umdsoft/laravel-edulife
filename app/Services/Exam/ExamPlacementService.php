<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Exceptions\Exam\ExamUnavailable;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\Subject;
use App\Models\User;

/**
 * Placement (qabul) test: free, once per subject.
 *
 * Every subject has one always-open public placement session built from its
 * active placement blueprint (created on first use). A student may take a
 * public placement session of a subject only while they have no finished
 * placement attempt in that subject. A retake needs the teacher: allowRetake()
 * creates a private session restricted to that student.
 */
class ExamPlacementService
{
    /** The public, always open placement session of a subject (NULL without an active blueprint). */
    public function publicSession(Subject $subject): ?ExamSession
    {
        $blueprint = ExamBlueprint::query()
            ->where('subject_id', $subject->id)
            ->purpose(ExamSessionType::Placement)
            ->active()
            ->latest('created_at')
            ->first();
        if (! $blueprint) {
            return null;
        }

        $session = ExamSession::query()
            ->where('blueprint_id', $blueprint->id)
            ->ofType(ExamSessionType::Placement)
            ->public()
            ->where('status', ExamSessionStatus::Open->value)
            ->orderBy('created_at')
            ->first();

        return $session ?? ExamSession::create([
            'subject_id' => $subject->id,
            'blueprint_id' => $blueprint->id,
            'type' => ExamSessionType::Placement,
            'title' => "{$subject->name_uz}: qabul testi",
            'status' => ExamSessionStatus::Open,
        ]);
    }

    /** Placement session the student should use now (retake first), NULL when none is available. */
    public function sessionFor(User $user, Subject $subject): ?ExamSession
    {
        $retake = $this->pendingRetake($user, $subject);
        if ($retake) {
            return $retake;
        }

        $inProgress = $this->attempts($user, $subject)->where('status', AttemptStatus::InProgress->value)->with('session')->first();
        if ($inProgress) {
            return $inProgress->session;
        }

        return $this->hasFinished($user, $subject) ? null : $this->publicSession($subject);
    }

    /** Finished (graded / submitted) placement attempt exists for the subject. */
    public function hasFinished(User $user, Subject|string $subject): bool
    {
        return $this->attempts($user, $subject)
            ->whereNotIn('status', [AttemptStatus::InProgress->value, AttemptStatus::Invalidated->value])
            ->exists();
    }

    public function latestAttempt(User $user, Subject|string $subject): ?ExamAttempt
    {
        return $this->attempts($user, $subject)
            ->whereNotIn('status', [AttemptStatus::Invalidated->value])
            ->latest('started_at')
            ->first();
    }

    public function assertCanStart(ExamSession $session, User $user): void
    {
        if ($session->restricted_to_user_id !== null) {
            if ($session->restricted_to_user_id !== $user->id) {
                throw ExamUnavailable::notAllowed();
            }

            return; // retake allowed by the teacher
        }

        if ($this->hasFinished($user, $session->subject_id)) {
            throw ExamUnavailable::placementDone();
        }
    }

    /** Teacher allows one more placement attempt: a private open session for the student. */
    public function allowRetake(User $student, Subject $subject, User $by): ExamSession
    {
        $pending = $this->pendingRetake($student, $subject);
        if ($pending) {
            return $pending;
        }

        $public = $this->publicSession($subject);
        if (! $public) {
            throw new \DomainException('No active placement blueprint for this subject.');
        }

        return ExamSession::create([
            'subject_id' => $subject->id,
            'blueprint_id' => $public->blueprint_id,
            'restricted_to_user_id' => $student->id,
            'type' => ExamSessionType::Placement,
            'title' => "{$subject->name_uz}: qabul testi (qayta topshirish)",
            'status' => ExamSessionStatus::Open,
            'created_by' => $by->id,
        ]);
    }

    /** A private placement session of the student that has no finished attempt yet. */
    public function pendingRetake(User $user, Subject $subject): ?ExamSession
    {
        return ExamSession::query()
            ->where('subject_id', $subject->id)
            ->ofType(ExamSessionType::Placement)
            ->where('restricted_to_user_id', $user->id)
            ->where('status', ExamSessionStatus::Open->value)
            ->whereDoesntHave('attempts', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', AttemptStatus::InProgress->value))
            ->latest('created_at')
            ->first();
    }

    private function attempts(User $user, Subject|string $subject)
    {
        $subjectId = $subject instanceof Subject ? $subject->id : $subject;

        return ExamAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('session_id', ExamSession::withTrashed()
                ->where('subject_id', $subjectId)
                ->where('type', ExamSessionType::Placement->value)
                ->select('id'));
    }
}
