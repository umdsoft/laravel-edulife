<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserPurchase;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class ShopAndPaidCourseTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function item(array $attributes = []): ShopItem
    {
        return ShopItem::forceCreate(array_merge([
            'name' => 'Ninja Avatar', 'description' => 'Avatar', 'type' => 'avatar', 'price' => 100,
            'max_per_user' => 1, 'min_level' => 1, 'is_active' => true,
        ], $attributes));
    }

    /** QA-S-03 / QA-S-11 */
    public function test_student_buys_an_item_with_coins_and_equips_it(): void
    {
        $student = $this->studentUser();
        app(CoinService::class)->addCoins($student, 150, 'test', 'Sinov');
        $ninja = $this->item();
        $robot = $this->item(['name' => 'Robot Avatar']);

        $this->actingAs($student)->get(route('student.shop.index'))
            ->assertInertia(fn (Assert $page) => $page->has('items', 2)->where('balance', 150)->where('onlinePaymentAvailable', false));

        $this->actingAs($student)->post(route('student.shop.items.buy', $ninja))->assertSessionHas('success');
        $this->assertSame(50, $student->fresh()->coins);

        // not enough coins for the second avatar
        $this->actingAs($student)->post(route('student.shop.items.buy', $robot))
            ->assertSessionHas('error', 'Coin yetarli emas: 100 coin kerak.');

        $purchase = UserPurchase::where('user_id', $student->id)->firstOrFail();
        $this->actingAs($student)->get(route('student.shop.myItems'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Shop/MyItems')->where('purchases.0.equippable', true));

        $this->actingAs($student)->post(route('student.shop.equip', $purchase))->assertSessionHas('success');
        $this->assertTrue($purchase->fresh()->is_equipped);

        // someone else's purchase
        $this->actingAs($this->studentUser())->post(route('student.shop.equip', $purchase))->assertForbidden();
    }

    /** QA3-S-04: an equipped avatar was shown nowhere (the header etc. kept the initials) */
    public function test_the_equipped_avatar_and_badge_are_shown(): void
    {
        $student = $this->studentUser();
        $student->studentProfile()->firstOrCreate([])->update(['xp' => 50]);
        $ninja = $this->item(['image' => '/images/shop/avatars/ninja.svg']);
        $vip = $this->item(['name' => 'VIP nishoni', 'type' => 'badge', 'image' => '/images/shop/badges/vip.svg']);
        $this->assertFileExists(public_path('images/shop/avatars/ninja.svg'));

        // nothing equipped yet: the photo (none) is used
        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn (Assert $p) => $p->where('auth.user.avatar_url', null));

        foreach ([$ninja, $vip] as $item) {
            $purchase = UserPurchase::forceCreate(['user_id' => $student->id, 'item_id' => $item->id, 'price_paid' => 0, 'quantity' => 1, 'is_active' => true]);
            $this->actingAs($student)->post(route('student.shop.equip', $purchase))->assertSessionHas('success');
        }

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn (Assert $p) => $p->where('auth.user.avatar_url', '/images/shop/avatars/ninja.svg'));
        $this->actingAs($student)->get(route('student.profile.show'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('user.avatar_url', '/images/shop/avatars/ninja.svg')
                ->missing('user.equipped_avatar')
                ->where('equippedBadge.name', 'VIP nishoni'));
        $this->actingAs($this->studentUser())->get(route('student.users.show', $student->id))
            ->assertInertia(fn (Assert $p) => $p->where('user.avatar_url', '/images/shop/avatars/ninja.svg'));
        $this->actingAs($student)->get(route('student.leaderboard.index'))
            ->assertInertia(fn (Assert $p) => $p->where('leaderboard.0.avatar', '/images/shop/avatars/ninja.svg'));
        $this->actingAs($student)->get(route('student.shop.myItems'))
            ->assertInertia(fn (Assert $p) => $p->where('purchases.0.image_url', fn ($url) => str_starts_with((string) $url, '/images/shop/')));
    }

    public function test_coin_packages_explain_that_online_payment_is_not_connected(): void
    {
        $package = \App\Models\CoinPackage::forceCreate(['name' => 'Kichik', 'coins' => 100, 'bonus_coins' => 0, 'total_coins' => 100, 'price' => 10000, 'is_active' => true]);

        $this->actingAs($this->studentUser())->post(route('student.shop.purchase', $package))
            ->assertSessionHas('info');
    }

    /** QA-S-04: no payment gateway → clear message, admin opens access after an offline payment */
    public function test_paid_course_is_opened_by_an_admin(): void
    {
        $course = $this->course($this->teacher(), ['is_free' => false, 'price' => 200000]);
        $student = $this->studentUser();

        $this->actingAs($student)->post(route('student.courses.enroll', $course))->assertSessionHas('info');
        $this->assertFalse($student->isEnrolled($course));

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.users.show', $student))
            ->assertInertia(fn (Assert $page) => $page->where('availableCourses.0.id', $course->id));

        $this->actingAs($admin)->post(route('admin.users.enroll', $student), ['course_id' => $course->id])
            ->assertSessionHas('success');

        $enrollment = Enrollment::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('gift', $enrollment->access_type);
        $this->assertSame(1, $student->notifications()->where('type', 'course_access')->count());

        $this->actingAs($student)->get(route('student.learn.course', $course))->assertOk();
    }

    public function test_seeded_shop_is_uzbek_and_reseeding_does_not_duplicate_it(): void
    {
        $this->seed(\Database\Seeders\ShopItemSeeder::class);
        $this->seed(\Database\Seeders\ShopItemSeeder::class);

        $this->assertSame(11, \App\Models\ShopItem::count());
        $this->assertTrue(\App\Models\ShopItem::where('name', 'Test maslahati (5 ta)')->exists());
        $this->assertFalse(\App\Models\ShopItem::where('name', 'like', '%Hint%')->orWhere('name', 'like', '%Badge%')->exists());
    }
}
