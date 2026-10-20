<?php

namespace Tests\Feature\Admin;

use App\Models\Olympiad;
use App\Models\OlympiadQuestion;
use App\Models\OlympiadType;
use App\Models\QuestionBank;
use App\Models\User;
use Database\Seeders\Exam\ExamSeeder;
use Database\Seeders\OlympiadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Olympiad sections take bank questions by an explicitly chosen difficulty (docs/exam/03 §2.2). */
class OlympiadBankDifficultyTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_is_filled_with_the_chosen_level_only(): void
    {
        $this->seed([OlympiadSeeder::class, ExamSeeder::class]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->post(route('admin.olympiads.store'), [
            'title' => 'Matematika olimpiadasi',
            'olympiad_type_id' => OlympiadType::where('name', 'math')->value('id'),
            'registration_fee' => 0,
            'olympiad_start_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'olympiad_end_at' => now()->addDay()->addHours(3)->format('Y-m-d\TH:i'),
            'create_default_sections' => true,
        ])->assertSessionHasNoErrors();
        $olympiad = Olympiad::firstOrFail();
        $section = $olympiad->sections()->firstOrFail();
        $assign = fn (array $data) => $this->actingAs($admin)->post(route('admin.olympiads.sections.questions.assign', [$olympiad->id, $section->id]), $data);

        $page = $this->actingAs($admin)->get(route('admin.olympiads.edit', $olympiad->id))->assertOk();
        $levels = $page->viewData('page')['props']['bankLevels'];
        $this->assertSame([1, 2, 3, 4, 5], array_keys($levels));
        $level = (int) collect($levels)->filter(fn ($n) => $n >= 2)->keys()->first();
        $this->assertGreaterThan(0, $level, 'the demo bank has a level with at least two questions');

        $assign(['count' => 2])->assertSessionHasErrors('difficulty');
        $assign(['count' => 2, 'difficulty' => 'extreme'])->assertSessionHasErrors('difficulty');
        $this->assertSame(0, OlympiadQuestion::query()->count());

        $assign(['count' => 2, 'difficulty' => (string) $level])->assertSessionHas('success');
        $ids = OlympiadQuestion::where('section_id', $section->id)->pluck('question_id');
        $this->assertCount(2, $ids);
        $this->assertSame([$level], QuestionBank::query()->whereIn('id', $ids)->pluck('difficulty_level')->unique()->values()->all());

        // asking for more than the level has: what is there, and a note
        $assign(['count' => 100, 'difficulty' => (string) $level])->assertSessionHas('success', fn ($m) => str_contains($m, 'bu qiyinlikda'));
        $this->assertSame($levels[$level], OlympiadQuestion::where('section_id', $section->id)->count());
        $this->actingAs($admin)->get(route('admin.olympiads.edit', $olympiad->id))
            ->assertInertia(fn (Assert $p) => $p->where("bankLevels.{$level}", 0));
    }
}
