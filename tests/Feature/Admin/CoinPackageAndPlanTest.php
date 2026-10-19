<?php

namespace Tests\Feature\Admin;

use App\Models\CoinPackage;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinPackageAndPlanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** QA-A-07 / QA-A-08 */
    public function test_coin_package_can_be_created_and_edited_with_decimal_prices(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.coin-packages.store'), [
            'name' => 'Boshlang\'ich', 'coins' => 100, 'bonus_coins' => 10, 'price' => 15000, 'is_featured' => true,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $package = CoinPackage::firstOrFail();
        $this->assertTrue($package->is_popular);
        $this->assertSame(110, $package->total_coins);

        // the edit form sends the decimal strings it received
        $this->actingAs($admin)->put(route('admin.coin-packages.update', $package), [
            'name' => 'Boshlang\'ich', 'coins' => 100, 'bonus_coins' => 25, 'price' => '15000.00', 'original_price' => '20000.00',
            'is_featured' => false,
        ])->assertSessionHasNoErrors();

        $package->refresh();
        $this->assertSame(125, $package->total_coins);
        $this->assertFalse($package->is_popular);
        $this->assertEquals(20000, $package->original_price);
    }

    /** QA-A-09 */
    public function test_subscription_plan_can_be_created_and_updated(): void
    {
        $admin = $this->admin();
        $payload = [
            'name' => 'Pro', 'slug' => 'pro', 'description' => 'Hammasi', 'price' => 99000, 'original_price' => 120000,
            'interval' => 'month', 'interval_count' => 1, 'is_featured' => true, 'is_active' => true,
        ];

        $this->actingAs($admin)->post(route('admin.subscriptions.store-plan'), $payload)->assertSessionHasNoErrors();
        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $this->assertEquals(120000, $plan->original_price);

        $this->actingAs($admin)->put(route('admin.subscriptions.update-plan', $plan), [...$payload, 'description' => 'Yangi', 'price' => '99000.00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Yangi', $plan->fresh()->description);
    }
}
