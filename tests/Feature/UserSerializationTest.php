<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_serialized_user_hides_contact_and_security_fields(): void
    {
        $user = User::factory()->create(['first_name' => 'Ali', 'last_name' => 'Valiyev']);

        $data = $user->fresh()->toArray();

        foreach (['password', 'remember_token', 'phone', 'email', 'failed_login_attempts', 'locked_until'] as $field) {
            $this->assertArrayNotHasKey($field, $data);
        }
        $this->assertSame('Ali Valiyev', $data['full_name']);
        $this->assertArrayHasKey('avatar_url', $data);
        // Public profile fields must stay visible
        $this->assertArrayHasKey('bio', $data);
        $this->assertArrayHasKey('username', $data);
    }

    public function test_own_profile_page_still_receives_contact_fields(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active', 'phone' => '+998901110000']);

        $this->actingAs($teacher)
            ->get(route('teacher.profile.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('user.phone', '+998901110000'));
    }
}
