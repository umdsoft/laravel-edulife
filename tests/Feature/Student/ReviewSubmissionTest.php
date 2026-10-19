<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-S-16: submitting a review 500'd (comment / enrollment_id).
 */
class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_review_is_saved_moderated_and_shown_to_the_teacher(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'progress' => 40]);

        $this->actingAs($student)->post(route('student.reviews.store', $course), [
            'rating' => 5,
            'comment' => 'Juda foydali kurs, misollar tushunarli edi.',
        ])->assertSessionHasNoErrors()->assertRedirect(route('student.learn.course', $course));

        $review = Review::firstOrFail();
        $this->assertSame('Juda foydali kurs, misollar tushunarli edi.', $review->review_text);
        $this->assertSame('pending', $review->status);

        // pending reviews are not public yet
        $this->actingAs($this->studentUser())->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page->where('course.reviews', []));

        $this->actingAs($teacher)->get(route('teacher.reviews.index'))
            ->assertInertia(fn (Assert $page) => $page->where('reviews.data.0.review_text', $review->review_text));

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->patch(route('admin.reviews.approve', $review));
        $this->actingAs($this->studentUser())->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page->where('course.reviews.0.review_text', $review->review_text));

        // QA2-S-06: the teacher's reply reaches the course page
        $this->actingAs($teacher)->post(route('teacher.reviews.reply', $review), ['reply' => 'Rahmat, omad!'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->studentUser())->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page->where('course.reviews.0.teacher_reply', 'Rahmat, omad!'));
    }

    /** QA2-S-11: the catalog card said "4.0 (0)" while the course page said "4.0 (1)" */
    public function test_course_cards_count_only_approved_reviews_and_follow_moderation(): void
    {
        $course = $this->course($this->teacher());
        $review = function (int $rating, string $text) use ($course) {
            $student = $this->studentUser();
            $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

            return Review::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'enrollment_id' => $enrollment->id,
                'rating' => $rating, 'review_text' => $text, 'status' => 'pending']);
        };
        $approved = $review(4, 'Yaxshi');
        $review(1, 'Kutilmoqda');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->patch(route('admin.reviews.approve', $approved));

        $course->refresh();
        $this->assertSame(1, (int) $course->reviews_count);
        $this->assertEquals(4, (float) $course->avg_rating);

        $this->actingAs($this->studentUser())->get(route('student.courses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('courses.data.0.reviews_count', 1)
                ->where('courses.data.0.reviews_avg_rating', fn ($avg) => (float) $avg === 4.0));

        $this->actingAs($admin)->patch(route('admin.reviews.reject', $approved));
        $this->assertSame(0, (int) $course->fresh()->reviews_count);
    }
}
