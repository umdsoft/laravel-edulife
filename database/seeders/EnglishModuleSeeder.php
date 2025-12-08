<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\English\LevelTopicSeeder;
use Database\Seeders\English\BattleQuestionsSeeder;
use Database\Seeders\English\GameSeeder;
use Database\Seeders\English\AIConversationScenarioSeeder;
use Database\Seeders\English\AchievementSeeder;

class EnglishModuleSeeder extends Seeder
{
    /**
     * Run the English Module database seeders.
     * 
     * This seeder orchestrates all English learning content seeders including:
     * - Levels & Topics (6 CEFR levels, 48 topics)
     * - Battle Questions (A1-B1)
     * - Games (5 categories, 11 games, 55 levels)
     * - AI Conversation Scenarios (7 scenarios)
     * - Achievements (6 categories, 18+ achievements)
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║         EDULIFE English Module Seeder                 ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
        $this->command->info('');

        // Part 22: Level & Topic Seeders
        $this->command->info('📚 Seeding Levels & Topics...');
        $this->call(LevelTopicSeeder::class);

        // Part 23: Battle & Game Seeders
        $this->command->info('');
        $this->command->info('🎮 Seeding Battle Questions & Games...');
        $this->call(BattleQuestionsSeeder::class);
        $this->call(GameSeeder::class);

        // Part 24: AI & Achievement Seeders
        $this->command->info('');
        $this->command->info('🏆 Seeding AI Scenarios & Achievements...');
        $this->call(AIConversationScenarioSeeder::class);
        $this->call(AchievementSeeder::class);

        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║  ✅ English Module Seeding Complete!                  ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
    }
}
