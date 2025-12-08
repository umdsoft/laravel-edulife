<?php

namespace Database\Seeders;

use App\Models\English\UserEnglishProfile;
use App\Models\English\EnglishLevel;
use App\Models\English\EnglishUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserEnglishProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Get levels and units
        $a1Level = EnglishLevel::where('code', 'A1')->first();
        $a2Level = EnglishLevel::where('code', 'A2')->first();
        $firstUnit = EnglishUnit::orderBy('order_number')->first();

        if (!$a1Level) {
            $this->command->warn('A1 level not found. Skipping UserEnglishProfileSeeder.');
            return;
        }

        // Get admin user
        $adminUser = User::where('email', 'admin@edulife.uz')->first();

        if ($adminUser && !UserEnglishProfile::where('user_id', $adminUser->id)->exists()) {
            UserEnglishProfile::create([
                'id' => Str::uuid(),
                'user_id' => $adminUser->id,
                'current_level_id' => $a2Level?->id ?? $a1Level->id,
                'current_unit_id' => $firstUnit?->id,
                'total_xp' => 15000,
                'current_level_xp' => 5000,
                'coins' => 2500,
                'gems' => 50,
                'elo_rating' => 1250,
                'battles_played' => 25,
                'battles_won' => 18,
                'battles_lost' => 7,
                'win_streak' => 5,
                'best_win_streak' => 8,
                'words_learned' => 350,
                'words_mastered' => 120,
                'lessons_completed' => 45,
                'units_completed' => 7,
                'games_played' => 80,
                'tests_passed' => 12,
                'ai_conversations' => 15,
                'total_study_minutes' => 1800,
                'today_study_minutes' => 45,
                'last_study_date' => now()->toDateString(),
                'current_streak' => 15,
                'longest_streak' => 30,
                'streak_start_date' => now()->subDays(14)->toDateString(),
                'streak_freezes_available' => 3,
                'placement_test_completed' => true,
                'placement_test_date' => now()->subMonths(2),
                'placement_test_results' => [
                    'vocabulary' => 75,
                    'grammar' => 68,
                    'listening' => 72,
                    'reading' => 80,
                    'recommended_level' => 'A2',
                ],
                'daily_xp_goal' => 100,
                'today_xp_earned' => 45,
                'preferences' => [
                    'notifications' => true,
                    'daily_reminder_time' => '09:00',
                    'auto_play_audio' => true,
                    'show_translations' => true,
                ],
                'target_ielts_band' => 6.0,
                'estimated_ielts_band' => 4.5,
            ]);
        }

        // Create profile for moderator
        $moderatorUser = User::where('email', 'moderator@edulife.uz')->first();

        if ($moderatorUser && !UserEnglishProfile::where('user_id', $moderatorUser->id)->exists()) {
            UserEnglishProfile::create([
                'id' => Str::uuid(),
                'user_id' => $moderatorUser->id,
                'current_level_id' => $a1Level->id,
                'current_unit_id' => $firstUnit?->id,
                'total_xp' => 500,
                'current_level_xp' => 500,
                'coins' => 100,
                'gems' => 10,
                'elo_rating' => 1000,
                'words_learned' => 50,
                'lessons_completed' => 5,
                'games_played' => 10,
                'total_study_minutes' => 120,
                'current_streak' => 3,
                'longest_streak' => 5,
                'daily_xp_goal' => 50,
            ]);
        }
    }
}
