<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishLevel;
use App\Models\English\EnglishGameCategory;
use App\Models\English\EnglishGame;
use App\Models\English\EnglishGameLevel;
use Illuminate\Support\Str;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $levels = EnglishLevel::all()->keyBy('code');

        if ($levels->isEmpty()) {
            $this->command->error('Levels not found! Run LevelTopicSeeder first.');
            return;
        }

        // Create game categories first
        $categories = [
            ['slug' => 'vocabulary', 'name' => 'Vocabulary Games', 'name_uz' => 'Lug\'at o\'yinlari', 'icon' => '📖', 'color' => '#8B5CF6', 'order_number' => 1],
            ['slug' => 'grammar', 'name' => 'Grammar Games', 'name_uz' => 'Grammatika o\'yinlari', 'icon' => '📝', 'color' => '#3B82F6', 'order_number' => 2],
            ['slug' => 'listening', 'name' => 'Listening Games', 'name_uz' => 'Tinglash o\'yinlari', 'icon' => '🎧', 'color' => '#10B981', 'order_number' => 3],
            ['slug' => 'speed', 'name' => 'Speed Games', 'name_uz' => 'Tezlik o\'yinlari', 'icon' => '⚡', 'color' => '#F59E0B', 'order_number' => 4],
            ['slug' => 'memory', 'name' => 'Memory Games', 'name_uz' => 'Xotira o\'yinlari', 'icon' => '🃏', 'color' => '#EC4899', 'order_number' => 5],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $category = EnglishGameCategory::firstOrCreate(
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

        // Create games
        $games = [
            ['category' => 'vocabulary', 'code' => 'word_scramble', 'slug' => 'word-scramble', 'name' => 'Word Scramble', 'name_uz' => 'So\'zlarni to\'g\'rilash', 'game_type' => 'word_game', 'xp_reward_min' => 10, 'xp_reward_max' => 30, 'icon' => '🔀'],
            ['category' => 'vocabulary', 'code' => 'word_match', 'slug' => 'word-match', 'name' => 'Word Match', 'name_uz' => 'So\'zlarni moslashtirish', 'game_type' => 'matching_game', 'xp_reward_min' => 15, 'xp_reward_max' => 40, 'icon' => '🔗'],
            ['category' => 'vocabulary', 'code' => 'hangman', 'slug' => 'hangman', 'name' => 'Hangman', 'name_uz' => 'Hangman', 'game_type' => 'word_game', 'xp_reward_min' => 10, 'xp_reward_max' => 25, 'icon' => '🎯'],
            ['category' => 'vocabulary', 'code' => 'flashcards', 'slug' => 'flashcards', 'name' => 'Flashcards', 'name_uz' => 'Flashkartalar', 'game_type' => 'memory_game', 'xp_reward_min' => 10, 'xp_reward_max' => 20, 'icon' => '📚'],

            ['category' => 'grammar', 'code' => 'sentence_builder', 'slug' => 'sentence-builder', 'name' => 'Sentence Builder', 'name_uz' => 'Jumla tuzish', 'game_type' => 'grammar_game', 'xp_reward_min' => 15, 'xp_reward_max' => 35, 'icon' => '🏗️'],
            ['category' => 'grammar', 'code' => 'grammar_quiz', 'slug' => 'grammar-quiz', 'name' => 'Grammar Quiz', 'name_uz' => 'Grammatika testi', 'game_type' => 'quiz_game', 'xp_reward_min' => 20, 'xp_reward_max' => 45, 'icon' => '📝'],

            ['category' => 'listening', 'code' => 'spelling_bee', 'slug' => 'spelling-bee', 'name' => 'Spelling Bee', 'name_uz' => 'Imlo bellashuvi', 'game_type' => 'listening_game', 'xp_reward_min' => 20, 'xp_reward_max' => 50, 'icon' => '🐝'],
            ['category' => 'listening', 'code' => 'audio_match', 'slug' => 'audio-match', 'name' => 'Audio Match', 'name_uz' => 'Audio moslashtirish', 'game_type' => 'listening_game', 'xp_reward_min' => 15, 'xp_reward_max' => 35, 'icon' => '🎧'],

            ['category' => 'speed', 'code' => 'speed_vocab', 'slug' => 'speed-vocab', 'name' => 'Speed Vocabulary', 'name_uz' => 'Tezkor lug\'at', 'game_type' => 'speed_game', 'xp_reward_min' => 20, 'xp_reward_max' => 45, 'icon' => '⚡'],
            ['category' => 'speed', 'code' => 'typing_race', 'slug' => 'typing-race', 'name' => 'Typing Race', 'name_uz' => 'Yozuv poygasi', 'game_type' => 'speed_game', 'xp_reward_min' => 15, 'xp_reward_max' => 35, 'icon' => '⌨️'],

            ['category' => 'memory', 'code' => 'memory_cards', 'slug' => 'memory-cards', 'name' => 'Memory Cards', 'name_uz' => 'Xotira kartalari', 'game_type' => 'memory_game', 'xp_reward_min' => 15, 'xp_reward_max' => 35, 'icon' => '🃏'],
        ];

        foreach ($games as $gameData) {
            $categorySlug = $gameData['category'];
            unset($gameData['category']);

            $game = EnglishGame::firstOrCreate(
                ['code' => $gameData['code']],
                [
                    'id' => Str::uuid(),
                    'category_id' => $categoryMap[$categorySlug],
                    'slug' => $gameData['slug'],
                    'name' => $gameData['name'],
                    'name_uz' => $gameData['name_uz'],
                    'game_type' => $gameData['game_type'],
                    'xp_reward_min' => $gameData['xp_reward_min'],
                    'xp_reward_max' => $gameData['xp_reward_max'],
                    'coin_reward_min' => 5,
                    'coin_reward_max' => 20,
                    'icon' => $gameData['icon'],
                    'is_active' => true,
                ]
            );

            // Create 5 levels for each game (for A1 level only for simplicity)
            $difficulties = ['easy', 'easy', 'medium', 'medium', 'hard'];
            for ($level = 1; $level <= 5; $level++) {
                EnglishGameLevel::firstOrCreate(
                    ['game_id' => $game->id, 'level_number' => $level],
                    [
                        'id' => Str::uuid(),
                        'english_level_id' => $levels['A1']->id,
                        'name' => "Level {$level}",
                        'name_uz' => "{$level}-daraja",
                        'difficulty' => $difficulties[$level - 1],
                        'xp_reward' => $gameData['xp_reward_min'] + ($level * 3),
                        'coin_reward' => 5 + $level,
                        'pass_score' => 60 + ($level * 5),
                        'order_number' => $level,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ Created 5 categories, 11 games, 55 game levels');
    }
}
