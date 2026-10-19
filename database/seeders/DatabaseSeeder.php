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
            Exam\ExamSeeder::class,
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info('   - 2 Directions (Matematika, Fizika)');
        $this->command->info('   - 7 Subscription Plans');
        $this->command->info('   - 32 Achievements');
        $this->command->info('   - 10 Daily Missions');
        $this->command->info('   - 5 COIN Packages');
        $this->command->info('   - 37 System Settings');
        $this->command->info('   - 4 Roles + 2 Admin Users');
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

