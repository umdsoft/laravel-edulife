<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Exposes the battle scoring parameters (App\Services\BattleScoringSettings) on the
 * admin settings page. Values equal the service defaults, so nothing changes until
 * an admin edits them. Existing rows are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $rows = array_map(fn (array $row) => $row + [
            'group' => 'battle',
            'is_public' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::rows());

        foreach ($rows as $row) {
            if (! DB::table('settings')->where('key', $row['key'])->exists()) {
                DB::table('settings')->insert(['id' => (string) Str::uuid7()] + $row);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', array_column(self::rows(), 'key'))->delete();
        }
    }

    public static function rows(): array
    {
        return [
            ['key' => 'battle_scoring_base', 'value' => '100', 'type' => 'integer', 'label' => 'Savolning asosiy qiymati', 'description' => "Hamma yechadigan savol taxminan shuncha ochko beradi"],
            ['key' => 'battle_scoring_k', 'value' => '2', 'type' => 'string', 'label' => 'Qiyinlik koeffitsiyenti (k)', 'description' => "Qiymat = asos × (1 + k·(1 − yechilish ulushi)); k=2 da eng qiyin savol ≈ 3 baravar qimmat"],
            ['key' => 'battle_scoring_speed_bonus', 'value' => '0.2', 'type' => 'string', 'label' => 'Tezlik bonusi (maksimal ulush)', 'description' => "0.2 = eng tez javobga +20%; tezlik qiyinlikdan muhim bo'lmasligi uchun kichik qoldiring"],
            ['key' => 'battle_scoring_solver_exponent', 'value' => '1', 'type' => 'string', 'label' => 'Yechganlar soni darajasi', 'description' => "1 = ikkalasi yechsa har biri yarim qiymat oladi; 0 = yechganlar soni hisobga olinmaydi"],
            ['key' => 'battle_scoring_prior_strength', 'value' => '10', 'type' => 'integer', 'label' => 'Yangi savol barqarorligi (m)', 'description' => "Savol qiyinligi shuncha urinishdan keyin o'z statistikasiga o'ta boshlaydi"],
            ['key' => 'battle_scoring_prior_easy', 'value' => '0.8', 'type' => 'string', 'label' => "Boshlang'ich yechilish ulushi: Oson", 'description' => "Ustoz 'Oson' belgilagan yangi savol uchun taxminiy yechilish ulushi"],
            ['key' => 'battle_scoring_prior_medium', 'value' => '0.6', 'type' => 'string', 'label' => "Boshlang'ich yechilish ulushi: O'rta", 'description' => "Ustoz 'O'rta' belgilagan yangi savol uchun taxminiy yechilish ulushi"],
            ['key' => 'battle_scoring_prior_hard', 'value' => '0.35', 'type' => 'string', 'label' => "Boshlang'ich yechilish ulushi: Qiyin", 'description' => "Ustoz 'Qiyin' belgilagan yangi savol uchun taxminiy yechilish ulushi"],
            ['key' => 'battle_scoring_test_weight', 'value' => '0.5', 'type' => 'string', 'label' => "Kurs testi javoblari og'irligi", 'description' => "Kurs testlaridagi javoblar savol statistikasiga shu og'irlik bilan qo'shiladi (battle javoblari = 1)"],
            ['key' => 'battle_level_easy_min_p', 'value' => '0.7', 'type' => 'string', 'label' => "'Oson' daraja chegarasi", 'description' => "Yechilish ulushi shundan yuqori savollar 'Oson' hisoblanadi"],
            ['key' => 'battle_level_hard_max_p', 'value' => '0.4', 'type' => 'string', 'label' => "'Qiyin' daraja chegarasi", 'description' => "Yechilish ulushi shundan past savollar 'Qiyin' hisoblanadi"],
            ['key' => 'battle_elo_medium_from', 'value' => '1200', 'type' => 'integer', 'label' => "Avtomatik 'O'rta' daraja ELO'dan", 'description' => "O'yinchilarning o'rtacha ELO'si shundan boshlab 'O'rta' savollar tushadi"],
            ['key' => 'battle_elo_hard_from', 'value' => '1500', 'type' => 'integer', 'label' => "Avtomatik 'Qiyin' daraja ELO'dan", 'description' => "O'yinchilarning o'rtacha ELO'si shundan boshlab 'Qiyin' savollar tushadi"],
            ['key' => 'battle_recent_battles', 'value' => '20', 'type' => 'integer', 'label' => 'Takrorlanmaslik oynasi', 'description' => "O'yinchi oxirgi shuncha battle'da ko'rgan savollar imkon qadar qayta tushmaydi"],
        ];
    }
};
