<?php

namespace Tests\Feature\Teacher;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\TestEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Teacher panel review (docs/qa/TEACHER_REVIEW.md): regressions for the bugs found
 * and the editor features added (difficulty, duplicate, "save and add another").
 */
class TeacherReviewFixesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function makeTest($course, array $attributes = []): Test
    {
        return Test::forceCreate(array_merge([
            'course_id' => $course->id, 'type' => 'final', 'title' => 'Yakuniy', 'questions_count' => 1,
            'passing_score' => 60, 'max_attempts' => 3, 'is_active' => false,
        ], $attributes));
    }

    private function singleChoice(User $teacher, Test $test, array $extra = []): Question
    {
        $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), array_merge([
            'type' => 'single_choice',
            'question_text' => '$\\sin 30^\\circ$ = ?',
            'points' => 1,
            'options' => [
                ['option_text' => '1/2', 'is_correct' => true],
                ['option_text' => '1', 'is_correct' => false],
            ],
        ], $extra))->assertSessionHasNoErrors();

        return $test->questions()->latest('sort_order')->firstOrFail();
    }

    private function submittedAttempt(Test $test, $course): TestAttempt
    {
        $student = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

        return TestAttempt::forceCreate([
            'user_id' => $student->id, 'test_id' => $test->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
            'status' => 'submitted', 'started_at' => now()->subMinutes(3), 'submitted_at' => now(),
            'expires_at' => now()->addHour(), 'time_spent' => 180, 'score' => 50, 'is_passed' => false,
        ]);
    }

    /** TR-01: a lesson test could be attached to another teacher's lesson, or to nothing at all */
    public function test_a_lesson_test_must_be_linked_to_a_lesson_of_the_same_course(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $module = $this->module($course);
        $lesson = $this->lesson($module, ['type' => 'quiz']);

        $other = $this->course($this->teacher());
        $foreignLesson = $this->lesson($this->module($other), ['type' => 'quiz']);

        $payload = ['title' => 'Dars testi', 'type' => 'lesson_test', 'max_attempts' => 3];

        // not linked at all
        $this->actingAs($teacher)->post(route('teacher.courses.tests.store', $course), $payload)
            ->assertSessionHasErrors(['module_id', 'lesson_id']);

        // another course's lesson
        $this->actingAs($teacher)->post(route('teacher.courses.tests.store', $course), $payload + [
            'module_id' => $foreignLesson->module_id, 'lesson_id' => $foreignLesson->id,
        ])->assertSessionHasErrors(['module_id', 'lesson_id']);

        // a lesson of a different module of the same course
        $otherModule = $this->module($course);
        $this->actingAs($teacher)->post(route('teacher.courses.tests.store', $course), $payload + [
            'module_id' => $otherModule->id, 'lesson_id' => $lesson->id,
        ])->assertSessionHasErrors(['lesson_id']);

        $this->assertSame(0, Test::count());

        $this->actingAs($teacher)->post(route('teacher.courses.tests.store', $course), $payload + [
            'module_id' => $module->id, 'lesson_id' => $lesson->id,
        ])->assertSessionHasNoErrors();

        $test = Test::firstOrFail();
        $this->assertSame(Lesson::class, $test->testable_type);
        $this->assertSame($lesson->id, $test->testable_id);

        // a module test needs a module; a final test needs neither
        $this->actingAs($teacher)->post(route('teacher.courses.tests.store', $course), ['title' => 'M', 'type' => 'module_test', 'max_attempts' => 1])
            ->assertSessionHasErrors('module_id');
        $this->actingAs($teacher)->post(route('teacher.courses.tests.store', $course), ['title' => 'Y', 'type' => 'final_test', 'max_attempts' => 1])
            ->assertSessionHasNoErrors();
    }

    /** TR-02: editing a question re-created its options, so students' saved option ids stopped matching */
    public function test_editing_a_question_keeps_the_option_ids(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $test = $this->makeTest($course);
        $question = $this->singleChoice($teacher, $test);
        [$right, $wrong] = $question->options()->get()->all();

        $this->actingAs($teacher)->put(route('teacher.questions.update', $question), [
            'question_text' => '$\\sin 30^\\circ$ ning qiymati?',
            'points' => 2,
            'options' => [
                ['id' => $right->id, 'option_text' => '0.5', 'is_correct' => true],
                ['option_text' => '√3/2', 'is_correct' => false],
            ],
        ])->assertSessionHasNoErrors();

        $options = $question->fresh()->options()->get();
        $this->assertCount(2, $options);
        $this->assertSame($right->id, $options[0]->id, 'the kept option has the same id');
        $this->assertSame('0.5', $options[0]->option_text);
        $this->assertNull($options->firstWhere('id', $wrong->id), 'the removed option is deleted');
        $this->assertSame($right->id, $question->fresh()->correct_answer, 'the answer key still points at the same row');
    }

    /** TR-03: per-question rate divided by answered rows only — skipped questions showed 100% */
    public function test_question_stats_count_skipped_answers_as_wrong(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $test = $this->makeTest($course);
        $question = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => 'Q']);

        $answered = $this->submittedAttempt($test, $course);
        TestAnswer::forceCreate(['attempt_id' => $answered->id, 'question_id' => $question->id, 'is_answered' => true, 'is_correct' => true]);
        $skipped = $this->submittedAttempt($test, $course);
        TestAnswer::forceCreate(['attempt_id' => $skipped->id, 'question_id' => $question->id, 'is_answered' => false, 'is_correct' => false]);

        // an attempt still in progress does not count
        $open = $this->submittedAttempt($test, $course);
        $open->forceFill(['status' => 'in_progress'])->save();
        TestAnswer::forceCreate(['attempt_id' => $open->id, 'question_id' => $question->id, 'is_answered' => true, 'is_correct' => false]);

        $this->actingAs($teacher)->get(route('teacher.courses.tests.show', [$course, $test]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('questions.0.correct_rate', 50)
                ->where('questions.0.answered_count', 1)
                ->where('questions.0.skipped_count', 1));
    }

    /** UX: question difficulty (battles pick questions by it) can be set and changed */
    public function test_question_difficulty_is_saved_and_validated(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $test = $this->makeTest($course);

        $question = $this->singleChoice($teacher, $test, ['difficulty' => 'hard']);
        $this->assertSame('hard', $question->difficulty);

        $default = $this->singleChoice($teacher, $test);
        $this->assertSame('medium', $default->difficulty);

        $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), [
            'type' => 'true_false', 'question_text' => 'x', 'points' => 1, 'difficulty' => 'extreme',
            'options' => [['option_text' => 'Rost', 'is_correct' => true], ['option_text' => "Yolg'on", 'is_correct' => false]],
        ])->assertSessionHasErrors('difficulty');

        $this->actingAs($teacher)->put(route('teacher.questions.update', $question), [
            'question_text' => 'x', 'points' => 1, 'difficulty' => 'easy',
            'options' => $question->options->map(fn ($o) => $o->only(['id', 'option_text', 'is_correct']))->all(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('easy', $question->fresh()->difficulty);
    }

    /** UX: "Saqlash va yana qo'shish" returns to an empty form of the same type and level */
    public function test_save_and_add_another_redirects_back_to_the_create_form(): void
    {
        $teacher = $this->teacher();
        $test = $this->makeTest($this->course($teacher));

        $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), [
            'type' => 'fill_blank', 'question_text' => 'g = ___', 'points' => 1, 'difficulty' => 'easy', 'after' => 'create',
            'options' => [['option_text' => '9.8', 'is_correct' => true], ['option_text' => '9,81', 'is_correct' => true]],
        ])->assertRedirect(route('teacher.tests.questions.create', ['test' => $test, 'type' => 'fill_blank', 'difficulty' => 'easy']));

        $this->assertSame(['9.8', '9,81'], $test->questions()->first()->accepted_answers);
    }

    /** UX: duplicate a question right after the original, with its own copy of the options */
    public function test_a_question_can_be_duplicated(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $test = $this->makeTest($course);
        $first = $this->singleChoice($teacher, $test, ['difficulty' => 'hard', 'explanation' => 'Yechim']);
        $last = $this->singleChoice($teacher, $test);

        $response = $this->actingAs($teacher)->post(route('teacher.questions.duplicate', $first));

        $copy = Question::where('test_id', $test->id)->whereNotIn('id', [$first->id, $last->id])->firstOrFail();
        $response->assertRedirect(route('teacher.questions.edit', $copy));

        $this->assertSame([1, 2, 3], [$first->fresh()->sort_order, $copy->sort_order, $last->fresh()->sort_order]);
        $this->assertSame('hard', $copy->difficulty);
        $this->assertSame('Yechim', $copy->explanation);
        $this->assertCount(2, $copy->options);
        $this->assertEmpty(array_intersect($copy->options->pluck('id')->all(), $first->options->pluck('id')->all()));
        $this->assertSame($copy->options->firstWhere('is_correct', true)->id, $copy->correct_answer);

        // someone else's question
        $this->actingAs($this->teacher())->post(route('teacher.questions.duplicate', $first))->assertForbidden();
    }

    /** UX: lessons without material are flagged before the course is submitted */
    public function test_lessons_without_material_are_listed_for_the_teacher(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['status' => 'draft']);
        $module = $this->module($course);
        $filled = $this->lesson($module, ['title' => 'Matnli', 'type' => 'text']);
        $empty = $this->lesson($module, ['title' => "Bo'sh video", 'type' => 'video', 'video_url' => null, 'sort_order' => 2]);

        $this->actingAs($teacher)->get(route('teacher.courses.edit', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->has('emptyLessons', 1)
                ->where('emptyLessons.0.id', $empty->id)
                ->where('emptyLessons.0.title', "Bo'sh video"));

        $this->actingAs($teacher)->get(route('teacher.courses.modules.index', $course))
            ->assertInertia(fn (Assert $page) => $page->where('emptyLessonIds', [$empty->id]));

        $this->assertNotNull($filled);
    }

    /** UX: "@ustoz" / "t.me/ustoz" are accepted as social links */
    public function test_profile_links_without_https_are_normalised(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->put(route('teacher.profile.update'), [
            'first_name' => 'Nilufar', 'last_name' => 'Usmonova',
            'website' => 'edulife.uz',
            'social_links' => ['telegram' => '@ustoz_fizika', 'instagram' => 'instagram.com/ustoz', 'youtube' => 'https://youtube.com/@ustoz'],
        ])->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertSame('https://edulife.uz', $teacher->website);
        $this->assertSame('https://t.me/ustoz_fizika', $teacher->social_links['telegram']);
        $this->assertSame('https://instagram.com/ustoz', $teacher->social_links['instagram']);
        $this->assertSame('https://youtube.com/@ustoz', $teacher->social_links['youtube']);

        $this->actingAs($teacher)->put(route('teacher.profile.update'), [
            'first_name' => 'Nilufar', 'last_name' => 'Usmonova', 'social_links' => ['telegram' => 'bu havola emas'],
        ])->assertSessionHasErrors('social_links.telegram');
    }

    /** UX: a fill-in answer typed with a decimal comma (9,8) matches the teacher's 9.8 */
    public function test_fill_blank_answers_ignore_decimal_comma_and_spaces(): void
    {
        $this->assertSame('9.8', TestEvaluationService::normalizeFillBlank(' 9,8 '));
        $this->assertSame('2m/s', TestEvaluationService::normalizeFillBlank('2 M/s'));
        $this->assertSame(TestEvaluationService::normalizeFillBlank('0.5'), TestEvaluationService::normalizeFillBlank('0,5'));
    }

    /** TR-04 (review modal): the reply dialog quotes the review text */
    public function test_reviews_page_sends_the_review_text(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $student = $this->studentUser();
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        \App\Models\Review::forceCreate([
            'user_id' => $student->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
            'rating' => 5, 'review_text' => 'Juda tushunarli', 'status' => 'approved',
        ]);

        $this->actingAs($teacher)->get(route('teacher.reviews.index'))
            ->assertInertia(fn (Assert $page) => $page->where('reviews.data.0.review_text', 'Juda tushunarli'));

        $vue = file_get_contents(resource_path('js/Pages/Teacher/Reviews/Index.vue'));
        $this->assertStringContainsString('selectedReview.review_text', $vue);
        $this->assertStringNotContainsString('selectedReview.content', $vue);
    }
}
