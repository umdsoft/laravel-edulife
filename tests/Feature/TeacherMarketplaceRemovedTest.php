<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Direction;
use App\Models\Enrollment;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The platform serves a single teacher: the multi-teacher marketplace
 * (rating/score/levels, commission, earnings, payouts, bank accounts) is gone,
 * while the teacher content panel keeps working.
 */
class TeacherMarketplaceRemovedTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
    }

    public function test_no_marketplace_routes_are_registered(): void
    {
        $patterns = ['earning', 'payout', 'bank-account', 'teacher-rating', 'teacher.rating', 'update-level'];

        $leftovers = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route) use ($patterns) {
                $haystack = $route->uri() . ' ' . $route->getName();
                foreach ($patterns as $pattern) {
                    if (str_contains($haystack, $pattern)) {
                        return true;
                    }
                }
                return false;
            })
            ->map(fn ($route) => $route->uri());

        $this->assertEmpty($leftovers->all(), 'Marketplace routes still registered: ' . $leftovers->implode(', '));
    }

    public function test_removed_teacher_urls_return_not_found(): void
    {
        $this->actingAs($this->teacher);

        $this->get('/teacher/earnings')->assertNotFound();
        $this->get('/teacher/earnings/history')->assertNotFound();
        $this->post('/teacher/earnings/withdraw')->assertNotFound();
        $this->get('/teacher/bank-accounts')->assertNotFound();
        $this->post('/teacher/bank-accounts')->assertNotFound();
        $this->get('/teacher/rating')->assertNotFound();
    }

    public function test_removed_admin_urls_return_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);

        $this->get('/admin/teacher-payouts')->assertNotFound();
        $this->get('/admin/teacher-payouts/bank-accounts')->assertNotFound();
        $this->post('/admin/teacher-payouts')->assertNotFound();
        $this->get('/admin/teacher-ratings')->assertNotFound();
        $this->post('/admin/teacher-ratings/recalculate-all')->assertNotFound();
        $this->patch("/admin/teachers/{$this->teacher->id}/update-level", ['level' => 'top'])->assertNotFound();
    }

    public function test_teacher_score_job_is_no_longer_scheduled(): void
    {
        // Running a console command bootstraps routes/console.php, where schedules live
        Artisan::call('schedule:list');

        $this->assertStringNotContainsString('TeacherScore', Artisan::output());

        $events = collect(app(Schedule::class)->events())
            ->map(fn ($event) => (string) $event->description . ' ' . (string) $event->command);

        $this->assertFalse(
            $events->contains(fn ($e) => str_contains($e, 'TeacherScore')),
            'Teacher score job is still scheduled'
        );
    }

    public function test_teacher_dashboard_renders_enrollment_stats_instead_of_earnings(): void
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'direction_id' => Direction::factory(),
            'status' => 'published',
        ]);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($this->teacher)
            ->get('/teacher/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Dashboard')
                ->where('stats.total_courses', 1)
                ->where('stats.total_students', 1)
                ->where('stats.monthly_enrollments', 1)
                ->missing('stats.total_earnings')
                ->missing('stats.pending_earnings')
                ->missing('earningsChart')
                ->missing('monthlyEarnings')
                ->has('enrollmentsChart', 6)
                ->where('enrollmentsChart.5.value', 1));
    }

    public function test_teacher_dashboard_renders_without_teacher_profile(): void
    {
        $this->actingAs($this->teacher)
            ->get('/teacher/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teacher/Dashboard')->where('stats.total_courses', 0));
    }

    public function test_teacher_courses_index_renders(): void
    {
        Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'direction_id' => Direction::factory(),
        ]);

        $this->actingAs($this->teacher)
            ->get('/teacher/courses')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teacher/Courses/Index'));
    }

    public function test_teacher_analytics_renders_without_earnings(): void
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'direction_id' => Direction::factory(),
            'status' => 'published',
        ]);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($this->teacher)
            ->get('/teacher/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Analytics/Index')
                ->where('coursePerformance.0.active_students', 1)
                ->missing('coursePerformance.0.earnings'));
    }

    public function test_admin_teacher_pages_have_no_level_or_commission(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        TeacherProfile::create(['user_id' => $this->teacher->id, 'bio' => 'Matematika va fizika ustozi']);

        $this->actingAs($admin)
            ->get('/admin/teachers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Teachers/Index')
                ->missing('teachers.data.0.level')
                ->missing('teachers.data.0.total_earnings'));

        $this->actingAs($admin)
            ->get("/admin/teachers/{$this->teacher->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Teachers/Show')
                ->missing('teacher.level')
                ->missing('teacher.commission_rate')
                ->missing('teacher.total_earnings'));

        $this->actingAs($admin)
            ->patch("/admin/teachers/{$this->teacher->id}/verify")
            ->assertRedirect();

        $this->assertNotNull($this->teacher->teacherProfile()->first()->verified_at);
    }

    public function test_fresh_schema_drops_rating_tables_but_keeps_financial_records(): void
    {
        $this->assertFalse(Schema::hasTable('teacher_score_history'));
        $this->assertFalse(Schema::hasTable('teacher_level_changes'));
        $this->assertFalse(Schema::hasColumn('teacher_profiles', 'score'));
        $this->assertFalse(Schema::hasColumn('teacher_profiles', 'score_breakdown'));

        // Financial history is retained even though the code using it was removed
        $this->assertTrue(Schema::hasTable('teacher_earnings'));
        $this->assertTrue(Schema::hasTable('teacher_payouts'));
        $this->assertTrue(Schema::hasTable('teacher_bank_accounts'));
    }

    public function test_cleanup_migration_drops_legacy_rating_tables_only(): void
    {
        Schema::create('teacher_score_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        Schema::create('teacher_level_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        $migration = require database_path('migrations/2026_10_19_000010_drop_teacher_rating_tables.php');
        $migration->up();
        // Running it again on an already-clean database is a no-op
        $migration->up();

        $this->assertFalse(Schema::hasTable('teacher_score_history'));
        $this->assertFalse(Schema::hasTable('teacher_level_changes'));
        $this->assertTrue(Schema::hasTable('teacher_earnings'));
        $this->assertTrue(Schema::hasTable('teacher_payouts'));
        $this->assertTrue(Schema::hasTable('teacher_bank_accounts'));
    }
}
