<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $phone = '+998901112233'): User
    {
        return User::factory()->create(['role' => 'student', 'status' => 'active', 'phone' => $phone]);
    }

    public function test_cannot_take_another_users_phone(): void
    {
        $this->student('+998909999999');
        $user = $this->student();

        $this->actingAs($user)
            ->put(route('student.settings.account.update'), [
                'first_name' => 'A', 'last_name' => 'B', 'phone' => '+998909999999',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame('+998901112233', $user->fresh()->phone);
    }

    public function test_phone_cannot_be_cleared(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->put(route('student.settings.account.update'), ['first_name' => 'A', 'last_name' => 'B', 'phone' => ''])
            ->assertSessionHasErrors('phone');
    }

    public function test_privacy_settings_are_persisted(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->put(route('student.settings.privacy.update'), ['is_profile_public' => false, 'show_stats' => false])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_profile_public);
        $this->assertFalse($user->fresh()->show_stats);
    }

    public function test_deleted_account_can_no_longer_log_in(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->delete(route('student.settings.account.delete'), ['password' => 'password', 'confirmation' => 'DELETE'])
            ->assertRedirect('/');

        $this->assertSoftDeleted($user);
        $this->assertGuest();

        $this->post('/login', ['phone' => '+998901112233', 'password' => 'password']);
        $this->assertGuest();
    }
}
