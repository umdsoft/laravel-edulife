<?php

namespace Tests\Feature\Student;

use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * The page posts the Achievement id, the route bound it as a UserAchievement
 * id → every claim was a 404.
 */
class AchievementClaimTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_unlocked_achievement_reward_is_claimed_once_by_achievement_id(): void
    {
        $student = $this->studentUser();
        $achievement = Achievement::forceCreate([
            'code' => 'first_lesson', 'title' => 'Birinchi qadam', 'description' => 'Birinchi dars', 'category' => 'learning',
            'rarity' => 'common', 'xp_reward' => 50, 'coin_reward' => 10, 'requirements' => ['lessons' => 1], 'is_active' => true,
        ]);
        $locked = Achievement::forceCreate([
            'code' => 'lessons_10', 'title' => "O'quvchi", 'description' => '10 dars', 'category' => 'learning',
            'rarity' => 'common', 'xp_reward' => 100, 'coin_reward' => 20, 'requirements' => ['lessons' => 10], 'is_active' => true,
        ]);
        UserAchievement::forceCreate(['user_id' => $student->id, 'achievement_id' => $achievement->id, 'earned_at' => now(), 'is_claimed' => false]);

        $this->actingAs($student)->postJson(route('student.achievements.claim', $achievement))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(10, $student->fresh()->coins);

        $this->actingAs($student)->postJson(route('student.achievements.claim', $achievement))
            ->assertJsonPath('success', false)->assertJsonPath('message', 'Mukofot allaqachon olingan');
        $this->assertSame(10, $student->fresh()->coins);

        $this->actingAs($student)->postJson(route('student.achievements.claim', $locked))->assertStatus(422);
    }
}
