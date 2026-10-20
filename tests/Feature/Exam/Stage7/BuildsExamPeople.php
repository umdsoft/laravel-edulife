<?php

namespace Tests\Feature\Exam\Stage7;

use App\Models\Exam\ExamSession;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Fixtures for stage 7 tests: stage 5 (engine) output is built with
 * factories, never through the engine itself.
 */
trait BuildsExamPeople
{
    /** @var array<string, ExamSession> */
    private array $sessions = [];

    protected function math(): Subject
    {
        return Subject::query()->where('code', Subject::MATH)->first() ?? Subject::factory()->math()->create();
    }

    protected function physics(): Subject
    {
        return Subject::query()->where('code', Subject::PHYSICS)->first() ?? Subject::factory()->physics()->create();
    }

    protected function student(array $profile = [], array $user = []): User
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active'] + $user);
        if ($profile !== []) {
            StudentExamProfile::factory()->create(['user_id' => $student->id] + $profile);
        }

        return $student;
    }

    protected function guardianUser(array $attrs = []): User
    {
        return User::factory()->create(['role' => 'guardian', 'status' => 'active'] + $attrs);
    }

    protected function link(User $guardian, User $student, bool $verified = true, array $attrs = []): GuardianStudent
    {
        return GuardianStudent::factory()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'verified_at' => $verified ? now() : null,
        ] + $attrs);
    }

    protected function weeklySession(Subject $subject, string $weekStart): ExamSession
    {
        $key = $subject->id.'|'.$weekStart;

        return $this->sessions[$key] ??= ExamSession::factory()->create([
            'subject_id' => $subject->id,
            'type' => 'weekly',
            'status' => 'graded',
            'starts_at' => Carbon::parse($weekStart)->addDays(5)->setTime(10, 0),
            'ends_at' => Carbon::parse($weekStart)->addDays(5)->setTime(13, 0),
            'registration_closes_at' => null,
        ]);
    }

    protected function weeklyResult(User $student, Subject $subject, string $weekStart, float $percent, ?float $delta = null, array $attrs = []): StudentWeeklyResult
    {
        return StudentWeeklyResult::factory()->create([
            'user_id' => $student->id,
            'session_id' => $this->weeklySession($subject, $weekStart)->id,
            'subject_id' => $subject->id,
            'week_start' => $weekStart,
            'score' => round($percent * 0.45, 2),
            'max_score' => 45,
            'score_percent' => $percent,
            'delta_prev' => $delta,
        ] + $attrs);
    }

    /** section -> topic pair in $subject */
    protected function topicPair(Subject $subject, string $section, string $topic, int $sort = 0): array
    {
        $s = Topic::factory()->create(['subject_id' => $subject->id, 'name_uz' => $section, 'sort_order' => $sort]);
        $t = Topic::factory()->childOf($s)->create(['name_uz' => $topic, 'sort_order' => $sort]);

        return [$s, $t];
    }

    protected function mastery(User $student, Topic $topic, float $score, int $answered = 5): StudentTopicMastery
    {
        return StudentTopicMastery::factory()->create([
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'subject_id' => $topic->subject_id,
            'score' => $score,
            'answered_count' => $answered,
        ]);
    }

    protected function lastMonday(): string
    {
        return now('Asia/Tashkent')->startOfWeek()->toDateString();
    }
}
