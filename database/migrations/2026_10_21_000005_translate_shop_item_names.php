<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The shop is Uzbek like the rest of the UI ("Test Hint (5x)" -> "Test maslahati (5 ta)").
 * Renames only rows that still carry the seeded English name.
 */
return new class extends Migration
{
    private const NAMES = [
        ['Ninja Avatar', 'Ninja avatari'],
        ['Robot Avatar', 'Robot avatari'],
        ['Dragon Avatar', 'Ajdaho avatari'],
        ['VIP Badge', 'VIP nishoni'],
        ['Champion Badge', 'Chempion nishoni'],
        ['XP Boost 2x (1 kun)', 'XP kuchaytirgich 2x (1 kun)'],
        ['XP Boost 3x (1 kun)', 'XP kuchaytirgich 3x (1 kun)'],
        ['Coin Boost 2x (1 kun)', 'Coin kuchaytirgich 2x (1 kun)'],
        ['Test Hint (5x)', 'Test maslahati (5 ta)'],
        ['Test Hint (20x)', 'Test maslahati (20 ta)'],
        ['Battle Extra Life', "Battle: qo'shimcha imkoniyat"],
    ];

    public function up(): void
    {
        foreach (self::NAMES as [$en, $uz]) {
            DB::table('shop_items')->where('name', $en)->update(['name' => $uz]);
        }
    }

    public function down(): void
    {
        foreach (self::NAMES as [$en, $uz]) {
            DB::table('shop_items')->where('name', $uz)->update(['name' => $en]);
        }
    }
};
