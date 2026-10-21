<?php

namespace Tests\Feature\Exam\Stage7;

use App\Models\Exam\GuardianReport;
use App\Models\Exam\GuardianStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuardianAccessTest extends TestCase
{
    use BuildsExamPeople, RefreshDatabase;

    public function test_guardians_can_not_reach_student_teacher_or_admin_areas(): void
    {
        $guardian = $this->guardianUser();

        foreach (['/student/dashboard', route('student.exam.progress'), route('student.exam.leaderboard'), '/teacher/dashboard', route('teacher.exam.guardian-links.index'), '/admin/dashboard'] as $url) {
            $this->actingAs($guardian)->get($url)->assertForbidden();
        }
    }

    public function test_students_and_teachers_can_not_reach_the_guardian_panel(): void
    {
        foreach (['student', 'teacher', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($user)->get(route('guardian.dashboard'))->assertForbidden();
        }
        auth()->logout();
        $this->get(route('guardian.dashboard'))->assertRedirect(route('login'));
    }

    public function test_home_and_login_send_guardians_to_their_panel(): void
    {
        $guardian = $this->guardianUser(['phone' => '+998901112233', 'password' => bcrypt('secret123')]);

        $this->post(route('login.submit'), ['login' => '+998901112233', 'password' => 'secret123'])
            ->assertRedirect('/guardian/dashboard');
        $this->actingAs($guardian)->get('/')->assertRedirect('/guardian/dashboard');
    }

    public function test_the_dashboard_shows_only_own_approved_children(): void
    {
        $guardian = $this->guardianUser();
        $mine = $this->student(['subjects' => ['math']]);
        $pending = $this->student();
        $foreign = $this->student();
        $this->link($guardian, $mine);
        $this->link($guardian, $pending, verified: false);
        $this->link($this->guardianUser(), $foreign);
        $this->weeklyResult($mine, $this->math(), $this->lastMonday(), 70, 5);
        $this->weeklyResult($foreign, $this->math(), $this->lastMonday(), 90, 10);

        $this->actingAs($guardian)->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guardian/Dashboard')
                ->has('children', 1)
                ->where('children.0.id', $mine->id)
                ->where('children.0.subjects.0.last.percent', 70)
                ->has('pending', 1));
    }

    public function test_a_child_page_is_404_unless_the_link_is_approved_and_own(): void
    {
        $guardian = $this->guardianUser();
        $mine = $this->student();
        $pending = $this->student();
        $foreign = $this->student();
        $this->link($guardian, $mine);
        $this->link($guardian, $pending, verified: false);
        $this->link($this->guardianUser(), $foreign);
        $rejected = $this->student();
        $this->link($guardian, $rejected, attrs: ['rejected_at' => now()]);

        $this->actingAs($guardian)->get(route('guardian.children.show', $mine))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Guardian/Child')->where('child.id', $mine->id));
        $this->actingAs($guardian)->get(route('guardian.children.show', $pending))->assertNotFound();
        $this->actingAs($guardian)->get(route('guardian.children.show', $foreign))->assertNotFound();
        $this->actingAs($guardian)->get(route('guardian.children.show', $rejected))->assertNotFound();
    }

    public function test_reports_of_other_guardians_are_404(): void
    {
        $guardian = $this->guardianUser();
        $other = $this->guardianUser();
        $child = $this->student();
        $link = $this->link($other, $child);
        $report = GuardianReport::create([
            'guardian_student_id' => $link->id, 'guardian_id' => $other->id, 'student_id' => $child->id,
            'week_start' => $this->lastMonday(), 'payload' => ['week_label' => 'x', 'subjects' => [], 'recommendations' => [], 'student' => ['first_name' => 'A']],
        ]);

        $this->actingAs($guardian)->get(route('guardian.reports.show', $report))->assertNotFound();
        $this->actingAs($other)->get(route('guardian.reports.show', $report))->assertOk();
        $this->assertNotNull($report->fresh()->read_at);
    }

    public function test_link_request_needs_consent_and_an_existing_student_phone(): void
    {
        $guardian = $this->guardianUser();
        $child = $this->student([], ['phone' => '+998935551122']);

        $this->actingAs($guardian)->post(route('guardian.links.store'), ['phone' => '+998935551122', 'relation' => 'mother'])
            ->assertSessionHasErrors('consent');
        $this->actingAs($guardian)->post(route('guardian.links.store'), ['phone' => '+998939999999', 'relation' => 'mother', 'consent' => true])
            ->assertSessionHasErrors('phone');

        $this->actingAs($guardian)->post(route('guardian.links.store'), ['phone' => '93 555 11 22', 'relation' => 'father', 'consent' => true])
            ->assertSessionHasNoErrors();

        $link = GuardianStudent::where('guardian_id', $guardian->id)->where('student_id', $child->id)->firstOrFail();
        $this->assertNotNull($link->consent_at);
        $this->assertNull($link->verified_at);
        $this->assertSame('father', $link->relation->value);
        // pending: still no access
        $this->actingAs($guardian)->get(route('guardian.children.show', $child))->assertNotFound();
    }

    public function test_the_teacher_approves_or_rejects_a_link(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $guardian = $this->guardianUser(['phone' => '+998901234567']);
        $child = $this->student(['parent_phone' => '+998901234567']);
        $link = $this->link($guardian, $child, verified: false);

        $this->actingAs($teacher)->get(route('teacher.exam.guardian-links.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Teacher/Exam/GuardianLinks')
                ->where('links.data.0.id', $link->id)
                ->where('links.data.0.phone_matches', true));

        // a guardian can not approve their own request
        $this->actingAs($guardian)->post(route('teacher.exam.guardian-links.approve', $link))->assertForbidden();

        $this->actingAs($teacher)->post(route('teacher.exam.guardian-links.approve', $link))->assertRedirect();
        $this->assertNotNull($link->fresh()->verified_at);
        $this->assertSame($teacher->id, $link->fresh()->approved_by);
        $this->actingAs($guardian)->get(route('guardian.children.show', $child))->assertOk();

        $this->actingAs($teacher)->post(route('teacher.exam.guardian-links.reject', $link), ['reason' => 'Raqam mos emas']);
        $this->assertNotNull($link->fresh()->rejected_at);
        $this->actingAs($guardian)->get(route('guardian.children.show', $child))->assertNotFound();
    }

    public function test_the_old_parent_sign_up_link_opens_the_telegram_registration(): void
    {
        $this->get('/guardian/register')->assertRedirect('/register?role=guardian');
        $this->post('/guardian/register', [
            'first_name' => 'Dilnoza', 'last_name' => 'Karimova', 'phone' => '+998977776655',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertStatus(405);
        $this->assertDatabaseMissing('users', ['phone' => '+998977776655']);
    }
}
