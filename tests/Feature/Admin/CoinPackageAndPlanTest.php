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

    /** QA2-A-08: "12 000 so'm ~~100 so'm~~" was accepted */
    public function test_coin_package_original_price_cannot_be_below_the_price(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.coin-packages.store'), [
            'name' => 'QA2 paket', 'coins' => 100, 'price' => 12000, 'original_price' => 100,
        ])->assertSessionHasErrors(['original_price' => "Asl narx joriy narxdan kam bo'lmasligi kerak"]);

        $this->actingAs($admin)->post(route('admin.coin-packages.store'), [
            'name' => 'QA2 paket', 'coins' => 100, 'price' => 12000, 'original_price' => 12000,
        ])->assertSessionHasNoErrors();
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

    /** QA2-A-03: seeded plans keep original_price = price; editing only the description must save */
    public function test_plan_without_discount_can_be_edited(): void
    {
        $admin = $this->admin();
        $payload = [
            'name' => 'Standart', 'slug' => 'standart', 'description' => 'Eski', 'price' => 149000, 'original_price' => 149000,
            'interval' => 'month', 'interval_count' => 1, 'is_featured' => false, 'is_active' => true,
        ];
        $this->actingAs($admin)->post(route('admin.subscriptions.store-plan'), $payload)->assertSessionHasNoErrors();
        $plan = SubscriptionPlan::where('slug', 'standart')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.subscriptions.update-plan', $plan), [...$payload, 'description' => 'Yangi'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Yangi', $plan->fresh()->description);

        $this->actingAs($admin)->put(route('admin.subscriptions.update-plan', $plan), [...$payload, 'original_price' => 100000])
            ->assertSessionHasErrors(['original_price' => "Asl narx joriy narxdan kam bo'lmasligi kerak"]);
    }

    /** QA2-A-09: the plan form had no features field */
    public function test_plan_features_are_saved_from_the_form(): void
    {
        $admin = $this->admin();
        $payload = [
            'name' => 'Pro', 'slug' => 'pro', 'price' => 99000, 'interval' => 'month', 'interval_count' => 1,
            'is_featured' => false, 'is_active' => true, 'features' => ['Barcha kurslar', '10 battle/kun'],
        ];

        $this->actingAs($admin)->post(route('admin.subscriptions.store-plan'), $payload)->assertSessionHasNoErrors();
        $plan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $this->assertSame(['Barcha kurslar', '10 battle/kun'], $plan->features);

        $this->actingAs($admin)->get(route('admin.subscriptions.plans'))
            ->assertInertia(fn ($page) => $page->where('plans.0.features', ['Barcha kurslar', '10 battle/kun']));

        $this->actingAs($admin)->put(route('admin.subscriptions.update-plan', $plan), [...$payload, 'features' => [str_repeat('a', 121)]])
            ->assertSessionHasErrors('features.0');
    }

    /** The seeder restores the seeded plans (e.g. "VIP Yillik" features) without duplicating them */
    public function test_subscription_plan_seeder_is_idempotent_and_restores_plans(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        SubscriptionPlan::where('slug', 'vip-yearly')->update(['features' => null, 'sort_order' => 0]);

        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);

        $this->assertSame(7, SubscriptionPlan::count());
        $vip = SubscriptionPlan::where('slug', 'vip-yearly')->firstOrFail();
        $this->assertContains('2x XP multiplikator', $vip->features);
        $this->assertSame(7, $vip->sort_order);
    }
}
