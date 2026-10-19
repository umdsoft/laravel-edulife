<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * QA3-A-01: the XP / Coin / Battle settings are now read by the reward code.
 * Their seeded values (30 XP a lesson, 1 500 coins a month, 3 battles a day...)
 * were never applied, so switching them on as they are would change every
 * reward at once. A row that still holds its seeded value is set to what the
 * platform actually gives today; a value an admin changed is kept. The
 * descriptions now say how each value is applied.
 */
return new class extends Migration
{
    /** key => [seeded value, value matching today's rewards, description] */
    private const ROWS = [
        'xp_per_lesson' => ['30', '8', "Matnli dars uchun XP. Video dars ×5/8, amaliy (test) dars ×15/8"],
        'xp_per_test_pass' => ['100', '40', "Testdan 90%+ bilan o'tganda. 70–89% — ×5/8, 50–69% — ×1/4"],
        'xp_per_battle_win' => ['150', '100', "Battle g'olibiga beriladigan XP"],
        'xp_multiplier_premium' => ['1.5', '1', "Premium obunachilar XP'si shu songa ko'paytiriladi (1 = o'zgarmaydi)"],
        'xp_multiplier_vip' => ['2.0', '1', "VIP obunachilar XP'si shu songa ko'paytiriladi (1 = o'zgarmaydi)"],
        'coin_per_lesson' => ['5', '0', "Har bir dars tugaganda beriladigan coin (0 = bermaydi)"],
        'coin_per_test_pass' => ['25', '0', "Testdan 90%+ bilan o'tganda beriladigan coin; past natijada XP kabi kamayadi (0 = bermaydi)"],
        'coin_per_battle_win' => ['35', '10', "Battle g'olibiga beriladigan coin"],
        'coin_monthly_limit_trial' => ['1500', '0', "Obunasiz o'quvchi bir oyda mukofotlardan yig'adigan maksimum coin (0 = cheksiz)"],
        'coin_monthly_limit_standart' => ['2235', '0', "Standart obunachi uchun oylik coin limiti (0 = cheksiz)"],
        'coin_monthly_limit_premium' => ['4980', '0', "Premium obunachi uchun oylik coin limiti (0 = cheksiz)"],
        'coin_monthly_limit_vip' => ['9975', '0', "VIP obunachi uchun oylik coin limiti (0 = cheksiz)"],
        'battle_daily_limit_trial' => ['3', '0', "Obunasiz o'quvchi bir kunda o'ynaydigan battle soni (0 = cheksiz)"],
        'battle_daily_limit_standart' => ['10', '0', "Standart obunachi uchun kunlik battle limiti (0 = cheksiz)"],
        'battle_daily_limit_premium' => ['25', '0', "Premium obunachi uchun kunlik battle limiti (0 = cheksiz)"],
        'battle_daily_limit_vip' => ['0', '0', "VIP obunachi uchun kunlik battle limiti (0 = cheksiz)"],
        'battle_questions_count' => ['5', '5', "Har bir battle'dagi savollar soni (kamida 3)"],
        'battle_time_per_question' => ['20', '20', "Har bir savol uchun vaqt, soniya"],
        'elo_k_factor' => ['32', '20', "Oddiy o'yinchi uchun K. Yangi o'yinchi (30 o'yingacha) ×2, ELO 2400+ ×½"],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $key => [$seeded, $current, $description]) {
            $row = DB::table('settings')->where('key', $key)->first();
            if (! $row) {
                continue;
            }

            $update = ['description' => $description, 'updated_at' => now()];
            if ($this->same($row->value, $seeded)) {
                $update['value'] = $current;
            }

            DB::table('settings')->where('key', $key)->update($update);
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings');
    }

    public function down(): void
    {
        foreach (self::ROWS as $key => [$seeded, $current]) {
            DB::table('settings')->where('key', $key)->get()->each(function ($row) use ($key, $seeded, $current) {
                if ($this->same($row->value, $current)) {
                    DB::table('settings')->where('key', $key)->update(['value' => $seeded]);
                }
            });
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings');
    }

    private function same(?string $a, string $b): bool
    {
        return is_numeric($a) ? (float) $a === (float) $b : $a === $b;
    }
};
