<?php

namespace Tests\Feature\Teacher;

use App\Models\CourseQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** QA-T-13: the Javobsiz tab still listed answered questions. */
class QnaFiltersTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_status_tabs_filter_the_questions(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $student = $this->studentUser();
        CourseQuestion::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'content' => 'Javobsiz']);
        CourseQuestion::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'content' => 'Javobli', 'is_answered' => true]);
        CourseQuestion::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'content' => 'Qadalgan', 'is_answered' => true, 'is_pinned' => true]);

        $contents = fn ($status) => collect($this->actingAs($teacher)->get(route('teacher.courses.qna.index', [$course, 'status' => $status]))
            ->viewData('page')['props']['questions']['data'])->pluck('content')->sort()->values()->all();

        $this->assertSame(['Javobsiz'], $contents('unanswered'));
        $this->assertSame(['Javobli', 'Qadalgan'], $contents('answered'));
        $this->assertSame(['Qadalgan'], $contents('pinned'));
        $this->assertCount(3, $contents(''));
    }
}
