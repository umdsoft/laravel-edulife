<?php

namespace Tests\Feature\Student;

use App\Models\Battle;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-S-02: every battle page 500'd — the battles table had the 2024 schema
 * the code no longer matched, and the Match / History pages did not exist.
 */
class BattleFlowTest extends TestCase
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

    public function test_two_students_play_a_battle_to_the_end(): void
    {
        $correct = $this->questionBank();
        $ali = $this->studentUser();
        $vali = $this->studentUser();

        $this->actingAs($ali)->get(route('student.battles.index'))
            ->assertOk()->assertInertia(fn (Assert $p) => $p->component('Student/Battle/Index')->where('questionsAvailable', 3));

        $this->actingAs($ali)->postJson(route('student.battles.find'))->assertOk()->assertJsonPath('match_found', false);
        $battle = Battle::firstOrFail();
        $this->assertSame('waiting', $battle->status);

        $this->actingAs($vali)->postJson(route('student.battles.find'))->assertOk()->assertJsonPath('match_found', true);
        $battle->refresh();
        $this->assertSame('in_progress', $battle->status);
        $this->assertSame(3, $battle->total_rounds);
        $this->travel(5)->seconds(); // countdown

        foreach (range(1, 3) as $n) {
            $page = $this->actingAs($ali)->get(route('student.battles.show', $battle))->assertOk()
                ->assertInertia(fn (Assert $p) => $p->component('Student/Battle/Match')->where('currentRound.round_number', $n))
                ->viewData('page')['props'];
            $questionId = $page['currentRound']['question']['id'];

            // Ali always right, Vali always wrong
            $this->actingAs($ali)->postJson(route('student.battles.answer', $battle), ['answer' => $correct[$questionId], 'time_taken' => 1])
                ->assertOk()->assertJsonPath('is_correct', true);
            $this->actingAs($vali)->postJson(route('student.battles.answer', $battle), ['answer' => 'wrong', 'time_taken' => 1])
                ->assertOk()->assertJsonPath('is_correct', false);
        }

        $battle->refresh();
        $this->assertSame('completed', $battle->status);
        $this->assertSame($ali->id, $battle->winner_id);
        $this->assertSame(3, $battle->player1_score);
        $this->assertSame(10, $ali->fresh()->coins);
        $this->assertGreaterThan(0, $battle->player1_elo_change);

        $this->actingAs($vali)->get(route('student.battles.show', $battle))
            ->assertInertia(fn (Assert $p) => $p->where('battle.status', 'completed')->has('battle.rounds', 3));

        $this->actingAs($ali)->get(route('student.battles.history'))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Battle/History')
                ->where('battles.data.0.result', 'win')
                ->where('stats.battles_won', 1));
    }

    public function test_an_unanswered_round_closes_after_its_time(): void
    {
        $correct = $this->questionBank();
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $this->actingAs($ali)->postJson(route('student.battles.find'));
        $this->actingAs($vali)->postJson(route('student.battles.find'));
        $battle = Battle::firstOrFail();

        $this->travel(5 + $battle->time_per_question + 3)->seconds();

        $this->actingAs($ali)->get(route('student.battles.show', $battle))
            ->assertInertia(fn (Assert $p) => $p->where('currentRound.round_number', 2));
    }

    public function test_battle_needs_enough_questions(): void
    {
        $this->questionBank(1);

        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'))
            ->assertStatus(422)->assertJsonPath('match_found', false);
        $this->assertSame(0, Battle::count());
    }
}
