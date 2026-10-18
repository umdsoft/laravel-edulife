<?php

namespace Tests\Feature\Student;

use App\Models\Achievement;
use App\Models\DailyMission;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserMission;
use App\Services\AchievementService;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardClaimTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'status' => 'active']);
    }

    private function completedMission(User $user, bool $completed = true): UserMission
    {
        $mission = DailyMission::create([
            'code' => 'watch_1',
            'title' => 'Watch a lesson',
            'description' => 'Watch one lesson',
            'icon' => 'play',
            'task_type' => 'watch_lesson',
            'target_value' => 1,
            'xp_reward' => 20,
            'coin_reward' => 15,
        ]);

        return UserMission::create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'current_value' => $completed ? 1 : 0,
            'target_value' => 1,
            'is_completed' => $completed,
            'is_claimed' => false,
            'assigned_date' => today(),
        ]);
    }

    public function test_mission_reward_can_be_claimed_only_once(): void
    {
        $user = $this->student();
        $userMission = $this->completedMission($user);

        $this->actingAs($user)->postJson(route('student.missions.claim', $userMission))
            ->assertOk()->assertJson(['success' => true]);

        $this->actingAs($user)->postJson(route('student.missions.claim', $userMission))
            ->assertOk()->assertJson(['success' => false]);

        $profile = $user->studentProfile()->first();
        $this->assertSame(15, $profile->coins);
        $this->assertSame(20, $profile->xp);
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_stale_model_cannot_double_claim_mission(): void
    {
        $user = $this->student();
        $userMission = $this->completedMission($user);
        $stale = UserMission::find($userMission->id);

        $service = app(\App\Services\MissionService::class);
        $this->assertTrue($service->claimReward($user, $userMission)['success']);
        // Simulates a concurrent request that loaded the row before the first claim committed
        $this->assertFalse($service->claimReward($user, $stale)['success']);

        $this->assertSame(15, $user->studentProfile()->first()->coins);
    }

    public function test_incomplete_mission_cannot_be_claimed(): void
    {
        $user = $this->student();
        $userMission = $this->completedMission($user, completed: false);

        $this->actingAs($user)->postJson(route('student.missions.claim', $userMission))
            ->assertJson(['success' => false]);

        $this->assertSame(0, $user->studentProfile()->first()->coins);
    }

    public function test_user_cannot_claim_someone_elses_mission(): void
    {
        $owner = $this->student();
        $userMission = $this->completedMission($owner);

        $this->actingAs($this->student())->postJson(route('student.missions.claim', $userMission))
            ->assertForbidden();
    }

    public function test_achievement_unlock_and_single_claim(): void
    {
        $user = $this->student();
        $achievement = Achievement::create([
            'code' => 'first_lesson',
            'title' => 'First lesson',
            'description' => 'Complete a lesson',
            'icon' => 'star',
            'category' => 'learning',
            'xp_reward' => 50,
            'coin_reward' => 10,
            'requirements' => ['type' => 'first_lesson', 'value' => 1],
        ]);

        $service = app(AchievementService::class);
        $userAchievement = $service->unlock($user, $achievement);
        $this->assertNotNull($userAchievement->earned_at);

        $stale = UserAchievement::find($userAchievement->id);
        $this->assertTrue($service->claim($user, $userAchievement)['success']);
        $this->assertFalse($service->claim($user, $stale)['success']);

        $profile = $user->studentProfile()->first();
        $this->assertSame(10, $profile->coins);
        $this->assertSame(50, $profile->xp);
    }

    private function shopItem(array $attributes = []): ShopItem
    {
        return ShopItem::create(array_merge([
            'name' => 'Golden frame',
            'description' => 'Avatar frame',
            'image' => 'frame.png',
            'type' => 'avatar',
            'price' => 100,
            'max_per_user' => 5,
            'min_level' => 1,
            'is_active' => true,
        ], $attributes));
    }

    public function test_purchase_fails_without_enough_coins(): void
    {
        $user = $this->student();
        $item = $this->shopItem();

        $result = app(CoinService::class)->purchaseItem($user, $item);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('user_purchases', 0);
    }

    public function test_purchase_deducts_coins_and_respects_stock(): void
    {
        $user = $this->student();
        $user->studentProfile()->update(['coins' => 1000]);
        $item = $this->shopItem(['stock' => 1]);

        $service = app(CoinService::class);
        $this->assertTrue($service->purchaseItem($user->fresh(), $item, 1)['success']);
        $this->assertFalse($service->purchaseItem($user->fresh(), $item, 1)['success']);

        $this->assertSame(900, $user->studentProfile()->first()->coins);
        $this->assertSame(0, $item->fresh()->stock);
    }

    public function test_purchase_quantity_cannot_exceed_stock(): void
    {
        $user = $this->student();
        $user->studentProfile()->update(['coins' => 1000]);
        $item = $this->shopItem(['stock' => 2]);

        $this->assertFalse(app(CoinService::class)->purchaseItem($user->fresh(), $item, 3)['success']);
        $this->assertSame(1000, $user->studentProfile()->first()->coins);
    }

    public function test_spend_coins_never_goes_negative_with_stale_balance(): void
    {
        $user = $this->student();
        $user->studentProfile()->update(['coins' => 100]);
        $user = $user->fresh();
        $user->load('studentProfile');

        $item = $this->shopItem();
        $service = app(CoinService::class);
        $this->assertTrue($service->spendCoins($user, 100, 'test', 'first', $item));
        // The in-memory profile still says 100 coins; the DB guard must reject this
        $user->studentProfile->coins = 100;
        $this->assertFalse($service->spendCoins($user, 100, 'test', 'second', $item));

        $this->assertSame(0, $user->studentProfile()->first()->coins);
    }
}
