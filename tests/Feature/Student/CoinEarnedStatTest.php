<?php

namespace Tests\Feature\Student;

use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinEarnedStatTest extends TestCase
{
    use RefreshDatabase;

    public function test_bought_and_refunded_coins_are_not_counted_as_earned(): void
    {
        $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $coins = app(CoinService::class);

        $coins->addCoins($user, 30, 'lab_experiment', 'Lab');
        $coins->addCoins($user, 500, 'coin_package', 'Coin paketi');
        $coins->addCoins($user, 5, 'tournament_refund', 'Qaytarildi');

        $profile = $user->studentProfile()->first();
        $this->assertSame(535, $profile->coins);
        $this->assertSame(30, $profile->total_coins_earned);

        $this->actingAs($user)
            ->get(route('student.coins.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('total_earned', 30));
    }

    public function test_is_earning_classification(): void
    {
        $this->assertTrue(CoinService::isEarning('lesson_complete'));
        $this->assertFalse(CoinService::isEarning('coin_package'));
        $this->assertFalse(CoinService::isEarning('legacy_transfer'));
        $this->assertFalse(CoinService::isEarning('olympiad_refund'));
    }
}
