<?php

namespace Tests\Feature\Student;

use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Rewards and purchases change student_profiles; the header read users.*.
 */
class CoinsSingleSourceTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_shared_props_show_the_student_profile_balance(): void
    {
        $student = $this->studentUser();
        $student->forceFill(['coin_balance' => 999, 'xp_total' => 999])->save();
        $student->studentProfile->update(['xp' => 120, 'level' => 2]);
        app(CoinService::class)->addCoins($student, 50, 'test', 'Sinov');

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.coin_balance', 50)
                ->where('auth.user.xp_total', 120)
                ->where('auth.user.level', 2)
                ->where('auth.studentProfile.coins', 50));

        $this->assertSame(50, $student->fresh()->coins);
    }
}
