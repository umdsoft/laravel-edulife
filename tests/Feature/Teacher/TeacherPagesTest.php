<?php

namespace Tests\Feature\Teacher;

use App\Models\TeacherEarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherPagesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_dashboard_sums_net_earnings(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        foreach ([['confirmed', 70000], ['paid', 30000], ['pending', 5000]] as [$status, $net]) {
            TeacherEarning::forceCreate([
                'teacher_id' => $teacher->id,
                'course_id' => $course->id,
                'type' => 'course_sale',
                'gross_amount' => $net * 1.25,
                'commission_rate' => 20,
                'commission_amount' => $net * 0.25,
                'net_amount' => $net,
                'status' => $status,
            ]);
        }

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total_earnings', fn ($v) => (float) $v === 100000.0));
    }

    public function test_earnings_and_analytics_pages_render(): void
    {
        $teacher = $this->teacher();
        $this->course($teacher);

        $this->actingAs($teacher)->get(route('teacher.earnings.index'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.analytics.index'))->assertOk();
    }
}
