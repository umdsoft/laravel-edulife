<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedUserTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'phone' => '+998901112233',
            'role' => 'student',
            'status' => 'active',
        ], $attributes));
    }

    public function test_active_user_can_login_by_phone(): void
    {
        $this->makeUser();

        $this->post('/login', ['login' => '+998901112233', 'password' => 'password'])
            ->assertRedirect('/student/dashboard');

        $this->assertAuthenticated();
    }

    public function test_blocked_user_cannot_login(): void
    {
        $this->makeUser(['status' => 'blocked']);

        $this->post('/login', ['login' => '+998901112233', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_blocked_user_cannot_login_by_email(): void
    {
        $this->makeUser(['status' => 'blocked', 'email' => 'blocked@example.com']);

        $this->post('/login', ['login' => 'blocked@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_unknown_and_blocked_get_same_generic_error(): void
    {
        $this->makeUser(['status' => 'blocked']);

        $this->post('/login', ['login' => '+998901112233', 'password' => 'password'])
            ->assertSessionHasErrors('login');
        $blockedMessage = session('errors')->first('login');

        $this->post('/login', ['login' => '+998900000000', 'password' => 'whatever1'])
            ->assertSessionHasErrors(['login' => $blockedMessage]);
    }

    public function test_registration_can_not_be_used_to_pick_a_staff_role(): void
    {
        // no password registration at all
        $this->post('/register', [
            'first_name' => 'Ali', 'last_name' => 'Valiyev', 'phone' => '+998907654321',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'super_admin',
        ])->assertStatus(405);
        $this->assertGuest();

        // the Telegram registration link accepts learner roles only
        $this->postJson('/login/telegram', ['role' => 'super_admin'])->assertStatus(422)->assertJsonValidationErrors('role');
        $this->assertSame(0, \App\Models\TelegramLoginRequest::query()->count());
    }
}
