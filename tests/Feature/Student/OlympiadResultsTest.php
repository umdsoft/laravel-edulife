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

        // QA3-S-03: one "Top X%" on both pages (#2 of 2 was "Top 100%" there and "Top 0%" here)
        $this->actingAs($weak)->get(route('student.olympiads.results', $olympiad->slug))
            ->assertInertia(fn (Assert $p) => $p->where('ranking.rank', 2)->where('ranking.top_percent', 100));
        $this->actingAs($weak)->get(route('student.olympiads.results.leaderboard', $olympiad->slug))
            ->assertInertia(fn (Assert $p) => $p->where('userPosition.top_percent', 100)->where('hasResults', true));

        // QA3-S-05: someone who did not take part sees the leaderboard without the "Natijalar" link (404 for them)
        $outsider = $this->studentUser();
        $this->actingAs($outsider)->get(route('student.olympiads.results', $olympiad->slug))->assertNotFound();
        $this->actingAs($outsider)->get(route('student.olympiads.results.leaderboard', $olympiad->slug))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('hasResults', false)->where('userPosition', null));
        $this->assertStringContainsString('...(props.hasResults ?', file_get_contents(resource_path('js/Pages/Student/Olympiad/Results/Leaderboard.vue')));
        $this->actingAs($strong)->get(route('student.olympiads.results', $olympiad->slug))
            ->assertInertia(fn (Assert $p) => $p->where('ranking.top_percent', 50));
        $this->actingAs($strong)->get(route('student.olympiads.results.leaderboard', $olympiad->slug))
            ->assertInertia(fn (Assert $p) => $p->where('userPosition.top_percent', 50));
        $this->assertSame(1, \App\Models\OlympiadLiveLeaderboard::topPercentFor(1, 200));
        foreach (['Results/Index.vue' => 'ranking.top_percent', 'Results/Leaderboard.vue' => 'me.top_percent'] as $file => $prop) {
            $this->assertStringContainsString("Top {{ {$prop} }}%", file_get_contents(resource_path("js/Pages/Student/Olympiad/{$file}")));
        }

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

        // its QR link verifies it
        $olympiadCertificate = OlympiadCertificate::where('user_id', $strong->id)->firstOrFail();
        $this->get(route('certificate.verify', $olympiadCertificate->verification_code))
            ->assertInertia(fn (Assert $p) => $p->where('found', true)
                ->where('certificate.subject_label', 'Olimpiada')
                ->where('certificate.number', $olympiadCertificate->certificate_number));

        // the CSV carries the places
        $csv = $this->actingAs($this->admin)->get(route('admin.olympiads.grading.export', $olympiad->id))->streamedContent();
        $this->assertMatchesRegularExpression('/2\.00,3\.00,66\.67,1,/', $csv);

        // QA3-A-02: rows in place order (they were by name); "To'liq reyting" is a page, not raw JSON
        $lines = array_values(array_filter(preg_split('/\R/', trim($csv))));
        $this->assertStringContainsString(',1,', $lines[1]);
        $this->assertStringContainsString(',2,', $lines[2]);
        $this->actingAs($this->admin)->get(route('admin.olympiads.monitor.leaderboard', $olympiad->id))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Olympiad/Leaderboard')
                ->where('leaderboard.0.rank', 1)
                ->where('leaderboard.0.user_name', $strong->name)
                ->where('leaderboard.1.rank', 2)
                ->where('statistics.total_participants', 2));
        $this->actingAs($this->admin)->getJson(route('admin.olympiads.monitor.leaderboard', $olympiad->id))->assertOk()
            ->assertJsonPath('leaderboard.0.rank', 1);
    }
    /** QA3-S-02: the review showed raw LaTeX, only the option letter and never the correct option */
    public function test_answer_review_shows_option_texts_the_choice_and_the_correct_option(): void
    {
        $olympiad = $this->liveOlympiad();
        $student = $this->studentUser();
        $this->actingAs($student)->post(route('student.olympiads.register.store', $olympiad->slug))->assertSessionHas('success');
        $this->actingAs($this->admin)->patch(route('admin.olympiads.status', $olympiad->id), ['status' => 'live'])->assertSessionHas('success');
        $this->sit($olympiad, $student, 1); // first question right, the others wrong
        $this->actingAs($this->admin)->post(route('admin.olympiads.grading.finalize', $olympiad->id))->assertSessionHas('success');

        $props = $this->actingAs($student)->get(route('student.olympiads.results.review', $olympiad->slug))->assertOk()
            ->viewData('page')['props'];
        $this->assertTrue($props['olympiad']['show_correct_answers']);

        foreach ($props['sections'][0]['answers'] as $i => $answer) {
            $bank = QuestionBank::where('question_text', $answer['question_text'])->firstOrFail();
            $this->assertTrue($answer['is_choice']);
            $this->assertSame(collect($bank->options)->pluck('text')->all(), collect($answer['options'])->pluck('text')->all());
            $this->assertSame('A', $answer['options'][0]['letter']);

            $chosen = collect($answer['options'])->where('selected', true);
            $correct = collect($answer['options'])->where('correct', true);
            $this->assertCount(1, $chosen);
            $this->assertSame([$bank->correct_answer], $correct->pluck('id')->values()->all());
            $this->assertSame($i === 0, $chosen->first()['id'] === $bank->correct_answer);
        }

        // results_config can hide the correct answers: nothing about them is sent
        $olympiad->forceFill(['results_config' => ['show_correct_answers' => false]])->save();
        $props = $this->actingAs($student)->get(route('student.olympiads.results.review', $olympiad->slug))->viewData('page')['props'];
        $this->assertFalse($props['olympiad']['show_correct_answers']);
        $this->assertSame([null], collect($props['sections'][0]['answers'])->flatMap(fn ($a) => collect($a['options'])->pluck('correct'))->unique()->values()->all());

        // the page renders question / option texts with the lessons' Markdown + KaTeX renderer
        $vue = file_get_contents(resource_path('js/Pages/Student/Olympiad/Results/Review.vue'));
        $this->assertStringContainsString('<RichContent :html="o.text"', $vue);
        $this->assertStringNotContainsString('v-html', $vue);
    }
}
