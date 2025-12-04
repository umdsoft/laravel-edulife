<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login page is accessible
     */
    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /**
     * Test user can login with correct credentials
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'phone' => '+998901234567',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'phone' => '+998901234567',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
    }

    /**
     * Test user cannot login with incorrect password
     */
    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'phone' => '+998901234567',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'phone' => '+998901234567',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }

    /**
     * Test login is rate limited
     */
    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'phone' => '+998901234567',
            'password' => bcrypt('password123'),
        ]);

        // Attempt login 6 times (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'phone' => '+998901234567',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test registration page is accessible
     */
    public function test_registration_page_is_displayed(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    /**
     * Test user can register
     */
    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+998901234568',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
    }
}
