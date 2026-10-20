<?php

namespace Tests\Feature\Student;

use App\Models\Battle;
use App\Models\Question;
use App\Models\Test;
use App\Services\Telegram\TelegramStartParam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Telegram WebView (wave 2): battle with a friend — an invite code instead of the battle id,
 * random matchmaking never joins it, the friend's deep link battle_<code>, and the light
 * state poll of the match page.
 */
class BattleChallengeTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function questionBank(int $count = 3): array
    {
        $course = $this->course($this->teacher());
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Bank', 'is_active' => true]);
        $correct = [];
        foreach (range(1, $count) as $i) {
            $q = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => "{$i} + {$i} = ?"]);
            $correct[$q->id] = $q->options()->create(['option_text' => (string) ($i * 2), 'is_correct' => true])->id;
            $q->options()->create(['option_text' => '0', 'is_correct' => false]);
        }

        return $correct;
    }

    public function test_a_friend_accepts_the_challenge_and_the_match_starts(): void
    {
        $this->questionBank();
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $stranger = $this->studentUser();

        $data = $this->actingAs($ali)->postJson(route('student.battles.challenge.store'))->assertOk()->json();
        $battle = Battle::findOrFail($data['battle_id']);
        $this->assertSame('waiting', $battle->status);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{8}$/', $battle->invite_code);
        $this->assertSame($battle->invite_code, $data['invite']['code']);
        $this->assertStringNotContainsString($battle->id, $data['invite']['web']);

        // random matchmaking does not take a friend's challenge
        $this->actingAs($stranger)->postJson(route('student.battles.find'))->assertOk()->assertJsonPath('match_found', false);
        $this->assertNull($battle->fresh()->player2_id);
        $this->actingAs($stranger)->postJson(route('student.battles.cancel'))->assertOk();

        // the waiting challenger sees the link on the match page
        $this->actingAs($ali)->get(route('student.battles.show', $battle))
            ->assertInertia(fn (Assert $p) => $p->where('battle.invite.code', $battle->invite_code));

        // the friend's deep link
        $this->assertSame(route('student.battles.challenge.show', $battle->invite_code, false),
            app(TelegramStartParam::class)->resolve($vali, 'battle_'.$battle->invite_code)['url']);
        $this->actingAs($vali)->get(route('student.battles.challenge.show', $battle->invite_code))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Battle/Challenge')->where('challenge.open', true)->missing('challenge.battle_id'));

        $this->actingAs($vali)->post(route('student.battles.challenge.accept', $battle->invite_code))
            ->assertRedirect(route('student.battles.show', $battle));
        $battle->refresh();
        $this->assertSame('in_progress', $battle->status);
        $this->assertSame($vali->id, $battle->player2_id);

        // nobody else can take it any more
        $this->actingAs($stranger)->post(route('student.battles.challenge.accept', $battle->invite_code))
            ->assertRedirect(route('student.battles.challenge.show', $battle->invite_code))
            ->assertSessionHas('error');
    }

    public function test_the_state_poll_is_small_and_only_for_players(): void
    {
        $correct = $this->questionBank();
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $this->actingAs($ali)->postJson(route('student.battles.challenge.store'));
        $battle = Battle::firstOrFail();
        $this->actingAs($vali)->post(route('student.battles.challenge.accept', $battle->invite_code));
        $battle->refresh();
        $this->travel(5)->seconds();

        $before = $this->actingAs($ali)->getJson(route('student.battles.state', $battle))->assertOk()->json();
        $this->assertSame('in_progress', $before['status']);
        $this->assertArrayNotHasKey('question', $before);

        $props = $this->actingAs($vali)->get(route('student.battles.show', $battle))->viewData('page')['props'];
        $this->actingAs($vali)->postJson(route('student.battles.answer', $battle), ['answer' => $correct[$props['currentRound']['question']['id']], 'time_taken' => 1])->assertOk();

        $after = $this->actingAs($ali)->getJson(route('student.battles.state', $battle))->json();
        $this->assertNotSame($before['v'], $after['v']); // the opponent answered: Ali's page reloads

        $this->actingAs($this->studentUser())->getJson(route('student.battles.state', $battle))->assertForbidden();
    }

    public function test_own_or_expired_challenge(): void
    {
        $this->questionBank();
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $this->actingAs($ali)->postJson(route('student.battles.challenge.store'));
        $battle = Battle::firstOrFail();

        // the challenger opening their own link goes back to the waiting page
        $this->actingAs($ali)->get(route('student.battles.challenge.show', $battle->invite_code))
            ->assertRedirect(route('student.battles.show', $battle));

        $this->travel((int) config('exam.telegram.challenge_minutes', 15) + 1)->minutes();
        $this->actingAs($vali)->post(route('student.battles.challenge.accept', $battle->invite_code))->assertSessionHas('error');
        $this->assertNull($battle->fresh()->player2_id);

        $this->actingAs($vali)->get(route('student.battles.challenge.show', 'nope1234'))->assertRedirect(route('student.battles.index'));
    }

    public function test_challenge_needs_enough_questions(): void
    {
        $this->questionBank(1);
        $this->actingAs($this->studentUser())->postJson(route('student.battles.challenge.store'))->assertStatus(422)->assertJsonStructure(['message']);
        $this->assertSame(0, Battle::count());
    }
}
