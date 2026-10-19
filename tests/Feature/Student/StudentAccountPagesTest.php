<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-S-01: these pages rendered a blank screen (no Vue page).
 */
class StudentAccountPagesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_support_ticket_can_be_created_followed_and_answered(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)->get(route('student.support.create'))->assertInertia(fn (Assert $p) => $p->component('Student/Support/Create'));
        $this->actingAs($student)->get(route('student.support.faq'))->assertInertia(fn (Assert $p) => $p->component('Student/Support/Faq')->has('faqs'));

        $this->actingAs($student)->post(route('student.support.store'), [
            'subject' => 'Video ochilmayapti',
            'category' => 'technical',
            'priority' => 'medium',
            'description' => 'Ikkinchi darsdagi video yuklanmayapti, sahifa qora.',
        ])->assertSessionHasNoErrors();

        $ticket = SupportTicket::firstOrFail();
        $this->actingAs($student)->get(route('student.support.show', $ticket))
            ->assertInertia(fn (Assert $p) => $p->component('Student/Support/Show')->has('ticket.messages', 1));

        $this->actingAs($student)->post(route('student.support.reply', $ticket), ['content' => "Brauzerni almashtirdim, baribir ishlamadi."])
            ->assertSessionHas('success');
        $this->assertSame(2, $ticket->messages()->count());

        $this->actingAs($this->studentUser())->get(route('student.support.show', $ticket))->assertForbidden();
    }

    public function test_profile_shows_real_activities_and_privacy_is_respected(): void
    {
        $student = $this->studentUser();
        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course), ['title' => 'Kasrlar']);
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        LessonProgress::forceCreate([
            'user_id' => $student->id, 'enrollment_id' => $enrollment->id, 'course_id' => $course->id,
            'lesson_id' => $lesson->id, 'is_completed' => true, 'completed_at' => now(),
        ]);

        // the page renders `activities` (it used to be sent as recentActivities, always empty)
        $this->actingAs($student)->get(route('student.profile.show'))
            ->assertInertia(fn (Assert $p) => $p->where('activities.0.description', 'Kasrlar'));
        $this->actingAs($student)->get(route('student.profile.edit'))->assertInertia(fn (Assert $p) => $p->component('Student/Profile/Edit'));
        $this->actingAs($student)->get(route('student.settings.privacy'))->assertInertia(fn (Assert $p) => $p->component('Student/Settings/Privacy'));

        $this->actingAs($student)->put(route('student.settings.privacy.update'), [
            'is_profile_public' => false, 'show_activity' => true, 'show_courses' => true, 'show_achievements' => true, 'show_stats' => true,
        ])->assertSessionHas('success');

        $viewer = $this->studentUser();
        $this->actingAs($viewer)->get(route('student.users.show', $student->id))
            ->assertInertia(fn (Assert $p) => $p->component('Student/Profile/Private')->missing('user.email'));

        $student->update(['is_profile_public' => true]);
        $this->actingAs($viewer)->get(route('student.users.show', $student->id))
            ->assertInertia(fn (Assert $p) => $p->component('Student/Profile/Show')->where('isOwnProfile', false));
    }

    public function test_followers_and_following_pages_list_people(): void
    {
        $me = $this->studentUser();
        $friend = User::factory()->create(['role' => 'student', 'first_name' => 'Sardor']);

        $this->actingAs($friend)->post(route('student.friends.follow', $me));
        $this->actingAs($me)->get(route('student.friends.followers'))
            ->assertInertia(fn (Assert $p) => $p->component('Student/Friends/Followers')->where('followers.data.0.first_name', 'Sardor'));

        $this->actingAs($me)->post(route('student.friends.follow', $friend));
        $this->actingAs($me)->get(route('student.friends.following'))
            ->assertInertia(fn (Assert $p) => $p->component('Student/Friends/Following')->where('following.data.0.first_name', 'Sardor'));
    }
}
