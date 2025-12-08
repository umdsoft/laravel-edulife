<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishAchievementCategory;
use App\Models\English\EnglishAchievement;
use Illuminate\Support\Str;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        // Create categories
        $categories = [
            ['slug' => 'learning', 'name' => 'Learning', 'name_uz' => 'O\'rganish', 'icon' => '📚', 'color' => '#3B82F6', 'order_number' => 1],
            ['slug' => 'vocabulary', 'name' => 'Vocabulary', 'name_uz' => 'Lug\'at', 'icon' => '📖', 'color' => '#8B5CF6', 'order_number' => 2],
            ['slug' => 'streak', 'name' => 'Streak', 'name_uz' => 'Kunlik streak', 'icon' => '🔥', 'color' => '#F59E0B', 'order_number' => 3],
            ['slug' => 'battle', 'name' => 'Battle', 'name_uz' => 'Jang', 'icon' => '⚔️', 'color' => '#EF4444', 'order_number' => 4],
            ['slug' => 'games', 'name' => 'Games', 'name_uz' => 'O\'yinlar', 'icon' => '🎮', 'color' => '#10B981', 'order_number' => 5],
            ['slug' => 'special', 'name' => 'Special', 'name_uz' => 'Maxsus', 'icon' => '⭐', 'color' => '#06B6D4', 'order_number' => 6],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $category = EnglishAchievementCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'id' => Str::uuid(),
                    'name' => $cat['name'],
                    'name_uz' => $cat['name_uz'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'order_number' => $cat['order_number'],
                    'is_active' => true,
                ]
            );
            $categoryMap[$cat['slug']] = $category->id;
        }

        $achievements = [
            // Learning Achievements
            ['category' => 'learning', 'slug' => 'first-lesson', 'code' => 'first_lesson', 'name' => 'First Steps', 'name_uz' => 'Birinchi qadamlar', 'description' => 'Complete your first lesson', 'description_uz' => 'Birinchi darsingizni yakunlang', 'icon' => '🎯', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 1, 'xp_reward' => 50, 'coin_reward' => 10],
            ['category' => 'learning', 'slug' => 'lesson-10', 'code' => 'lesson_10', 'name' => 'Eager Learner', 'name_uz' => 'Ishtiyoqli o\'quvchi', 'description' => 'Complete 10 lessons', 'description_uz' => '10 ta dars yakunlang', 'icon' => '📗', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 10, 'xp_reward' => 100, 'coin_reward' => 25],
            ['category' => 'learning', 'slug' => 'lesson-50', 'code' => 'lesson_50', 'name' => 'Dedicated Student', 'name_uz' => 'Sodiq talaba', 'description' => 'Complete 50 lessons', 'description_uz' => '50 ta dars yakunlang', 'icon' => '📘', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 50, 'xp_reward' => 300, 'coin_reward' => 75],
            ['category' => 'learning', 'slug' => 'lesson-100', 'code' => 'lesson_100', 'name' => 'Knowledge Seeker', 'name_uz' => 'Bilim izlovchi', 'description' => 'Complete 100 lessons', 'description_uz' => '100 ta dars yakunlang', 'icon' => '📙', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 100, 'xp_reward' => 500, 'coin_reward' => 150],

            // Vocabulary Achievements
            ['category' => 'vocabulary', 'slug' => 'vocab-50', 'code' => 'vocab_50', 'name' => 'Word Collector', 'name_uz' => 'So\'z to\'plovchi', 'description' => 'Learn 50 new words', 'description_uz' => '50 ta yangi so\'z o\'rganing', 'icon' => '📝', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 50, 'xp_reward' => 100, 'coin_reward' => 25],
            ['category' => 'vocabulary', 'slug' => 'vocab-200', 'code' => 'vocab_200', 'name' => 'Word Enthusiast', 'name_uz' => 'So\'z ishqibozi', 'description' => 'Learn 200 new words', 'description_uz' => '200 ta yangi so\'z o\'rganing', 'icon' => '📚', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 200, 'xp_reward' => 300, 'coin_reward' => 75],
            ['category' => 'vocabulary', 'slug' => 'vocab-500', 'code' => 'vocab_500', 'name' => 'Lexicon Expert', 'name_uz' => 'Lug\'at mutaxassisi', 'description' => 'Learn 500 new words', 'description_uz' => '500 ta yangi so\'z o\'rganing', 'icon' => '🏆', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 500, 'xp_reward' => 500, 'coin_reward' => 150],

            // Streak Achievements
            ['category' => 'streak', 'slug' => 'streak-7', 'code' => 'streak_7', 'name' => 'Week Warrior', 'name_uz' => 'Hafta jangchisi', 'description' => 'Maintain a 7-day streak', 'description_uz' => '7 kunlik streak saqlang', 'icon' => '🔥', 'tier' => 'bronze', 'requirement_type' => 'streak', 'requirement_key' => 'streak_days', 'requirement_value' => 7, 'xp_reward' => 100, 'coin_reward' => 25],
            ['category' => 'streak', 'slug' => 'streak-30', 'code' => 'streak_30', 'name' => 'Monthly Master', 'name_uz' => 'Oylik usta', 'description' => 'Maintain a 30-day streak', 'description_uz' => '30 kunlik streak saqlang', 'icon' => '🌟', 'tier' => 'silver', 'requirement_type' => 'streak', 'requirement_key' => 'streak_days', 'requirement_value' => 30, 'xp_reward' => 300, 'coin_reward' => 75],
            ['category' => 'streak', 'slug' => 'streak-100', 'code' => 'streak_100', 'name' => 'Century Achiever', 'name_uz' => '100 kun', 'description' => 'Maintain a 100-day streak', 'description_uz' => '100 kunlik streak saqlang', 'icon' => '💎', 'tier' => 'gold', 'requirement_type' => 'streak', 'requirement_key' => 'streak_days', 'requirement_value' => 100, 'xp_reward' => 750, 'coin_reward' => 200],

            // Battle Achievements
            ['category' => 'battle', 'slug' => 'first-win', 'code' => 'first_win', 'name' => 'First Victory', 'name_uz' => 'Birinchi g\'alaba', 'description' => 'Win your first battle', 'description_uz' => 'Birinchi janginggizda g\'olib bo\'ling', 'icon' => '⚔️', 'tier' => 'bronze', 'requirement_type' => 'battle', 'requirement_key' => 'battles_won', 'requirement_value' => 1, 'xp_reward' => 75, 'coin_reward' => 15],
            ['category' => 'battle', 'slug' => 'wins-10', 'code' => 'wins_10', 'name' => 'Battle Champion', 'name_uz' => 'Jang chempioni', 'description' => 'Win 10 battles', 'description_uz' => '10 ta jangda g\'olib bo\'ling', 'icon' => '🏆', 'tier' => 'silver', 'requirement_type' => 'battle', 'requirement_key' => 'battles_won', 'requirement_value' => 10, 'xp_reward' => 200, 'coin_reward' => 50],
            ['category' => 'battle', 'slug' => 'wins-50', 'code' => 'wins_50', 'name' => 'Battle Master', 'name_uz' => 'Jang ustasi', 'description' => 'Win 50 battles', 'description_uz' => '50 ta jangda g\'olib bo\'ling', 'icon' => '👑', 'tier' => 'gold', 'requirement_type' => 'battle', 'requirement_key' => 'battles_won', 'requirement_value' => 50, 'xp_reward' => 500, 'coin_reward' => 150],

            // Game Achievements
            ['category' => 'games', 'slug' => 'first-game', 'code' => 'first_game', 'name' => 'Game On', 'name_uz' => 'O\'yin boshlandi', 'description' => 'Complete your first game', 'description_uz' => 'Birinchi o\'yiningizni yakunlang', 'icon' => '🎮', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 1, 'xp_reward' => 25, 'coin_reward' => 5],
            ['category' => 'games', 'slug' => 'games-50', 'code' => 'games_50', 'name' => 'Game Enthusiast', 'name_uz' => 'O\'yin ishqibozi', 'description' => 'Complete 50 games', 'description_uz' => '50 ta o\'yinni yakunlang', 'icon' => '🕹️', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 50, 'xp_reward' => 150, 'coin_reward' => 40],

            // Special Achievements
            ['category' => 'special', 'slug' => 'early-bird', 'code' => 'early_bird', 'name' => 'Early Bird', 'name_uz' => 'Erta turuvchi', 'description' => 'Study before 7 AM', 'description_uz' => 'Soat 7 dan oldin o\'qing', 'icon' => '🌅', 'tier' => 'bronze', 'requirement_type' => 'special', 'requirement_key' => 'study_time', 'requirement_value' => 7, 'xp_reward' => 75, 'coin_reward' => 20],
            ['category' => 'special', 'slug' => 'night-owl', 'code' => 'night_owl', 'name' => 'Night Owl', 'name_uz' => 'Tun boyqushi', 'description' => 'Study after 11 PM', 'description_uz' => 'Soat 23 dan keyin o\'qing', 'icon' => '🦉', 'tier' => 'bronze', 'requirement_type' => 'special', 'requirement_key' => 'study_time', 'requirement_value' => 23, 'xp_reward' => 75, 'coin_reward' => 20],
        ];

        $order = 1;
        foreach ($achievements as $a) {
            $categorySlug = $a['category'];
            unset($a['category']);

            EnglishAchievement::firstOrCreate(
                ['code' => $a['code']],
                [
                    'id' => Str::uuid(),
                    'category_id' => $categoryMap[$categorySlug],
                    'slug' => $a['slug'],
                    'name' => $a['name'],
                    'name_uz' => $a['name_uz'],
                    'description' => $a['description'],
                    'description_uz' => $a['description_uz'],
                    'tier' => $a['tier'],
                    'requirement_type' => $a['requirement_type'],
                    'requirement_key' => $a['requirement_key'],
                    'requirement_value' => $a['requirement_value'],
                    'xp_reward' => $a['xp_reward'],
                    'coin_reward' => $a['coin_reward'],
                    'icon' => $a['icon'],
                    'order_number' => $order++,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Created 6 categories with ' . count($achievements) . ' achievements');
    }
}
