<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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

    public function test_guest_is_redirected_from_home_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->makeUser();

        $this->post('/login', ['phone' => '+998901112233', 'password' => 'password'])
            ->assertRedirect('/student/dashboard');

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser();

        $this->post('/login', ['phone' => '+998901112233', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_unknown_phone_gets_same_generic_error(): void
    {
        $this->post('/login', ['phone' => '+998900000000', 'password' => 'whatever1'])
            ->assertSessionHasErrors(['phone' => "Telefon yoki parol noto'g'ri"]);
    }

    public function test_blocked_user_cannot_login(): void
    {
        $this->makeUser(['status' => 'blocked']);

        $this->post('/login', ['phone' => '+998901112233', 'password' => 'password'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['phone' => '+998901112233', 'password' => 'wrong-password']);
        }

        $this->post('/login', ['phone' => '+998901112233', 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_registration_always_creates_a_student(): void
    {
        $this->post('/register', [
            'first_name' => 'Ali',
            'last_name' => 'Valiyev',
            'phone' => '+998907654321',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'super_admin',
            'terms' => true,
        ]);

        $user = User::where('phone', '+998907654321')->first();
        $this->assertNotNull($user, 'Registration should create the user');
        $this->assertSame('student', $user->role);
        $this->assertNotNull($user->studentProfile);
    }

    public function test_logout_ends_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
