<?php

namespace Tests\Feature\Teacher;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-T-02: cloning a course 500'd (lazy loading of module lessons).
 */
class CourseCloneTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_course_is_cloned_with_curriculum_and_tests(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['students_count' => 40, 'avg_rating' => 4.8, 'views_count' => 900]);
        $module = $this->module($course);
        $lesson = $this->lesson($module, ['type' => 'video', 'video_url' => 'videos/x.m3u8', 'video_status' => 'ready', 'video_duration' => 300]);

        $lessonTest = Test::forceCreate([
            'course_id' => $course->id, 'type' => 'lesson', 'title' => 'Dars testi',
            'testable_type' => Lesson::class, 'testable_id' => $lesson->id, 'is_active' => true,
        ]);
        $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $lessonTest), [
            'type' => 'ordering', 'question_text' => 'Tartibla', 'points' => 1,
            'options' => [['option_text' => 'a', 'correct_position' => 1], ['option_text' => 'b', 'correct_position' => 2]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($teacher)->post(route('teacher.courses.clone', $course), [
            'title' => 'Nusxa kurs',
            'clone_curriculum' => true,
            'clone_tests' => true,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $copy = Course::where('title', 'Nusxa kurs')->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertSame(0, (int) $copy->students_count);
        $this->assertEquals(0, $copy->avg_rating);
        $this->assertSame(0, (int) $copy->views_count);

        $copyModule = Module::where('course_id', $copy->id)->firstOrFail();
        $copyLesson = Lesson::where('module_id', $copyModule->id)->firstOrFail();
        $this->assertNull($copyLesson->getRawOriginal('video_url'));

        $copyTest = Test::where('course_id', $copy->id)->firstOrFail();
        $this->assertFalse((bool) $copyTest->is_active);
        $this->assertSame(Lesson::class, $copyTest->testable_type);
        $this->assertSame($copyLesson->id, $copyTest->testable_id);

        $copyQuestion = Question::where('test_id', $copyTest->id)->with('options')->firstOrFail();
        $this->assertSame($copy->id, $copyQuestion->course_id);
        $this->assertSame($copyQuestion->options->sortBy('correct_position')->pluck('id')->values()->all(), $copyQuestion->ordering_items);

        // the original is untouched
        $this->assertSame(1, $course->tests()->count());
        $this->assertTrue((bool) $lessonTest->fresh()->is_active);
    }

    public function test_only_the_owner_can_clone(): void
    {
        $course = $this->course($this->teacher());

        $this->actingAs($this->teacher())->post(route('teacher.courses.clone', $course), ['title' => 'X'])->assertForbidden();
    }
}
