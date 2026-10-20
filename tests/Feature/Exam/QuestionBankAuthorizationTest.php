<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Only the teacher and admins author / review exam questions; students and
 * guardians get 403 on every stage 3 route, guests go to login.
 */
class QuestionBankAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const STAGE3_PREFIXES = ['teacher.exam.topics.', 'teacher.exam.questions.', 'teacher.exam.review', 'teacher.exam.import.'];

    private QuestionBank $question;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->topic = Topic::factory()->create(['subject_id' => Subject::factory()->math()->create()->id]);
        $this->question = ExamQuestionFactory::new()->aiDraft()->withUnmarkedOptions()->forTopic($this->topic)->create();
    }

    /** @return list<array{string, string, string}> [method, uri, name] */
    private function stage3Routes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! collect(self::STAGE3_PREFIXES)->contains(fn ($p) => str_starts_with($name, $p))) {
                continue;
            }
            $params = [];
            foreach ($route->parameterNames() as $p) {
                $params[$p] = $p === 'topic' ? $this->topic->id : $this->question->id;
            }
            $method = collect($route->methods())->first(fn ($m) => $m !== 'HEAD');
            $out[] = [$method, route($name, $params), $name];
        }

        return $out;
    }

    public function test_students_and_guardians_get_403_everywhere(): void
    {
        $routes = $this->stage3Routes();
        $this->assertGreaterThanOrEqual(20, count($routes));

        foreach (['student', 'guardian'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            foreach ($routes as [$method, $uri, $name]) {
                $this->app['auth']->forgetGuards();
                $this->actingAs($user)->call($method, $uri)->assertStatus(403);
            }
        }

        $this->assertSame(ReviewStatus::InReview, $this->question->fresh()->review_status);
        $this->assertSame(4, $this->question->answerOptions()->whereNull('is_correct')->count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('teacher.exam.review'))->assertRedirect(route('login'));
        $this->post(route('teacher.exam.review.approve', $this->question))->assertRedirect(route('login'));
    }

    public function test_teacher_and_admins_can_open_the_pages(): void
    {
        foreach (['teacher', 'admin', 'super_admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->app['auth']->forgetGuards();
            $this->actingAs($user);
            $this->get(route('teacher.exam.questions.index'))->assertOk();
            $this->get(route('teacher.exam.topics.index'))->assertOk();
            $this->get(route('teacher.exam.import.index'))->assertOk();
            $this->get(route('teacher.exam.questions.edit', $this->question))->assertOk();
            $this->get(route('teacher.exam.review'))->assertOk()
                ->assertInertia(fn (Assert $p) => $p->where('exam_review_pending', 1));
        }
    }

    public function test_pending_badge_is_not_shared_with_students(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn (Assert $p) => $p->where('exam_review_pending', null));
    }
}
