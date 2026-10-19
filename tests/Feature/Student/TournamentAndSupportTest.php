<?php

namespace Tests\Feature\Student;

use App\Models\Direction;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
            'slug' => 'kubok-'.Str::random(8),
            'direction_id' => Direction::forceCreate(['name_uz' => 'Dasturlash', 'slug' => 'dir-'.Str::random(8)])->id,
            'max_participants' => 16,
            'min_participants' => 2,
            // the real column (the code used to read a non-existent entry_fee, so nothing was charged)
            'entry_fee_coins' => $fee,
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

    public function test_elo_bracket_is_enforced(): void
    {
        $user = $this->student(100);
        $tournament = $this->tournament();
        $tournament->forceFill(['min_elo' => 1500])->save();

        $this->actingAs($user)->postJson(route('student.tournaments.register', $tournament))
            ->assertStatus(400)->assertJsonPath('message', "Bu turnir 1500+ ELO reytingli o'quvchilar uchun.");
        $this->assertSame(0, TournamentParticipant::count());
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

    /** QA3-S-01: the tournament page had no way to withdraw; the endpoint worked */
    public function test_tournament_page_offers_withdraw_with_refund_while_registration_is_open(): void
    {
        $user = $this->student(100);
        $tournament = $this->tournament();
        $this->actingAs($user)->postJson(route('student.tournaments.register', $tournament))->assertOk();

        $vue = file_get_contents(resource_path('js/Pages/Student/Tournaments/Show.vue'));
        $this->assertStringContainsString("route('student.tournaments.withdraw'", $vue);
        $this->assertStringContainsString('Turnirdan chiqish', $vue);

        $fee = $tournament->entry_fee;
        $this->actingAs($user)->deleteJson(route('student.tournaments.withdraw', $tournament))
            ->assertOk()
            ->assertJsonPath('refunded', $fee)
            ->assertJsonPath('message', "Turnirdan chiqdingiz, {$fee} coin qaytarildi");

        // once the tournament has started the seat is kept
        $this->actingAs($user)->postJson(route('student.tournaments.register', $tournament))->assertOk();
        $tournament->forceFill(['status' => 'in_progress'])->save();
        $this->actingAs($user)->deleteJson(route('student.tournaments.withdraw', $tournament))
            ->assertStatus(400)
            ->assertJsonPath('message', "Turnir boshlangan — endi chiqib bo'lmaydi");
        $this->assertSame(1, TournamentParticipant::count());
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
