<?php

namespace Tests\Feature\Student;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TournamentAndSupportTest extends TestCase
{
    use RefreshDatabase;

    private function student(int $coins = 0): User
    {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $user->studentProfile()->update(['coins' => $coins]);

        return $user->fresh();
    }

    private function tournament(int $fee = 50): Tournament
    {
        return Tournament::forceCreate([
            'name' => 'Haftalik kubok',
            'max_participants' => 16,
            'min_participants' => 2,
            'min_level' => 1,
            'entry_fee' => $fee,
            'prizes' => json_encode(['1' => ['xp' => 100, 'coins' => 100]]),
            'status' => 'registration',
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDay(),
            'starts_at' => now()->addDays(2),
        ]);
    }

    public function test_registration_charges_fee_once(): void
    {
        $user = $this->student(100);
        $tournament = $this->tournament();

        $this->actingAs($user)->postJson(route('student.tournaments.register', $tournament))->assertOk();
        $this->actingAs($user)->postJson(route('student.tournaments.register', $tournament))->assertStatus(400);

        $this->assertSame(50, $user->studentProfile()->first()->coins);
        $this->assertSame(1, TournamentParticipant::count());
    }

    public function test_registration_without_coins_creates_nothing(): void
    {
        $user = $this->student(10);

        $this->actingAs($user)->postJson(route('student.tournaments.register', $this->tournament()))
            ->assertStatus(400);

        $this->assertSame(0, TournamentParticipant::count());
        $this->assertSame(10, $user->studentProfile()->first()->coins);
    }

    public function test_withdraw_refunds_only_once(): void
    {
        $user = $this->student(100);
        $tournament = $this->tournament();
        $this->actingAs($user)->postJson(route('student.tournaments.register', $tournament));

        $this->actingAs($user)->deleteJson(route('student.tournaments.withdraw', $tournament))->assertOk();
        $this->actingAs($user)->deleteJson(route('student.tournaments.withdraw', $tournament))->assertStatus(400);

        $this->assertSame(100, $user->studentProfile()->first()->coins);
    }

    public function test_student_does_not_see_internal_support_notes(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        $user = $this->student();
        $ticket = SupportTicket::forceCreate([
            'user_id' => $user->id,
            'ticket_number' => 'T-0001',
            'subject' => 'Muammo',
            'description' => 'Video ochilmayapti',
        ]);
        TicketMessage::forceCreate(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'content' => 'Public reply', 'is_internal_note' => false]);
        TicketMessage::forceCreate(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'content' => 'Secret staff note', 'is_internal_note' => true]);

        $this->actingAs($user)
            ->get(route('student.support.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('ticket.messages', 1)
                ->where('ticket.messages.0.content', 'Public reply'));
    }

    public function test_student_cannot_open_foreign_ticket(): void
    {
        $ticket = SupportTicket::forceCreate([
            'user_id' => $this->student()->id,
            'ticket_number' => 'T-0002',
            'subject' => 'Muammo',
            'description' => 'Tavsif',
        ]);

        $this->actingAs($this->student())->get(route('student.support.show', $ticket))->assertForbidden();
    }
}
