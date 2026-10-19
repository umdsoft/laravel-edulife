<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class NotificationMarkAllReadTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function notify(User $user, int $count = 2): void
    {
        foreach (range(1, $count) as $i) {
            Notification::forceCreate([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => "Xabar {$i}",
            ]);
        }
    }

    private function assertAllRead(User $user): void
    {
        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        $student = $this->studentUser();
        $other = $this->studentUser();
        $this->notify($student);
        $this->notify($other, 1);

        $this->actingAs($student)
            ->post(route('student.notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertAllRead($student);
        $this->assertSame(1, $other->unreadNotifications()->count(), 'other users must be untouched');
    }

    public function test_teacher_can_mark_all_notifications_as_read(): void
    {
        $teacher = $this->teacher();
        $this->notify($teacher);

        $this->actingAs($teacher)
            ->post(route('teacher.notifications.mark-all-read'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertAllRead($teacher);
    }
}
