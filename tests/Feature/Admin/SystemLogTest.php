<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-A-02: /admin/system-logs 500'd (spatie/laravel-activitylog is not installed).
 */
class SystemLogTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_admin_actions_and_logins_are_logged_and_listed(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create(['role' => 'student', 'status' => 'active', 'password' => bcrypt('secret123')]);

        $this->post(route('login'), ['login' => $student->email, 'password' => 'secret123']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login', 'user_id' => $student->id]);
        auth()->logout();

        $this->actingAs($admin)->patch(route('admin.users.toggle-status', $student))->assertSessionHas('success');
        $log = AuditLog::where('event', 'updated')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['status' => 'blocked'], $log->new_values);

        $this->actingAs($admin)->get(route('admin.system-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SystemLogs/Index')
                ->has('logs.data', 2)
                ->where('logs.data.0.event', 'updated')
                ->where('logs.data.0.causer', $admin->full_name)
                ->where('logs.data.0.subject_type', 'User')
                // QA2-A-07: carries its UTC offset so the page shows local time
                ->where('logs.data.0.created_at', fn ($v) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $v)));

        $this->actingAs($admin)->get(route('admin.system-logs.index', ['event' => 'login']))
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
        $this->actingAs($admin)->get(route('admin.system-logs.index', ['search' => 'bloklandi']))
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
    }

    public function test_course_approval_is_logged_published_and_the_teacher_notified(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.courses.approve', $course))->assertSessionHas('success');

        $course->refresh();
        $this->assertSame('published', $course->status);
        $this->assertNotNull($course->published_at);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $course->id, 'event' => 'updated']);
        $this->assertSame(1, Notification::where('user_id', $teacher->id)->where('type', 'course_approved')->count());
    }
}
