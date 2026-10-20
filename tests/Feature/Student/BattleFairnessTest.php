<?php

namespace Tests\Feature\Student;

use App\Models\Battle;
use App\Models\BattleQuestionStat;
use App\Models\Course;
use App\Models\Question;
use App\Models\Setting;
use App\Models\Test;
use App\Models\User;
use App\Services\BattleQuestionSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Battles play teachers' test questions: both players get exactly the same
 * questions in the same order with the same option order, all of one
 * difficulty level, drawn at random without recent repeats
 * (docs/battle/SCORING.md).
 */
class BattleFairnessTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private ?Course $bankCourse = null;

    private ?Test $bankTest = null;

    /** @var array<string, string> question id => correct option id */
    private array $correct = [];

    private function setting(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['group' => 'battle', 'value' => (string) $value, 'type' => 'string']);
    }

    /** @return array<int, string> the new question ids */
    private function bank(int $count, string $difficulty = 'medium', array $courseAttributes = [], bool $activeTest = true): array
    {
        if (! $this->bankCourse || $courseAttributes || ! $activeTest) {
            $course = $this->course($this->teacher(), $courseAttributes);
            $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Bank', 'is_active' => $activeTest]);
            if (! $courseAttributes && $activeTest) {
                [$this->bankCourse, $this->bankTest] = [$course, $test];
            }
        } else {
            [$course, $test] = [$this->bankCourse, $this->bankTest];
        }

        $ids = [];
        foreach (range(1, $count) as $i) {
            $q = Question::forceCreate([
                'course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice',
                'difficulty' => $difficulty, 'question_text' => "{$difficulty} savol {$i}",
            ]);
            foreach (['A', 'B', 'C', 'D'] as $n => $letter) {
                $option = $q->options()->create(['option_text' => $letter, 'is_correct' => $n === 0, 'sort_order' => $n]);
                if ($n === 0) {
                    $this->correct[$q->id] = $option->id;
                }
            }
            $ids[] = $q->id;
        }

        return $ids;
    }

    private function match(User $a, User $b, array $payload = []): Battle
    {
        $this->actingAs($a)->postJson(route('student.battles.find'), $payload)->assertOk()->assertJsonPath('match_found', false);
        $this->actingAs($b)->postJson(route('student.battles.find'), $payload)->assertOk()->assertJsonPath('match_found', true);

        return Battle::where('player2_id', $b->id)->latest()->firstOrFail();
    }

    private function roundProps(User $user, Battle $battle): array
    {
        return $this->actingAs($user)->get(route('student.battles.show', $battle))->assertOk()->viewData('page')['props'];
    }

    /** Play every round: $answers(round number) => [p1 correct?, p2 correct?] */
    private function play(Battle $battle, callable $answers): Battle
    {
        $this->travel(5)->seconds(); // countdown
        foreach (range(1, $battle->total_rounds) as $n) {
            $questionId = $this->roundProps($battle->player1, $battle)['currentRound']['question']['id'];
            [$p1, $p2] = $answers($n);
            foreach ([[$battle->player1, $p1], [$battle->player2, $p2]] as [$player, $right]) {
                $this->actingAs($player)->postJson(route('student.battles.answer', $battle), [
                    'answer' => $right ? $this->correct[$questionId] : 'wrong',
                    'time_taken' => 1,
                ])->assertOk();
            }
        }

        return $battle->fresh();
    }

    // ------------------------------------------------------------ selection

    public function test_both_players_see_identical_questions_and_option_order(): void
    {
        $this->bank(8);
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $battle = $this->match($ali, $vali);

        $this->assertNotNull($battle->question_seed);
        $this->assertSame($battle->rounds()->pluck('question_id')->all(), $battle->question_ids);
        $this->assertCount(5, array_unique($battle->question_ids));

        $this->travel(5)->seconds();
        foreach (range(1, $battle->total_rounds) as $n) {
            $mine = $this->roundProps($ali, $battle)['currentRound'];
            $theirs = $this->roundProps($vali, $battle)['currentRound'];
            $reload = $this->roundProps($ali, $battle)['currentRound']; // a reconnect

            $this->assertSame($n, $mine['round_number']);
            $this->assertSame($battle->question_ids[$n - 1], $mine['question']['id']);
            $this->assertSame($mine['question']['id'], $theirs['question']['id']);
            $this->assertSame(array_column($mine['question']['options'], 'id'), array_column($theirs['question']['options'], 'id'));
            $this->assertSame(array_column($mine['question']['options'], 'id'), array_column($reload['question']['options'], 'id'));
            $this->assertSame($mine['question_value'], $theirs['question_value']);
            $this->assertGreaterThan(0, $mine['question_value']);

            foreach ([$ali, $vali] as $player) {
                $this->actingAs($player)->postJson(route('student.battles.answer', $battle), ['answer' => 'wrong', 'time_taken' => 1])->assertOk();
            }
        }
    }

    public function test_the_option_order_is_shuffled_by_the_seed(): void
    {
        [$id] = $this->bank(1);
        $question = Question::with('options')->find($id);
        $selector = app(BattleQuestionSelector::class);

        $orders = collect(range(1, 12))->map(fn ($seed) => $selector->orderedOptions(new Battle(['question_seed' => $seed]), $question)->pluck('id')->implode(','));
        $this->assertGreaterThan(1, $orders->unique()->count(), 'different battles get different option orders');
        $this->assertSame($orders[3], $selector->orderedOptions(new Battle(['question_seed' => 4]), $question)->pluck('id')->implode(','));
    }

    public function test_every_question_of_a_battle_has_one_level_matched_to_elo(): void
    {
        $this->setting('battle_questions_count', 3);
        $easy = $this->bank(4, 'easy');
        $medium = $this->bank(4, 'medium');
        $hard = $this->bank(4, 'hard');

        // new players (ELO 1000): automatic = easy
        $battle = $this->match($this->studentUser(), $this->studentUser());
        $this->assertSame('easy', $battle->difficulty);
        $this->assertEmpty(array_diff($battle->question_ids, $easy));

        // strong players: automatic = hard
        [$a, $b] = [$this->studentUser(), $this->studentUser()];
        $a->studentProfile->update(['elo_rating' => 1600]);
        $b->studentProfile->update(['elo_rating' => 1580]);
        $battle = $this->match($a, $b);
        $this->assertSame('hard', $battle->difficulty);
        $this->assertEmpty(array_diff($battle->question_ids, $hard));

        // chosen in the lobby
        $battle = $this->match($this->studentUser(), $this->studentUser(), ['difficulty' => 'medium']);
        $this->assertSame('medium', $battle->difficulty);
        $this->assertSame('medium', $battle->requested_difficulty);
        $this->assertEmpty(array_diff($battle->question_ids, $medium));
        $this->assertSame(['medium'], Question::whereIn('id', $battle->question_ids)->distinct()->pluck('difficulty')->all());
    }

    public function test_the_level_follows_the_empirical_solve_rate(): void
    {
        $this->setting('battle_questions_count', 3);
        $labelledMedium = $this->bank(3, 'medium');
        $this->bank(3, 'easy');
        // nobody solves the "medium" ones: with enough evidence they are hard
        foreach ($labelledMedium as $id) {
            BattleQuestionStat::create(['question_id' => $id, 'attempts' => 60, 'correct' => 6]);
        }

        $battle = $this->match($this->studentUser(), $this->studentUser(), ['difficulty' => 'hard']);
        $this->assertSame('hard', $battle->difficulty);
        $this->assertEqualsCanonicalizing($labelledMedium, $battle->question_ids);
    }

    public function test_automatic_level_falls_back_to_the_nearest_playable_level(): void
    {
        $this->bank(5, 'medium');
        $this->bank(2, 'easy'); // not enough for a battle

        $battle = $this->match($this->studentUser(), $this->studentUser());
        $this->assertSame('medium', $battle->difficulty);
    }

    public function test_not_enough_questions_of_a_level_gives_an_uzbek_message(): void
    {
        $this->bank(5, 'medium');
        $this->bank(2, 'hard');

        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'), ['difficulty' => 'hard'])
            ->assertStatus(422)
            ->assertJsonPath('match_found', false)
            ->assertJsonPath('message', "\"Qiyin\" darajasida jang uchun savollar hali yetarli emas: 2 ta bor, kamida 3 ta kerak. Boshqa qiyinlikni yoki \"Avtomatik\"ni tanlang.");

    }

    public function test_a_battle_needs_enough_questions_of_one_level(): void
    {
        // six questions, but no single level has three
        $this->bank(2, 'easy');
        $this->bank(2, 'medium');
        $this->bank(2, 'hard');

        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'))
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_starts_with($m, 'Jang uchun savollar hali yetarli emas (bir xil qiyinlikdagi'));
        $this->assertSame(0, Battle::count());
    }

    public function test_only_active_tests_of_published_courses_count(): void
    {
        $this->bank(2);
        $this->bank(5, 'medium', ['status' => 'draft']);
        $this->bank(5, 'medium', [], false);
        $course = $this->bankCourse;
        // a multiple-choice question is not a one-click battle question
        Question::forceCreate(['course_id' => $course->id, 'test_id' => $this->bankTest->id, 'type' => 'multiple_choice', 'question_text' => 'MC'])
            ->options()->create(['option_text' => 'A', 'is_correct' => true]);

        $this->assertSame(2, app(BattleQuestionSelector::class)->pool()->count());
        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'))->assertStatus(422);
    }

    public function test_players_who_chose_different_levels_do_not_meet(): void
    {
        $this->bank(4, 'easy');
        $this->bank(4, 'hard');

        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'), ['difficulty' => 'easy'])->assertJsonPath('match_found', false);
        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'), ['difficulty' => 'hard'])->assertJsonPath('match_found', false);
        $this->assertSame(2, Battle::where('status', 'waiting')->count());

        // "automatic" meets either; the waiting player's choice is played
        $this->actingAs($this->studentUser())->postJson(route('student.battles.find'))->assertJsonPath('match_found', true);
        $this->assertSame('easy', Battle::where('status', 'in_progress')->value('difficulty'));
    }

    public function test_questions_are_random_across_battles_and_reproducible_from_the_seed(): void
    {
        $ids = $this->bank(30);
        $selector = app(BattleQuestionSelector::class);

        $draws = collect(range(1, 8))->map(fn ($seed) => $selector->select(null, 'medium', 1000, [], 5, 3, $seed));
        $this->assertGreaterThan(1, $draws->map(fn ($d) => implode(',', array_column($d['questions'], 'id')))->unique()->count());
        $this->assertGreaterThan(5, $draws->flatMap(fn ($d) => array_column($d['questions'], 'id'))->unique()->count());
        $this->assertSame(
            array_column($draws[2]['questions'], 'id'),
            array_column($selector->select(null, 'medium', 1000, [], 5, 3, 3)['questions'], 'id'),
            'the same seed draws the same questions in the same order',
        );
        $this->assertEmpty(array_diff(array_column($draws[0]['questions'], 'id'), $ids));

        // real battles get different seeds
        $first = $this->match($this->studentUser(), $this->studentUser());
        $second = $this->match($this->studentUser(), $this->studentUser());
        $this->assertNotSame($first->question_seed, $second->question_seed);
    }

    public function test_questions_seen_recently_are_not_repeated(): void
    {
        $this->setting('battle_questions_count', 3);
        $this->bank(7);
        $ali = $this->studentUser();

        $first = $this->match($ali, $this->studentUser());
        $this->play($first, fn () => [true, false]);
        $second = $this->match($ali, $this->studentUser());
        $this->assertEmpty(array_intersect($first->question_ids, $second->question_ids), 'Ali does not get the same questions again');
        $this->play($second, fn () => [true, false]);

        // only one unseen question is left: it comes first, then the ones seen longest ago
        $third = $this->match($this->studentUser(), $ali);
        $unseen = array_values(array_diff(Question::pluck('id')->all(), $first->question_ids, $second->question_ids));
        $this->assertSame($unseen[0], $third->question_ids[0]);
        $this->assertEmpty(array_diff(array_slice($third->question_ids, 1), $first->question_ids));
    }

    // -------------------------------------------------------------- scoring

    public function test_end_to_end_unique_solves_score_more_and_statistics_grow(): void
    {
        $this->setting('battle_scoring_speed_bonus', 0); // exact numbers
        $this->setting('battle_questions_count', 3);
        $this->bank(3);
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $battle = $this->match($ali, $vali);

        // round 1: both solve, round 2: only Ali, round 3: only Vali
        $battle = $this->play($battle, fn ($n) => [$n !== 3, $n !== 2]);
        $rounds = $battle->rounds()->get()->keyBy('round_number');
        $value = $rounds[1]->question_value; // no evidence yet: all three have the "medium" prior
        $this->assertSame(180, $value, '100 × (1 + 2 × (1 − 0.60))');

        $this->assertSame([90, 90, 2], [$rounds[1]->player1_points, $rounds[1]->player2_points, $rounds[1]->solver_count]);
        $this->assertTrue($rounds[1]->is_draw);
        $this->assertSame([180, 0, 1], [$rounds[2]->player1_points, $rounds[2]->player2_points, $rounds[2]->solver_count]);
        $this->assertSame([0, 180, 1], [$rounds[3]->player1_points, $rounds[3]->player2_points, $rounds[3]->solver_count]);
        $this->assertSame(270, $battle->player1_score);
        $this->assertSame(270, $battle->player2_score);
        $this->assertTrue($battle->is_draw);
        $this->assertSame('completed', $battle->status);

        foreach ([1 => 2, 2 => 1, 3 => 1] as $n => $solvers) {
            $stat = BattleQuestionStat::where('question_id', $rounds[$n]->question_id)->firstOrFail();
            $this->assertSame([2, $solvers], [$stat->attempts, $stat->correct]);
        }

        // the result page explains every round
        $this->actingAs($ali)->get(route('student.battles.show', $battle))->assertInertia(fn (Assert $p) => $p
            ->where('battle.difficulty', 'medium')
            ->where('battle.difficulty_label', "O'rta")
            ->where('battle.rounds.1.question_value', 180)
            ->where('battle.rounds.1.solver_count', 1)
            ->where('battle.rounds.1.player1_points', 180)
            ->where('battle.rounds.0.solve_rate', 60)
            ->where('scoring.base', 100));
    }

    public function test_the_match_is_decided_by_points_and_the_winner_gains_elo(): void
    {
        $this->setting('battle_scoring_speed_bonus', 0);
        $this->setting('battle_questions_count', 3);
        [$hardest, $easiest] = $this->bank(3);
        // p̂ = (36 + 10·0.6) / (90 + 10) = 0.42 → 216;  (63 + 6) / 100 = 0.69 → 162;  no evidence: 0.60 → 180
        BattleQuestionStat::create(['question_id' => $hardest, 'attempts' => 90, 'correct' => 36]);
        BattleQuestionStat::create(['question_id' => $easiest, 'attempts' => 90, 'correct' => 63]);
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $battle = $this->match($ali, $vali);
        $this->assertSame('medium', $battle->difficulty);
        $roundOf = array_flip($battle->question_ids); // question id => index

        // one round each and one nobody solves: Ali's question was the rarer one
        $battle = $this->play($battle, fn ($n) => [$n - 1 === $roundOf[$hardest], $n - 1 === $roundOf[$easiest]]);

        $this->assertSame(216, $battle->player1_score);
        $this->assertSame(162, $battle->player2_score);
        $this->assertSame($ali->id, $battle->winner_id);
        $this->assertGreaterThan(0, $battle->player1_elo_change);
        $this->assertLessThan(0, $battle->player2_elo_change);
    }

    public function test_the_lobby_shows_questions_per_level(): void
    {
        $this->bank(4, 'easy');
        $this->bank(3, 'hard');

        $this->actingAs($this->studentUser())->get(route('student.battles.index'))->assertInertia(fn (Assert $p) => $p
            ->component('Student/Battle/Index')
            ->where('questionLevels.all', ['easy' => 4, 'medium' => 0, 'hard' => 3])
            ->where('minRounds', 3)
            ->where('autoLevel', 'easy')
            ->has('scoring.base'));
    }
}
