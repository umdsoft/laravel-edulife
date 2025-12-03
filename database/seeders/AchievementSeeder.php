<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // LEARNING Category
            ['code' => 'first_lesson', 'title' => 'Birinchi qadam', 'description' => 'Birinchi darsni tugatdingiz', 'icon' => '🎯', 'category' => 'learning', 'rarity' => 'common', 'xp_reward' => 50, 'coin_reward' => 10, 'requirements' => ['lessons' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 1],
            ['code' => 'lessons_10', 'title' => 'O\'quvchi', 'description' => '10 ta darsni tugatdingiz', 'icon' => '📚', 'category' => 'learning', 'rarity' => 'common', 'xp_reward' => 100, 'coin_reward' => 20, 'requirements' => ['lessons' => 10], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 2],
            ['code' => 'lessons_50', 'title' => 'Bilimdon', 'description' => '50 ta darsni tugatdingiz', 'icon' => '🎓', 'category' => 'learning', 'rarity' => 'rare', 'xp_reward' => 300, 'coin_reward' => 50, 'requirements' => ['lessons' => 50], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 3],
            ['code' => 'lessons_100', 'title' => 'Professor', 'description' => '100 ta darsni tugatdingiz', 'icon' => '👨‍🏫', 'category' => 'learning', 'rarity' => 'epic', 'xp_reward' => 500, 'coin_reward' => 100, 'requirements' => ['lessons' => 100], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 4],
            ['code' => 'first_course', 'title' => 'Kurs tugadi', 'description' => 'Birinchi kursni tugatdingiz', 'icon' => '🏆', 'category' => 'learning', 'rarity' => 'rare', 'xp_reward' => 200, 'coin_reward' => 50, 'requirements' => ['courses' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 5],
            ['code' => 'courses_5', 'title' => 'Kollektor', 'description' => '5 ta kursni tugatdingiz', 'icon' => '🎖️', 'category' => 'learning', 'rarity' => 'epic', 'xp_reward' => 500, 'coin_reward' => 100, 'requirements' => ['courses' => 5], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 6],
            ['code' => 'speed_learner', 'title' => 'Tez o\'quvchi', 'description' => '1 kunda 10 ta dars', 'icon' => '⚡', 'category' => 'learning', 'rarity' => 'rare', 'xp_reward' => 200, 'coin_reward' => 50, 'requirements' => ['lessons_per_day' => 10], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 7],

            // TESTING Category
            ['code' => 'first_test', 'title' => 'Imtihonchi', 'description' => 'Birinchi testdan o\'tdingiz', 'icon' => '📝', 'category' => 'testing', 'rarity' => 'common', 'xp_reward' => 50, 'coin_reward' => 10, 'requirements' => ['tests' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 10],
            ['code' => 'perfect_score', 'title' => 'Mukammal', 'description' => '100% natija', 'icon' => '💯', 'category' => 'testing', 'rarity' => 'rare', 'xp_reward' => 150, 'coin_reward' => 30, 'requirements' => ['perfect_tests' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 11],
            ['code' => 'test_streak_5', 'title' => 'Barqaror', 'description' => 'Ketma-ket 5 ta testdan o\'tish', 'icon' => '🔥', 'category' => 'testing', 'rarity' => 'rare', 'xp_reward' => 200, 'coin_reward' => 40, 'requirements' => ['test_streak' => 5], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 12],
            ['code' => 'no_mistakes_week', 'title' => 'Xatosiz hafta', 'description' => '1 hafta xatosiz testlar', 'icon' => '✨', 'category' => 'testing', 'rarity' => 'epic', 'xp_reward' => 400, 'coin_reward' => 80, 'requirements' => ['perfect_week' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 13],

            // BATTLE Category
            ['code' => 'first_blood', 'title' => 'Birinchi qon', 'description' => 'Birinchi battle g\'alabasi', 'icon' => '⚔️', 'category' => 'battle', 'rarity' => 'common', 'xp_reward' => 50, 'coin_reward' => 15, 'requirements' => ['battle_wins' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 20],
            ['code' => 'win_streak_3', 'title' => 'Hat-trick', 'description' => '3 ta ketma-ket g\'alaba', 'icon' => '🎯', 'category' => 'battle', 'rarity' => 'common', 'xp_reward' => 100, 'coin_reward' => 25, 'requirements' => ['win_streak' => 3], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 21],
            ['code' => 'win_streak_5', 'title' => 'Dominant', 'description' => '5 ta ketma-ket g\'alaba', 'icon' => '🔥', 'category' => 'battle', 'rarity' => 'rare', 'xp_reward' => 200, 'coin_reward' => 50, 'requirements' => ['win_streak' => 5], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 22],
            ['code' => 'win_streak_10', 'title' => 'Yengilmas', 'description' => '10 ta ketma-ket g\'alaba', 'icon' => '👑', 'category' => 'battle', 'rarity' => 'epic', 'xp_reward' => 500, 'coin_reward' => 100, 'requirements' => ['win_streak' => 10], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 23],
            ['code' => 'battles_50', 'title' => 'Jangchi', 'description' => '50 ta battle o\'ynash', 'icon' => '🛡️', 'category' => 'battle', 'rarity' => 'rare', 'xp_reward' => 150, 'coin_reward' => 30, 'requirements' => ['battles' => 50], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 24],
            ['code' => 'battles_100', 'title' => 'Gladiator', 'description' => '100 ta battle o\'ynash', 'icon' => '🏛️', 'category' => 'battle', 'rarity' => 'epic', 'xp_reward' => 300, 'coin_reward' => 60, 'requirements' => ['battles' => 100], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 25],
            ['code' => 'elo_1500', 'title' => 'Gold darajasi', 'description' => '1500 ELO ga yetish', 'icon' => '🥇', 'category' => 'battle', 'rarity' => 'rare', 'xp_reward' => 300, 'coin_reward' => 75, 'requirements' => ['elo' => 1500], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 26],
            ['code' => 'elo_2000', 'title' => 'Master', 'description' => '2000 ELO ga yetish', 'icon' => '💎', 'category' => 'battle', 'rarity' => 'legendary', 'xp_reward' => 1000, 'coin_reward' => 200, 'requirements' => ['elo' => 2000], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 27],

            // TOURNAMENT Category
            ['code' => 'first_tournament', 'title' => 'Musobaqachi', 'description' => 'Birinchi turnirda qatnashish', 'icon' => '🎪', 'category' => 'tournament', 'rarity' => 'common', 'xp_reward' => 100, 'coin_reward' => 25, 'requirements' => ['tournaments' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 30],
            ['code' => 'tournament_top8', 'title' => 'Yarim finalchi', 'description' => 'Top 8 ga kirish', 'icon' => '🏅', 'category' => 'tournament', 'rarity' => 'rare', 'xp_reward' => 250, 'coin_reward' => 50, 'requirements' => ['top8' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 31],
            ['code' => 'tournament_winner', 'title' => 'Chempion', 'description' => 'Turnir g\'olibi', 'icon' => '🏆', 'category' => 'tournament', 'rarity' => 'epic', 'xp_reward' => 500, 'coin_reward' => 150, 'requirements' => ['tournament_wins' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 32],
            ['code' => 'tournament_undefeated', 'title' => 'Absolut', 'description' => 'Yutqazmasdan g\'alaba', 'icon' => '⭐', 'category' => 'tournament', 'rarity' => 'legendary', 'xp_reward' => 1000, 'coin_reward' => 300, 'requirements' => ['undefeated_wins' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 33],

            // STREAK Category
            ['code' => 'streak_7', 'title' => 'Bir hafta', 'description' => '7 kunlik streak', 'icon' => '🔥', 'category' => 'streak', 'rarity' => 'common', 'xp_reward' => 100, 'coin_reward' => 25, 'requirements' => ['streak' => 7], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 40],
            ['code' => 'streak_30', 'title' => 'Bir oy', 'description' => '30 kunlik streak', 'icon' => '🌟', 'category' => 'streak', 'rarity' => 'rare', 'xp_reward' => 300, 'coin_reward' => 75, 'requirements' => ['streak' => 30], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 41],
            ['code' => 'streak_100', 'title' => 'Yuz kun', 'description' => '100 kunlik streak', 'icon' => '💫', 'category' => 'streak', 'rarity' => 'epic', 'xp_reward' => 700, 'coin_reward' => 150, 'requirements' => ['streak' => 100], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 42],
            ['code' => 'streak_365', 'title' => 'Bir yil', 'description' => '365 kunlik streak', 'icon' => '🌈', 'category' => 'streak', 'rarity' => 'legendary', 'xp_reward' => 2000, 'coin_reward' => 500, 'requirements' => ['streak' => 365], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 43],

            // SOCIAL Category
            ['code' => 'first_referral', 'title' => 'Taklif', 'description' => 'Birinchi do\'stni taklif qilish', 'icon' => '👥', 'category' => 'social', 'rarity' => 'common', 'xp_reward' => 100, 'coin_reward' => 50, 'requirements' => ['referrals' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 50],
            ['code' => 'referrals_10', 'title' => 'Targ\'ibotchi', 'description' => '10 ta do\'st taklif qilish', 'icon' => '📣', 'category' => 'social', 'rarity' => 'rare', 'xp_reward' => 500, 'coin_reward' => 200, 'requirements' => ['referrals' => 10], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 51],
            ['code' => 'first_review', 'title' => 'Tanqidchi', 'description' => 'Birinchi sharh yozish', 'icon' => '💬', 'category' => 'social', 'rarity' => 'common', 'xp_reward' => 50, 'coin_reward' => 15, 'requirements' => ['reviews' => 1], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 52],

            // SPECIAL Category
            ['code' => 'early_adopter', 'title' => 'Ilk foydalanuvchi', 'description' => 'Beta versiya foydalanuvchisi', 'icon' => '🚀', 'category' => 'special', 'rarity' => 'rare', 'xp_reward' => 200, 'coin_reward' => 100, 'requirements' => ['beta' => true], 'is_hidden' => true, 'is_active' => true, 'sort_order' => 60],
            ['code' => 'vip_member', 'title' => 'VIP', 'description' => 'VIP obunachi bo\'lish', 'icon' => '👑', 'category' => 'special', 'rarity' => 'rare', 'xp_reward' => 300, 'coin_reward' => 0, 'requirements' => ['subscription' => 'vip'], 'is_hidden' => false, 'is_active' => true, 'sort_order' => 61],
        ];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }
    }
}
