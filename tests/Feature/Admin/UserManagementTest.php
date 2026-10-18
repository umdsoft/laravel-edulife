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
}
