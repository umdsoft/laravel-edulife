<?php

namespace Tests\Feature\Exam\Stage7;

use App\Models\Exam\GuardianStudent;
use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoGuardianSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_guardian_is_linked_to_the_demo_student_and_reseeding_is_idempotent(): void
    {
        $this->seed(TestUserSeeder::class);
        $this->seed(TestUserSeeder::class);

        $guardian = User::where('email', 'guardian@test.uz')->firstOrFail();
        $student = User::where('email', 'student@test.uz')->firstOrFail();
        $this->assertSame('guardian', $guardian->role);
        $this->assertTrue(Hash::check('password123', $guardian->password));
        $this->assertSame(1, User::where('email', 'guardian@test.uz')->count());

        $link = GuardianStudent::where('guardian_id', $guardian->id)->where('student_id', $student->id)->sole();
        $this->assertTrue($link->isVerified());
        $this->assertNotNull($link->consent_at);

        $this->actingAs($guardian)->get(route('guardian.children.show', $student))->assertOk();
    }
}
