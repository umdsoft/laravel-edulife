<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\User;
use App\Services\Exam\ExamSessionLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsExams;
use Tests\TestCase;

class ExamPagesTest extends TestCase
{
    use BuildsExams, RefreshDatabase;

    private function bank(): array
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 4, 2);
        $this->approvedQuestions($a, 2, 3, 'numeric_with_unit');
        $placement = $this->examBlueprint($subject, [[$section, null, 'closed', 2]], ['purpose' => 'placement', 'duration_min' => 20]);
        $weekly = $this->examBlueprint($subject, [[$section, null, 'closed', 2], [$section, null, 'open', 1, 2]], ['duration_min' => 40]);

        return [$subject, $placement, $weekly, $section];
    }

    public function test_student_home_placement_take_autosave_submit_result(): void
    {
        [$subject, , $weekly] = $this->bank();
        $session = ExamSession::factory()->create([
            'subject_id' => $subject->id, 'blueprint_id' => $weekly->id, 'title' => 'Shanba imtihoni test',
            'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(3), 'status' => 'scheduled',
        ]);
        $student = $this->examStudent($subject);

        $this->actingAs($student)->get(route('student.exam.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Exam/Index')
                ->has('placement', 1)
                ->where('placement.0.state', 'available')
                ->where('placement.0.questions', 2)
                ->has('upcoming', 1)
                ->where('upcoming.0.id', $session->id)
                ->where('upcoming.0.attempt', null));

        // placement start -> take page
        $response = $this->actingAs($student)->post(route('student.exam.placement.start', $subject->code));
        $attempt = ExamAttempt::query()->where('user_id', $student->id)->firstOrFail();
        $response->assertRedirect(route('student.exam.take', $attempt));

        $take = $this->actingAs($student)->get(route('student.exam.take', $attempt))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Exam/Take')
                ->has('questions', 2)
                ->has('questions.0.options', 4)
                ->where('attempt.token', $attempt->session_token));
        $this->assertStringNotContainsString('is_correct', json_encode($take->viewData('page')['props']['questions']), 'no answer key while taking');

        // autosave needs the attempt token
        $answers = ExamAnswer::query()->where('attempt_id', $attempt->id)->with('question.answerOptions')->get();
        $items = $answers->map(fn ($a) => ['question_id' => $a->question_id, 'selected_option_id' => $a->question->answerOptions->firstWhere('is_correct', true)->id])->all();
        $this->actingAs($student)->postJson(route('student.exam.answers', $attempt), ['answers' => $items])->assertForbidden();
        $this->actingAs($student)->postJson(route('student.exam.answers', $attempt), ['answers' => $items], ['X-Exam-Token' => $attempt->session_token])
            ->assertOk()->assertJsonPath('saved', 2);
        $this->actingAs($student)->postJson(route('student.exam.events', $attempt), ['type' => 'tab_switch', 'hidden_ms' => 3000], ['X-Exam-Token' => $attempt->session_token])
            ->assertOk()->assertJsonPath('violations_count', 1);

        $this->actingAs($student)->post(route('student.exam.submit', $attempt))->assertRedirect(route('student.exam.result', $attempt));
        $this->assertSame(AttemptStatus::Graded, $attempt->fresh()->status);

        // placement: review right away
        $this->actingAs($student)->get(route('student.exam.result', $attempt))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Exam/Result')
                ->where('attempt.score_percent', 100)
                ->where('reviewAvailable', true)
                ->has('review', 2)
                ->where('review.0.is_correct', true));

        // the finished attempt can not be taken again; answers are refused
        $this->actingAs($student)->get(route('student.exam.take', $attempt))->assertRedirect(route('student.exam.result', $attempt));
        $this->actingAs($student)->postJson(route('student.exam.answers', $attempt), ['answers' => $items], ['X-Exam-Token' => $attempt->session_token])
            ->assertStatus(409)->assertJsonPath('reason', 'attempt_closed');

        // second placement start goes to the result
        $this->actingAs($student)->post(route('student.exam.placement.start', $subject->code))->assertRedirect(route('student.exam.result', $attempt));
        $this->assertSame(1, ExamAttempt::query()->where('user_id', $student->id)->count());
    }

    public function test_weekly_review_only_after_the_session_closes(): void
    {
        [$subject, , $weekly] = $this->bank();
        $session = $this->openWeeklySession($weekly, ['ends_at' => now()->addHour()]);
        $student = $this->examStudent($subject);

        $this->actingAs($student)->post(route('student.exam.sessions.start', $session))->assertRedirect();
        $attempt = ExamAttempt::query()->where('session_id', $session->id)->firstOrFail();
        $this->actingAs($student)->post(route('student.exam.submit', $attempt));

        $this->actingAs($student)->get(route('student.exam.result', $attempt))
            ->assertInertia(fn (Assert $page) => $page->component('Student/Exam/Result')
                ->where('reviewAvailable', false)
                ->where('review', [])
                ->has('weekly'));

        $this->travel(61)->minutes();
        $this->actingAs($student)->get(route('student.exam.result', $attempt))
            ->assertInertia(fn (Assert $page) => $page->where('reviewAvailable', true)->has('review', 3)->where('weekly.rank', 1));
        $this->assertSame(ExamSessionStatus::Graded, $session->fresh()->status, 'lazy close on access');
        if (Route::has('student.exam.card')) {
            $this->actingAs($student)->get(route('student.exam.result', $attempt))
                ->assertInertia(fn (Assert $page) => $page->where('links.card', route('student.exam.card', ['attempt' => $attempt->id, 'download' => 1])));
        }
    }

    public function test_authorization(): void
    {
        [$subject, , $weekly] = $this->bank();
        $session = $this->openWeeklySession($weekly);
        $owner = $this->examStudent($subject);
        $intruder = $this->examStudent($subject);
        $this->actingAs($owner)->post(route('student.exam.sessions.start', $session));
        $attempt = ExamAttempt::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($intruder)->get(route('student.exam.take', $attempt))->assertForbidden();
        $this->actingAs($intruder)->get(route('student.exam.result', $attempt))->assertForbidden();
        $this->actingAs($intruder)->post(route('student.exam.submit', $attempt))->assertForbidden();
        $this->actingAs($intruder)->postJson(route('student.exam.answers', $attempt), ['answers' => [['question_id' => 'x']]], ['X-Exam-Token' => $attempt->session_token])->assertForbidden();
        $this->assertSame(AttemptStatus::InProgress, $attempt->fresh()->status);

        // roles
        $this->actingAs($owner)->get(route('teacher.exam.sessions.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('teacher.exam.blueprints.index'))->assertForbidden();
        $this->actingAs($this->examTeacher())->get(route('student.exam.index'))->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get(route('student.exam.index'))->assertRedirect(route('login'));

        // a student without a complete profile goes to onboarding (stage 7 route)
        if (Route::has('student.exam.onboarding')) {
            $fresh = User::factory()->create(['role' => 'student', 'status' => 'active']);
            $this->actingAs($fresh)->get(route('student.exam.index'))->assertRedirect(route('student.exam.onboarding'));
            $this->actingAs($fresh)->post(route('student.exam.sessions.start', $session))->assertRedirect(route('student.exam.onboarding'));
        }
    }

    public function test_teacher_blueprint_editor_and_live_capacity(): void
    {
        [$subject, , , $section] = $this->bank();
        $teacher = $this->examTeacher();

        $this->actingAs($teacher)->get(route('teacher.exam.blueprints.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Teacher/Exam/Blueprints/Index')->has('blueprints', 2));
        $this->actingAs($teacher)->get(route('teacher.exam.blueprints.create'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Teacher/Exam/Blueprints/Form')->has("topics.{$subject->id}"));

        $this->actingAs($teacher)->postJson(route('teacher.exam.blueprints.capacity'), [
            'subject_id' => $subject->id,
            'allow_adjacent_difficulty' => true,
            'rules' => [
                ['key' => 'a', 'topic_id' => $section->id, 'difficulty_level' => 2, 'part' => 'closed', 'count' => 3],
                ['key' => 'b', 'topic_id' => $section->id, 'difficulty_level' => null, 'part' => 'open', 'count' => 5],
            ],
        ])->assertOk()
            ->assertJsonPath('status', 'short')
            ->assertJsonPath('rules.0.status', 'ok')
            ->assertJsonPath('rules.1.candidates', 2)
            ->assertJsonPath('rules.1.shortfall', 3);

        $payload = [
            'subject_id' => $subject->id, 'name' => 'Yangi reja', 'purpose' => 'weekly', 'format' => 'milliy_sertifikat',
            'duration_min' => 60, 'wrong_unit_ratio' => 0.5, 'avoid_repeat_weeks' => 3,
            'allow_adjacent_difficulty' => true, 'shuffle_questions' => true, 'shuffle_options' => false, 'is_active' => true,
            'rules' => [
                ['topic_id' => $section->id, 'difficulty_level' => 2, 'part' => 'closed', 'count' => 3, 'points_per_question' => 1],
                ['topic_id' => $section->id, 'difficulty_level' => null, 'part' => 'open', 'count' => 1, 'points_per_question' => 2],
            ],
        ];
        $this->actingAs($teacher)->post(route('teacher.exam.blueprints.store'), $payload)->assertRedirect();
        $blueprint = ExamBlueprint::query()->where('name', 'Yangi reja')->firstOrFail();
        $this->assertSame(4, $blueprint->total_questions);
        $this->assertTrue($blueprint->isConsistent());

        // edit keeps rule ids, drops removed rules
        $first = $blueprint->rules()->first();
        $payload['rules'] = [['id' => $first->id, 'topic_id' => $section->id, 'difficulty_level' => 3, 'part' => 'closed', 'count' => 2, 'points_per_question' => 1.5]];
        $this->actingAs($teacher)->put(route('teacher.exam.blueprints.update', $blueprint), $payload)->assertRedirect();
        $this->assertSame([$first->id], $blueprint->rules()->pluck('id')->all());
        $this->assertSame(2, $blueprint->fresh()->total_questions);
        $this->actingAs($teacher)->get(route('teacher.exam.blueprints.edit', $blueprint))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('blueprint.rules.0.difficulty_level', 3)->has('readiness.rules', 1));

        // topic of another subject is refused
        $other = $this->examSubject('physics');
        [, $foreign] = $this->examTopics($other);
        $payload['rules'][0]['topic_id'] = $foreign->id;
        $this->actingAs($teacher)->put(route('teacher.exam.blueprints.update', $blueprint), $payload)->assertSessionHasErrors('rules.0.topic_id');
    }

    public function test_teacher_schedules_a_session_generates_variants_and_exports_results(): void
    {
        [$subject, , $weekly] = $this->bank();
        $teacher = $this->examTeacher();
        $students = [$this->examStudent($subject), $this->examStudent($subject)];
        $saturday = now()->next('Saturday');

        $this->actingAs($teacher)->get(route('teacher.exam.sessions.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Teacher/Exam/Sessions/Form')->has('blueprints', 1));

        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), [
            'subject_id' => $subject->id, 'blueprint_id' => $weekly->id, 'type' => 'weekly', 'title' => 'Shanba',
            'date' => $saturday->toDateString(), 'start_time' => '10:00', 'end_time' => '10:30', 'duration_min' => 40,
        ])->assertSessionHasErrors('duration_min');

        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), [
            'subject_id' => $subject->id, 'blueprint_id' => $weekly->id, 'type' => 'weekly', 'title' => 'Shanba',
            'date' => $saturday->toDateString(), 'start_time' => '10:00', 'end_time' => '13:00',
        ])->assertRedirect();
        $session = ExamSession::query()->where('title', 'Shanba')->firstOrFail();
        $this->assertSame(ExamSessionStatus::Draft, $session->status);
        $this->assertSame('10:00', $session->starts_at->format('H:i'));

        $this->actingAs($teacher)->get(route('teacher.exam.sessions.show', $session))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Teacher/Exam/Sessions/Show')
                ->where('readiness.bank.status', 'ok')
                ->where('stats.audience', 2)
                ->where('stats.variants', 0));

        $this->actingAs($teacher)->post(route('teacher.exam.sessions.schedule', $session))->assertRedirect();
        $this->assertSame(ExamSessionStatus::Scheduled, $session->fresh()->status);

        $this->actingAs($teacher)->post(route('teacher.exam.sessions.variants', $session))->assertRedirect();
        $this->assertSame(2, ExamVariant::query()->where('session_id', $session->id)->count());

        // Saturday: both students sit the exam, the session closes
        $this->travelTo($saturday->copy()->setTime(10, 5));
        foreach ($students as $student) {
            $this->actingAs($student)->post(route('student.exam.sessions.start', $session))->assertRedirect();
        }
        $this->actingAs($teacher)->getJson(route('teacher.exam.sessions.live', $session))
            ->assertOk()->assertJsonPath('status', 'open')->assertJsonPath('in_progress', 2);

        $this->travelTo($saturday->copy()->setTime(13, 1));
        app(ExamSessionLifecycle::class)->sync($session->fresh());

        $csv = $this->actingAs($teacher)->get(route('teacher.exam.sessions.export', $session));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $body = $csv->streamedContent();
        $this->assertStringContainsString("O'rin;", $body);
        $this->assertSame(3, substr_count(trim($body), "\n") + 1, 'header + 2 students');

        $this->actingAs($teacher)->get(route('teacher.exam.sessions.index', ['tab' => 'past']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Teacher/Exam/Sessions/Index')->has('sessions.data', 1));
    }

    public function test_teacher_allows_a_placement_retake(): void
    {
        [$subject] = $this->bank();
        $student = $this->examStudent($subject);
        $this->actingAs($student)->post(route('student.exam.placement.start', $subject->code));
        $attempt = ExamAttempt::query()->where('user_id', $student->id)->firstOrFail();
        $this->actingAs($student)->post(route('student.exam.submit', $attempt));

        $teacher = $this->examTeacher();
        $this->actingAs($teacher)->get(route('teacher.exam.sessions.show', $attempt->session_id))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canRetake', true)->has('results', 1));
        $this->actingAs($teacher)->post(route('teacher.exam.sessions.retake', [$attempt->session_id, $attempt->id]))->assertRedirect();

        $this->actingAs($student)->get(route('student.exam.index'))
            ->assertInertia(fn (Assert $page) => $page->where('placement.0.state', 'retake'));
        $this->actingAs($student)->post(route('student.exam.placement.start', $subject->code))->assertRedirect();
        $this->assertSame(2, ExamAttempt::query()->where('user_id', $student->id)->count());
    }
}
