<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Direction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private Direction $direction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->direction = Direction::factory()->create();
        
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);
        
        $this->student = User::factory()->create([
            'role' => 'student',
        ]);
    }

    /**
     * Test student can view courses list
     */
    public function test_student_can_view_courses_list(): void
    {
        $response = $this->actingAs($this->student)->get('/student/courses');
        $response->assertStatus(200);
    }

    /**
     * Test student can view course details
     */
    public function test_student_can_view_course_details(): void
    {
        $course = Course::factory()->create([
            'teacher_id' => $this->teacher->id,
            'direction_id' => $this->direction->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->student)->get("/student/courses/{$course->slug}");
        $response->assertStatus(200);
    }

    /**
     * Test student cannot access teacher routes
     */
    public function test_student_cannot_access_teacher_routes(): void
    {
        $response = $this->actingAs($this->student)->get('/teacher/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Test student cannot access admin routes
     */
    public function test_student_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->student)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Test guest is redirected to login
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/student/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test student dashboard loads
     */
    public function test_student_dashboard_loads(): void
    {
        $response = $this->actingAs($this->student)->get('/student/dashboard');
        $response->assertStatus(200);
    }
}
