<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $phone): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'phone' => $phone]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+998901234500',
            'role' => 'student',
            'status' => 'active',
        ], $overrides);
    }

    public function test_admin_cannot_promote_user_to_super_admin(): void
    {
        $admin = $this->user('admin', '+998901234501');
        $student = $this->user('student', '+998901234502');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $student), $this->payload(['phone' => $student->phone, 'role' => 'super_admin']))
            ->assertSessionHasErrors('role');

        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_admin_cannot_edit_or_delete_super_admin(): void
    {
        $admin = $this->user('admin', '+998901234501');
        $root = $this->user('super_admin', '+998901234503');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $root), $this->payload(['phone' => $root->phone, 'password' => 'newpass123', 'password_confirmation' => 'newpass123']))
            ->assertForbidden();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $root))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.users.toggle-status', $root))->assertForbidden();

        $this->assertNotSoftDeleted($root);
        $this->assertSame('active', $root->fresh()->status);
    }

    public function test_super_admin_can_grant_admin_role(): void
    {
        $root = $this->user('super_admin', '+998901234503');
        $student = $this->user('student', '+998901234502');

        $this->actingAs($root)
            ->put(route('admin.users.update', $student), $this->payload(['phone' => $student->phone, 'role' => 'admin']))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('admin', $student->fresh()->role);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $root = $this->user('super_admin', '+998901234503');

        $this->actingAs($root)->delete(route('admin.users.destroy', $root));

        $this->assertNotSoftDeleted($root);
    }

    public function test_admin_can_block_a_student(): void
    {
        $admin = $this->user('admin', '+998901234501');
        $student = $this->user('student', '+998901234502');

        $this->actingAs($admin)->patch(route('admin.users.toggle-status', $student))->assertRedirect();

        $this->assertSame('blocked', $student->fresh()->status);
    }

    /** QA-A-06: creating a user failed on the non-fillable phone_verified_at */
    public function test_admin_creates_students_and_teachers(): void
    {
        $admin = $this->user('admin', '+998901111111');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Yangi',
            'last_name' => 'Ustoz',
            'phone' => '+998901112233',
            'email' => 'ustoz@example.com',
            'password' => 'Parol12345',
            'password_confirmation' => 'Parol12345',
            'role' => 'teacher',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $user = User::where('phone', '+998901112233')->firstOrFail();
        $this->assertSame('teacher', $user->role);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNotNull($user->teacherProfile);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Yangi', 'last_name' => "O'quvchi", 'phone' => '+998901112244',
            'password' => 'Parol12345', 'password_confirmation' => 'Parol12345', 'role' => 'student',
        ])->assertSessionHasNoErrors();
        $this->assertNotNull(User::where('phone', '+998901112244')->firstOrFail()->studentProfile);
    }
}
