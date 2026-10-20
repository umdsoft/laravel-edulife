<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamAttempt;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\ReferralCode;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Telegram\TelegramInitData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * TW (Telegram WebView-first, docs/exam/TELEGRAM_AUDIT.md): registration inside the Mini App
 * (student, parent for the child, parent watching a child), the requestContact verification,
 * the phone rules (existing phone, staff phone, typed phone), consent, the invite code, the
 * guardian area in the WebView, the short bot replies with web_app buttons and the
 * "Saytda ochish" handoff. Telegram is faked; nothing leaves the test.
 */
class TelegramWebviewRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '424242:TW-token';
    private const SECRET = 'tw-webhook-secret-0123456789';
    private const TG_ID = 606060;

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

    private function initData(array $override = [], int $id = self::TG_ID): string
    {
        $fields = array_merge([
            'query_id' => 'AAH-tw',
            'user' => json_encode(['id' => $id, 'first_name' => 'Nodira', 'last_name' => 'Aliyeva', 'allows_write_to_pm' => true], JSON_UNESCAPED_UNICODE),
            'auth_date' => (string) time(),
        ], $override);
        $fields['hash'] = TelegramInitData::sign($fields, self::TOKEN);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    /** What Telegram.WebApp.requestContact() hands to its callback (event.response). */
    private function contact(string $phone, int $owner = self::TG_ID, ?int $authDate = null, ?string $token = null): string
    {
        $fields = [
            'contact' => json_encode(['user_id' => $owner, 'phone_number' => $phone, 'first_name' => 'Nodira']),
            'auth_date' => (string) ($authDate ?? time()),
        ];
        $fields['hash'] = TelegramInitData::sign($fields, $token ?? self::TOKEN);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    private function studentForm(array $override = []): array
    {
        return array_merge([
            'kind' => 'student',
            'grade' => '10',
            'subject' => 'physics',
            'goal' => 'otm_grant',
            'school_name' => '14-maktab',
            'parent_phone' => '+998 90 444 55 66',
            'parent_consent' => true,
            'show_name_publicly' => true,
        ], $override);
    }

    private function register(array $body, ?string $initData = null): TestResponse
    {
        return $this->postJson('/telegram/app/register', ['init_data' => $initData ?? $this->initData()] + $body);
    }

    private function webhook(array $payload): TestResponse
    {
        return $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => self::SECRET])
            ->postJson('/telegram/webhook', ['update_id' => ++$this->updateId] + $payload);
    }

    private function chat(string $text, int $from = self::TG_ID): TestResponse
    {
        return $this->webhook(['message' => [
            'message_id' => $this->updateId, 'date' => time(), 'text' => $text,
            'from' => ['id' => $from, 'is_bot' => false, 'first_name' => 'Nodira'],
            'chat' => ['id' => $from, 'type' => 'private'],
        ]]);
    }

    private function linkedUser(string $role, ?int $tg = self::TG_ID): User
    {
        $user = User::factory()->create(['role' => $role]);
        TelegramAccount::factory()->create(['user_id' => $user->id, 'telegram_id' => $tg, 'chat_id' => $tg, 'is_blocked' => false]);
        if ($role === 'student') {
            StudentExamProfile::factory()->create(['user_id' => $user->id, 'subjects' => ['math'], 'parent_consent_at' => now()]);
        }

        return $user;
    }

    // ------------------------------------------------------------------ entry

    public function test_an_unregistered_telegram_user_is_sent_to_the_webview_registration(): void
    {
        $other = User::factory()->create(['role' => 'student']);
        $this->actingAs($other); // another Telegram account used this webview before

        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(['start_param' => 'battle_ABCD1234'])])
            ->assertStatus(409)
            ->assertJsonPath('register', '/telegram/app/register?to=battle_ABCD1234');

        $this->assertGuest();
        $this->assertSame('telegram', session('webview'));

        $this->get('/telegram/app/register?to=battle_ABCD1234')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Telegram/Register')
            ->where('to', 'battle_ABCD1234')
            ->has('subjects', 2)
            ->has('relations', 4)
            ->where('webview', 'telegram'));
    }

    public function test_staff_accounts_are_never_logged_in_by_the_mini_app(): void
    {
        foreach (['teacher', 'admin'] as $i => $role) {
            $this->linkedUser($role, self::TG_ID + $i);
            $this->postJson('/telegram/app/auth', ['init_data' => $this->initData([], self::TG_ID + $i)])
                ->assertStatus(403)->assertJsonStructure(['message', 'site']);
            $this->postJson('/telegram/app/reauth', ['init_data' => $this->initData([], self::TG_ID + $i)])->assertStatus(403);
            $this->assertGuest();
        }
    }

    // ------------------------------------------------------------------ registration

    public function test_student_registers_in_the_webview_with_the_shared_contact(): void
    {
        $res = $this->register($this->studentForm(['contact' => $this->contact('998901112233')]))->assertOk();

        $user = User::query()->where('phone', '+998901112233')->firstOrFail();
        $this->assertSame('student', $user->role);
        $this->assertSame('Nodira', $user->first_name);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('telegram', session('webview'));

        $profile = StudentExamProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(10, $profile->grade);
        $this->assertSame(['physics'], $profile->subjects);
        $this->assertSame('otm_grant', $profile->goal->value);
        $this->assertSame('student', $profile->filled_by);
        $this->assertSame('+998904445566', $profile->parent_phone);
        $this->assertSame('telegram_student', $profile->parent_consent_source);
        $this->assertSame('14-maktab', $profile->school_name);
        $this->assertTrue($profile->show_name_publicly);
        $this->assertNotNull($profile->public_name_consent_at);
        $this->assertTrue($profile->isComplete());

        // the Telegram account is created from initData (opened without writing to the bot first)
        $account = TelegramAccount::query()->where('telegram_id', self::TG_ID)->firstOrFail();
        $this->assertSame($user->id, $account->user_id);
        $this->assertSame(self::TG_ID, $account->chat_id);

        // next: the free placement test of the chosen subject (a POST the page submits)
        $res->assertJsonPath('post', '/student/exam/placement/physics')->assertJsonPath('role', 'student');
        $this->assertNotEmpty($res->json('csrf_token'));

        // reopening the app logs straight in
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertOk();
    }

    public function test_a_friends_challenge_link_continues_after_registration(): void
    {
        $this->register($this->studentForm(['contact' => $this->contact('998901112233'), 'to' => 'battle']))
            ->assertOk()->assertJsonPath('redirect', '/student/battles')->assertJsonPath('post', null);
    }

    public function test_invite_code_from_the_start_param_or_the_chat_is_recorded(): void
    {
        $referrer = User::factory()->create(['role' => 'student']);
        ReferralCode::create(['owner_id' => $referrer->id, 'code' => 'ABCD2345']);

        // startapp=ref_ABCD2345 (initData.start_param)
        $this->register($this->studentForm(['contact' => $this->contact('998901112233')]), $this->initData(['start_param' => 'ref_ABCD2345']))->assertOk();
        $first = User::query()->where('phone', '+998901112233')->firstOrFail();
        $this->assertDatabaseHas('referrals', ['referrer_id' => $referrer->id, 'referred_id' => $first->id, 'status' => 'joined']);

        // /start ref_ABCD2345 in the chat, registration in the Mini App
        $this->app['auth']->forgetGuards();
        $this->chat('/start ref_ABCD2345', 707070);
        $this->register($this->studentForm(['contact' => $this->contact('998902223344', 707070)]), $this->initData([], 707070))->assertOk();
        $second = User::query()->where('phone', '+998902223344')->firstOrFail();
        $this->assertDatabaseHas('referrals', ['referrer_id' => $referrer->id, 'referred_id' => $second->id]);
    }

    public function test_parent_registering_the_child_creates_the_childs_student_account(): void
    {
        $this->register([
            'kind' => 'parent_child', 'grade' => 'graduate', 'subject' => 'both', 'goal' => 'sat',
            'parent_consent' => true, 'contact' => $this->contact('+998935556677'),
        ])->assertOk()->assertJsonPath('post', '/student/exam/placement/math');

        $user = User::query()->where('phone', '+998935556677')->firstOrFail();
        $this->assertSame('student', $user->role);
        $profile = StudentExamProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('parent', $profile->filled_by);
        $this->assertSame('+998935556677', $profile->parent_phone);
        $this->assertSame('telegram_parent', $profile->parent_consent_source);
        $this->assertNull($profile->grade);
        $this->assertTrue($profile->is_graduate);
        $this->assertSame(['math', 'physics'], $profile->subjects);
    }

    public function test_parent_watching_a_child_gets_a_guardian_account_and_a_pending_link(): void
    {
        $child = User::factory()->create(['role' => 'student', 'phone' => '+998901110000']);

        $this->register([
            'kind' => 'guardian', 'first_name' => 'Dilnoza', 'last_name' => 'Karimova',
            'child_phone' => '90 111 00 00', 'relation' => 'mother', 'consent' => true,
            'contact' => $this->contact('998977776655'),
        ])->assertOk()->assertJsonPath('redirect', '/guardian/dashboard')->assertJsonPath('role', 'guardian');

        $guardian = User::query()->where('phone', '+998977776655')->firstOrFail();
        $this->assertSame('guardian', $guardian->role);
        $this->assertSame('Dilnoza', $guardian->first_name);
        $link = GuardianStudent::query()->where('guardian_id', $guardian->id)->firstOrFail();
        $this->assertSame($child->id, $link->student_id);
        $this->assertNull($link->verified_at); // the teacher approves it, as on the website
        $this->assertSame('telegram', $link->consent_source);
        $this->assertNotNull($link->consent_at);
        $this->assertSame(0, StudentExamProfile::query()->where('user_id', $guardian->id)->count());

        // the guardian area runs in the compact WebView chrome
        $this->get('/guardian/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Guardian/Dashboard')->where('webview', 'telegram'));
    }

    public function test_guardian_with_an_unknown_child_phone_creates_nothing(): void
    {
        $this->register([
            'kind' => 'guardian', 'child_phone' => '+998901119999', 'relation' => 'father', 'consent' => true,
            'contact' => $this->contact('998977776655'),
        ])->assertStatus(422)->assertJsonValidationErrors('child_phone');

        $this->assertSame(0, User::query()->where('phone', '+998977776655')->count());
        $this->assertNull(TelegramAccount::query()->where('telegram_id', self::TG_ID)->value('user_id'));
        $this->assertGuest();
    }

    public function test_consent_and_the_answers_are_required(): void
    {
        $this->register($this->studentForm(['parent_consent' => false, 'contact' => $this->contact('998901112233')]))
            ->assertStatus(422)->assertJsonValidationErrors('parent_consent');
        $this->register(['kind' => 'student', 'contact' => $this->contact('998901112233')])
            ->assertStatus(422)->assertJsonValidationErrors(['grade', 'subject', 'goal', 'parent_phone', 'parent_consent']);
        $this->register(['kind' => 'guardian', 'child_phone' => '+998901110000', 'contact' => $this->contact('998977776655')])
            ->assertStatus(422)->assertJsonValidationErrors('consent');
        $this->register(['kind' => 'hacker'])->assertStatus(422)->assertJsonValidationErrors('kind');
        $this->register($this->studentForm())->assertStatus(422)->assertJsonValidationErrors('phone');

        $this->assertSame(0, User::query()->where('phone', '+998901112233')->count());
        $this->assertGuest();
    }

    public function test_an_existing_student_is_linked_only_with_a_telegram_verified_phone(): void
    {
        $student = User::factory()->create(['role' => 'student', 'phone' => '+998901112233']);

        // a typed phone never takes an existing account over
        $this->register($this->studentForm(['phone' => '+998901112233']))
            ->assertStatus(422)->assertJsonPath('reason', 'reg.phone_taken');
        $this->assertNull(TelegramAccount::query()->where('telegram_id', self::TG_ID)->value('user_id'));
        $this->assertGuest();

        // the shared contact (Telegram verified it is theirs) links the website account
        $this->register($this->studentForm(['contact' => $this->contact('998901112233')]))->assertOk();
        $this->assertSame($student->id, TelegramAccount::query()->where('telegram_id', self::TG_ID)->value('user_id'));
        $this->assertSame(1, User::query()->where('phone', '+998901112233')->count());
        $this->assertAuthenticatedAs($student);
    }

    public function test_a_typed_phone_creates_a_new_unverified_account(): void
    {
        $this->register($this->studentForm(['phone' => '90 123 45 67']))->assertOk();
        $user = User::query()->where('phone', '+998901234567')->firstOrFail();
        $this->assertNull($user->phone_verified_at);
    }

    public function test_staff_and_linked_phones_are_refused(): void
    {
        User::factory()->create(['role' => 'teacher', 'phone' => '+998907778899']);
        $this->register($this->studentForm(['contact' => $this->contact('998907778899')]))
            ->assertStatus(422)->assertJsonPath('reason', 'reg.role_conflict');
        $this->register(['kind' => 'guardian', 'contact' => $this->contact('998907778899')])
            ->assertStatus(422)->assertJsonPath('reason', 'reg.role_conflict');

        $linked = $this->linkedUser('student', 111222);
        $linked->forceFill(['phone' => '+998905554433'])->save();
        $this->register($this->studentForm(['contact' => $this->contact('998905554433')]))
            ->assertStatus(422)->assertJsonPath('reason', 'reg.already_linked');

        $this->assertNull(TelegramAccount::query()->where('telegram_id', self::TG_ID)->value('user_id'));
        $this->assertGuest();
    }

    public function test_contact_verification(): void
    {
        // someone else's contact, a wrong signature, an old one, a foreign number
        foreach ([
            $this->contact('998901112233', owner: 42),
            $this->contact('998901112233', token: '1:other-bot'),
            $this->contact('998901112233', authDate: time() - 3600),
            str_replace('998901112233', '998901112234', $this->contact('998901112233')),
        ] as $bad) {
            $this->postJson('/telegram/app/contact', ['init_data' => $this->initData(), 'contact' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors('phone');
            $this->register($this->studentForm(['contact' => $bad]))->assertStatus(422)->assertJsonValidationErrors('phone');
        }
        $this->postJson('/telegram/app/contact', ['init_data' => $this->initData(), 'contact' => $this->contact('+79161234567')])
            ->assertStatus(422);

        $this->postJson('/telegram/app/contact', ['init_data' => $this->initData(), 'contact' => $this->contact('998901112233')])
            ->assertOk()->assertJson(['verified' => true, 'phone' => '+998 90 *** ** 33']);
        $this->assertSame(0, User::count());
    }

    public function test_the_contact_the_bot_received_verifies_the_phone(): void
    {
        $this->postJson('/telegram/app/contact', ['init_data' => $this->initData()])->assertOk()->assertJson(['verified' => false]);

        // requestContact also sends the contact to the bot chat: kept, no reply
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);
        $this->webhook(['message' => [
            'message_id' => 5, 'date' => time(),
            'from' => ['id' => self::TG_ID, 'is_bot' => false, 'first_name' => 'Nodira'],
            'chat' => ['id' => self::TG_ID, 'type' => 'private'],
            'contact' => ['phone_number' => '998901112233', 'first_name' => 'Nodira', 'user_id' => self::TG_ID],
        ]])->assertOk();
        Http::assertNothingSent();

        $this->postJson('/telegram/app/contact', ['init_data' => $this->initData()])->assertOk()->assertJson(['verified' => true]);
        $this->register($this->studentForm())->assertOk();
        $this->assertNotNull(User::query()->where('phone', '+998901112233')->value('phone_verified_at'));
    }

    public function test_registration_endpoints_need_json_and_valid_init_data(): void
    {
        $this->post('/telegram/app/register', ['init_data' => $this->initData()] + $this->studentForm())->assertStatus(415);
        $this->post('/telegram/app/contact', ['init_data' => $this->initData()])->assertStatus(415);
        $tampered = str_replace('Nodira', 'Hacker', $this->initData());
        $this->register($this->studentForm(['contact' => $this->contact('998901112233')]), $tampered)->assertStatus(401);
        $this->assertSame(0, User::count());
    }

    public function test_a_registered_user_posting_again_is_just_logged_in(): void
    {
        $user = $this->linkedUser('student');
        $this->register($this->studentForm(['phone' => '+998901112233']))->assertOk()->assertJsonPath('redirect', '/student/exam');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, User::query()->where('phone', '+998901112233')->count());
    }

    // ------------------------------------------------------------------ guardian WebView + notifications

    public function test_guardian_result_message_opens_the_childs_page_in_the_mini_app(): void
    {
        $guardian = $this->linkedUser('guardian');
        $child = User::factory()->create(['role' => 'student']);
        GuardianStudent::factory()->create(['guardian_id' => $guardian->id, 'student_id' => $child->id, 'verified_at' => now(), 'receives_reports' => true]);
        $attempt = ExamAttempt::factory()->create(['user_id' => $child->id, 'status' => 'graded', 'score' => 10, 'max_score' => 20, 'score_percent' => 50]);

        app(\App\Services\Telegram\ExamTelegramNotifier::class)->attemptGraded($attempt);
        Http::assertSent(fn (HttpRequest $r) => ($r->data()['chat_id'] ?? null) === self::TG_ID
            && ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? null) === 'https://exam.example.uz/telegram/app?to=child_'.$child->id);

        // the button logs the guardian in and opens that child (approved links only)
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'to' => 'child_'.$child->id])
            ->assertOk()->assertJsonPath('redirect', '/guardian/children/'.$child->id);
        $stranger = User::factory()->create(['role' => 'student']);
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'to' => 'child_'.$stranger->id])
            ->assertOk()->assertJsonPath('redirect', '/guardian/dashboard');
        $this->get('/guardian/children/'.$stranger->id)->assertNotFound();
    }

    public function test_bot_replies_are_short_and_open_the_mini_app(): void
    {
        $user = $this->linkedUser('student');
        ExamAttempt::factory()->create(['user_id' => $user->id, 'status' => 'graded', 'graded_at' => now(), 'score' => 12, 'max_score' => 30, 'score_percent' => 40]);
        $webApp = fn (HttpRequest $r, string $to) => collect($r->data()['reply_markup']['inline_keyboard'] ?? [])->flatten(1)
            ->contains(fn ($b) => ($b['web_app']['url'] ?? null) === 'https://exam.example.uz/telegram/app?to='.$to);

        $this->chat('/start');
        Http::assertSent(fn (HttpRequest $r) => $webApp($r, 'exam') && $webApp($r, 'progress') && $webApp($r, 'invite'));

        $this->chat('/natijalar');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->data()['text'] ?? '', '12 / 30') && $webApp($r, 'progress')
            && substr_count($r->data()['text'], "\n") <= 2);

        $this->chat('/taklif');
        Http::assertSent(fn (HttpRequest $r) => $webApp($r, 'invite')
            && str_starts_with($r->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? '', 'https://t.me/share/url?url='.rawurlencode('https://t.me/shanba_test_bot?start=ref_')));

        // the invite page in the Mini App
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(), 'to' => 'invite'])
            ->assertOk()->assertJsonPath('redirect', '/student/exam/progress#taklif');
    }

    public function test_unregistered_chat_commands_point_to_the_mini_app(): void
    {
        foreach (['/natijalar', '/battle', '/imtihon', '/taklif', 'salom'] as $command) {
            $this->chat($command);
        }
        Http::assertSentCount(5);
        Http::assertSent(fn (HttpRequest $r) => ($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? null) === 'https://exam.example.uz/telegram/app?to=exam');
        Http::assertNotSent(fn (HttpRequest $r) => ! isset($r->data()['reply_markup']['inline_keyboard'][0][0]['web_app']));
    }

    public function test_blocking_and_unblocking_the_bot_is_tracked(): void
    {
        $user = $this->linkedUser('student');
        $status = fn (string $s) => $this->webhook(['my_chat_member' => [
            'chat' => ['id' => self::TG_ID, 'type' => 'private'],
            'from' => ['id' => self::TG_ID, 'is_bot' => false, 'first_name' => 'Nodira'],
            'date' => time(),
            'old_chat_member' => ['status' => 'member', 'user' => ['id' => 1, 'is_bot' => true, 'first_name' => 'bot']],
            'new_chat_member' => ['status' => $s, 'user' => ['id' => 1, 'is_bot' => true, 'first_name' => 'bot']],
        ]])->assertOk();

        $status('kicked');
        $this->assertTrue(TelegramAccount::query()->where('user_id', $user->id)->value('is_blocked'));
        $status('member');
        $this->assertFalse(TelegramAccount::query()->where('user_id', $user->id)->value('is_blocked'));
        Http::assertNothingSent();
    }

    // ------------------------------------------------------------------ rate limits

    public function test_mini_app_limits_count_per_telegram_user_not_per_shared_ip(): void
    {
        // a class behind one carrier IP (CGNAT) opens the Mini App at the same minute
        for ($i = 0; $i < 40; $i++) {
            $this->postJson('/telegram/app/auth', ['init_data' => $this->initData([], 800000 + $i)])->assertStatus(409);
        }
        // one Telegram user hammering the endpoint is still limited
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/telegram/app/auth', ['init_data' => $this->initData([], 900001)])->assertStatus(409);
        }
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData([], 900001)])->assertStatus(429);
    }

    public function test_battle_routes_have_their_own_counters(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertContains('throttle:battle-state', $routes->getByName('student.battles.state')->gatherMiddleware());
        $this->assertContains('throttle:battle-challenge', $routes->getByName('student.battles.challenge.store')->gatherMiddleware());
        $this->assertContains('throttle:battle-accept', $routes->getByName('student.battles.challenge.accept')->gatherMiddleware());

        // 45 state polls (about a minute of a match) do not use up "Do'stni chaqirish"
        $user = $this->linkedUser('student');
        $this->actingAs($user);
        $missing = '01a00000-0000-7000-8000-000000000000';
        for ($i = 0; $i < 45; $i++) {
            $this->assertNotSame(429, $this->getJson('/student/battles/'.$missing.'/state')->status());
        }
        $this->assertNotSame(429, $this->postJson('/student/battles/challenge', ['difficulty' => 'easy'])->status());
    }

    // ------------------------------------------------------------------ "Saytda ochish"

    public function test_open_on_site_hands_the_session_to_the_browser_once(): void
    {
        $user = $this->linkedUser('student');
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertOk();

        $url = $this->postJson('/telegram/app/site-link', ['path' => '/student/exam/progress'])->assertOk()->json('url');
        $this->assertMatchesRegularExpression('#/telegram/site-login/[A-Za-z0-9]{48}$#', $url);

        // the user's own browser: a fresh session
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect('/student/exam/progress');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('webview'));

        // single use
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_open_on_site_needs_a_mini_app_session(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user)->postJson('/telegram/app/site-link')->assertForbidden();
        $teacher = $this->linkedUser('teacher', 999);
        $this->actingAs($teacher)->withSession(['webview' => 'telegram'])->postJson('/telegram/app/site-link')->assertForbidden();
    }
}
