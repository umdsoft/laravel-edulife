<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ReferralCode;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\TelegramAccount;
use App\Models\Exam\TelegramBotState;
use App\Models\User;
use App\Services\Telegram\BotRegistration;
use App\Services\Telegram\TelegramLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Stage 6: webhook secret, /start ref_<code>, the 5-question registration,
 * linking from the site. Telegram is faked; nothing leaves the test.
 */
class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TEST-token';
    private const SECRET = 'webhook-secret-0123456789';
    private const TG_ID = 555000111;

    private int $updateId = 1000;

    /** What the faked Bot API answers: [body, status]. */
    private array $telegramReply = [['ok' => true, 'result' => ['message_id' => 1]], 200];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => fn () => Http::response(...$this->telegramReply)]);

        config([
            'exam.telegram.bot_token' => self::TOKEN,
            'exam.telegram.webhook_secret' => self::SECRET,
            'exam.telegram.bot_username' => 'shanba_test_bot',
            'exam.telegram.mini_app_url' => 'https://exam.example.uz/telegram/app',
        ]);
    }

    private function tgUser(int $id = self::TG_ID): array
    {
        return ['id' => $id, 'is_bot' => false, 'first_name' => 'Aziz', 'last_name' => 'Karimov', 'username' => 'aziz', 'language_code' => 'uz'];
    }

    private function update(array $payload, ?string $secret = self::SECRET): TestResponse
    {
        $headers = $secret === null ? [] : ['X-Telegram-Bot-Api-Secret-Token' => $secret];

        return $this->withHeaders($headers)->postJson('/telegram/webhook', ['update_id' => ++$this->updateId] + $payload);
    }

    private function message(string $text, int $from = self::TG_ID): TestResponse
    {
        return $this->update(['message' => [
            'message_id' => $this->updateId,
            'from' => $this->tgUser($from),
            'chat' => ['id' => $from, 'type' => 'private'],
            'date' => time(),
            'text' => $text,
        ]]);
    }

    private function press(string $data): TestResponse
    {
        return $this->update(['callback_query' => [
            'id' => 'cb'.$this->updateId,
            'from' => $this->tgUser(),
            'message' => ['message_id' => 1, 'chat' => ['id' => self::TG_ID, 'type' => 'private']],
            'data' => $data,
        ]]);
    }

    private function shareContact(string $phone, int $owner = self::TG_ID): TestResponse
    {
        return $this->update(['message' => [
            'message_id' => $this->updateId,
            'from' => $this->tgUser(),
            'chat' => ['id' => self::TG_ID, 'type' => 'private'],
            'date' => time(),
            'contact' => ['phone_number' => $phone, 'first_name' => 'Aziz', 'user_id' => $owner],
        ]]);
    }

    private function assertBotSaid(string $needle): void
    {
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && str_contains((string) ($r->data()['text'] ?? ''), $needle));
    }

    private function account(): ?TelegramAccount
    {
        return TelegramAccount::query()->where('telegram_id', self::TG_ID)->first();
    }

    // ---- webhook security ----

    public function test_webhook_requires_the_secret_token_header(): void
    {
        $this->update(['message' => ['text' => '/start']], null)->assertForbidden();
        $this->update(['message' => ['text' => '/start']], 'wrong-secret')->assertForbidden();
        $this->message('/start')->assertOk()->assertJson(['ok' => true]);
        $this->assertNotNull($this->account());
    }

    public function test_webhook_does_not_exist_while_the_bot_is_not_configured(): void
    {
        config(['exam.telegram.bot_token' => '', 'exam.telegram.webhook_secret' => '']);
        Http::fake();

        $this->message('/start')->assertNotFound();
        Http::assertNothingSent();
        $this->assertNull($this->account());
    }

    public function test_webhook_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('telegram.webhook');
        $this->assertContains('throttle:telegram-webhook', $route->gatherMiddleware()); // TW: a named limiter (Telegram's few IPs)
        $this->assertContains('web', $route->excludedMiddleware());
    }

    // ---- /start ----

    public function test_start_with_ref_code_stores_the_payload_and_answers_with_the_mini_app_button(): void
    {
        $this->message('/start ref_ABCD2345')->assertOk();

        $account = $this->account();
        $this->assertSame('ref_ABCD2345', $account->start_payload);
        $this->assertNull($account->user_id);
        // TW (WebView-first): no chat questionnaire, one message with a web_app "Ochish" button
        $this->assertNull(TelegramBotState::query()->where('telegram_account_id', $account->id)->value('step'));
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage')
            && str_contains($r->data()['text'], 'Ochish')
            && count($r->data()['reply_markup']['inline_keyboard']) === 1
            && ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? null) === 'https://exam.example.uz/telegram/app?to=exam');

        // the chat questionnaire is only a fallback: /royxat
        $this->message('/royxat');
        $this->assertSame(BotRegistration::STEP_ROLE, TelegramBotState::query()->where('telegram_account_id', $account->id)->value('step'));
        $this->assertBotSaid('1/5');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage')
            && ($r->data()['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? null) === 'reg:role:student');
    }

    public function test_group_chats_and_bots_are_ignored(): void
    {
        $this->update(['message' => ['from' => $this->tgUser(), 'chat' => ['id' => -100, 'type' => 'group'], 'text' => '/start']])->assertOk();
        $this->update(['message' => ['from' => ['id' => 9, 'is_bot' => true], 'chat' => ['id' => 9, 'type' => 'private'], 'text' => '/start']])->assertOk();

        $this->assertSame(0, TelegramAccount::count());
        Http::assertNothingSent();
    }

    // ---- registration ----

    public function test_student_registration_creates_the_account_profile_and_consent(): void
    {
        $referrer = User::factory()->create(['role' => 'student']);
        ReferralCode::create(['owner_id' => $referrer->id, 'code' => 'ABCD2345']);

        $this->message('/start ref_ABCD2345');
        $this->message('/royxat');
        $this->press('reg:role:student');
        $this->press('reg:grade:10');
        $this->press('reg:subject:both');
        $this->press('reg:goal:otm_grant');
        $this->assertBotSaid('5/5');
        $this->shareContact('998901112233');
        $this->message('+998 90 444-55-66');
        $this->press('reg:consent:yes');

        $user = User::query()->where('phone', '+998901112233')->first();
        $this->assertNotNull($user);
        $this->assertSame('student', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame('Aziz', $user->first_name);
        $this->assertNotNull($user->phone_verified_at);

        $profile = StudentExamProfile::query()->where('user_id', $user->id)->first();
        $this->assertSame(10, $profile->grade);
        $this->assertFalse($profile->is_graduate);
        $this->assertSame(['math', 'physics'], $profile->subjects);
        $this->assertSame('otm_grant', $profile->goal->value);
        $this->assertSame('student', $profile->filled_by);
        $this->assertSame('+998904445566', $profile->parent_phone);
        $this->assertNotNull($profile->parent_consent_at);
        $this->assertSame('telegram_student', $profile->parent_consent_source);
        $this->assertTrue($profile->isComplete());

        $account = $this->account();
        $this->assertSame($user->id, $account->user_id);
        $this->assertNotNull($account->linked_at);
        $this->assertBotSaid('qabul testi');
        // the placement button opens the Mini App
        Http::assertSent(fn (Request $r) => ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? null) === 'https://exam.example.uz/telegram/app?to=exam');

        if (class_exists(\App\Services\Exam\ReferralService::class)) {
            $this->assertDatabaseHas('referrals', ['referrer_id' => $referrer->id, 'referred_id' => $user->id, 'status' => 'joined']);
        }
    }

    public function test_parent_registration_skips_the_parent_phone_question(): void
    {
        $this->message('/royxat');
        $this->press('reg:role:parent');
        $this->press('reg:grade:graduate');
        $this->press('reg:subject:physics');
        $this->press('reg:goal:sat');
        $this->shareContact('+998935556677');
        $this->assertSame(BotRegistration::STEP_CONSENT, TelegramBotState::query()->value('step'));
        $this->press('reg:consent:yes');

        $profile = StudentExamProfile::query()->first();
        $this->assertNull($profile->grade);
        $this->assertTrue($profile->is_graduate);
        $this->assertSame(['physics'], $profile->subjects);
        $this->assertSame('parent', $profile->filled_by);
        $this->assertSame('+998935556677', $profile->parent_phone);
        $this->assertSame('telegram_parent', $profile->parent_consent_source);
    }

    public function test_someone_elses_contact_and_bad_phones_are_refused(): void
    {
        $this->message('/royxat');
        foreach (['reg:role:student', 'reg:grade:9', 'reg:subject:math', 'reg:goal:sat'] as $d) {
            $this->press($d);
        }

        $this->shareContact('998901112233', owner: 42);
        $this->assertBotSaid("o'zingizning raqamingizni");
        $this->shareContact('+79161234567');
        $this->assertBotSaid('+998');
        $this->assertSame(BotRegistration::STEP_CONTACT, TelegramBotState::query()->value('step'));

        $this->shareContact('998901112233');
        $this->message('12345');
        $this->assertBotSaid("Raqam noto'g'ri");
        $this->assertSame(0, User::query()->where('phone', '+998901112233')->count());
    }

    public function test_no_consent_no_account(): void
    {
        $this->message('/royxat');
        foreach (['reg:role:parent', 'reg:grade:11', 'reg:subject:math', 'reg:goal:otm_grant'] as $d) {
            $this->press($d);
        }
        $this->shareContact('998901112233');
        $this->press('reg:consent:no');

        $this->assertSame(0, User::query()->where('phone', '+998901112233')->count());
        $this->assertBotSaid('Rozilik');
    }

    public function test_stale_buttons_repeat_the_current_question(): void
    {
        $this->message('/royxat');
        $this->press('reg:goal:sat'); // out of order
        $this->assertSame(BotRegistration::STEP_ROLE, TelegramBotState::query()->value('step'));
        $this->press('reg:role:hacker'); // not a choice
        $this->assertSame(BotRegistration::STEP_ROLE, TelegramBotState::query()->value('step'));
    }

    public function test_existing_student_is_linked_by_phone_but_staff_accounts_are_not(): void
    {
        $student = User::factory()->create(['role' => 'student', 'phone' => '+998901112233']);
        $this->message('/royxat');
        foreach (['reg:role:student', 'reg:grade:9', 'reg:subject:math', 'reg:goal:sat'] as $d) {
            $this->press($d);
        }
        $this->shareContact('998901112233');
        $this->message('+998904445566');
        $this->press('reg:consent:yes');
        $this->assertSame($student->id, $this->account()->user_id);
        $this->assertSame(1, User::query()->where('phone', '+998901112233')->count());

        // a teacher's phone is never taken over through the bot
        User::factory()->create(['role' => 'teacher', 'phone' => '+998907778899']);
        $other = 777000222;
        $this->message('/royxat', $other);
        $acc = TelegramAccount::query()->where('telegram_id', $other)->first();
        $state = TelegramBotState::query()->where('telegram_account_id', $acc->id)->first();
        $state->fill(['step' => 'consent', 'data' => ['role' => 'parent', 'grade' => '9', 'subject' => 'math', 'goal' => 'sat', 'phone' => '+998907778899', 'parent_phone' => '+998907778899']])->save();
        $this->update(['callback_query' => ['id' => 'x', 'from' => $this->tgUser($other), 'message' => ['chat' => ['id' => $other, 'type' => 'private']], 'data' => 'reg:consent:yes']]);

        $this->assertNull($acc->fresh()->user_id);
        $this->assertBotSaid('boshqa turdagi akkaunt');
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('+998901234567', BotRegistration::normalizePhone('998901234567'));
        $this->assertSame('+998901234567', BotRegistration::normalizePhone('+998 (90) 123-45-67'));
        $this->assertSame('+998901234567', BotRegistration::normalizePhone('901234567'));
        $this->assertNull(BotRegistration::normalizePhone('+7 916 123 45 67'));
        $this->assertNull(BotRegistration::normalizePhone('abc'));
    }

    // ---- linking a web account, menu ----

    public function test_start_link_token_links_an_existing_web_account(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $token = app(TelegramLinkService::class)->createToken($guardian);

        $this->message('/start link_'.$token);

        $this->assertSame($guardian->id, $this->account()->user_id);
        $this->assertBotSaid('ulandi');

        // one-time token
        $this->message('/start link_'.$token, 888);
        $this->assertNull(TelegramAccount::query()->where('telegram_id', 888)->value('user_id'));
    }

    public function test_link_route_redirects_to_the_bot_deep_link(): void
    {
        $user = User::factory()->create(['role' => 'guardian']);

        $this->actingAs($user)->get('/telegram/link')->assertRedirectContains('https://t.me/shanba_test_bot?start=link_');
    }

    public function test_results_command_for_linked_student(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        TelegramAccount::factory()->create(['user_id' => $user->id, 'telegram_id' => self::TG_ID, 'chat_id' => self::TG_ID]);

        $this->message('/natijalar');
        $this->assertBotSaid("Hali natija yo'q");

        $this->message('/start');
        $this->assertBotSaid('Assalomu alaykum, '.$user->first_name);
    }

    public function test_blocked_bot_marks_the_account(): void
    {
        $this->telegramReply = [['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403];
        $user = User::factory()->create(['role' => 'student']);
        $account = TelegramAccount::factory()->create(['user_id' => $user->id]);

        $sent = app(\App\Services\Telegram\TelegramMessenger::class)->notifyOnce($account, 'reminder_eve', 'sess-1', 'Salom');

        $this->assertFalse($sent);
        $this->assertTrue($account->fresh()->is_blocked);
        $this->assertDatabaseHas('telegram_notifications', ['telegram_account_id' => $account->id, 'status' => 'blocked']);
    }
}
