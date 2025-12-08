<?php

namespace Database\Seeders;

use App\Models\English\EnglishLeaderboard;
use Illuminate\Database\Seeder;

class EnglishLeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        $leaderboards = [
            [
                'slug' => 'daily-xp',
                'code' => 'daily_xp',
                'name' => 'Daily XP',
                'name_uz' => 'Kunlik XP',
                'type' => 'daily_xp',
                'period' => 'daily',
                'icon' => 'lightning-bolt',
                'color' => '#F59E0B',
                'position_rewards' => [
                    '1' => ['xp' => 100, 'coins' => 50],
                    '2' => ['xp' => 75, 'coins' => 35],
                    '3' => ['xp' => 50, 'coins' => 25],
                ],
            ],
            [
                'slug' => 'weekly-xp',
                'code' => 'weekly_xp',
                'name' => 'Weekly XP',
                'name_uz' => 'Haftalik XP',
                'type' => 'weekly_xp',
                'period' => 'weekly',
                'icon' => 'star',
                'color' => '#3B82F6',
                'position_rewards' => [
                    '1' => ['xp' => 500, 'coins' => 250, 'badge' => 'weekly_champion'],
                    '2' => ['xp' => 300, 'coins' => 150],
                    '3' => ['xp' => 200, 'coins' => 100],
                ],
            ],
            [
                'slug' => 'monthly-xp',
                'code' => 'monthly_xp',
                'name' => 'Monthly XP',
                'name_uz' => 'Oylik XP',
                'type' => 'monthly_xp',
                'period' => 'monthly',
                'icon' => 'trophy',
                'color' => '#10B981',
                'position_rewards' => [
                    '1' => ['xp' => 2000, 'coins' => 1000, 'gems' => 50],
                    '2' => ['xp' => 1500, 'coins' => 750, 'gems' => 30],
                    '3' => ['xp' => 1000, 'coins' => 500, 'gems' => 20],
                ],
            ],
            [
                'slug' => 'all-time-xp',
                'code' => 'all_time_xp',
                'name' => 'All Time XP',
                'name_uz' => 'Umumiy XP',
                'type' => 'all_time_xp',
                'period' => 'all_time',
                'icon' => 'crown',
                'color' => '#8B5CF6',
            ],
            [
                'slug' => 'battle-elo',
                'code' => 'battle_elo',
                'name' => 'Battle Ranking',
                'name_uz' => 'Jang Reytingi',
                'type' => 'battle_elo',
                'period' => 'all_time',
                'icon' => 'swords',
                'color' => '#EF4444',
            ],
            [
                'slug' => 'weekly-battles',
                'code' => 'weekly_battles',
                'name' => 'Weekly Battle Wins',
                'name_uz' => 'Haftalik Jang G\'alabalari',
                'type' => 'battle_wins',
                'period' => 'weekly',
                'icon' => 'swords',
                'color' => '#F97316',
                'position_rewards' => [
                    '1' => ['xp' => 500, 'coins' => 250],
                    '2' => ['xp' => 300, 'coins' => 150],
                    '3' => ['xp' => 200, 'coins' => 100],
                ],
            ],
            [
                'slug' => 'streak-leaders',
                'code' => 'streak_leaders',
                'name' => 'Streak Leaders',
                'name_uz' => 'Streak Liderlari',
                'type' => 'streak',
                'period' => 'all_time',
                'icon' => 'fire',
                'color' => '#DC2626',
            ],
            [
                'slug' => 'words-learned',
                'code' => 'words_learned',
                'name' => 'Words Learned',
                'name_uz' => 'O\'rganilgan So\'zlar',
                'type' => 'words_learned',
                'period' => 'all_time',
                'icon' => 'book-open',
                'color' => '#06B6D4',
            ],
        ];

        foreach ($leaderboards as $index => $data) {
            EnglishLeaderboard::create(array_merge($data, [
                'description' => "Leaderboard for {$data['name']}",
                'display_count' => 100,
                'show_position_change' => true,
                'is_active' => true,
                'order_number' => $index + 1,
            ]));
        }

        $this->command->info('✅ ' . count($leaderboards) . ' leaderboards created!');
    }
}
