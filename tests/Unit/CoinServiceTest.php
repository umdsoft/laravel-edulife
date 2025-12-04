<?php

namespace Tests\Unit;

use App\Services\CoinService;
use App\Models\User;
use App\Models\ShopItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoinService $coinService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coinService = new CoinService();
        $this->user = User::factory()->create(['role' => 'student']);
        
        // Create student profile with coins
        $this->user->studentProfile()->create([
            'coins' => 1000,
            'total_coins_earned' => 1000,
            'level' => 1,
            'xp' => 0,
        ]);
    }

    /**
     * Test adding coins to user balance
     */
    public function test_can_add_coins_to_user(): void
    {
        $initialCoins = $this->user->studentProfile->coins;
        
        $this->coinService->addCoins($this->user, 100, 'test', 'Test reward');
        
        $this->user->refresh();
        $this->assertEquals($initialCoins + 100, $this->user->studentProfile->coins);
    }

    /**
     * Test spending coins from user balance
     */
    public function test_can_spend_coins(): void
    {
        $initialCoins = $this->user->studentProfile->coins;
        
        $result = $this->coinService->spendCoins($this->user, 100, 'test', 'Test purchase');
        
        $this->assertTrue($result);
        $this->user->refresh();
        $this->assertEquals($initialCoins - 100, $this->user->studentProfile->coins);
    }

    /**
     * Test cannot spend more coins than balance
     */
    public function test_cannot_spend_more_than_balance(): void
    {
        $result = $this->coinService->spendCoins($this->user, 5000, 'test', 'Test purchase');
        
        $this->assertFalse($result);
        $this->user->refresh();
        $this->assertEquals(1000, $this->user->studentProfile->coins);
    }

    /**
     * Test purchasing shop item
     */
    public function test_can_purchase_shop_item(): void
    {
        $item = ShopItem::factory()->create([
            'price' => 100,
            'is_active' => true,
            'min_level' => 1,
            'max_per_user' => 10,
            'stock' => 100,
        ]);

        $result = $this->coinService->purchaseItem($this->user, $item);

        $this->assertTrue($result['success']);
        $this->user->refresh();
        $this->assertEquals(900, $this->user->studentProfile->coins);
    }

    /**
     * Test cannot purchase if not enough coins
     */
    public function test_cannot_purchase_without_enough_coins(): void
    {
        $item = ShopItem::factory()->create([
            'price' => 5000,
            'is_active' => true,
            'min_level' => 1,
        ]);

        $result = $this->coinService->purchaseItem($this->user, $item);

        $this->assertFalse($result['success']);
        $this->assertEquals('Not enough coins', $result['message']);
    }

    /**
     * Test cannot purchase if level too low
     */
    public function test_cannot_purchase_if_level_too_low(): void
    {
        $item = ShopItem::factory()->create([
            'price' => 100,
            'is_active' => true,
            'min_level' => 10,
        ]);

        $result = $this->coinService->purchaseItem($this->user, $item);

        $this->assertFalse($result['success']);
        $this->assertEquals('Level too low', $result['message']);
    }
}
