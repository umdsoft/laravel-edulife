<?php

namespace Tests\Feature\Teacher;

use App\Jobs\SendAnnouncementNotificationsJob;
use App\Models\CourseAnnouncement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherAnnouncementsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function announce($teacher, $course, array $data = [])
    {
        return $this->actingAs($teacher)->post(route('teacher.courses.announcements.store', $course), array_merge([
            'title' => 'Imtihon',
            'content' => str_repeat('Shanba kuni imtihon. ', 3),
            'type' => 'info',
            'is_pinned' => false,
            'send_notification' => true,
            'publish_now' => true,
            'published_at' => '',
        ], $data));
    }

    /** QA-T-03 */
    public function test_editing_an_announcement_saves_without_a_publish_date(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $this->announce($teacher, $course)->assertSessionHasNoErrors();
        $announcement = CourseAnnouncement::firstOrFail();

        $this->actingAs($teacher)->put(route('teacher.announcements.update', $announcement), [
            'title' => 'Yangilangan',
            'content' => str_repeat('Yangilangan matn. ', 3),
            'type' => 'important',
            'is_pinned' => true,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $announcement->refresh();
        $this->assertSame('Yangilangan', $announcement->title);
        $this->assertSame('important', $announcement->type);
        $this->assertTrue((bool) $announcement->is_pinned);
    }

    public function test_scheduling_requires_a_date_and_notifies_when_it_goes_live(): void
    {
        Queue::fake();
        $teacher = $this->teacher();
        $course = $this->course($teacher);

        $this->announce($teacher, $course, ['publish_now' => false])->assertSessionHasErrors('published_at');

        $when = now()->addDays(3)->startOfMinute();
        $this->announce($teacher, $course, ['publish_now' => false, 'published_at' => $when->toDateTimeString()])
            ->assertSessionHasNoErrors();

        Queue::assertPushed(SendAnnouncementNotificationsJob::class, fn ($job) => $job->delay !== null
            && $job->delay->equalTo($when));
    }

    /** QA-T-07 */
    public function test_pinned_announcements_are_listed_first(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        foreach (['Eski qadalgan' => [true, 5], 'Yangi' => [false, 1], 'O\'rta' => [false, 3]] as $title => [$pinned, $daysAgo]) {
            CourseAnnouncement::forceCreate([
                'course_id' => $course->id, 'teacher_id' => $teacher->id, 'title' => $title, 'content' => 'Matn',
                'is_pinned' => $pinned, 'published_at' => now()->subDays($daysAgo),
            ]);
        }

        $this->actingAs($teacher)->get(route('teacher.courses.announcements.index', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('announcements.data.0.title', 'Eski qadalgan')
                ->where('announcements.data.1.title', 'Yangi')
                ->where('announcements.data.2.title', 'O\'rta'));
    }
}
