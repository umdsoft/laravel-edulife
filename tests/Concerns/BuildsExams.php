<?php

namespace Tests\Concerns;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Support\Collection;

/**
 * Fixtures for the stage 5 exam engine tests: a subject with a small topic
 * tree, approved questions, blueprints, sessions and onboarded students.
 */
trait BuildsExams
{
    protected function examSubject(string $code = Subject::MATH): Subject
    {
        return Subject::query()->where('code', $code)->first()
            ?? ($code === Subject::PHYSICS ? Subject::factory()->physics()->create() : Subject::factory()->math()->create());
    }

    /** @return array{0: Topic, 1: Topic, 2: Topic} section, leaf A, leaf B */
    protected function examTopics(Subject $subject): array
    {
        $section = Topic::factory()->create(['subject_id' => $subject->id]);

        return [$section, Topic::factory()->childOf($section)->create(), Topic::factory()->childOf($section)->create()];
    }

    /** @return Collection<int, QuestionBank> */
    protected function approvedQuestions(Topic $topic, int $count, int $difficulty = 2, string $type = 'single_choice'): Collection
    {
        $factory = ExamQuestionFactory::new()->forTopic($topic);
        $factory = match ($type) {
            'numeric' => $factory->numeric(),
            'numeric_with_unit' => $factory->numericWithUnit(),
            default => $factory->state(['question_type' => $type]),
        };

        return $factory->approved()->count($count)->create(['difficulty_level' => $difficulty]);
    }

    /**
     * @param  list<array{0: Topic, 1: ?int, 2: string, 3: int, 4?: float}>  $rules  topic, difficulty, part, count, points
     */
    protected function examBlueprint(Subject $subject, array $rules, array $attributes = []): ExamBlueprint
    {
        $blueprint = ExamBlueprint::factory()->create($attributes + [
            'subject_id' => $subject->id,
            'total_questions' => array_sum(array_column($rules, 3)),
        ]);

        foreach ($rules as $i => $rule) {
            $blueprint->rules()->create([
                'topic_id' => $rule[0]->id,
                'difficulty_level' => $rule[1],
                'part' => $rule[2],
                'count' => $rule[3],
                'points_per_question' => $rule[4] ?? 1,
                'sort_order' => $i + 1,
            ]);
        }

        return $blueprint->fresh('rules');
    }

    /** Student with a complete exam profile (subjects + parent consent). */
    protected function examStudent(Subject|array|null $subjects = null): User
    {
        $codes = $subjects instanceof Subject ? [$subjects->code] : ($subjects ?? [Subject::MATH]);
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        StudentExamProfile::factory()->create(['user_id' => $user->id, 'subjects' => $codes, 'parent_consent_at' => now()]);

        return $user;
    }

    protected function examTeacher(): User
    {
        return User::factory()->create(['role' => 'teacher', 'status' => 'active']);
    }

    /** Weekly session open right now (started 10 minutes ago, 3 hours window). */
    protected function openWeeklySession(ExamBlueprint $blueprint, array $attributes = []): ExamSession
    {
        return ExamSession::factory()->create($attributes + [
            'subject_id' => $blueprint->subject_id,
            'blueprint_id' => $blueprint->id,
            'type' => 'weekly',
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addHours(3),
            'registration_closes_at' => null,
            'status' => 'open',
        ]);
    }
}
