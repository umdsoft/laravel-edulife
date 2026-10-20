<?php

namespace Tests\Feature\Media;

use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Battle questions reach Match.vue with their formulas, pictures and graph blocks intact
 * (the page renders them with MathText — resources/js/Utils/__tests__/battleRender.test.js).
 */
class BattleMathPayloadTest extends TestCase
{
    use CreatesCourses, RefreshDatabase;

    public function test_match_payload_keeps_latex_images_and_graphs(): void
    {
        $course = $this->course($this->teacher());
        [$p1, $p2] = [$this->studentUser(), $this->studentUser()];
        $stem = "Tezlik \$v = \\frac{s}{t}\$. Grafikka qarang:\n```graph\n{\"x\":[0,5],\"y\":[0,10],\"functions\":[{\"expr\":\"2x\"}]}\n```";
        $image = '![chizma](/storage/questions/2026/10/0b6a4b0e-1c1d-4f7e-9b0a-2d3c4e5f6a7b.webp)';
        $q = Question::forceCreate(['course_id' => $course->id, 'type' => 'single_choice', 'difficulty' => 'medium', 'question_text' => $stem, 'points' => 1]);
        QuestionOption::forceCreate(['question_id' => $q->id, 'option_text' => '$2\ \mathrm{m/s}$', 'is_correct' => true, 'sort_order' => 1]);
        QuestionOption::forceCreate(['question_id' => $q->id, 'option_text' => $image, 'is_correct' => false, 'sort_order' => 2]);

        $battle = Battle::forceCreate([
            'player1_id' => $p1->id, 'player2_id' => $p2->id, 'direction_id' => $course->direction_id,
            'total_rounds' => 5, 'time_per_question' => 15, 'status' => 'in_progress', 'current_round' => 1, 'started_at' => now(),
        ]);
        BattleRound::forceCreate(['battle_id' => $battle->id, 'question_id' => $q->id, 'round_number' => 1, 'started_at' => now()->subSeconds(2)]);

        $this->actingAs($p1)->get(route('student.battles.show', $battle))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Battle/Match', false)
                ->where('currentRound.question.question_text', $stem)
                ->where('currentRound.question.options', fn ($options) => collect($options)->pluck('content')->sort()->values()->all()
                    === collect(['$2\ \mathrm{m/s}$', $image])->sort()->values()->all()));
    }
}
