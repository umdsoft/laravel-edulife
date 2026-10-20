<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Telegram\InvalidInitData;
use App\Services\Telegram\TelegramInitData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Stage 6: Telegram Mini App initData is verified on the server (HMAC with
 * the bot token, auth_date TTL) before the linked user is logged in.
 */
class TelegramMiniAppTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '987654:MINIAPP-token';
    private const TG_ID = 424242;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['exam.telegram.bot_token' => self::TOKEN, 'exam.telegram.init_data_ttl' => 3600]);
    }

    /** initData exactly as Telegram builds it (URL-encoded query string). */
    private function initData(array $override = [], ?string $token = self::TOKEN, ?int $authDate = null): string
    {
        $fields = array_merge([
            'query_id' => 'AAHdF6IQAAAAAN0XohDhrOrc',
            'user' => json_encode(['id' => self::TG_ID, 'first_name' => 'Aziz', 'last_name' => 'Karimov', 'username' => 'aziz', 'language_code' => 'uz'], JSON_UNESCAPED_UNICODE),
            'auth_date' => (string) ($authDate ?? time()),
            'signature' => 'ZmFrZS1zaWduYXR1cmU',
        ], $override);
        $fields['hash'] = TelegramInitData::sign($fields, $token);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    private function linkedStudent(): User
    {
        $user = User::factory()->create(['role' => 'student']);
        TelegramAccount::factory()->create(['user_id' => $user->id, 'telegram_id' => self::TG_ID]);

        return $user;
    }

    public function test_valid_init_data_logs_the_linked_student_in(): void
    {
        $user = $this->linkedStudent();

        $response = $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()]);

        $expected = Route::has('student.exam.index') ? route('student.exam.index', [], false) : '/student/dashboard';
        $response->assertOk()->assertJson(['redirect' => $expected]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_tampered_init_data_is_rejected(): void
    {
        $this->linkedStudent();
        $valid = $this->initData();
        // change the user id but keep the old hash
        $tampered = str_replace(urlencode('"id":'.self::TG_ID), urlencode('"id":1'), $valid);
        $tampered = str_replace(rawurlencode('"id":'.self::TG_ID), rawurlencode('"id":1'), $tampered);

        $this->postJson('/telegram/app/auth', ['init_data' => $tampered])->assertStatus(401)->assertJson(['reason' => 'bad_hash']);
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData([], 'other:token')])->assertStatus(401)->assertJson(['reason' => 'bad_hash']);
        $this->postJson('/telegram/app/auth', ['init_data' => 'user=%7B%7D&auth_date=1'])->assertStatus(401)->assertJson(['reason' => 'missing_hash']);
        $this->assertGuest();
    }

    public function test_expired_init_data_is_rejected(): void
    {
        $this->linkedStudent();

        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData(authDate: time() - 7200)])
            ->assertStatus(401)->assertJson(['reason' => 'expired']);
        $this->assertGuest();
    }

    public function test_unregistered_telegram_user_gets_a_hint_not_a_session(): void
    {
        TelegramAccount::factory()->unlinked()->create(['telegram_id' => self::TG_ID]);

        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertStatus(409);
        $this->assertGuest();
    }

    public function test_without_bot_token_nothing_is_accepted(): void
    {
        $this->linkedStudent();
        $data = $this->initData();
        config(['exam.telegram.bot_token' => '']);

        $this->postJson('/telegram/app/auth', ['init_data' => $data])->assertStatus(401)->assertJson(['reason' => 'not_configured']);
        $this->assertGuest();
    }

    public function test_verifier_returns_the_user_and_start_param(): void
    {
        $result = app(TelegramInitData::class)->verify($this->initData(['start_param' => 'exam']));

        $this->assertSame(self::TG_ID, $result['user']['id']);
        $this->assertSame('exam', $result['start_param']);

        $this->expectException(InvalidInitData::class);
        app(TelegramInitData::class)->verify($this->initData(['user' => 'not-json']));
    }

    public function test_guardian_goes_to_the_guardian_area(): void
    {
        $user = User::factory()->create(['role' => 'guardian']);
        TelegramAccount::factory()->create(['user_id' => $user->id, 'telegram_id' => self::TG_ID]);

        $expected = Route::has('guardian.dashboard') ? route('guardian.dashboard', [], false) : '/';
        $this->postJson('/telegram/app/auth', ['init_data' => $this->initData()])->assertOk()->assertJson(['redirect' => $expected]);
    }

    public function test_mini_app_page_renders(): void
    {
        $this->get('/telegram/app?to=exam')
            ->assertOk()
            ->assertSee('telegram-web-app.js', false)
            ->assertSee(json_encode(route('telegram.app.auth')), false);
    }
}
