<?php

namespace Database\Seeders;

use App\Models\English\EnglishGameCategory;
use App\Models\English\EnglishGame;
use App\Models\English\EnglishLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishGameSeeder extends Seeder
{
    public function run(): void
    {
        $a1Level = EnglishLevel::where('code', 'A1')->first();

        $categories = [
            [
                'slug' => 'word-games',
                'name' => 'Word Games',
                'name_uz' => 'So\'z O\'yinlari',
                'description' => 'Fun games to learn and practice vocabulary',
                'icon' => 'text',
                'color' => '#3B82F6',
                'games' => [
                    ['code' => 'word_scramble', 'name' => 'Word Scramble', 'name_uz' => 'So\'z Jumboq', 'game_type' => 'word_game', 'skills_focus' => ['vocabulary', 'spelling']],
                    ['code' => 'word_match', 'name' => 'Word Match', 'name_uz' => 'So\'z Moslashtirish', 'game_type' => 'matching_game', 'skills_focus' => ['vocabulary', 'reading']],
                    ['code' => 'hangman', 'name' => 'Hangman', 'name_uz' => 'Osilgan Odam', 'game_type' => 'word_game', 'skills_focus' => ['vocabulary', 'spelling']],
                    ['code' => 'spelling_bee', 'name' => 'Spelling Bee', 'name_uz' => 'Imlo Musobaqasi', 'game_type' => 'word_game', 'skills_focus' => ['spelling', 'listening']],
                    ['code' => 'word_search', 'name' => 'Word Search', 'name_uz' => 'So\'z Qidirish', 'game_type' => 'word_game', 'skills_focus' => ['vocabulary', 'reading']],
                    ['code' => 'crossword', 'name' => 'Crossword Puzzle', 'name_uz' => 'Krossvord', 'game_type' => 'word_game', 'skills_focus' => ['vocabulary', 'reading', 'spelling']],
                    ['code' => 'word_chain', 'name' => 'Word Chain', 'name_uz' => 'So\'z Zanjiri', 'game_type' => 'word_game', 'skills_focus' => ['vocabulary', 'spelling']],
                    ['code' => 'anagram_solver', 'name' => 'Anagram Solver', 'name_uz' => 'Anagramma Yechish', 'game_type' => 'word_game', 'skills_focus' => ['vocabulary', 'spelling']],
                ],
            ],
            [
                'slug' => 'grammar-games',
                'name' => 'Grammar Games',
                'name_uz' => 'Grammatika O\'yinlari',
                'description' => 'Practice grammar rules through interactive games',
                'icon' => 'academic-cap',
                'color' => '#10B981',
                'games' => [
                    ['code' => 'sentence_builder', 'name' => 'Sentence Builder', 'name_uz' => 'Gap Tuzuvchi', 'game_type' => 'grammar_game', 'skills_focus' => ['grammar', 'writing']],
                    ['code' => 'error_hunter', 'name' => 'Error Hunter', 'name_uz' => 'Xato Qidiruvchi', 'game_type' => 'grammar_game', 'skills_focus' => ['grammar', 'reading']],
                    ['code' => 'tense_race', 'name' => 'Tense Race', 'name_uz' => 'Zamon Poygasi', 'game_type' => 'grammar_game', 'skills_focus' => ['grammar']],
                    ['code' => 'fill_the_gap', 'name' => 'Fill the Gap', 'name_uz' => 'Bo\'sh Joyni To\'ldiring', 'game_type' => 'grammar_game', 'skills_focus' => ['grammar', 'vocabulary']],
                    ['code' => 'verb_conjugator', 'name' => 'Verb Conjugator', 'name_uz' => 'Fe\'l Tuslovchi', 'game_type' => 'grammar_game', 'skills_focus' => ['grammar']],
                    ['code' => 'article_master', 'name' => 'Article Master', 'name_uz' => 'Artikl Ustasi', 'game_type' => 'grammar_game', 'skills_focus' => ['grammar']],
                ],
            ],
            [
                'slug' => 'listening-games',
                'name' => 'Listening Games',
                'name_uz' => 'Tinglash O\'yinlari',
                'description' => 'Improve your listening skills with audio-based games',
                'icon' => 'volume-up',
                'color' => '#8B5CF6',
                'games' => [
                    ['code' => 'dictation', 'name' => 'Dictation', 'name_uz' => 'Diktant', 'game_type' => 'listening_game', 'skills_focus' => ['listening', 'spelling', 'writing']],
                    ['code' => 'minimal_pairs', 'name' => 'Minimal Pairs', 'name_uz' => 'Minimal Juftliklar', 'game_type' => 'listening_game', 'skills_focus' => ['listening', 'pronunciation']],
                    ['code' => 'listen_and_choose', 'name' => 'Listen & Choose', 'name_uz' => 'Tinglang va Tanlang', 'game_type' => 'listening_game', 'skills_focus' => ['listening', 'reading']],
                    ['code' => 'number_listener', 'name' => 'Number Listener', 'name_uz' => 'Raqam Tinglovchi', 'game_type' => 'listening_game', 'skills_focus' => ['listening']],
                    ['code' => 'conversation_catcher', 'name' => 'Conversation Catcher', 'name_uz' => 'Suhbat Ushlash', 'game_type' => 'listening_game', 'skills_focus' => ['listening', 'reading']],
                ],
            ],
            [
                'slug' => 'speed-games',
                'name' => 'Speed Games',
                'name_uz' => 'Tezlik O\'yinlari',
                'description' => 'Test your English skills under time pressure',
                'icon' => 'lightning-bolt',
                'color' => '#F59E0B',
                'games' => [
                    ['code' => 'word_blitz', 'name' => 'Word Blitz', 'name_uz' => 'So\'z Blitz', 'game_type' => 'speed_game', 'skills_focus' => ['vocabulary', 'spelling']],
                    ['code' => 'quick_translate', 'name' => 'Quick Translate', 'name_uz' => 'Tez Tarjima', 'game_type' => 'speed_game', 'skills_focus' => ['vocabulary']],
                    ['code' => 'rapid_fire', 'name' => 'Rapid Fire', 'name_uz' => 'Tezkor O\'t', 'game_type' => 'speed_game', 'skills_focus' => ['vocabulary', 'grammar']],
                    ['code' => 'typing_race', 'name' => 'Typing Race', 'name_uz' => 'Yozish Poygasi', 'game_type' => 'speed_game', 'skills_focus' => ['spelling', 'writing']],
                    ['code' => 'beat_the_clock', 'name' => 'Beat the Clock', 'name_uz' => 'Soatni Yeng', 'game_type' => 'speed_game', 'skills_focus' => ['vocabulary', 'grammar', 'reading']],
                ],
            ],
            [
                'slug' => 'memory-games',
                'name' => 'Memory Games',
                'name_uz' => 'Xotira O\'yinlari',
                'description' => 'Train your memory while learning English',
                'icon' => 'puzzle',
                'color' => '#EC4899',
                'games' => [
                    ['code' => 'flashcard_flip', 'name' => 'Flashcard Flip', 'name_uz' => 'Kartochka Ag\'darish', 'game_type' => 'memory_game', 'skills_focus' => ['vocabulary']],
                    ['code' => 'picture_word', 'name' => 'Picture Word Match', 'name_uz' => 'Rasm-So\'z Juftlash', 'game_type' => 'memory_game', 'skills_focus' => ['vocabulary']],
                    ['code' => 'sequence_recall', 'name' => 'Sequence Recall', 'name_uz' => 'Ketma-ketlikni Eslash', 'game_type' => 'memory_game', 'skills_focus' => ['vocabulary', 'listening']],
                    ['code' => 'word_recall', 'name' => 'Word Recall', 'name_uz' => 'So\'z Eslash', 'game_type' => 'memory_game', 'skills_focus' => ['vocabulary']],
                ],
            ],
            [
                'slug' => 'quiz-games',
                'name' => 'Quiz Games',
                'name_uz' => 'Quiz O\'yinlari',
                'description' => 'Test your knowledge with various quiz formats',
                'icon' => 'question-mark-circle',
                'color' => '#06B6D4',
                'games' => [
                    ['code' => 'vocabulary_quiz', 'name' => 'Vocabulary Quiz', 'name_uz' => 'So\'z Quizi', 'game_type' => 'quiz_game', 'skills_focus' => ['vocabulary']],
                    ['code' => 'grammar_quiz', 'name' => 'Grammar Quiz', 'name_uz' => 'Grammatika Quizi', 'game_type' => 'quiz_game', 'skills_focus' => ['grammar']],
                    ['code' => 'true_false_quiz', 'name' => 'True or False', 'name_uz' => 'To\'g\'ri yoki Noto\'g\'ri', 'game_type' => 'quiz_game', 'skills_focus' => ['grammar', 'reading']],
                    ['code' => 'picture_quiz', 'name' => 'Picture Quiz', 'name_uz' => 'Rasm Quizi', 'game_type' => 'quiz_game', 'skills_focus' => ['vocabulary']],
                    ['code' => 'daily_challenge_quiz', 'name' => 'Daily Challenge Quiz', 'name_uz' => 'Kunlik Sinov Quiz', 'game_type' => 'quiz_game', 'skills_focus' => ['vocabulary', 'grammar']],
                ],
            ],
        ];

        foreach ($categories as $catIndex => $categoryData) {
            $games = $categoryData['games'];
            unset($categoryData['games']);

            $category = EnglishGameCategory::create(array_merge($categoryData, [
                'id' => Str::uuid(),
                'order_number' => $catIndex + 1,
                'is_active' => true,
            ]));

            foreach ($games as $gameIndex => $gameData) {
                EnglishGame::create(array_merge($gameData, [
                    'id' => Str::uuid(),
                    'category_id' => $category->id,
                    'slug' => $gameData['code'],
                    'description' => '',
                    'description_uz' => '',
                    'min_level_id' => $a1Level?->id,
                    'xp_reward_min' => 10,
                    'xp_reward_max' => 50,
                    'coin_reward_min' => 5,
                    'coin_reward_max' => 25,
                    'order_number' => $gameIndex + 1,
                    'is_active' => true,
                ]));
            }
        }
    }
}
