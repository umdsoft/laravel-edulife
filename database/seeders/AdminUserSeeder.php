<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = $this->password('SEED_ADMIN_PASSWORD', 'admin123');
        $moderatorPassword = $this->password('SEED_MODERATOR_PASSWORD', 'moderator123');

        // Create Super Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@edulife.uz'],
            [
                'phone' => '+998901234567',
                'password' => Hash::make($adminPassword),
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
                'password' => Hash::make($moderatorPassword),
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

    /**
     * Well-known demo passwords are only acceptable on a local machine; anywhere
     * else use the env value or a random one printed once.
     */
    private function password(string $envKey, string $localDefault): string
    {
        if ($fromEnv = env($envKey)) {
            return $fromEnv;
        }

        if (app()->isLocal()) {
            return $localDefault;
        }

        $generated = Str::password(20);
        $this->command?->warn("{$envKey} not set, generated: {$generated}");

        return $generated;
    }
}
