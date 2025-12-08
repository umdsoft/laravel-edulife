<?php

namespace Database\Seeders;

use App\Models\English\EnglishStreakReward;
use Illuminate\Database\Seeder;

class EnglishStreakRewardSeeder extends Seeder
{
    public function run(): void
    {
        $streakRewards = [
            ['streak_days' => 3, 'title' => '3 Day Streak!', 'title_uz' => '3 Kunlik Streak!', 'xp_reward' => 30, 'coin_reward' => 15, 'icon' => 'fire'],
            ['streak_days' => 7, 'title' => 'Week Streak!', 'title_uz' => 'Haftalik Streak!', 'xp_reward' => 75, 'coin_reward' => 35, 'streak_freeze_reward' => 1, 'icon' => 'fire'],
            ['streak_days' => 14, 'title' => '2 Week Streak!', 'title_uz' => '2 Haftalik Streak!', 'xp_reward' => 150, 'coin_reward' => 75, 'icon' => 'fire'],
            ['streak_days' => 21, 'title' => '3 Week Streak!', 'title_uz' => '3 Haftalik Streak!', 'xp_reward' => 200, 'coin_reward' => 100, 'streak_freeze_reward' => 1, 'icon' => 'fire'],
            ['streak_days' => 30, 'title' => 'Month Streak!', 'title_uz' => 'Oylik Streak!', 'xp_reward' => 300, 'coin_reward' => 150, 'streak_freeze_reward' => 2, 'gem_reward' => 10, 'icon' => 'fire'],
            ['streak_days' => 50, 'title' => '50 Day Streak!', 'title_uz' => '50 Kunlik Streak!', 'xp_reward' => 400, 'coin_reward' => 200, 'streak_freeze_reward' => 2, 'icon' => 'fire'],
            ['streak_days' => 60, 'title' => '2 Month Streak!', 'title_uz' => '2 Oylik Streak!', 'xp_reward' => 500, 'coin_reward' => 250, 'streak_freeze_reward' => 3, 'icon' => 'fire'],
            ['streak_days' => 90, 'title' => '3 Month Streak!', 'title_uz' => '3 Oylik Streak!', 'xp_reward' => 750, 'coin_reward' => 400, 'streak_freeze_reward' => 3, 'gem_reward' => 25, 'icon' => 'fire'],
            ['streak_days' => 100, 'title' => '100 Day Streak!', 'title_uz' => '100 Kunlik Streak!', 'xp_reward' => 1000, 'coin_reward' => 500, 'streak_freeze_reward' => 5, 'gem_reward' => 50, 'icon' => 'fire'],
            ['streak_days' => 180, 'title' => '6 Month Streak!', 'title_uz' => '6 Oylik Streak!', 'xp_reward' => 1500, 'coin_reward' => 750, 'streak_freeze_reward' => 7, 'gem_reward' => 75, 'icon' => 'fire'],
            ['streak_days' => 365, 'title' => 'Year Streak!', 'title_uz' => 'Yillik Streak!', 'xp_reward' => 5000, 'coin_reward' => 2500, 'streak_freeze_reward' => 14, 'gem_reward' => 200, 'icon' => 'crown'],
        ];

        foreach ($streakRewards as $reward) {
            EnglishStreakReward::create(array_merge($reward, [
                'message' => "Congratulations! You've reached a {$reward['streak_days']} day streak!",
                'message_uz' => "Tabriklaymiz! Siz {$reward['streak_days']} kunlik streak ga erishdingiz!",
                'streak_freeze_reward' => $reward['streak_freeze_reward'] ?? 0,
                'gem_reward' => $reward['gem_reward'] ?? 0,
                'is_active' => true,
            ]));
        }

        $this->command->info('✅ ' . count($streakRewards) . ' streak rewards created!');
    }
}
