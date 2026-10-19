<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function protectedAreas(): array
    {
        return [
            'admin' => ['/admin/dashboard'],
            'teacher' => ['/teacher/dashboard'],
            'student' => ['/student/dashboard'],
        ];
    }

    #[DataProvider('protectedAreas')]
    public function test_guest_is_redirected_to_login(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_student_cannot_open_admin_or_teacher_area(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/teacher/dashboard')->assertForbidden();
    }

    public function test_teacher_cannot_open_admin_area(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($teacher)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_blocked_user_with_live_session_is_logged_out(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'blocked']);

        $this->actingAs($student)->get('/student/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_home_redirects_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');
    }
}
