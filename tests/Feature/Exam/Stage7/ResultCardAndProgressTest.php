<?php

namespace Tests\Feature\Exam\Stage7;

use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Topic;
use App\Models\User;
use App\Services\Exam\ReportBuilder;
use App\Services\Exam\ResultCardRenderer;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResultCardAndProgressTest extends TestCase
{
    use BuildsExamPeople, RefreshDatabase;

    public function test_the_card_is_a_1080x1920_png(): void
    {
        $student = $this->student(['subjects' => ['math']]);
        $result = $this->weeklyResult($student, $this->math(), $this->lastMonday(), 71.1, 6.7, ['rank' => 3, 'participants_count' => 40]);

        $response = $this->actingAs($student)->get(route('student.exam.card', ['result' => $result->id, 'download' => 1]));

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $png = $response->getContent();
        $this->assertSame("\x89PNG", substr($png, 0, 4));
        [$w, $h, $type] = getimagesizefromstring($png);
        $this->assertSame([1080, 1920, IMAGETYPE_PNG], [$w, $h, $type]);
    }

    public function test_the_card_shows_no_name_and_delta_only_on_growth(): void
    {
        $student = $this->student([], ['first_name' => 'Ali', 'last_name' => 'Valiyev']);
        $renderer = app(ResultCardRenderer::class);

        $up = $this->weeklyResult($student, $this->math(), $this->lastMonday(), 70, 5.5);
        $data = $renderer->data($up, $student);
        $this->assertSame(5.5, $data['delta']);
        $this->assertStringContainsString('/r/', $data['link']);
        $flat = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Ali', $flat);
        $this->assertStringNotContainsString('Valiyev', $flat);

        $down = $this->weeklyResult($student, $this->physics(), $this->lastMonday(), 40, -3);
        $this->assertNull($renderer->data($down, $student)['delta']);
        $first = $this->weeklyResult($this->student(), $this->math(), $this->lastMonday(), 40, null);
        $this->assertNull($renderer->data($first, $student)['delta']);
    }

    public function test_the_card_is_404_without_a_result_or_for_someone_elses(): void
    {
        $student = $this->student();
        $this->actingAs($student)->get(route('student.exam.card'))->assertNotFound();

        $foreign = $this->weeklyResult($this->student(), $this->math(), $this->lastMonday(), 90);
        $this->actingAs($student)->get(route('student.exam.card', ['result' => $foreign->id]))->assertNotFound();
        $this->actingAs($student)->get(route('student.exam.card', ['attempt' => (string) \Illuminate\Support\Str::uuid()]))->assertNotFound();
    }

    public function test_the_card_can_be_found_by_the_attempt(): void
    {
        $student = $this->student();
        $variant = ExamVariant::factory()->create(['session_id' => $this->weeklySession($this->math(), $this->lastMonday())->id, 'user_id' => $student->id]);
        $attempt = ExamAttempt::factory()->graded(30, 45)->create(['variant_id' => $variant->id]);
        $this->weeklyResult($student, $this->math(), $this->lastMonday(), 66.7, null, ['attempt_id' => $attempt->id]);

        $this->actingAs($student)->get(route('student.exam.card', ['attempt' => $attempt->id]))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_most_improved_topic_compares_with_the_previous_week(): void
    {
        $student = $this->student();
        [, $quadratic] = $this->topicPair($this->math(), 'Algebra', 'Kvadrat tenglamalar', 1);
        [, $triangles] = $this->topicPair($this->math(), 'Geometriya', 'Uchburchaklar', 2);

        $prevWeek = now()->subWeek()->startOfWeek()->toDateString();
        $prev = $this->resultWithAnswers($student, $prevWeek, [[$quadratic, 1, 4], [$triangles, 3, 4]], null);
        $now = $this->resultWithAnswers($student, $this->lastMonday(), [[$quadratic, 4, 4], [$triangles, 3, 4]], 10);

        $topic = app(ReportBuilder::class)->mostImprovedTopic($now);
        $this->assertSame('Kvadrat tenglamalar', $topic['name']);
        $this->assertEquals(75.0, $topic['delta']);
        $this->assertNull(app(ReportBuilder::class)->mostImprovedTopic($prev));
    }

    public function test_the_progress_page_shows_history_topic_map_and_three_recommendations(): void
    {
        $student = $this->student(['subjects' => ['math']]);
        [, $a] = $this->topicPair($this->math(), 'Algebra', 'A1', 1);
        [, $b] = $this->topicPair($this->math(), 'Geometriya', 'B1', 2);
        [, $c] = $this->topicPair($this->math(), 'Trigonometriya', 'C1', 3);
        $this->mastery($student, $a, 30);
        $this->mastery($student, $b, 90);
        $this->weeklyResult($student, $this->math(), now()->subWeek()->startOfWeek()->toDateString(), 50);
        $this->weeklyResult($student, $this->math(), $this->lastMonday(), 62, 12);

        $this->actingAs($student)->get(route('student.exam.progress'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Exam/Progress')
                ->has('history', 2)
                ->where('history.1.percent', 62)
                ->where('history.1.delta', 12)
                ->where('topicMap.0.code', 'math')
                ->has('topicMap.0.sections', 3)
                ->where('topicMap.0.sections.0.score', 30)
                ->where('topicMap.0.sections.2.score', null)
                ->where('weakest.0.name', 'A1')
                ->has('recommendations', 3)
                ->where('latest.percent', 62)
                ->where('referralLink', fn ($link) => str_contains($link, '/r/')));
    }

    public function test_the_progress_page_works_for_a_new_student(): void
    {
        $this->actingAs($this->student())->get(route('student.exam.progress'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('history', [])->where('latest', null)->has('recommendations', 0));
    }

    /** @param list<array{0: Topic, 1: float, 2: float}> $answers topic, points, max */
    private function resultWithAnswers(User $student, string $week, array $answers, ?float $delta): StudentWeeklyResult
    {
        $session = $this->weeklySession($this->math(), $week);
        $variant = ExamVariant::factory()->create(['session_id' => $session->id, 'user_id' => $student->id]);
        $attempt = ExamAttempt::factory()->graded(10, 20)->create(['variant_id' => $variant->id]);
        foreach ($answers as $i => [$topic, $points, $max]) {
            $question = (new ExamQuestionFactory)->create(['subject_id' => $topic->subject_id, 'topic_id' => $topic->id]);
            ExamAnswer::factory()->create([
                'attempt_id' => $attempt->id, 'question_id' => $question->id, 'position' => $i + 1,
                'points' => $points, 'max_points' => $max, 'is_correct' => $points >= $max,
            ]);
        }

        return $this->weeklyResult($student, $this->math(), $week, 50, $delta, ['attempt_id' => $attempt->id]);
    }
}
