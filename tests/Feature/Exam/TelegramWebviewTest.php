<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Telegram\TelegramInitData;
use App\Services\Telegram\TelegramReminderService;
use App\Services\Telegram\TelegramStartParam;
use App\Models\Exam\StudentExamProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Telegram WebView (wave 2, docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §3): deep links, the
 * webview session flag and Inertia prop, silent re-login, Mini App cookies, the local
 * emulator, bot commands / menu button and web_app buttons in messages — and the website
 * responses staying unchanged outside Telegram.
 */
class TelegramWebviewTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '987654:WEBVIEW-token';
    private const TG_ID = 515151;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);
        config([
            'exam.telegram.bot_token' => self::TOKEN,
            'exam.telegram.bot_username' => 'shanba_test_bot',
            'exam.telegram.mini_app_url' => 'https://exam.example.uz/telegram/app',
            'exam.telegram.init_data_ttl' => 3600,
        ]);
    }

    private function initData(array $override = []): string
    {
        $fields = array_merge([
            'query_id' => 'AAH-test',
            'user' => json_encode(['id' => self::TG_ID, 'first_name' => 'Aziz'], JSON_UNESCAPED_UNICODE),
            'auth_date' => (string) time(),
        ], $override);
        $fields['hash'] = TelegramInitData::sign($fields, self::TOKEN);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    private function student(bool $profile = true): User
    {
        $user = User::factory()->create(['role' => 'student']);
        TelegramAccount::factory()->create(['user_id' => $user->id, 'telegram_id' => self::TG_ID]);
        if ($profile) {
            StudentExamProfile::factory()->create(['user_id' => $user->id, 'subjects' => ['math'], 'parent_consent_at' => now()]);
        }

        return $user;
    }

    private function attemptFor(User $user, ExamSession $session, array $attrs = []): ExamAttempt
    {
        $variant = ExamVariant::factory()->create(['session_id' => $session->id, 'user_id' => $user->id]);

        return ExamAttempt::factory()->create(['variant_id' => $variant->id] + $attrs);
    }

    // ---- deep links --------------------------------------------------------------------

    public function test_start_parameters_route_to_the_right_page(): void
    {
        $user = $this->student();
        $math = Subject::factory()->math()->create();
        $start = app(TelegramStartParam::class);

        $this->assertSame(route('student.exam.index', [], false), $start->resolve($user, 'exam')['url']);
        $this->assertSame(route('student.battles.index', [], false), $start->resolve($user, 'battle')['url']);
        $this->assertSame(route('student.exam.progress', [], false), $start->resolve($user, 'progress')['url']);

        $placement = $start->resolve($user, 'placement_math');
        $this->assertSame(route('student.exam.placement.start', 'math', false), $placement['post']);
        $this->assertSame(route('student.exam.index', [], false), $placement['url']);

        // a running attempt of that session -> take; graded -> result
        $session = ExamSession::factory()->create(['subject_id' => $math->id, 'status' => 'open', 'starts_at' => now()->subMinutes(5)]);
        $attempt = $this->attemptFor($user, $session);
        $this->assertSame(route('student.exam.take', $attempt, false), $start->resolve($user, 'exam_s_'.$session->id)['url']);
        $attempt->forceFill(['status' => 'graded', 'graded_at' => now(), 'submitted_at' => now()])->save();
        $this->assertSame(route('student.exam.result', $attempt, false), $start->resolve($user, 'exam_s_'.$session->id)['url']);

        // own result; legacy "result:<uuid>" of stage 6 buttons
        $this->assertSame(route('student.exam.result', $attempt, false), $start->resolve($user, 'result_'.$attempt->id)['url']);
        $this->assertSame(route('student.exam.result', $attempt, false), $start->resolve($user, 'result:'.$attempt->id)['url']);
    }

    public function test_invalid_or_foreign_start_parameters_open_the_exam_home(): void
    {
        $user = $this->student();
        $other = User::factory()->create(['role' => 'student']);
        $session = ExamSession::factory()->create(['status' => 'open']);
        $foreign = $this->attemptFor($other, $session);
        $home = route('student.exam.index', [], false);
        $start = app(TelegramStartParam::class);

        foreach (['', 'nope', 'placement_chemistry', 'result_'.$foreign->id, 'exam_s_00000000-0000-0000-0000-000000000000',
            'battle_ZZZZZZZZ', '../../admin', str_repeat('a', 65), 'exam;drop'] as $param) {
            $target = $start->resolve($user, $param);
            $this->assertArrayNotHasKey('post', $target, $param);
            $this->assertContains($target['url'], [$home, route('student.battles.index', [], false)], $param);
        }
        $this->assertFalse(TelegramStartParam::isValid('exam;drop'));
        $this->assertFalse(TelegramStartParam::isValid(str_repeat('a', 65)));
        $this->assertNull(TelegramStartParam::safeReturnPath('//evil.example/student'));
        $this->assertNull(TelegramStartParam::safeReturnPath('/admin/users'));
        $this->assertSame('/student/exam/progress', TelegramStartParam::safeReturnPath('/student/exam/progress'));
    }

    public function test_auth_uses_the_start_param_and_returns_a_post_for_placement(): void
    {
        $this->student();
        Subject::factory()->math()->create();

        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(['start_param' => 'placement_math'])])
            ->assertOk()
            ->assertJsonPath('post', route('student.exam.placement.start', 'math', false))
            ->assertJsonStructure(['redirect', 'post', 'csrf_token']);

        // ?to= (web_app button) wins over the start_param; a safe return path wins over both
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(['start_param' => 'battle']), 'to' => 'progress'])
            ->assertJsonPath('redirect', route('student.exam.progress', [], false));
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'return' => '/student/battles/history'])
            ->assertJsonPath('redirect', '/student/battles/history');
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'return' => 'https://evil.example/'])
            ->assertJsonPath('redirect', route('student.exam.index', [], false));
    }

    // ---- webview flag and layout prop ----------------------------------------------------

    public function test_mini_app_login_marks_the_session_and_shares_the_webview_prop(): void
    {
        $this->student();

        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertOk()->assertSessionHas('webview', 'telegram');

        $this->get(route('student.battles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Battle/Index')
                ->where('webview', 'telegram')
                ->where('telegram.bot', 'shanba_test_bot'))
            ->assertSee('telegram-web-app.js', false);
    }

    public function test_website_responses_do_not_change_outside_telegram(): void
    {
        $user = $this->student();

        $response = $this->actingAs($user)->get(route('student.battles.index'))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Student/Battle/Index')->missing('webview')->missing('telegram'));
        $response->assertDontSee('<script src="https://telegram.org/js/telegram-web-app.js">', false);

        $session = ExamSession::factory()->create(['status' => 'open']);
        $attempt = $this->attemptFor($user, $session, ['status' => 'graded', 'graded_at' => now(), 'submitted_at' => now()]);
        $this->actingAs($user)->get(route('student.exam.result', $attempt))
            ->assertInertia(fn (Assert $p) => $p->missing('links.card_share'));

        // the website's cookies keep config/session.php (lax), also on HTTPS
        $cookie = $this->get('https://localhost/login')->headers->getCookies()[0] ?? null;
        $this->assertNotNull($cookie);
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }

    // ---- silent re-login -----------------------------------------------------------------

    public function test_reauth_logs_the_telegram_user_in_again_and_returns_a_csrf_token(): void
    {
        $user = $this->student();

        $this->postJson('/telegram/app/reauth', ['init_data' => $this->initData()])
            ->assertOk()->assertJsonPath('ok', true)->assertJsonStructure(['csrf_token']);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('telegram', session('webview'));

        $this->postJson('/telegram/app/reauth', ['init_data' => 'user=%7B%7D&auth_date=1'])->assertStatus(401);
    }

    public function test_login_endpoints_only_accept_json(): void
    {
        $this->student();

        // a cross-site form can not send application/json, so these stay safe without a CSRF token
        $this->post('/telegram/app/reauth', ['init_data' => $this->initData()])->assertStatus(415);
        $this->post('/telegram/app/auth', ['init_data' => $this->initData()])->assertStatus(415);
        $this->assertGuest();
    }

    public function test_reauth_switches_to_the_telegram_account(): void
    {
        $owner = $this->student();
        $other = User::factory()->create(['role' => 'student']);

        $this->actingAs($other)->postJson('/telegram/app/reauth', ['init_data' => $this->initData()])->assertOk();
        $this->assertAuthenticatedAs($owner);
    }

    // ---- cookies in the cross-site iframe ------------------------------------------------

    public function test_mini_app_cookies_are_samesite_none_secure_partitioned_on_https(): void
    {
        $this->student();

        $response = $this->postJson('https://localhost/telegram/app/auth', ['init_data' => $this->initData()])->assertOk();
        $session = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));
        $this->assertNotNull($session);
        $this->assertSame('none', strtolower((string) $session->getSameSite()));
        $this->assertTrue($session->isSecure());
        $this->assertTrue($session->isPartitioned());

        // later pages of the same (webview) session keep them
        $page = $this->get('https://localhost'.route('student.battles.index', [], false))->assertOk();
        foreach ($page->headers->getCookies() as $cookie) {
            $this->assertSame('none', strtolower((string) $cookie->getSameSite()), $cookie->getName());
            $this->assertTrue($cookie->isSecure());
        }
        // no frame-blocking header on the webview pages
        $this->assertFalse($page->headers->has('X-Frame-Options'));
        $this->assertStringNotContainsString('frame-ancestors', (string) $page->headers->get('Content-Security-Policy'));
    }

    public function test_plain_http_keeps_the_default_cookie_policy(): void
    {
        $this->student();
        $response = $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertOk();
        $session = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));
        $this->assertSame('lax', strtolower((string) $session->getSameSite()));
    }

    public function test_entry_page_reads_telegrams_start_param_query(): void
    {
        $this->get('/telegram/app?tgWebAppStartParam=battle')->assertOk()->assertSee('var to = "battle"', false);
        $this->get('/telegram/app?to=<script>')->assertOk()->assertSee('var to = ""', false);
    }

    // ---- emulator ------------------------------------------------------------------------

    public function test_dev_emulator_is_404_outside_local(): void
    {
        $user = $this->student();
        $this->get('/telegram/dev')->assertNotFound();
        $this->get('/telegram/dev/sdk.js')->assertNotFound();
        $this->actingAs($user)->postJson('/telegram/dev/sign', ['user_id' => $user->id])->assertNotFound();
    }

    public function test_dev_emulator_signs_init_data_in_local(): void
    {
        $this->app['env'] = 'local';
        config(['exam.telegram.bot_token' => '']);
        $user = User::factory()->create(['role' => 'student']);

        $this->get('/telegram/dev')->assertOk()->assertSee('Mini App emulyatori');
        $this->get('/telegram/dev/sdk.js')->assertOk()->assertSee('window.Telegram.WebApp = WebApp', false);

        // APP_ENV=local turns CSRF checks back on; the emulator page sends the token itself
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $data = $this->postJson('/telegram/dev/sign', ['user_id' => $user->id, 'start_param' => 'battle'])->assertOk()->json();
        $account = TelegramAccount::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($account->is_blocked); // never messaged
        $this->assertSame((int) $account->telegram_id, $data['telegram_id']);

        // signed with the dev token (no bot token locally) and accepted by the real verifier
        $verified = app(TelegramInitData::class)->verify($data['init_data']);
        $this->assertSame('battle', $verified['start_param']);

        $this->postJson('/telegram/app/auth', ['init_data' => $data['init_data']])
            ->assertOk()->assertJsonPath('redirect', route('student.battles.index', [], false));
    }

    // ---- bot commands, menu button, buttons in messages -----------------------------------

    public function test_setup_menu_sets_commands_and_the_web_app_menu_button(): void
    {
        $this->artisan('telegram:setup-menu')->assertSuccessful();

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/setMyCommands')
            && collect($r->data()['commands'])->pluck('command')->all() === ['start', 'imtihon', 'battle', 'natijalar', 'taklif', 'yordam', 'bekor']);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/setChatMenuButton')
            && $r->data()['menu_button']['type'] === 'web_app'
            && $r->data()['menu_button']['web_app']['url'] === 'https://exam.example.uz/telegram/app');
    }

    public function test_set_webhook_also_sets_the_commands(): void
    {
        config(['exam.telegram.webhook_secret' => 'secret-0123456789abcdef']);

        $this->artisan('telegram:set-webhook', ['--url' => 'https://exam.example.uz/telegram/webhook'])->assertSuccessful();

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/setWebhook'));
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/setMyCommands'));
    }

    public function test_imtihon_and_battle_commands_reply_with_web_app_buttons(): void
    {
        config(['exam.telegram.webhook_secret' => 'secret-0123456789abcdef']);
        Subject::factory()->math()->create();
        $this->student();

        foreach (['/imtihon' => 'to=exam', '/battle' => 'to=battle'] as $command => $to) {
            $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'secret-0123456789abcdef'])->postJson('/telegram/webhook', [
                'update_id' => random_int(1, 1_000_000),
                'message' => ['message_id' => 1, 'date' => time(), 'text' => $command,
                    'from' => ['id' => self::TG_ID, 'is_bot' => false, 'first_name' => 'Aziz'],
                    'chat' => ['id' => self::TG_ID, 'type' => 'private']],
            ])->assertOk();

            Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage')
                && str_contains((string) ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? ''), $to));
        }

        // the student has not done the math placement test: a button for it
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage')
            && collect($r->data()['reply_markup']['inline_keyboard'] ?? [])->flatten(1)
                ->contains(fn ($b) => str_contains((string) ($b['web_app']['url'] ?? ''), 'to=placement_math')));
    }

    public function test_reminders_and_results_open_the_mini_app_on_the_right_page(): void
    {
        $user = $this->student();
        TelegramAccount::query()->where('user_id', $user->id)->update(['chat_id' => self::TG_ID]);
        $math = Subject::query()->where('code', 'math')->first() ?? Subject::factory()->math()->create();
        StudentExamProfile::query()->where('user_id', $user->id)->update(['subjects' => ['math']]);
        $session = ExamSession::factory()->create(['subject_id' => $math->id, 'starts_at' => now()->addMinutes(30), 'status' => 'scheduled']);

        app(TelegramReminderService::class)->send($session, 'reminder_hour');
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage')
            && ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? '') === 'https://exam.example.uz/telegram/app?to=exam_s_'.$session->id);

        $attempt = $this->attemptFor($user, $session, ['status' => 'graded', 'graded_at' => now(), 'submitted_at' => now(), 'score' => 5, 'max_score' => 10, 'score_percent' => 50]);
        app(\App\Services\Telegram\ExamTelegramNotifier::class)->attemptGraded($attempt);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage')
            && ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? '') === 'https://exam.example.uz/telegram/app?to=result_'.$attempt->id);
    }

    // ---- result card for shareToStory ------------------------------------------------------

    public function test_share_card_needs_a_valid_signature(): void
    {
        $user = $this->student();
        $session = ExamSession::factory()->create(['status' => 'open']);
        $attempt = $this->attemptFor($user, $session);

        $this->get('/telegram/card/'.$attempt->id)->assertForbidden();
        $this->get(URL::temporarySignedRoute('telegram.card', now()->addMinutes(5), ['attempt' => $attempt->id]))->assertNotFound(); // no weekly result yet
        $this->get(URL::temporarySignedRoute('telegram.card', now()->subMinute(), ['attempt' => $attempt->id]))->assertForbidden();
    }
}
