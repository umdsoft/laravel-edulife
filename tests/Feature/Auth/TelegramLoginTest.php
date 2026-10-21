<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use App\Services\Auth\TelegramLoginService;
use App\Services\Telegram\DevOutbox;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramInitData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Website login through the Telegram bot (docs/auth/TELEGRAM_LOGIN.md): the phone + bot code,
 * "Ha, bu men", the deep link confirmed in the Mini App (existing user, new user, staff, someone
 * else's contact), the chat fallback, rate limits, enumeration-safe answers, one users.id for the
 * website and the Mini App, and the email / password login left as it was.
 * Telegram is faked; nothing leaves the test.
 */
class TelegramLoginTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '515151:L-token';
    private const SECRET = 'l-webhook-secret-0123456789';
    private const TG = 717171;

    private int $updateId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        config([
            'exam.telegram.bot_token' => self::TOKEN,
            'exam.telegram.webhook_secret' => self::SECRET,
            'exam.telegram.bot_username' => 'shanba_test_bot',
            'exam.telegram.mini_app_url' => 'https://exam.example.uz/telegram/app',
            'exam.telegram.init_data_ttl' => 3600,
        ]);
        Subject::factory()->math()->create();
        Subject::factory()->physics()->create();
    }

    // ------------------------------------------------------------------ helpers

    private function student(string $phone = '+998901234500', ?int $tg = self::TG, string $role = 'student'): User
    {
        $user = User::factory()->create(['role' => $role, 'phone' => $phone, 'status' => 'active']);
        if ($tg !== null) {
            TelegramAccount::factory()->create(['user_id' => $user->id, 'telegram_id' => $tg, 'chat_id' => $tg]);
        }
        if ($role === 'student') {
            StudentExamProfile::factory()->create(['user_id' => $user->id, 'subjects' => ['math'], 'parent_consent_at' => now()]);
        }

        return $user;
    }

    /** @return list<array<string, mixed>> sendMessage bodies */
    private function sent(): array
    {
        return Http::recorded(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage'))
            ->map(fn ($pair) => $pair[0]->data())->values()->all();
    }

    private function lastSent(): array
    {
        $sent = $this->sent();

        return $sent === [] ? [] : $sent[count($sent) - 1];
    }

    private function lastCode(): string
    {
        $text = (string) ($this->lastSent()['text'] ?? '');
        $this->assertMatchesRegularExpression('/<code>(\d{6})<\/code>/', $text);
        preg_match('/<code>(\d{6})<\/code>/', $text, $m);

        return $m[1];
    }

    private function initData(int $id = self::TG, array $override = []): string
    {
        $fields = array_merge([
            'query_id' => 'AAH-l',
            'user' => json_encode(['id' => $id, 'first_name' => 'Laylo', 'last_name' => 'Karimova', 'allows_write_to_pm' => true], JSON_UNESCAPED_UNICODE),
            'auth_date' => (string) time(),
        ], $override);
        $fields['hash'] = TelegramInitData::sign($fields, self::TOKEN);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    private function contact(string $phone, int $owner = self::TG): string
    {
        $fields = [
            'contact' => json_encode(['user_id' => $owner, 'phone_number' => $phone, 'first_name' => 'Laylo']),
            'auth_date' => (string) time(),
        ];
        $fields['hash'] = TelegramInitData::sign($fields, self::TOKEN);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    private function webhook(array $payload): TestResponse
    {
        return $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => self::SECRET])
            ->postJson('/telegram/webhook', ['update_id' => ++$this->updateId] + $payload);
    }

    private function chat(string $text, int $from = self::TG): TestResponse
    {
        return $this->webhook(['message' => [
            'message_id' => $this->updateId, 'date' => time(), 'text' => $text,
            'from' => ['id' => $from, 'is_bot' => false, 'first_name' => 'Laylo'],
            'chat' => ['id' => $from, 'type' => 'private'],
        ]]);
    }

    private function press(string $data, int $from = self::TG): TestResponse
    {
        return $this->webhook(['callback_query' => [
            'id' => 'cb'.$this->updateId, 'data' => $data,
            'from' => ['id' => $from, 'is_bot' => false, 'first_name' => 'Laylo'],
            'message' => ['message_id' => 1, 'chat' => ['id' => $from, 'type' => 'private']],
        ]]);
    }

    private function shareInChat(string $phone, int $from = self::TG, ?int $owner = null): TestResponse
    {
        return $this->webhook(['message' => [
            'message_id' => $this->updateId, 'date' => time(),
            'from' => ['id' => $from, 'is_bot' => false, 'first_name' => 'Laylo'],
            'chat' => ['id' => $from, 'type' => 'private'],
            'contact' => ['phone_number' => $phone, 'first_name' => 'Laylo', 'user_id' => $owner ?? $from],
        ]]);
    }

    /** Website: start a deep link; returns the token. */
    private function startLink(array $body = []): string
    {
        $res = $this->postJson('/login/telegram', $body)->assertOk()->assertJsonPath('expires_in', 600);
        $this->assertMatchesRegularExpression('#^https://t\.me/shanba_test_bot\?start=login_[A-Za-z0-9]{32}$#', $res->json('link'));
        $this->assertStringContainsString('<svg', (string) $res->json('qr'));

        return substr((string) $res->json('link'), -32);
    }

    /** The browser's next poll (logs out the Telegram side's session-less state first). */
    private function poll(): TestResponse
    {
        return $this->postJson('/login/telegram/status')->assertOk();
    }

    /** The Mini App side runs in another "browser": keep the website session aside. */
    private function inMiniApp(callable $fn): mixed
    {
        $web = session()->all();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $result = $fn();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        session()->put($web);

        return $result;
    }

    // ------------------------------------------------------------------ code login

    public function test_a_code_goes_to_the_linked_chat_and_logs_in_once(): void
    {
        $user = $this->student();

        $this->postJson('/login/phone', ['phone' => '90 123 45 00', 'remember' => true])->assertOk()
            ->assertJsonPath('message', "Agar raqam botga ulangan bo'lsa, kod Telegram'ga yuborildi.")
            ->assertJsonPath('expires_in', 300);

        $message = $this->lastSent();
        $this->assertSame(self::TG, (int) $message['chat_id']);
        $this->assertStringContainsString('Kodni hech kimga bermang', $message['text']);
        $code = $this->lastCode();
        $keyboard = $message['reply_markup']['inline_keyboard'];
        $this->assertSame($code, $keyboard[0][0]['copy_text']['text']);
        $this->assertStringStartsWith('login_ok:', $keyboard[1][0]['callback_data']);

        // only an HMAC at rest
        $row = TelegramLoginRequest::query()->firstOrFail();
        $this->assertNotSame($code, $row->code_hash);
        $this->assertStringNotContainsString($code, json_encode($row->getAttributes()));

        $this->postJson('/login/phone/verify', ['code' => $code])->assertOk()->assertJsonPath('redirect', '/student/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(TelegramLoginRequest::USED, $row->refresh()->status);
        $this->assertNotNull(AuditLog::query()->where('event', 'login')->where('user_id', $user->id)
            ->where('description', 'like', '%(Telegram kod)')->first());

        // single use
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/login/phone/verify', ['code' => $code])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_wrong_codes_end_after_five_tries_and_expiry_and_a_new_code_cancels_the_old(): void
    {
        $this->student();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $code = $this->lastCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/login/phone/verify', ['code' => $wrong])->assertStatus(422)
                ->assertJsonPath('errors.code.0', "Kod noto'g'ri. Yana ".(5 - $i).' ta urinish qoldi.');
        }
        $this->postJson('/login/phone/verify', ['code' => $wrong])->assertStatus(422)->assertJsonPath('errors.code.0', 'Urinishlar tugadi. Yangi kod oling.');
        $this->postJson('/login/phone/verify', ['code' => $code])->assertStatus(422);
        $this->assertGuest();

        // a new code: the old one is cancelled
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $first = $this->lastCode();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $second = $this->lastCode();
        $this->assertSame(1, TelegramLoginRequest::query()->where('status', 'pending')->count());
        if ($first !== $second) {
            $this->postJson('/login/phone/verify', ['code' => $first])->assertStatus(422);
        }

        // 5 minutes
        $this->travel(6)->minutes();
        $this->postJson('/login/phone/verify', ['code' => $second])->assertStatus(422)->assertJsonPath('errors.code.0', 'Kod eskirgan yoki ishlatilgan. Yangi kod oling.');
        $this->assertGuest();
    }

    public function test_the_answer_is_the_same_for_any_phone_and_only_linked_learners_get_a_code(): void
    {
        $this->student('+998901234500');                       // linked student
        $this->student('+998901234501', null);                 // student without Telegram
        $this->student('+998901234502', self::TG + 1, 'teacher'); // linked staff
        $blocked = $this->student('+998901234503', self::TG + 2);
        $blocked->forceFill(['status' => 'blocked'])->save();

        $answers = [];
        foreach (['+998901234500', '+998901234501', '+998901234502', '+998901234503', '+998909999999'] as $i => $phone) {
            $this->flushSession();
            $answers[] = $this->postJson('/login/phone', ['phone' => $phone])->assertOk()->json();
            $this->assertCount(1, $this->sent(), 'only the linked student gets a message');
        }
        $this->assertCount(1, array_unique(array_map(fn ($a) => json_encode(array_diff_key($a, ['phone' => 1])), $answers)));

        // codes for the others never log anyone in
        $this->postJson('/login/phone/verify', ['code' => '123456'])->assertStatus(422);
        $this->assertGuest();

        $this->postJson('/login/phone', ['phone' => '12345'])->assertStatus(422)->assertJsonPath('errors.phone.0', "Telefon raqam +998XXXXXXXXX formatida bo'lishi kerak.");
    }

    public function test_sending_is_rate_limited_per_phone_with_an_uzbek_message(): void
    {
        $this->student();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        }
        $res = $this->postJson('/login/phone', ['phone' => '998 90 123 45 00'])->assertStatus(429);
        $this->assertMatchesRegularExpression("/^Juda ko'p urinish\. \d+ soniyadan keyin qayta urinib ko'ring\.$/", $res->json('message'));
        // another phone is not affected
        $this->postJson('/login/phone', ['phone' => '+998901234599'])->assertOk();
    }

    public function test_a_user_who_blocked_the_bot_gets_the_same_answer_and_is_marked_blocked(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);
        $this->student();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk()
            ->assertJsonPath('hint', "Kod kelmadimi? Botni bloklagan bo'lsangiz — botni qayta ishga tushiring (/start). Hali botdan foydalanmagan bo'lsangiz — «Telegram orqali kirish».");
        $this->assertTrue(TelegramAccount::query()->where('telegram_id', self::TG)->value('is_blocked'));
        $this->assertSame('bot_blocked', TelegramLoginRequest::query()->value('reason'));
    }

    public function test_ha_bu_men_logs_in_only_the_browser_that_asked_and_only_from_that_telegram_user(): void
    {
        $user = $this->student();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $id = TelegramLoginRequest::query()->value('id');

        // another Telegram user presses the button: nothing happens
        $this->student('+998901234511', self::TG + 5);
        $this->inMiniApp(fn () => $this->press('login_ok:'.$id, self::TG + 5));
        $this->poll()->assertJsonPath('status', 'waiting')->assertJsonPath('code.status', 'pending');

        // the owner approves
        $this->inMiniApp(fn () => $this->press('login_ok:'.$id));
        $this->assertStringContainsString('Tasdiqlandi', $this->lastSent()['text']);

        // another browser polling gets nothing
        $web = session()->all();
        $this->flushSession();
        $this->poll()->assertJsonPath('status', 'waiting');
        $this->assertGuest();
        session()->put($web);

        $this->poll()->assertJsonPath('status', 'ok')->assertJsonPath('redirect', '/student/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(AuditLog::query()->where('description', 'like', '%(Telegram tasdiq)')->first());
    }

    public function test_bu_men_emas_cancels_the_code(): void
    {
        $this->student();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $code = $this->lastCode();
        $id = TelegramLoginRequest::query()->value('id');

        $this->inMiniApp(fn () => $this->press('login_no:'.$id));
        $this->poll()->assertJsonPath('code.status', 'cancelled')->assertJsonPath('code.message', "So'rov Telegram'da rad etildi.");
        $this->postJson('/login/phone/verify', ['code' => $code])->assertStatus(422);
        $this->assertGuest();
    }

    // ------------------------------------------------------------------ deep link

    public function test_bot_start_answers_with_a_web_app_button_and_the_mini_app_never_shows_a_login_form(): void
    {
        $user = $this->student();
        $token = $this->startLink();

        $this->inMiniApp(fn () => $this->chat('/start login_'.$token));
        $message = $this->lastSent();
        $button = $message['reply_markup']['inline_keyboard'][0][0];
        $this->assertSame('✅ Saytga kirishni tasdiqlash', $button['text']);
        $this->assertSame('https://exam.example.uz/telegram/app?to=login_'.$token, $button['web_app']['url']);
        $this->assertStringStartsWith('login_chat:', $message['reply_markup']['inline_keyboard'][1][0]['callback_data']);

        $this->inMiniApp(function () use ($token, $user) {
            // the Mini App entry page signs in from initData and goes to the confirm screen
            $this->get('/telegram/app?to=login_'.$token)->assertOk()->assertDontSee('type="password"', false);
            $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'to' => 'login_'.$token])
                ->assertOk()->assertJsonPath('redirect', '/telegram/app/login?t='.$token);
            $this->assertAuthenticatedAs($user);

            $this->get('/telegram/app/login?t='.$token)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Telegram/LoginConfirm')->where('token', $token)->missing('errors.password'));
            $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token])
                ->assertOk()->assertJsonPath('status', 'confirmed');
        });

        $this->poll()->assertJsonPath('status', 'ok')->assertJsonPath('redirect', '/student/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(AuditLog::query()->where('description', 'like', '%(Telegram havola)')->first());

        // the link is single use
        $this->inMiniApp(fn () => $this->chat('/start login_'.$token));
        $this->assertStringContainsString('eskirgan', $this->lastSent()['text']);
    }

    public function test_an_unlinked_telegram_user_shares_the_phone_of_an_existing_website_account(): void
    {
        $user = $this->student('+998901234500', null);
        $token = $this->startLink();

        $this->inMiniApp(function () use ($token) {
            // not registered: the entry page goes to the confirm screen, not to the registration
            $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'to' => 'login_'.$token])
                ->assertStatus(409)->assertJsonPath('register', '/telegram/app/login?t='.$token);
            $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token])
                ->assertOk()->assertJsonPath('status', 'need_contact');
            $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token, 'contact' => $this->contact('998901234500')])
                ->assertOk()->assertJsonPath('status', 'confirmed');
        });

        $this->assertSame($user->id, TelegramAccount::query()->where('telegram_id', self::TG)->value('user_id'));
        $this->poll()->assertJsonPath('status', 'ok');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_new_phone_registers_in_the_mini_app_and_the_browser_gets_the_same_account(): void
    {
        $token = $this->startLink(['role' => 'student']);

        $created = $this->inMiniApp(function () use ($token) {
            $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token, 'contact' => $this->contact('998907776655')])
                ->assertOk()->assertJsonPath('status', 'register')->assertJsonPath('register', '/telegram/app/register?to=login_'.$token);

            $this->postJson('/telegram/app/register', [
                'init_data' => $this->initData(), 'to' => 'login_'.$token, 'kind' => 'student',
                'grade' => '11', 'subject' => 'math', 'goal' => 'otm_grant',
                'parent_phone' => '+998904445566', 'parent_consent' => true,
            ])->assertOk()->assertJsonPath('redirect', '/telegram/app/login?t='.$token);
            $user = User::query()->where('phone', '+998907776655')->firstOrFail();
            $this->assertAuthenticatedAs($user);

            // the confirm screen shows "tasdiqlandi" again on reload
            $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token])->assertJsonPath('status', 'confirmed');

            return $user;
        });

        $this->poll()->assertJsonPath('status', 'ok');
        $this->assertAuthenticatedAs($created);
        $this->assertSame(1, User::query()->where('phone', '+998907776655')->count());
    }

    public function test_staff_phones_are_refused_and_never_linked(): void
    {
        $teacher = $this->student('+998901234502', null, 'teacher');
        $token = $this->startLink();

        $this->inMiniApp(fn () => $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token, 'contact' => $this->contact('998901234502')])
            ->assertOk()->assertJsonPath('status', 'refused')
            ->assertJsonPath('message', 'Bu raqam ustoz yoki admin akkauntiga tegishli. Saytga email va parol bilan kiring.'));

        $this->assertNull(TelegramAccount::query()->where('telegram_id', self::TG)->value('user_id'));
        $this->poll()->assertJsonPath('link.status', 'refused')->assertJsonPath('link.message', 'Bu raqam ustoz yoki admin akkauntiga tegishli. Saytga email va parol bilan kiring.');
        $this->assertGuest();
        $this->assertSame(0, TelegramAccount::query()->where('user_id', $teacher->id)->count());
    }

    public function test_a_contact_of_someone_else_is_refused(): void
    {
        $this->student('+998901234500', null);
        $token = $this->startLink();

        $this->inMiniApp(fn () => $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token, 'contact' => $this->contact('998901234500', self::TG + 9)])
            ->assertOk()->assertJsonPath('status', 'need_contact'));
        $this->assertNull(TelegramAccount::query()->where('telegram_id', self::TG)->value('user_id'));

        // chat fallback: a forwarded contact is refused too
        $this->inMiniApp(function () use ($token) {
            $id = TelegramLoginRequest::query()->where('kind', 'link')->value('id');
            $this->press('login_chat:'.$id);
            $this->shareInChat('+998901234500', self::TG, self::TG + 9);
        });
        $this->assertStringContainsString('o\'zingizning', mb_strtolower($this->lastSent()['text']) ?: 'o\'zingizning');
        $this->assertNull(TelegramAccount::query()->where('telegram_id', self::TG)->value('user_id'));
        $this->poll()->assertJsonPath('status', 'waiting');
        $this->assertGuest();
    }

    public function test_another_telegram_user_can_not_take_over_a_link_somebody_opened(): void
    {
        $this->student('+998901234500');
        $token = $this->startLink();
        $this->inMiniApp(fn () => $this->chat('/start login_'.$token));                // the owner opens it
        $this->inMiniApp(fn () => $this->postJson('/telegram/app/login', ['init_data' => $this->initData(self::TG + 3), 'token' => $token])
            ->assertOk()->assertJsonPath('status', 'expired'));
        $this->poll()->assertJsonPath('status', 'waiting');
    }

    public function test_the_chat_fallback_creates_a_bare_account_for_a_new_phone(): void
    {
        $token = $this->startLink(['role' => 'guardian']);

        $this->inMiniApp(function () use ($token) {
            $this->chat('/start login_'.$token);
            $id = TelegramLoginRequest::query()->where('kind', 'link')->value('id');
            $this->press('login_chat:'.$id);
            $keyboard = $this->lastSent()['reply_markup']['keyboard'];
            $this->assertTrue($keyboard[0][0]['request_contact']);
            $this->shareInChat('+998 90 555 44 33');
            $this->assertStringContainsString('Akkaunt ochildi', $this->lastSent()['text']);
        });

        $user = User::query()->where('phone', '+998905554433')->firstOrFail();
        $this->assertSame('guardian', $user->role);
        $this->assertNotNull($user->phone_verified_at);
        $this->poll()->assertJsonPath('status', 'ok')->assertJsonPath('redirect', '/guardian/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_links_expire_after_ten_minutes(): void
    {
        $this->student();
        $token = $this->startLink();
        $this->travel(11)->minutes();
        $this->inMiniApp(fn () => $this->postJson('/telegram/app/login', ['init_data' => $this->initData(), 'token' => $token])->assertJsonPath('status', 'expired'));
        $this->poll()->assertJsonPath('link.status', 'expired');
    }

    // ------------------------------------------------------------------ one account everywhere

    public function test_the_website_code_login_and_the_mini_app_resolve_to_the_same_user(): void
    {
        $user = $this->student();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $this->postJson('/login/phone/verify', ['code' => $this->lastCode()])->assertOk();
        $web = auth()->id();

        $mini = $this->inMiniApp(function () {
            $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertOk();

            return auth()->id();
        });

        $this->assertSame($user->id, $web);
        $this->assertSame($web, $mini);
        $this->assertSame(1, User::query()->where('phone', '+998901234500')->count());
    }

    // ------------------------------------------------------------------ unchanged

    public function test_email_and_password_login_still_works_for_admin_and_students(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'email' => 'admin@edulife.uz', 'phone' => '+998901234567', 'password' => Hash::make('admin123'), 'status' => 'active']);
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->post('/login', ['login' => 'admin@edulife.uz', 'password' => 'admin123'])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);

        // the admin's phone gets no code
        $this->post('/logout');
        $this->postJson('/login/phone', ['phone' => '+998901234567'])->assertOk();
        $this->assertCount(0, $this->sent());
    }

    // ------------------------------------------------------------------ local bot chat

    public function test_the_local_bot_chat_keeps_the_messages_only_in_local(): void
    {
        config(['exam.telegram.bot_token' => '']);
        $this->assertFalse(app(TelegramClient::class)->sendMessage(1, 'x')->ok); // testing: skipped as before
        $this->get('/telegram/dev/outbox')->assertNotFound();

        $this->app['env'] = 'local';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        DevOutbox::clear();
        $user = $this->student();
        $this->postJson('/login/phone', ['phone' => '+998901234500'])->assertOk();
        $this->assertCount(0, $this->sent());
        $outbox = $this->getJson('/telegram/dev/outbox')->assertOk()->json('messages');
        $this->assertCount(1, $outbox);
        $this->assertSame($user->id, $outbox[0]['user_id']);
        preg_match('/<code>(\d{6})<\/code>/', $outbox[0]['text'], $m);

        // "Ha, bu men" from the emulator
        $this->postJson('/telegram/dev/update', ['telegram_id' => self::TG, 'callback' => $outbox[0]['markup']['inline_keyboard'][1][0]['callback_data']])->assertOk();
        $this->poll()->assertJsonPath('status', 'ok');
        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($m[1] ?? null);
    }
}
