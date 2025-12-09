<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // English Module Settings
            [
                'group' => 'english',
                'key' => 'english_test_mode',
                'value' => 'false',
                'type' => 'boolean',
                'label' => 'Test rejimi',
                'description' => 'Yoqilganda barcha darslar va levellar ochiq bo\'ladi. Faqat test qilish uchun ishlatiladi.',
                'is_public' => false,
            ],
            [
                'group' => 'english',
                'key' => 'english_require_80_percent',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'O\'tish uchun 80% talab qilish',
                'description' => 'O\'chirilganda foydalanuvchilar istalgan ball bilan o\'tishi mumkin.',
                'is_public' => false,
            ],
            [
                'group' => 'english',
                'key' => 'english_max_xp_per_lesson',
                'value' => '10',
                'type' => 'integer',
                'label' => 'Dars uchun maksimal XP',
                'description' => 'Bitta darsdan olinadigan maksimal XP miqdori.',
                'is_public' => false,
            ],
            [
                'group' => 'english',
                'key' => 'english_max_coins_per_lesson',
                'value' => '5',
                'type' => 'integer',
                'label' => 'Dars uchun maksimal Coin',
                'description' => 'Bitta darsdan olinadigan maksimal Coin miqdori.',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('English settings seeded successfully.');
    }
}
