<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
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
                'xp_total' => 1500,
                'level' => 5,
                'coin_balance' => 500,
                'elo_rating' => 1200,
                'battles_won' => 10,
                'battles_total' => 15,
                'streak_current' => 5,
                'streak_best' => 14,
            ]
        );

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
                'xp_total' => 25000,
                'level' => 15,
                'coin_balance' => 5000,
                'elo_rating' => 1800,
                'battles_won' => 80,
                'battles_total' => 100,
                'streak_current' => 21,
                'streak_best' => 60,
            ]
        );

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

        $this->command->newLine();
        $this->command->info('🎉 Barcha test foydalanuvchilar tayyor!');
    }
}
