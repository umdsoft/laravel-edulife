<?php

namespace Tests\Feature\Admin;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA-A-01: /admin/notifications 500'd, and "send" reported success without sending anything.
 */
class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_notifications_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Notifications/Index'));
    }

    public function test_broadcast_reaches_the_selected_audience(): void
    {
        $admin = $this->admin();
        $students = User::factory()->count(3)->create(['role' => 'student', 'status' => 'active']);
        User::factory()->create(['role' => 'student', 'status' => 'blocked']);
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.notifications.send'), [
            'title' => 'Shanba imtihoni',
            'message' => 'Ertaga soat 10:00 da',
            'type' => 'students',
        ])->assertSessionHas('success', '3 ta foydalanuvchiga xabar yuborildi!');

        $this->assertSame(3, Notification::where('title', 'Shanba imtihoni')->count());
        foreach ($students as $student) {
            $this->assertSame(1, $student->notifications()->unread()->count());
        }
        $this->assertSame(0, $teacher->notifications()->count());

        $this->actingAs($admin)->post(route('admin.notifications.send'), [
            'title' => 'Hammaga', 'message' => 'Salom', 'type' => 'specific', 'user_ids' => [$teacher->id],
        ])->assertSessionHas('success');
        $this->assertSame(1, $teacher->notifications()->count());
    }
}
