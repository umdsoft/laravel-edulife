<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA-P-01: hitting the login/register rate limit showed the raw 429 page.
 * QA-P-02: a too-short password hid the "Parollar mos kelmadi" error.
 */
class AuthFormErrorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rate_limit_comes_back_as_an_uzbek_form_error(): void
    {
        foreach (range(1, 5) as $i) {
            $this->from(route('login'))->post(route('login.submit'), ['login' => '+998900000000', 'password' => 'wrong-pass']);
        }

        $this->from(route('login'))
            ->post(route('login.submit'), ['login' => '+998900000000', 'password' => 'wrong-pass'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertStringContainsString("Juda ko'p urinish", session('errors')->first('login'));
    }

    public function test_register_rate_limit_comes_back_as_an_uzbek_form_error(): void
    {
        foreach (range(1, 3) as $i) {
            $this->from(route('register'))->post(route('register.submit'), ['first_name' => 'A']);
        }

        $this->from(route('register'))
            ->post(route('register.submit'), ['first_name' => 'A'])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('phone');
        $this->assertStringContainsString('soniyadan keyin', session('errors')->first('phone'));
    }

    public function test_json_clients_still_get_a_429(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson(route('login.submit'), ['login' => '+998900000000', 'password' => 'wrong-pass']);
        }

        $this->postJson(route('login.submit'), ['login' => '+998900000000', 'password' => 'wrong-pass'])
            ->assertStatus(429);
    }

    public function test_short_and_mismatched_passwords_are_both_reported(): void
    {
        $this->from(route('register'))
            ->post(route('register.submit'), [
                'first_name' => 'Ali', 'last_name' => 'Valiyev', 'phone' => '+998901112233',
                'password' => '123', 'password_confirmation' => '321',
            ])
            ->assertSessionHasErrors(['password', 'password_confirmation' => 'Parollar mos kelmadi']);
    }
}
