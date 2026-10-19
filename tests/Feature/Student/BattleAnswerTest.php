<?php

namespace Tests\Feature\Student;

use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class BattleAnswerTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    protected function setUp(): void
    {
        parent::setUp();

        // KNOWN SCHEMA GAP: the Battle model/controller/MatchmakingService expect the
        // 2025 battles schema (current_round, total_rounds, ended_at, ...) but only the
        // legacy 2024 battles migration exists on this branch. Add the columns the
        // answer flow needs so the leak/race logic can be exercised.
        Schema::table('battles', function (Blueprint $table) {
            foreach (['current_round' => 1, 'total_rounds' => 5] as $column => $default) {
                if (! Schema::hasColumn('battles', $column)) {
                    $table->unsignedInteger($column)->default($default);
                }
            }
        });
    }

    private function battleWithRound(): array
    {
        $course = $this->course($this->teacher());
        $p1 = $this->studentUser();
        $p2 = $this->studentUser();

        $question = Question::forceCreate([
            'course_id' => $course->id,
            'type' => 'single_choice',
            'question_text' => 'Savol',
            'explanation' => 'A is correct because...',
            'points' => 1,
        ]);
        $correct = QuestionOption::forceCreate(['question_id' => $question->id, 'option_text' => 'A', 'is_correct' => true, 'sort_order' => 1]);
        QuestionOption::forceCreate(['question_id' => $question->id, 'option_text' => 'B', 'is_correct' => false, 'sort_order' => 2]);

        $battle = Battle::forceCreate([
            'player1_id' => $p1->id,
            'player2_id' => $p2->id,
            'direction_id' => $course->direction_id,
            'total_rounds' => 5,
            'time_per_question' => 15,
            'status' => 'in_progress',
            'current_round' => 1,
            'started_at' => now(),
        ]);
        BattleRound::forceCreate([
            'battle_id' => $battle->id,
            'question_id' => $question->id,
            'round_number' => 1,
            'started_at' => now()->subSeconds(5),
        ]);

        return [$battle, $p1, $p2, $correct];
    }

    public function test_battle_page_does_not_leak_correct_answer(): void
    {
        [$battle, $p1, $p2, $correct] = $this->battleWithRound();

        // Opponent already answered correctly
        $battle->rounds()->first()->update(['player2_answer' => $correct->id, 'player2_correct' => true, 'player2_time' => 3]);

        $this->actingAs($p1)
            ->get(route('student.battles.show', $battle))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Battle/Match', false)
                ->has('currentRound.question.options', 2)
                ->missing('currentRound.question.options.0.is_correct')
                ->missing('currentRound.question.explanation')
                ->missing('currentRound.question.correct_answer')
                ->missing('currentRound.player2_answer')
                ->missing('currentRound.player2_correct')
                ->has('battle.rounds', 0));
    }

    public function test_player_cannot_answer_same_round_twice(): void
    {
        [$battle, $p1, , $correct] = $this->battleWithRound();

        $this->actingAs($p1)->postJson(route('student.battles.answer', $battle), ['answer' => $correct->id, 'time_taken' => 0])
            ->assertOk()->assertJson(['is_correct' => true]);

        $this->actingAs($p1)->postJson(route('student.battles.answer', $battle), ['answer' => $correct->id, 'time_taken' => 0])
            ->assertStatus(400);
    }

    public function test_client_time_taken_is_not_trusted(): void
    {
        [$battle, $p1, , $correct] = $this->battleWithRound();

        $this->actingAs($p1)->postJson(route('student.battles.answer', $battle), ['answer' => $correct->id, 'time_taken' => 0]);

        $round = $battle->rounds()->first();
        $this->assertGreaterThanOrEqual(4, $round->player1_time, 'time must come from round.started_at, not the client');
        $this->assertLessThanOrEqual(15, $round->player1_time);
    }

    public function test_outsider_cannot_answer(): void
    {
        [$battle, , , $correct] = $this->battleWithRound();

        $this->actingAs($this->studentUser())
            ->postJson(route('student.battles.answer', $battle), ['answer' => $correct->id, 'time_taken' => 1])
            ->assertForbidden();
    }

    public function test_finished_battle_rejects_answers(): void
    {
        [$battle, $p1, , $correct] = $this->battleWithRound();
        $battle->forceFill(['status' => 'finished'])->save();

        $this->actingAs($p1)->postJson(route('student.battles.answer', $battle), ['answer' => $correct->id, 'time_taken' => 1])
            ->assertStatus(400);
    }
}
