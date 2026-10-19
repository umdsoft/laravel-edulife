<?php

namespace Tests\Feature\Student;

use App\Models\Battle;
use App\Models\CoinTransaction;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Test;
use App\Models\User;
use App\Services\CoinService;
use App\Services\RewardSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA3-A-01: the XP / Coin / Battle values on /admin/settings were saved but
 * the rewards were hard-coded. Without a setting row the rewards stay what
 * they were; an admin edit changes the next reward.
 */
class RewardSettingsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function setting(string $key, string $group, $value, string $type = 'integer'): void
    {
        Setting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => (string) $value, 'type' => $type]);
    }

    /** Opening the lesson starts a 1-day streak: +5 XP on top of the lesson's XP. */
    private const STREAK_BONUS = 5;

    private function adminSaves(array $values): void
    {
        $settings = [];
        foreach ($values as $key => $value) {
            [$group] = explode('_', $key, 2);
            $group = str_starts_with($key, 'elo_') ? 'battle' : $group;
            $settings[] = ['key' => "{$group}.{$key}", 'value' => $value];
        }

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->put(route('admin.settings.update'), ['settings' => $settings])
            ->assertSessionHas('success');
    }

    private function completeLesson(User $student, string $type): LessonProgress
    {
        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course), ['type' => $type]);
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        $this->actingAs($student)->get(route('student.learn.lesson', [$course, $lesson]))->assertOk();
        $this->actingAs($student)->postJson(route('student.learn.progress.complete', $lesson))->assertOk();

        return LessonProgress::where('user_id', $student->id)->where('lesson_id', $lesson->id)->firstOrFail();
    }

    public function test_without_settings_the_rewards_are_unchanged(): void
    {
        $rewards = app(RewardSettings::class);

        $this->assertSame(8, $rewards->lessonXp('text'));
        $this->assertSame(5, $rewards->lessonXp('video'));
        $this->assertSame(15, $rewards->lessonXp('quiz'));
        $this->assertSame(40, $rewards->testXp(95));
        $this->assertSame(25, $rewards->testXp(75));
        $this->assertSame(10, $rewards->testXp(55));
        $this->assertSame(100, $rewards->battleWinXp());
        $this->assertSame(0, $rewards->lessonCoins());
        $this->assertSame(10, $rewards->battleWinCoins());
        $this->assertSame(5, $rewards->battleRounds());
        $this->assertSame(20, $rewards->eloKFactor());

        $student = $this->studentUser();
        $progress = $this->completeLesson($student, 'text');
        $this->assertSame(8 + self::STREAK_BONUS, $progress->xp_amount);
        $this->assertSame(0, (int) $student->fresh()->studentProfile->coins);
    }

    public function test_lesson_xp_and_coins_follow_the_settings(): void
    {
        $this->setting('xp_per_lesson', 'xp', 8);
        $this->setting('coin_per_lesson', 'coin', 0);
        $this->assertSame(8, app(RewardSettings::class)->lessonXp('text')); // now cached

        $this->adminSaves(['xp_per_lesson' => 30, 'coin_per_lesson' => 5]);

        $student = $this->studentUser();
        $text = $this->completeLesson($student, 'text');
        $this->assertSame(30 + self::STREAK_BONUS, $text->xp_amount);
        $this->assertSame(5, (int) $student->fresh()->studentProfile->coins);
        $this->assertDatabaseHas('coin_transactions', ['user_id' => $student->id, 'source' => 'lesson_complete', 'amount' => 5]);

        // other lesson types keep their ratio to a text lesson (5/8)
        $video = $this->completeLesson($student, 'video');
        $this->assertSame(19 + self::STREAK_BONUS, $video->xp_amount);

        // completing it again gives nothing
        $this->actingAs($student)->postJson(route('student.learn.progress.complete', $text->lesson))->assertOk();
        $this->assertSame(10, (int) $student->fresh()->studentProfile->coins);
    }

    public function test_premium_xp_multiplier_applies_to_premium_subscribers(): void
    {
        $this->setting('xp_multiplier_premium', 'xp', '1.5', 'string');
        $plan = SubscriptionPlan::forceCreate(['name' => 'Premium', 'slug' => 'premium', 'price' => 1000, 'interval' => 'month']);
        $premium = $this->studentUser();
        Subscription::forceCreate(['user_id' => $premium->id, 'plan_id' => $plan->id, 'status' => 'active', 'started_at' => now(), 'ends_at' => now()->addMonth()]);

        $this->assertSame('premium', app(RewardSettings::class)->tier($premium));
        $this->assertSame(20, $this->completeLesson($premium, 'text')->xp_amount); // (8 + 5) × 1.5
        $this->assertSame(8 + self::STREAK_BONUS, $this->completeLesson($this->studentUser(), 'text')->xp_amount);
    }

    private function playBattle(User $winner, User $loser): Battle
    {
        $course = $this->course($this->teacher());
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Bank', 'is_active' => true]);
        $correct = [];
        foreach (range(1, 5) as $i) {
            $q = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => "{$i} + {$i} = ?"]);
            $correct[$q->id] = $q->options()->create(['option_text' => (string) ($i * 2), 'is_correct' => true])->id;
            $q->options()->create(['option_text' => '0', 'is_correct' => false]);
        }

        $this->actingAs($winner)->postJson(route('student.battles.find'))->assertOk();
        $this->actingAs($loser)->postJson(route('student.battles.find'))->assertOk()->assertJsonPath('match_found', true);
        $battle = Battle::latest()->firstOrFail();
        $this->travel(5)->seconds();

        foreach (range(1, $battle->total_rounds) as $n) {
            $page = $this->actingAs($winner)->get(route('student.battles.show', $battle))->viewData('page')['props'];
            $questionId = $page['currentRound']['question']['id'];
            $this->actingAs($winner)->postJson(route('student.battles.answer', $battle), ['answer' => $correct[$questionId], 'time_taken' => 1])->assertOk();
            $this->actingAs($loser)->postJson(route('student.battles.answer', $battle), ['answer' => 'wrong', 'time_taken' => 1])->assertOk();
        }

        return $battle->fresh();
    }

    public function test_battle_rewards_rounds_and_elo_follow_the_settings(): void
    {
        foreach ([['xp_per_battle_win', 'xp', 100], ['coin_per_battle_win', 'coin', 10], ['battle_questions_count', 'battle', 5],
            ['battle_time_per_question', 'battle', 20], ['elo_k_factor', 'battle', 20]] as [$k, $g, $v]) {
            $this->setting($k, $g, $v);
        }
        $this->adminSaves(['xp_per_battle_win' => 150, 'coin_per_battle_win' => 35, 'battle_questions_count' => 4,
            'battle_time_per_question' => 30, 'elo_k_factor' => 16]);

        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $battle = $this->playBattle($ali, $vali);

        $this->assertSame('completed', $battle->status);
        $this->assertSame(4, $battle->total_rounds);
        $this->assertSame(30, (int) $battle->time_per_question);
        $this->assertSame(35, (int) $battle->coin_reward);
        $this->assertSame(150, (int) $battle->xp_reward);
        $this->assertSame(35, (int) $ali->fresh()->studentProfile->coins);
        // a new player moves at twice the K factor: 2 × 16 × (1 − 0.5)
        $this->assertSame(16, (int) $battle->player1_elo_change);
    }

    public function test_daily_battle_limit_and_monthly_coin_limit(): void
    {
        $this->setting('battle_daily_limit_trial', 'battle', 1);
        $this->setting('coin_monthly_limit_trial', 'coin', 40);
        $ali = $this->studentUser();
        $vali = $this->studentUser();
        $this->playBattle($ali, $vali);

        $this->actingAs($ali)->postJson(route('student.battles.find'))
            ->assertStatus(422)
            ->assertJsonPath('message', "Bugungi jang limiti tugadi (1 ta). Ertaga yana o'ynashingiz mumkin.");

        // 10 battle coins earned this month: a 50-coin reward is cut to the 30 left, then nothing
        $coins = app(CoinService::class);
        $this->assertSame(30, $coins->addCoins($ali, 50, 'mission', 'Vazifa'));
        $this->assertSame(0, $coins->addCoins($ali, 5, 'mission', 'Vazifa'));
        // a refund is not an earning
        $this->assertSame(20, $coins->addCoins($ali, 20, 'tournament_refund', 'Qaytarildi'));
        $this->assertSame(60, (int) $ali->fresh()->studentProfile->coins);
        $this->assertSame(3, CoinTransaction::where('user_id', $ali->id)->count());
    }
}
