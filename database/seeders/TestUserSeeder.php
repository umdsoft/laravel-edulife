<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed demo accounts (incl. a super_admin) with a well-known password:
        // never create them outside a local/testing environment
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('TestUserSeeder skipped: demo accounts are only seeded in local/testing');

            return;
        }

        // Super Admin - Test uchun
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@test.uz'],
            [
                'phone' => '+998900000001',
                'password' => Hash::make('password123'),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'role' => 'super_admin',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'xp_total' => 50000,
                'level' => 25,
                'coin_balance' => 100000,
                'elo_rating' => 2000,
                'battles_won' => 100,
                'battles_total' => 120,
                'streak_current' => 30,
                'streak_best' => 100,
            ]
        );

        $this->command->info('✅ Super Admin yaratildi:');
        $this->command->line('   📧 Email: superadmin@test.uz');
        $this->command->line('   🔑 Parol: password123');

        // Student 1 - Beginner
        $student1 = User::firstOrCreate(
            ['email' => 'student@test.uz'],
            [
                'phone' => '+998900000002',
                'password' => Hash::make('password123'),
                'first_name' => 'Ali',
                'last_name' => 'Valiyev',
                'role' => 'student',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'elo_rating' => 1200,
                'battles_won' => 10,
                'battles_total' => 15,
            ]
        );
        $this->seedStudentStats($student1, xp: 1500, coins: 500, streak: 5, longestStreak: 14);

        $this->command->info('✅ Student yaratildi (Beginner):');
        $this->command->line('   📧 Email: student@test.uz');
        $this->command->line('   🔑 Parol: password123');

        // Student 2 - Advanced
        $student2 = User::firstOrCreate(
            ['email' => 'pro@test.uz'],
            [
                'phone' => '+998900000003',
                'password' => Hash::make('password123'),
                'first_name' => 'Sardor',
                'last_name' => 'Karimov',
                'role' => 'student',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'elo_rating' => 1800,
                'battles_won' => 80,
                'battles_total' => 100,
            ]
        );
        $this->seedStudentStats($student2, xp: 25000, coins: 5000, streak: 21, longestStreak: 60);

        $this->command->info('✅ Student yaratildi (Advanced):');
        $this->command->line('   📧 Email: pro@test.uz');
        $this->command->line('   🔑 Parol: password123');

        // Teacher
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@test.uz'],
            [
                'phone' => '+998900000004',
                'password' => Hash::make('password123'),
                'first_name' => 'Nilufar',
                'last_name' => 'Usmonova',
                'role' => 'teacher',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'xp_total' => 10000,
                'level' => 10,
                'coin_balance' => 2000,
                'elo_rating' => 1500,
                'battles_won' => 0,
                'battles_total' => 0,
                'streak_current' => 0,
                'streak_best' => 0,
            ]
        );

        $this->command->info('✅ Teacher yaratildi:');
        $this->command->line('   📧 Email: teacher@test.uz');
        $this->command->line('   🔑 Parol: password123');

        // Pupil 1 - Beginner Child
        $pupil1 = User::firstOrCreate(
            ['email' => 'pupil1@test.uz'],
            [
                'phone' => '+998900000005',
                'password' => Hash::make('password123'),
                'first_name' => 'Azizbek',
                'last_name' => 'Olimov',
                'role' => 'student',
                'title' => 'Beginner',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'elo_rating' => 1000,
                'battles_won' => 2,
                'battles_total' => 5,
            ]
        );
        $this->seedStudentStats($pupil1, xp: 200, coins: 100, streak: 2, longestStreak: 2);

        $this->command->info('✅ Pupil 1 yaratildi (Beginner):');
        $this->command->line('   📧 Email: pupil1@test.uz');
        $this->command->line('   🔑 Parol: password123');

        // Pupil 2 - Advanced Child
        $pupil2 = User::firstOrCreate(
            ['email' => 'pupil2@test.uz'],
            [
                'phone' => '+998900000006',
                'password' => Hash::make('password123'),
                'first_name' => 'Malika',
                'last_name' => 'Karimova',
                'role' => 'student',
                'title' => 'Advanced',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'elo_rating' => 1100,
                'battles_won' => 15,
                'battles_total' => 20,
            ]
        );
        $this->seedStudentStats($pupil2, xp: 800, coins: 300, streak: 10, longestStreak: 10);

        $this->command->info('✅ Pupil 2 yaratildi (Advanced):');
        $this->command->line('   📧 Email: pupil2@test.uz');
        $this->command->line('   🔑 Parol: password123');

        $this->command->newLine();
        $this->command->info('🎉 Barcha test foydalanuvchilar tayyor!');
    }

    /**
     * Demo XP / coins / streak go on the student profile, the single source the
     * app reads (users.xp_total / coin_balance / streak_* are legacy columns).
     * Only a freshly created account is seeded, so re-running never adds twice.
     */
    private function seedStudentStats(User $student, int $xp, int $coins, int $streak, int $longestStreak): void
    {
        if (! $student->wasRecentlyCreated) {
            return;
        }

        $student->ensureStudentProfile()->update([
            'xp' => $xp,
            'level' => StudentProfile::calculateLevel($xp),
            'coins' => $coins,
            'total_coins_earned' => $coins,
            'streak_days' => $streak,
            'longest_streak' => $longestStreak,
            'last_activity_date' => now()->toDateString(),
        ]);
    }
}
