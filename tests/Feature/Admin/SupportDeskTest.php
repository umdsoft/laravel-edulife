<?php

namespace Tests\Feature\Admin;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Students' support tickets had no admin side: nobody could read or answer them.
 */
class SupportDeskTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_admin_answers_a_ticket_and_the_student_sees_it(): void
    {
        $student = $this->studentUser();
        $this->actingAs($student)->post(route('student.support.store'), [
            'subject' => "To'lov qilmoqchiman", 'category' => 'payment', 'priority' => 'medium',
            'description' => 'Pullik kursga qanday yozilaman, karta bilan bo\'lmayapti?',
        ]);
        $ticket = SupportTicket::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.support.index', ['status' => 'open']))
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Support/Index')->where('tickets.data.0.id', $ticket->id)->where('counts.open', 1));

        $this->actingAs($admin)->get(route('admin.support.show', $ticket))->assertInertia(fn (Assert $p) => $p->component('Admin/Support/Show'));

        $this->actingAs($admin)->post(route('admin.support.reply', $ticket), ['content' => 'Ichki: tekshirish kerak', 'is_internal_note' => true]);
        $this->actingAs($admin)->post(route('admin.support.reply', $ticket), ['content' => "Kursni sizga ochib qo'ydik."])->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('waiting_user', $ticket->status);
        $this->assertNotNull($ticket->first_response_at);
        $this->assertSame(1, $student->notifications()->where('type', 'support_reply')->count());

        $this->actingAs($student)->get(route('student.support.show', $ticket))
            ->assertInertia(fn (Assert $p) => $p->has('ticket.messages', 2)->where('ticket.messages.1.content', "Kursni sizga ochib qo'ydik."));

        $this->actingAs($admin)->patch(route('admin.support.status', $ticket), ['status' => 'resolved'])->assertSessionHas('success');
        $this->assertNotNull($ticket->fresh()->resolved_at);

        $this->actingAs($student)->get(route('admin.support.index'))->assertForbidden();
    }

    /** QA2-A-14: rows only opened on click (no <a>: no keyboard, no new tab) */
    public function test_ticket_rows_are_links(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Admin/Support/Index.vue'));
        $this->assertMatchesRegularExpression("/<Link :href=\"route\('admin\.support\.show', row\.id\)\"/", $vue);
    }
}
