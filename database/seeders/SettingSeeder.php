<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // GENERAL Group
            ['group' => 'general', 'key' => 'site_name', 'value' => 'EDULIFE LMS', 'type' => 'string', 'label' => 'Sayt nomi', 'description' => 'Platforma nomi', 'is_public' => true],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'O\'zbekistonning eng yaxshi ta\'lim platformasi', 'type' => 'text', 'label' => 'Sayt tavsifi', 'description' => 'Qisqacha tavsif', 'is_public' => true],
            ['group' => 'general', 'key' => 'contact_email', 'value' => 'info@edulife.uz', 'type' => 'string', 'label' => 'Aloqa email', 'description' => 'Asosiy email manzil', 'is_public' => false],
            ['group' => 'general', 'key' => 'contact_phone', 'value' => '+998 90 123 45 67', 'type' => 'string', 'label' => 'Aloqa telefon', 'description' => 'Asosiy telefon raqam', 'is_public' => false],

            // XP Group
            ['group' => 'xp', 'key' => 'xp_per_lesson', 'value' => '30', 'type' => 'integer', 'label' => 'Dars uchun XP', 'description' => 'Har bir dars tugaganda beriladigan XP', 'is_public' => false],
            ['group' => 'xp', 'key' => 'xp_per_test_pass', 'value' => '100', 'type' => 'integer', 'label' => 'Test uchun XP', 'description' => 'Testdan o\'tganda beriladigan XP', 'is_public' => false],
            ['group' => 'xp', 'key' => 'xp_per_battle_win', 'value' => '150', 'type' => 'integer', 'label' => 'Battle g\'alaba uchun XP', 'description' => 'Battle yutganda beriladigan XP', 'is_public' => false],
            ['group' => 'xp', 'key' => 'xp_multiplier_premium', 'value' => '1.5', 'type' => 'string', 'label' => 'Premium XP multiplikator', 'description' => 'Premium foydalanuvchilar uchun XP ko\'paytmasi', 'is_public' => false],
            ['group' => 'xp', 'key' => 'xp_multiplier_vip', 'value' => '2.0', 'type' => 'string', 'label' => 'VIP XP multiplikator', 'description' => 'VIP foydalanuvchilar uchun XP ko\'paytmasi', 'is_public' => false],

            // COIN Group
            ['group' => 'coin', 'key' => 'coin_per_lesson', 'value' => '5', 'type' => 'integer', 'label' => 'Dars uchun COIN', 'description' => 'Har bir dars tugaganda beriladigan COIN', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_per_test_pass', 'value' => '25', 'type' => 'integer', 'label' => 'Test uchun COIN', 'description' => 'Testdan o\'tganda beriladigan COIN', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_per_battle_win', 'value' => '35', 'type' => 'integer', 'label' => 'Battle g\'alaba uchun COIN', 'description' => 'Battle yutganda beriladigan COIN', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_monthly_limit_trial', 'value' => '1500', 'type' => 'integer', 'label' => 'Trial oylik limit', 'description' => 'Trial foydalanuvchilar uchun oylik COIN limit', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_monthly_limit_standart', 'value' => '2235', 'type' => 'integer', 'label' => 'Standart oylik limit', 'description' => 'Standart foydalanuvchilar uchun oylik COIN limit', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_monthly_limit_premium', 'value' => '4980', 'type' => 'integer', 'label' => 'Premium oylik limit', 'description' => 'Premium foydalanuvchilar uchun oylik COIN limit', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_monthly_limit_vip', 'value' => '9975', 'type' => 'integer', 'label' => 'VIP oylik limit', 'description' => 'VIP foydalanuvchilar uchun oylik COIN limit', 'is_public' => false],
            ['group' => 'coin', 'key' => 'coin_transfer_fee_percent', 'value' => '5', 'type' => 'integer', 'label' => 'Transfer komissiya %', 'description' => 'COIN o\'tkazmalarda komissiya foizi', 'is_public' => false],

            // BATTLE Group
            ['group' => 'battle', 'key' => 'battle_questions_count', 'value' => '5', 'type' => 'integer', 'label' => 'Savol soni', 'description' => 'Har bir battle dagi savol soni', 'is_public' => false],
            ['group' => 'battle', 'key' => 'battle_time_per_question', 'value' => '20', 'type' => 'integer', 'label' => 'Javob vaqti (soniya)', 'description' => 'Har bir savol uchun vaqt (soniyada)', 'is_public' => false],
            ['group' => 'battle', 'key' => 'battle_daily_limit_trial', 'value' => '3', 'type' => 'integer', 'label' => 'Trial kunlik limit', 'description' => 'Trial foydalanuvchilar uchun kunlik battle limit', 'is_public' => false],
            ['group' => 'battle', 'key' => 'battle_daily_limit_standart', 'value' => '10', 'type' => 'integer', 'label' => 'Standart kunlik limit', 'description' => 'Standart foydalanuvchilar uchun kunlik battle limit', 'is_public' => false],
            ['group' => 'battle', 'key' => 'battle_daily_limit_premium', 'value' => '25', 'type' => 'integer', 'label' => 'Premium kunlik limit', 'description' => 'Premium foydalanuvchilar uchun kunlik battle limit', 'is_public' => false],
            ['group' => 'battle', 'key' => 'battle_daily_limit_vip', 'value' => '0', 'type' => 'integer', 'label' => 'VIP kunlik limit', 'description' => 'VIP foydalanuvchilar uchun kunlik battle limit (0 = cheksiz)', 'is_public' => false],
            ['group' => 'battle', 'key' => 'elo_k_factor', 'value' => '32', 'type' => 'integer', 'label' => 'ELO K-faktori', 'description' => 'ELO reyting hisoblash uchun K-faktor', 'is_public' => false],
            ['group' => 'battle', 'key' => 'elo_starting', 'value' => '1000', 'type' => 'integer', 'label' => 'Boshlang\'ich ELO', 'description' => 'Yangi foydalanuvchilar uchun boshlang\'ich ELO', 'is_public' => false],

            // TEST Group
            ['group' => 'test', 'key' => 'test_lesson_pass_score', 'value' => '86', 'type' => 'integer', 'label' => 'Dars test o\'tish bali', 'description' => 'Dars testidan o\'tish uchun minimal ball (%)', 'is_public' => false],
            ['group' => 'test', 'key' => 'test_module_pass_score', 'value' => '90', 'type' => 'integer', 'label' => 'Modul test o\'tish bali', 'description' => 'Modul testidan o\'tish uchun minimal ball (%)', 'is_public' => false],
            ['group' => 'test', 'key' => 'test_final_pass_score', 'value' => '85', 'type' => 'integer', 'label' => 'Final test o\'tish bali', 'description' => 'Final testidan o\'tish uchun minimal ball (%)', 'is_public' => false],
            ['group' => 'test', 'key' => 'test_daily_attempts', 'value' => '3', 'type' => 'integer', 'label' => 'Kunlik urinishlar', 'description' => 'Har bir test uchun kunlik urinishlar soni', 'is_public' => false],
            ['group' => 'test', 'key' => 'test_cooldown_hours', 'value' => '24', 'type' => 'integer', 'label' => 'Urinishlar orasidagi vaqt', 'description' => 'Test urinishlari orasidagi minimal vaqt (soatda)', 'is_public' => false],

            // STREAK Group
            ['group' => 'streak', 'key' => 'streak_multiplier_7', 'value' => '1.2', 'type' => 'string', 'label' => '7 kun streak multiplikator', 'description' => '7 kunlik streak uchun mukofot ko\'paytmasi', 'is_public' => false],
            ['group' => 'streak', 'key' => 'streak_multiplier_14', 'value' => '1.5', 'type' => 'string', 'label' => '14 kun streak multiplikator', 'description' => '14 kunlik streak uchun mukofot ko\'paytmasi', 'is_public' => false],
            ['group' => 'streak', 'key' => 'streak_multiplier_30', 'value' => '1.8', 'type' => 'string', 'label' => '30 kun streak multiplikator', 'description' => '30 kunlik streak uchun mukofot ko\'paytmasi', 'is_public' => false],
            ['group' => 'streak', 'key' => 'streak_multiplier_60', 'value' => '2.0', 'type' => 'string', 'label' => '60 kun streak multiplikator', 'description' => '60 kunlik streak uchun mukofot ko\'paytmasi', 'is_public' => false],
            ['group' => 'streak', 'key' => 'streak_multiplier_90', 'value' => '2.5', 'type' => 'string', 'label' => '90 kun streak multiplikator', 'description' => '90 kunlik streak uchun mukofot ko\'paytmasi', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
