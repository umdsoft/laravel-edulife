<?php

namespace Tests\Feature\Student;

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadCertificate;
use App\Models\OlympiadType;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserOlympiadHistory;
use Database\Seeders\Exam\ExamSeeder;
use Database\Seeders\OlympiadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA2-S-14: attempts stored the weighted percentage as the score ("40.00 / 5.00"),
 * every rank stayed 0 and the leaderboard order was wrong.
 * QA2-A-01: "Natijalarni yakunlash" failed with a lazy-loading error shown raw.
 * QA2-S-20: "Javoblarni ko'rish" was a 500 once the olympiad was completed.
 */
class OlympiadResultsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private User $admin;

    private function liveOlympiad(): Olympiad
    {
        $this->seed([OlympiadSeeder::class, ExamSeeder::class]);
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($this->admin)->post(route('admin.olympiads.store'), [
            'title' => 'Fizika olimpiadasi',
            'olympiad_type_id' => OlympiadType::where('name', 'math')->value('id'),
            'registration_fee' => 0,
            'olympiad_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'olympiad_end_at' => now()->addHours(3)->format('Y-m-d\TH:i'),
            'anti_cheat_config' => ['fullscreen_required' => false, 'device_lock' => true, 'max_tab_switches' => 3, 'max_warnings' => 5],
            'reward_config' => ['coins_per_correct' => 1, 'top_10_coins' => 10],
            'create_default_sections' => true,
        ])->assertSessionHasNoErrors();

        $olympiad = Olympiad::firstOrFail();
        $section = $olympiad->sections()->firstOrFail();

        // an olympiad without questions cannot open registration (QA2-A-13)
        $this->actingAs($this->admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => 'upcoming'])->assertSessionHas('success');
        $this->actingAs($this->admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => 'registration_open'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'savollar biriktiring'));

        $this->actingAs($this->admin)->post(route('admin.olympiads.sections.questions.assign', [$olympiad->id, $section->id]), ['count' => 3])
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => 'registration_open'])->assertSessionHas('success');

        return $olympiad->fresh();
    }

    /**
     * Takes the exam with $correct right answers out of three and submits it.
     */
    private function sit(Olympiad $olympiad, User $student, int $correct): void
    {
        $this->actingAs($student)->postJson(route('student.olympiads.start', $olympiad->slug), [
            'device_fingerprint' => hash('sha256', 'browser-' . $student->id),
            'fingerprint_data' => ['canvas_fingerprint' => substr(md5($student->id), 0, 16), 'screen_resolution' => '1920x1080', 'timezone' => 'Asia/Tashkent', 'platform' => 'Win32'],
        ])->assertOk()->assertJsonPath('success', true);

        $props = $this->actingAs($student)->get(route('student.olympiads.exam', $olympiad->slug))->viewData('page')['props'];
        $token = $props['attempt']['session_token'];
        $sectionId = $props['sections'][0]['id'];

        $section = $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->getJson(route('student.olympiads.exam.section', [$olympiad->slug, $sectionId]))->json();

        foreach ($section['questions'] as $i => $q) {
            $bank = QuestionBank::findOrFail($q['question_id']);
            $right = collect($bank->options)->search(fn ($o) => $o['id'] === $bank->correct_answer);
            $answer = $i < $correct ? $right : ($right === 0 ? 1 : 0);
            $this->actingAs($student)->withHeader('X-Session-Token', $token)
                ->postJson(route('student.olympiads.exam.answer', $olympiad->slug), [
                    'section_id' => $sectionId, 'question_id' => $q['id'], 'answer' => $answer, 'time_spent' => 10,
                ])->assertOk();
        }

        $this->actingAs($student)->withHeader('X-Session-Token', $token)
            ->postJson(route('student.olympiads.exam.submit', $olympiad->slug))->assertOk()->assertJsonPath('success', true);
    }

    public function test_scores_are_points_ranks_follow_the_score_and_finalizing_rewards_once(): void
    {
        Storage::fake('public');
        $olympiad = $this->liveOlympiad();
        $weak = $this->studentUser();
        $strong = $this->studentUser();

        foreach ([$weak, $strong] as $student) {
            $this->actingAs($student)->post(route('student.olympiads.register.store', $olympiad->slug))->assertSessionHas('success');
        }
        $this->actingAs($this->admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => 'live'])->assertSessionHas('success');

        $this->sit($olympiad, $weak, 1);
        $this->sit($olympiad, $strong, 2);

        $weakAttempt = OlympiadAttempt::where('user_id', $weak->id)->firstOrFail();
        $strongAttempt = OlympiadAttempt::where('user_id', $strong->id)->firstOrFail();

        // points out of points, not the weighted percentage
        $this->assertEquals(1, (float) $weakAttempt->total_raw_score);
        $this->assertEquals(2, (float) $strongAttempt->total_raw_score);
        $this->assertEquals(3, (float) $strongAttempt->total_max_score);
        $this->assertSame('2.00 / 3.00', $strongAttempt->formatted_score);

        // ranks are live, before finalizing
        $this->assertSame(1, $strongAttempt->rank);
        $this->assertSame(2, $weakAttempt->rank);

        $this->actingAs($strong)->get(route('student.olympiads.results', $olympiad->slug))->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->where('attempt.total_score', '2.00')
                ->where('attempt.max_score', '3.00')
                ->where('attempt.duration', fn ($d) => is_string($d) && $d !== '')
                ->where('ranking.rank', 1)
                ->where('ranking.total_participants', 2));

        $this->actingAs($weak)->get(route('student.olympiads.results.leaderboard', $olympiad->slug))->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->where('leaderboard.0.rank', 1)
                ->where('leaderboard.0.score', '2.00')
                ->where('leaderboard.1.rank', 2)
                ->where('leaderboard.1.is_current_user', true)
                ->where('statistics.total_participants', 2));

        // QA2-S-04: the live olympiad stays in the list and the history shows the participation
        $this->actingAs($strong)->get(route('student.olympiads.index'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('olympiads.0.slug', $olympiad->slug)
                ->where('olympiads.0.status', 'live')
                ->where('olympiads.0.is_registered', true));
        $this->actingAs($strong)->get(route('student.olympiad-history'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('statistics.total_participated', 1)
                ->where('history.0.status', 'participated'));

        // finalize (twice: the second run must not pay out again)
        $this->actingAs($this->admin)->post(route('admin.olympiads.grading.finalize', $olympiad->id))
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.olympiads.grading.finalize', $olympiad->id))
            ->assertSessionHas('success');

        $this->assertSame(Olympiad::STATUS_COMPLETED, $olympiad->fresh()->status);
        // 1 coin per correct answer + 10 for the top 10
        $this->assertSame(12, $strong->fresh()->coins);
        $this->assertSame(11, $weak->fresh()->coins);

        $history = UserOlympiadHistory::where('user_id', $strong->id)->firstOrFail();
        $this->assertSame(1, $history->rank);
        $this->assertSame(2, $history->total_participants);
        $this->assertSame(12, (int) $history->coins_earned);

        // finished olympiads are still listed (and searchable); the history counts them
        $this->actingAs($strong)->get(route('student.olympiads.index', ['search' => 'Fizika']))
            ->assertInertia(fn (Assert $p) => $p->has('olympiads', 1)->where('olympiads.0.status', 'completed'));
        $this->actingAs($strong)->get(route('student.olympiads.index', ['search' => 'Kimyo']))
            ->assertInertia(fn (Assert $p) => $p->has('olympiads', 0));
        $this->actingAs($strong)->get(route('student.olympiad-history'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('statistics.total_completed', 1)
                ->where('statistics.best_rank', 1)
                ->where('history.0.status', 'completed'));

        // the review loads once the olympiad is completed
        $this->actingAs($weak)->get(route('student.olympiads.results.review', $olympiad->slug))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Olympiad/Results/Review')->has('sections.0.answers', 3));

        // QA2-S-15: the olympiad certificate is a real PDF (SVG QR code, no Imagick), made once
        $response = $this->actingAs($strong)->get(route('student.olympiads.certificate.download', $olympiad->slug))->assertOk();
        $this->assertStringStartsWith('%PDF', file_get_contents($response->getFile()->getPathname()));
        $this->actingAs($strong)->get(route('student.olympiads.certificate.download', $olympiad->slug))->assertOk();
        $this->assertSame(1, OlympiadCertificate::where('user_id', $strong->id)->count());

        // the CSV carries the places
        $csv = $this->actingAs($this->admin)->get(route('admin.olympiads.grading.export', $olympiad->id))->streamedContent();
        $this->assertMatchesRegularExpression('/2\.00,3\.00,66\.67,1,/', $csv);
    }
}
