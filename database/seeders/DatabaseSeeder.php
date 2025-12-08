<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in order
        $this->call([
            DirectionSeeder::class,
            SubscriptionPlanSeeder::class,
            AchievementSeeder::class,
            DailyMissionSeeder::class,
            CoinPackageSeeder::class,
            SettingSeeder::class,
            ShopItemSeeder::class,
            AdminUserSeeder::class,
            TestUserSeeder::class,
                // Virtual Physics Lab
            LabCategorySeeder::class,
            LabBadgeSeeder::class,
            LabExperimentSeeder::class,
                // English Learning System - Core
            EnglishLevelSeeder::class,
            EnglishTopicSeeder::class,
            EnglishGrammarCategorySeeder::class,
            EnglishA1UnitSeeder::class,
                // English Learning System - Games & AI
            EnglishGameSeeder::class,
            EnglishAIScenarioSeeder::class,
            UserEnglishProfileSeeder::class,
                // English Learning System - Competitive & Gamification (Part 7-8-9)
            EnglishAchievementSeeder::class,
            EnglishStreakRewardSeeder::class,
            EnglishLeaderboardSeeder::class,
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info('   - 7 Directions');
        $this->command->info('   - 7 Subscription Plans');
        $this->command->info('   - 32 Achievements');
        $this->command->info('   - 10 Daily Missions');
        $this->command->info('   - 5 COIN Packages');
        $this->command->info('   - 37 System Settings');
        $this->command->info('   - 4 Roles + 2 Admin Users');
        $this->command->info('');
        $this->command->info('📚 English Learning System:');
        $this->command->info('   - 6 CEFR Levels (A1-C2)');
        $this->command->info('   - 24 Vocabulary Topics');
        $this->command->info('   - 14+ Grammar Categories');
        $this->command->info('   - 8 A1 Units with 48 Lessons');
        $this->command->info('');
        $this->command->info('🎮 Games & AI System:');
        $this->command->info('   - 6 Game Categories');
        $this->command->info('   - 34 Games');
        $this->command->info('   - 10 AI Scenarios');
        $this->command->info('   - Test User Profiles');
        $this->command->info('');
        $this->command->info('⚔️ Competitive & Gamification:');
        $this->command->info('   - 9 Achievement Categories');
        $this->command->info('   - 80+ Achievements');
        $this->command->info('   - 11 Streak Milestones');
        $this->command->info('   - 8 Leaderboard Types');
        $this->command->info('');
        $this->command->warn('Default Credentials:');
        $this->command->warn('   Super Admin:');
        $this->command->warn('   Email: admin@edulife.uz');
        $this->command->warn('   Password: admin123');
        $this->command->warn('');
        $this->command->warn('   Moderator:');
        $this->command->warn('   Email: moderator@edulife.uz');
        $this->command->warn('   Password: moderator123');
    }
}

