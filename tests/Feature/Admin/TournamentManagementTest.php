<?php

namespace Tests\Feature\Admin;

use App\Models\Direction;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * QA2-A-11: there was no way to create a tournament, so /student/tournaments
 * was always empty and registration / withdrawal could not be used.
 */
class TournamentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function student(int $coins): User
    {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $user->studentProfile()->update(['coins' => $coins]);

        return $user->fresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Haftalik fizika kubogi',
            'description' => 'Battle turniri',
            'direction_id' => Direction::forceCreate(['name_uz' => 'Fizika', 'slug' => 'fizika-'.Str::random(6), 'is_active' => true])->id,
            'type' => 'weekly',
            'status' => 'registration',
            'entry_fee_coins' => 30,
            'prize_pool_coins' => 500,
            'min_participants' => 2,
            'max_participants' => 16,
            'min_elo' => null,
            'max_elo' => null,
            'registration_starts_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'registration_ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'ends_at' => null,
            'questions_per_match' => 5,
            'time_per_question' => 20,
        ], $overrides);
    }

    public function test_admin_creates_a_tournament_students_register_withdraw_and_cancelling_refunds(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.tournaments.store'), $this->payload())
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $tournament = Tournament::firstOrFail();
        $this->assertStringStartsWith('haftalik-fizika-kubogi-', $tournament->slug);

        $this->actingAs($admin)->get(route('admin.tournaments.index'))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Tournaments/Index')
                ->where('tournaments.0.name', 'Haftalik fizika kubogi')
                ->where('tournaments.0.participants_count', 0));

        // students now see it, register (fee charged) and withdraw (refunded)
        $first = $this->student(100);
        $second = $this->student(100);
        $this->actingAs($first)->get(route('student.tournaments.index'))
            ->assertInertia(fn (Assert $p) => $p->has('tournaments', 1));
        $this->actingAs($first)->get(route('student.tournaments.show', $tournament))->assertOk();
        $this->actingAs($first)->postJson(route('student.tournaments.register', $tournament))->assertOk();
        $this->actingAs($second)->postJson(route('student.tournaments.register', $tournament))->assertOk();
        $this->assertSame(70, $first->studentProfile()->first()->coins);

        $this->actingAs($first)->deleteJson(route('student.tournaments.withdraw', $tournament))->assertOk();
        $this->assertSame(100, $first->studentProfile()->first()->coins);

        // a tournament with participants is cancelled, not deleted; cancelling refunds
        $this->actingAs($admin)->delete(route('admin.tournaments.destroy', $tournament))->assertSessionHas('error');
        $this->actingAs($admin)->put(route('admin.tournaments.update', $tournament), $this->payload(['status' => 'cancelled', 'direction_id' => $tournament->direction_id]))
            ->assertSessionHasNoErrors();
        $this->assertSame(100, $second->studentProfile()->first()->coins);
        $this->assertSame('cancelled', $tournament->fresh()->status);

        // an empty tournament can be deleted
        TournamentParticipant::query()->delete();
        $this->actingAs($admin)->delete(route('admin.tournaments.destroy', $tournament))->assertSessionHas('success');
        $this->assertSame(0, Tournament::count());
    }

    public function test_invalid_input_gets_uzbek_errors(): void
    {
        $this->actingAs($this->admin())->post(route('admin.tournaments.store'), $this->payload([
            'name' => '', 'max_participants' => 1, 'starts_at' => '',
        ]))->assertSessionHasErrors([
            'name' => "Nomi to'ldirilishi shart.",
            'max_participants' => "Maksimal ishtirokchilar minimaldan kam bo'lmasligi kerak.",
        ]);
        $this->assertSame(0, Tournament::count());
    }
}
