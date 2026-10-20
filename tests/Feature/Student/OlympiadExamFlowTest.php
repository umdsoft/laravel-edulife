<?php

namespace Tests\Feature\Student;

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadQuestion;
use App\Models\OlympiadType;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\CoinService;
use Database\Seeders\Exam\ExamSeeder;
use Database\Seeders\OlympiadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * The olympiad exam could not start: seven OlympiadExamController methods
 * did not exist, sections had no way to get questions, and the coin payment
 * used a non-existent users.coins column with a client-chosen amount.
 */
class OlympiadExamFlowTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function liveOlympiad(int $fee = 0): Olympiad
    {
        $this->seed([OlympiadSeeder::class, ExamSeeder::class]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.olympiads.store'), [
            'title' => 'Matematika olimpiadasi',
            'olympiad_type_id' => OlympiadType::where('name', 'math')->value('id'),
            'registration_fee' => $fee,
            'olympiad_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'olympiad_end_at' => now()->addHours(3)->format('Y-m-d\TH:i'),
            'anti_cheat_config' => ['fullscreen_required' => false, 'device_lock' => true, 'max_tab_switches' => 3, 'max_warnings' => 5],
            'create_default_sections' => true,
        ])->assertSessionHasNoErrors();

        $olympiad = Olympiad::firstOrFail();
        $section = $olympiad->sections()->firstOrFail();

        // admin fills the section from the approved question bank
        $this->actingAs($admin)->post(route('admin.olympiads.sections.questions.assign', [$olympiad->id, $section->id]), ['count' => 3, 'difficulty' => 'any'])
            ->assertSessionHas('success');
        $this->assertSame(3, OlympiadQuestion::where('section_id', $section->id)->count());

        foreach (['upcoming', 'registration_open'] as $status) {
            $this->actingAs($admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => $status])->assertSessionHas('success');
        }

        return $olympiad->fresh();
    }

    public function test_student_registers_takes_the_exam_and_sees_results(): void
    {
        $olympiad = $this->liveOlympiad();
        $student = $this->studentUser();

        $this->actingAs($student)->post(route('student.olympiads.register.store', $olympiad->slug))->assertSessionHas('success');

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => 'live'])->assertSessionHas('success');

        $this->actingAs($student)->get(route('student.olympiads.preflight', $olympiad->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Olympiad/Exam/PreFlight')->where('canStart.can_start', true));

        $this->actingAs($student)->postJson(route('student.olympiads.start', $olympiad->slug), [
            'device_fingerprint' => hash('sha256', 'browser'),
            'fingerprint_data' => ['canvas_fingerprint' => 'abc', 'screen_resolution' => '1920x1080', 'timezone' => 'Asia/Tashkent', 'platform' => 'Win32', 'languages' => 'uz,ru', 'user_agent' => 'x'],
        ])->assertOk()->assertJsonPath('success', true);

        $attempt = OlympiadAttempt::firstOrFail();
        $this->assertSame(1, $attempt->sectionAttempts()->count(), 'section attempts must not be duplicated');

        $page = $this->actingAs($student)->get(route('student.olympiads.exam', $olympiad->slug))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Olympiad/Exam/Index'))
            ->viewData('page')['props'];
        $token = $page['attempt']['session_token'];
        $sectionId = $page['sections'][0]['id'];

        $section = $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->getJson(route('student.olympiads.exam.section', [$olympiad->slug, $sectionId]))->assertOk()->json();
        $this->assertCount(3, $section['questions']);
        $this->assertStringNotContainsString('is_correct', json_encode($section));
        $this->assertIsString($section['questions'][0]['options'][0]);

        // answer every question correctly, by option index as the page does
        foreach ($section['questions'] as $q) {
            $bank = QuestionBank::findOrFail($q['question_id']);
            $index = collect($bank->options)->search(fn ($o) => $o['id'] === $bank->correct_answer);
            $this->actingAs($student)->withHeader('X-Session-Token', $token)
                ->postJson(route('student.olympiads.exam.answer', $olympiad->slug), [
                    'section_id' => $sectionId, 'question_id' => $q['id'], 'answer' => $index, 'time_spent' => 10,
                ])->assertOk();
        }

        $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->postJson(route('student.olympiads.exam.flag', $olympiad->slug), ['question_id' => $section['questions'][0]['id']])
            ->assertOk()->assertJsonPath('is_flagged', true);

        // reloading keeps the chosen answers (as indexes)
        $again = $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->getJson(route('student.olympiads.exam.section', [$olympiad->slug, $sectionId]))->json();
        $this->assertNotNull($again['questions'][0]['user_answer']);

        $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->postJson(route('student.olympiads.exam.section.complete', [$olympiad->slug, $sectionId]))
            ->assertOk()->assertJsonPath('next_section_id', null);

        $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->postJson(route('student.olympiads.exam.submit', $olympiad->slug))->assertOk()->assertJsonPath('success', true);

        $attempt->refresh();
        $this->assertTrue($attempt->is_completed);
        $this->assertEquals(100, round((float) $attempt->score_percent));

        $this->actingAs($student)->get(route('student.olympiads.results', $olympiad->slug))->assertOk();

        // a wrong token is refused
        $this->actingAs($student)->withHeader('X-Session-Token', 'nope')
            ->getJson(route('student.olympiads.exam.section', [$olympiad->slug, $sectionId]))->assertStatus(401);
    }

    public function test_paid_registration_is_paid_with_coins_at_the_server_price(): void
    {
        $olympiad = $this->liveOlympiad(15000);
        $student = $this->studentUser();
        app(CoinService::class)->addCoins($student, 200, 'test', 'Sinov');

        $this->actingAs($student)->post(route('student.olympiads.register.store', $olympiad->slug))->assertRedirect();
        $registration = $olympiad->registrations()->firstOrFail();
        $this->assertSame('pending_payment', $registration->status);

        $this->actingAs($student)->get(route('student.olympiads.payment', [$olympiad->slug, $registration->id]))
            ->assertInertia(fn (Assert $p) => $p->where('coinsNeeded', 150));

        // card is not connected
        $this->actingAs($student)->post(route('student.olympiads.payment.process', [$olympiad->slug, $registration->id]), ['payment_method' => 'card'])
            ->assertSessionHas('error');

        // the client cannot choose the amount
        $this->actingAs($student)->post(route('student.olympiads.payment.process', [$olympiad->slug, $registration->id]), [
            'payment_method' => 'coins', 'coins_amount' => 1,
        ])->assertSessionHas('success');

        $this->assertSame('confirmed', $registration->fresh()->status);
        $this->assertSame(50, $student->fresh()->coins);
    }
}
