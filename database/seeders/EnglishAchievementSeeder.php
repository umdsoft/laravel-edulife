<?php

namespace Database\Seeders;

use App\Models\English\EnglishAchievementCategory;
use App\Models\English\EnglishAchievement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishAchievementSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // VOCABULARY ACHIEVEMENTS
            [
                'slug' => 'vocabulary',
                'name' => 'Vocabulary Master',
                'name_uz' => 'So\'z Ustasi',
                'icon' => 'book-open',
                'color' => '#3B82F6',
                'achievements' => [
                    ['code' => 'first_word', 'name' => 'First Word', 'name_uz' => 'Birinchi So\'z', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 1, 'xp_reward' => 10, 'coin_reward' => 5],
                    ['code' => 'word_collector_1', 'name' => 'Word Collector I', 'name_uz' => 'So\'z Yig\'uvchi I', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 50, 'xp_reward' => 50, 'coin_reward' => 25],
                    ['code' => 'word_collector_2', 'name' => 'Word Collector II', 'name_uz' => 'So\'z Yig\'uvchi II', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 200, 'xp_reward' => 100, 'coin_reward' => 50],
                    ['code' => 'word_collector_3', 'name' => 'Word Collector III', 'name_uz' => 'So\'z Yig\'uvchi III', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 500, 'xp_reward' => 200, 'coin_reward' => 100],
                    ['code' => 'word_collector_4', 'name' => 'Word Collector IV', 'name_uz' => 'So\'z Yig\'uvchi IV', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 1000, 'xp_reward' => 500, 'coin_reward' => 250],
                    ['code' => 'word_collector_5', 'name' => 'Lexicon Master', 'name_uz' => 'Leksikon Ustasi', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'words_learned', 'requirement_value' => 2500, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'word_master_1', 'name' => 'Word Master I', 'name_uz' => 'So\'z Ustasi I', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'words_mastered', 'requirement_value' => 25, 'xp_reward' => 75, 'coin_reward' => 35],
                    ['code' => 'word_master_2', 'name' => 'Word Master II', 'name_uz' => 'So\'z Ustasi II', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'words_mastered', 'requirement_value' => 100, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'word_master_3', 'name' => 'Word Master III', 'name_uz' => 'So\'z Ustasi III', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'words_mastered', 'requirement_value' => 300, 'xp_reward' => 300, 'coin_reward' => 150],
                    ['code' => 'word_master_4', 'name' => 'Vocabulary Sage', 'name_uz' => 'So\'z Donishmandi', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'words_mastered', 'requirement_value' => 1000, 'xp_reward' => 750, 'coin_reward' => 400],
                    ['code' => 'review_champion', 'name' => 'Review Champion', 'name_uz' => 'Takrorlash Chempioni', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'words_reviewed', 'requirement_value' => 1000, 'xp_reward' => 200, 'coin_reward' => 100],
                ],
            ],
            // STREAK ACHIEVEMENTS
            [
                'slug' => 'streak',
                'name' => 'Streak Champion',
                'name_uz' => 'Streak Chempioni',
                'icon' => 'fire',
                'color' => '#EF4444',
                'achievements' => [
                    ['code' => 'streak_3', 'name' => '3 Day Streak', 'name_uz' => '3 Kunlik Streak', 'tier' => 'bronze', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 3, 'xp_reward' => 30, 'coin_reward' => 15],
                    ['code' => 'streak_7', 'name' => 'Week Warrior', 'name_uz' => 'Hafta Jangchisi', 'tier' => 'bronze', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 7, 'xp_reward' => 75, 'coin_reward' => 35],
                    ['code' => 'streak_14', 'name' => 'Two Week Hero', 'name_uz' => 'Ikki Hafta Qahramoni', 'tier' => 'silver', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 14, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'streak_30', 'name' => 'Month Master', 'name_uz' => 'Oy Ustasi', 'tier' => 'gold', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 30, 'xp_reward' => 300, 'coin_reward' => 150],
                    ['code' => 'streak_60', 'name' => 'Dedicated Learner', 'name_uz' => 'Fidoyi O\'quvchi', 'tier' => 'gold', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 60, 'xp_reward' => 500, 'coin_reward' => 250],
                    ['code' => 'streak_90', 'name' => 'Quarter Champion', 'name_uz' => 'Chorak Chempioni', 'tier' => 'platinum', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 90, 'xp_reward' => 750, 'coin_reward' => 400],
                    ['code' => 'streak_180', 'name' => 'Half Year Hero', 'name_uz' => 'Yarim Yil Qahramoni', 'tier' => 'platinum', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 180, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'streak_365', 'name' => 'Year Legend', 'name_uz' => 'Yil Afsonasi', 'tier' => 'diamond', 'requirement_type' => 'streak', 'requirement_key' => 'current_streak', 'requirement_value' => 365, 'xp_reward' => 2500, 'coin_reward' => 1500, 'gem_reward' => 100],
                ],
            ],
            // BATTLE ACHIEVEMENTS
            [
                'slug' => 'battle',
                'name' => 'Battle Champion',
                'name_uz' => 'Jang Chempioni',
                'icon' => 'swords',
                'color' => '#F59E0B',
                'achievements' => [
                    ['code' => 'first_battle', 'name' => 'First Battle', 'name_uz' => 'Birinchi Jang', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'battles_played', 'requirement_value' => 1, 'xp_reward' => 20, 'coin_reward' => 10],
                    ['code' => 'first_win', 'name' => 'First Victory', 'name_uz' => 'Birinchi G\'alaba', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'battles_won', 'requirement_value' => 1, 'xp_reward' => 30, 'coin_reward' => 15],
                    ['code' => 'battle_veteran_1', 'name' => 'Battle Veteran I', 'name_uz' => 'Jang Veterani I', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'battles_won', 'requirement_value' => 10, 'xp_reward' => 100, 'coin_reward' => 50],
                    ['code' => 'battle_veteran_2', 'name' => 'Battle Veteran II', 'name_uz' => 'Jang Veterani II', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'battles_won', 'requirement_value' => 50, 'xp_reward' => 250, 'coin_reward' => 125],
                    ['code' => 'battle_veteran_3', 'name' => 'Battle Veteran III', 'name_uz' => 'Jang Veterani III', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'battles_won', 'requirement_value' => 100, 'xp_reward' => 500, 'coin_reward' => 250],
                    ['code' => 'battle_master', 'name' => 'Battle Master', 'name_uz' => 'Jang Ustasi', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'battles_won', 'requirement_value' => 250, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'battle_legend', 'name' => 'Battle Legend', 'name_uz' => 'Jang Afsonasi', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'battles_won', 'requirement_value' => 500, 'xp_reward' => 2000, 'coin_reward' => 1000],
                    ['code' => 'win_streak_5', 'name' => 'Unstoppable', 'name_uz' => 'To\'xtovsiz', 'tier' => 'gold', 'requirement_type' => 'streak', 'requirement_key' => 'battle_win_streak', 'requirement_value' => 5, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'win_streak_10', 'name' => 'Invincible', 'name_uz' => 'Yengilmas', 'tier' => 'platinum', 'requirement_type' => 'streak', 'requirement_key' => 'battle_win_streak', 'requirement_value' => 10, 'xp_reward' => 400, 'coin_reward' => 200],
                    ['code' => 'elo_1200', 'name' => 'Gold Rank', 'name_uz' => 'Oltin Daraja', 'tier' => 'gold', 'requirement_type' => 'score', 'requirement_key' => 'elo_rating', 'requirement_value' => 1200, 'xp_reward' => 200, 'coin_reward' => 100],
                    ['code' => 'elo_1400', 'name' => 'Platinum Rank', 'name_uz' => 'Platina Daraja', 'tier' => 'platinum', 'requirement_type' => 'score', 'requirement_key' => 'elo_rating', 'requirement_value' => 1400, 'xp_reward' => 400, 'coin_reward' => 200],
                    ['code' => 'elo_1600', 'name' => 'Diamond Rank', 'name_uz' => 'Olmos Daraja', 'tier' => 'diamond', 'requirement_type' => 'score', 'requirement_key' => 'elo_rating', 'requirement_value' => 1600, 'xp_reward' => 750, 'coin_reward' => 400],
                ],
            ],
            // LESSON ACHIEVEMENTS
            [
                'slug' => 'lessons',
                'name' => 'Lesson Progress',
                'name_uz' => 'Dars Progressi',
                'icon' => 'academic-cap',
                'color' => '#10B981',
                'achievements' => [
                    ['code' => 'first_lesson', 'name' => 'First Lesson', 'name_uz' => 'Birinchi Dars', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 1, 'xp_reward' => 15, 'coin_reward' => 10],
                    ['code' => 'lesson_10', 'name' => 'Getting Started', 'name_uz' => 'Boshlash', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 10, 'xp_reward' => 75, 'coin_reward' => 35],
                    ['code' => 'lesson_25', 'name' => 'Dedicated Student', 'name_uz' => 'Fidoyi Talaba', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 25, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'lesson_50', 'name' => 'Committed Learner', 'name_uz' => 'Sodiq O\'quvchi', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 50, 'xp_reward' => 300, 'coin_reward' => 150],
                    ['code' => 'lesson_100', 'name' => 'Century Club', 'name_uz' => 'Yuz Dars Klubi', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 100, 'xp_reward' => 500, 'coin_reward' => 250],
                    ['code' => 'lesson_200', 'name' => 'Lesson Master', 'name_uz' => 'Dars Ustasi', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 200, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'lesson_360', 'name' => 'Course Conqueror', 'name_uz' => 'Kurs Fotihi', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'lessons_completed', 'requirement_value' => 360, 'xp_reward' => 2500, 'coin_reward' => 1250],
                    ['code' => 'perfect_lesson_10', 'name' => 'Perfectionist', 'name_uz' => 'Mukammaliyatchi', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'perfect_lessons', 'requirement_value' => 10, 'xp_reward' => 150, 'coin_reward' => 75],
                ],
            ],
            // LEVEL PROGRESS
            [
                'slug' => 'levels',
                'name' => 'Level Progress',
                'name_uz' => 'Daraja Progressi',
                'icon' => 'trending-up',
                'color' => '#8B5CF6',
                'achievements' => [
                    ['code' => 'reach_a1', 'name' => 'A1 Beginner', 'name_uz' => 'A1 Boshlang\'ich', 'tier' => 'bronze', 'requirement_type' => 'level', 'requirement_key' => 'current_level', 'requirement_value' => 1, 'xp_reward' => 50, 'coin_reward' => 25],
                    ['code' => 'complete_a1', 'name' => 'A1 Complete', 'name_uz' => 'A1 Tugallandi', 'tier' => 'silver', 'requirement_type' => 'level', 'requirement_key' => 'completed_level', 'requirement_value' => 1, 'xp_reward' => 200, 'coin_reward' => 100],
                    ['code' => 'reach_a2', 'name' => 'A2 Elementary', 'name_uz' => 'A2 Elementar', 'tier' => 'silver', 'requirement_type' => 'level', 'requirement_key' => 'current_level', 'requirement_value' => 2, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'complete_a2', 'name' => 'A2 Complete', 'name_uz' => 'A2 Tugallandi', 'tier' => 'gold', 'requirement_type' => 'level', 'requirement_key' => 'completed_level', 'requirement_value' => 2, 'xp_reward' => 400, 'coin_reward' => 200],
                    ['code' => 'reach_b1', 'name' => 'B1 Intermediate', 'name_uz' => 'B1 O\'rta', 'tier' => 'gold', 'requirement_type' => 'level', 'requirement_key' => 'current_level', 'requirement_value' => 3, 'xp_reward' => 300, 'coin_reward' => 150],
                    ['code' => 'reach_b2', 'name' => 'B2 Upper-Intermediate', 'name_uz' => 'B2 Yuqori O\'rta', 'tier' => 'platinum', 'requirement_type' => 'level', 'requirement_key' => 'current_level', 'requirement_value' => 4, 'xp_reward' => 500, 'coin_reward' => 250],
                    ['code' => 'reach_c1', 'name' => 'C1 Advanced', 'name_uz' => 'C1 Yuqori', 'tier' => 'platinum', 'requirement_type' => 'level', 'requirement_key' => 'current_level', 'requirement_value' => 5, 'xp_reward' => 750, 'coin_reward' => 400],
                    ['code' => 'reach_c2', 'name' => 'C2 Proficiency', 'name_uz' => 'C2 Mohir', 'tier' => 'diamond', 'requirement_type' => 'level', 'requirement_key' => 'current_level', 'requirement_value' => 6, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'complete_c2', 'name' => 'English Master', 'name_uz' => 'Ingliz Tili Ustasi', 'tier' => 'diamond', 'requirement_type' => 'level', 'requirement_key' => 'completed_level', 'requirement_value' => 6, 'xp_reward' => 5000, 'coin_reward' => 2500, 'gem_reward' => 250],
                ],
            ],
            // GAMES
            [
                'slug' => 'games',
                'name' => 'Game Master',
                'name_uz' => 'O\'yin Ustasi',
                'icon' => 'puzzle',
                'color' => '#EC4899',
                'achievements' => [
                    ['code' => 'first_game', 'name' => 'First Game', 'name_uz' => 'Birinchi O\'yin', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 1, 'xp_reward' => 10, 'coin_reward' => 5],
                    ['code' => 'game_enthusiast_1', 'name' => 'Game Enthusiast I', 'name_uz' => 'O\'yin Ishqibozi I', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 25, 'xp_reward' => 75, 'coin_reward' => 35],
                    ['code' => 'game_enthusiast_2', 'name' => 'Game Enthusiast II', 'name_uz' => 'O\'yin Ishqibozi II', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 100, 'xp_reward' => 200, 'coin_reward' => 100],
                    ['code' => 'game_master', 'name' => 'Game Master', 'name_uz' => 'O\'yin Ustasi', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 500, 'xp_reward' => 750, 'coin_reward' => 400],
                    ['code' => 'game_legend', 'name' => 'Game Legend', 'name_uz' => 'O\'yin Afsonasi', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'games_played', 'requirement_value' => 1000, 'xp_reward' => 1500, 'coin_reward' => 750],
                    ['code' => 'perfect_game_25', 'name' => 'Flawless', 'name_uz' => 'Nuqsonsiz', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'perfect_games', 'requirement_value' => 25, 'xp_reward' => 300, 'coin_reward' => 150],
                ],
            ],
            // AI CONVERSATION
            [
                'slug' => 'ai_conversation',
                'name' => 'Conversation Pro',
                'name_uz' => 'Suhbat Professional',
                'icon' => 'chat',
                'color' => '#06B6D4',
                'achievements' => [
                    ['code' => 'first_ai_chat', 'name' => 'First Conversation', 'name_uz' => 'Birinchi Suhbat', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'ai_conversations', 'requirement_value' => 1, 'xp_reward' => 20, 'coin_reward' => 10],
                    ['code' => 'ai_chat_10', 'name' => 'Conversationalist I', 'name_uz' => 'Suhbatdosh I', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'ai_conversations', 'requirement_value' => 10, 'xp_reward' => 75, 'coin_reward' => 35],
                    ['code' => 'ai_chat_50', 'name' => 'Conversationalist II', 'name_uz' => 'Suhbatdosh II', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'ai_conversations', 'requirement_value' => 50, 'xp_reward' => 300, 'coin_reward' => 150],
                    ['code' => 'ai_chat_100', 'name' => 'Conversation Master', 'name_uz' => 'Suhbat Ustasi', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'ai_conversations', 'requirement_value' => 100, 'xp_reward' => 600, 'coin_reward' => 300],
                    ['code' => 'scenario_complete_15', 'name' => 'Role Player', 'name_uz' => 'Rol O\'yinchi', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'scenarios_completed', 'requirement_value' => 15, 'xp_reward' => 250, 'coin_reward' => 125],
                ],
            ],
            // XP & TIME
            [
                'slug' => 'xp_time',
                'name' => 'Experience',
                'name_uz' => 'Tajriba',
                'icon' => 'star',
                'color' => '#FBBF24',
                'achievements' => [
                    ['code' => 'xp_1000', 'name' => 'Rising Star', 'name_uz' => 'Ko\'tarilayotgan Yulduz', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'total_xp', 'requirement_value' => 1000, 'xp_reward' => 50, 'coin_reward' => 25],
                    ['code' => 'xp_5000', 'name' => 'Shining Star', 'name_uz' => 'Porloq Yulduz', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'total_xp', 'requirement_value' => 5000, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'xp_10000', 'name' => 'Bright Star', 'name_uz' => 'Yorqin Yulduz', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'total_xp', 'requirement_value' => 10000, 'xp_reward' => 300, 'coin_reward' => 150],
                    ['code' => 'xp_50000', 'name' => 'Mega Star', 'name_uz' => 'Mega Yulduz', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'total_xp', 'requirement_value' => 50000, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'xp_100000', 'name' => 'Legend', 'name_uz' => 'Afsona', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'total_xp', 'requirement_value' => 100000, 'xp_reward' => 2500, 'coin_reward' => 1250],
                    ['code' => 'time_50h', 'name' => '50 Hours', 'name_uz' => '50 Soat', 'tier' => 'silver', 'requirement_type' => 'time', 'requirement_key' => 'total_study_hours', 'requirement_value' => 50, 'xp_reward' => 200, 'coin_reward' => 100],
                    ['code' => 'time_100h', 'name' => '100 Hours', 'name_uz' => '100 Soat', 'tier' => 'gold', 'requirement_type' => 'time', 'requirement_key' => 'total_study_hours', 'requirement_value' => 100, 'xp_reward' => 400, 'coin_reward' => 200],
                    ['code' => 'time_500h', 'name' => '500 Hours', 'name_uz' => '500 Soat', 'tier' => 'platinum', 'requirement_type' => 'time', 'requirement_key' => 'total_study_hours', 'requirement_value' => 500, 'xp_reward' => 1000, 'coin_reward' => 500],
                ],
            ],
            // TOURNAMENT
            [
                'slug' => 'tournament',
                'name' => 'Tournament',
                'name_uz' => 'Turnir',
                'icon' => 'trophy',
                'color' => '#A855F7',
                'achievements' => [
                    ['code' => 'first_tournament', 'name' => 'Tournament Debut', 'name_uz' => 'Turnir Debuti', 'tier' => 'bronze', 'requirement_type' => 'count', 'requirement_key' => 'tournaments_participated', 'requirement_value' => 1, 'xp_reward' => 50, 'coin_reward' => 25],
                    ['code' => 'tournament_5', 'name' => 'Tournament Regular', 'name_uz' => 'Doimiy Ishtirokchi', 'tier' => 'silver', 'requirement_type' => 'count', 'requirement_key' => 'tournaments_participated', 'requirement_value' => 5, 'xp_reward' => 150, 'coin_reward' => 75],
                    ['code' => 'tournament_win', 'name' => 'Tournament Champion', 'name_uz' => 'Turnir Chempioni', 'tier' => 'gold', 'requirement_type' => 'count', 'requirement_key' => 'tournaments_won', 'requirement_value' => 1, 'xp_reward' => 500, 'coin_reward' => 250],
                    ['code' => 'tournament_win_3', 'name' => 'Triple Champion', 'name_uz' => 'Uch Karra Chempion', 'tier' => 'platinum', 'requirement_type' => 'count', 'requirement_key' => 'tournaments_won', 'requirement_value' => 3, 'xp_reward' => 1000, 'coin_reward' => 500],
                    ['code' => 'tournament_win_10', 'name' => 'Tournament King', 'name_uz' => 'Turnir Qiroli', 'tier' => 'diamond', 'requirement_type' => 'count', 'requirement_key' => 'tournaments_won', 'requirement_value' => 10, 'xp_reward' => 3000, 'coin_reward' => 1500],
                ],
            ],
        ];

        foreach ($categories as $catIndex => $categoryData) {
            $achievements = $categoryData['achievements'];
            unset($categoryData['achievements']);

            $category = EnglishAchievementCategory::create(array_merge($categoryData, [
                'order_number' => $catIndex + 1,
                'is_active' => true,
            ]));

            foreach ($achievements as $achIndex => $achData) {
                EnglishAchievement::create(array_merge([
                    'category_id' => $category->id,
                    'slug' => $achData['code'],
                    'description' => $achData['name'] . ' achievement',
                    'description_uz' => $achData['name_uz'] . ' yutuq',
                    'tier_level' => 1,
                    'coin_reward' => $achData['coin_reward'] ?? 0,
                    'gem_reward' => $achData['gem_reward'] ?? 0,
                    'is_secret' => $achData['is_secret'] ?? false,
                    'rarity' => match ($achData['tier']) {
                        'bronze' => 'common',
                        'silver' => 'uncommon',
                        'gold' => 'rare',
                        'platinum' => 'epic',
                        'diamond' => 'legendary',
                    },
                    'order_number' => $achIndex + 1,
                    'is_active' => true,
                ], $achData));
            }
        }

        $this->command->info('✅ ' . EnglishAchievementCategory::count() . ' categories, ' . EnglishAchievement::count() . ' achievements created!');
    }
}
