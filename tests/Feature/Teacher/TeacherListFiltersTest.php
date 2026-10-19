<?php

namespace Tests\Feature\Teacher;

use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Teacher list filters used to run in the browser over the loaded page only.
 */
class TeacherListFiltersTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_courses_are_filtered_by_status_and_title_on_the_server(): void
    {
        $teacher = $this->teacher();
        foreach (range(1, 13) as $i) {
            $this->course($teacher, ['title' => "Qoralama {$i}", 'status' => 'draft']);
        }
        $this->course($teacher, ['title' => 'Algebra', 'status' => 'published']);
        $this->course($teacher, ['title' => 'Geometriya', 'status' => 'published']);
        $this->course($this->teacher(), ['title' => 'Algebra (boshqa ustoz)', 'status' => 'published']);

        $this->actingAs($teacher)->get(route('teacher.courses.index', ['status' => 'published']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('courses.data', 2)
                ->where('filters.status', 'published')
                ->where('statusCounts.draft', 13)
                ->where('statusCounts.published', 2));

        $this->actingAs($teacher)->get(route('teacher.courses.index', ['search' => 'algeb']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('courses.data', 1)
                ->where('courses.data.0.title', 'Algebra'));

        // the page is paginated by 12: the 13th draft is only reachable via the server filter
        $this->actingAs($teacher)->get(route('teacher.courses.index', ['search' => 'Qoralama 1']))
            ->assertInertia(fn (Assert $page) => $page->where('courses.total', 5)); // 1, 10, 11, 12, 13
    }

    public function test_reviews_pending_and_rating_filters_run_on_the_server(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);

        foreach ([[5, null], [4, 'Rahmat'], [5, null], [2, null]] as [$rating, $reply]) {
            $student = $this->studentUser();
            $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
            Review::forceCreate([
                'user_id' => $student->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
                'rating' => $rating, 'review_text' => 'Fikr', 'status' => 'approved', 'teacher_reply' => $reply,
            ]);
        }

        $this->actingAs($teacher)->get(route('teacher.reviews.index', ['filter' => 'pending']))
            ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 3)->where('filters.filter', 'pending'));

        $this->actingAs($teacher)->get(route('teacher.reviews.index', ['rating' => 5]))
            ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 2));

        $this->actingAs($teacher)->get(route('teacher.reviews.index', ['filter' => 'bogus', 'rating' => 9]))
            ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 4)->where('filters.filter', '')->where('filters.rating', ''));
    }
}
