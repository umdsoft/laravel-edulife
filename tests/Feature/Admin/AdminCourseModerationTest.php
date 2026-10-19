<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class AdminCourseModerationTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    /** QA-A-03: level read a non-existent column; a discounted price showed the discount twice */
    public function test_course_page_shows_level_and_list_price(): void
    {
        $course = $this->course($this->teacher(), ['difficulty' => 'intermediate', 'is_free' => false, 'price' => 80000, 'original_price' => 100000]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.courses.show', $course))
            ->assertInertia(fn (Assert $p) => $p
                ->where('course.level', 'intermediate')
                ->where('course.price', 100000)
                ->where('course.discount_price', 80000));
    }

    /** QA-A-15: a published course can be taken down and published again */
    public function test_published_course_can_be_taken_down_and_restored(): void
    {
        $course = $this->course($this->teacher(), ['status' => 'published']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->patch(route('admin.courses.reject', $course), ['rejection_reason' => "Mazmun to'liq emas"])->assertSessionHas('success');
        $this->assertSame('rejected', $course->fresh()->status);

        $this->actingAs($this->studentUser())->get(route('student.courses.show', $course->slug))->assertNotFound();

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');
        $this->assertSame('published', $course->fresh()->status);
        $this->assertNull($course->fresh()->rejection_reason);
    }
}
