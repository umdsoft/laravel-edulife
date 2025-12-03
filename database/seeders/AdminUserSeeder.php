<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@edulife.uz'],
            [
                'phone' => '+998901234567',
                'password' => Hash::make('admin123'),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'role' => 'super_admin',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'xp_total' => 0,
                'level' => 1,
                'coin_balance' => 10000, // Starting bonus
                'elo_rating' => 1000,
                'battles_won' => 0,
                'battles_total' => 0,
                'streak_current' => 0,
                'streak_best' => 0,
            ]
        );

        // Create additional admin user
        $moderator = User::firstOrCreate(
            ['email' => 'moderator@edulife.uz'],
            [
                'phone' => '+998901234568',
                'password' => Hash::make('moderator123'),
                'first_name' => 'Content',
                'last_name' => 'Moderator',
                'role' => 'admin',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'xp_total' => 0,
                'level' => 1,
                'coin_balance' => 5000,
                'elo_rating' =>1000,
                'battles_won' => 0,
                'battles_total' => 0,
                'streak_current' => 0,
                'streak_best' => 0,
            ]
        );
        
        // Note: Spatie Permission roles will be created via admin panel or separate migration
        $this->command->warn('⚠️ Spatie Permission roles skipped - create via admin panel');
    }
}
