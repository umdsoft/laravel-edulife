<?php

namespace Database\Seeders;

use App\Models\LabBadge;
use Illuminate\Database\Seeder;

class LabBadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // ═══════════════════════════════════════════════════════════════
            // BOSHLANG'ICH BADGELAR (Common)
            // ═══════════════════════════════════════════════════════════════
            [
                'slug' => 'first-experiment',
                'name' => 'First Experiment',
                'name_uz' => 'Birinchi tajriba',
                'name_ru' => 'Первый эксперимент',
                'description_uz' => 'Birinchi tajribangizni muvaffaqiyatli yakunladingiz!',
                'icon' => 'beaker',
                'color' => '#10B981',
                'rarity' => 'common',
                'earn_condition' => ['type' => 'first_experiment'],
                'xp_reward' => 25,
                'coin_reward' => 5,
                'order_number' => 1,
            ],
            [
                'slug' => 'curious-mind',
                'name' => 'Curious Mind',
                'name_uz' => 'Qiziquvchan aql',
                'name_ru' => 'Любознательный ум',
                'description_uz' => '5 ta tajribani yakunlang',
                'icon' => 'light-bulb',
                'color' => '#F59E0B',
                'rarity' => 'common',
                'earn_condition' => ['type' => 'complete_experiments', 'count' => 5],
                'xp_reward' => 50,
                'coin_reward' => 10,
                'order_number' => 2,
            ],
            [
                'slug' => 'weekend-scientist',
                'name' => 'Weekend Scientist',
                'name_uz' => 'Dam olish kuni olimi',
                'name_ru' => 'Учёный выходного дня',
                'description_uz' => '3 kun ketma-ket lab bajaring',
                'icon' => 'calendar',
                'color' => '#3B82F6',
                'rarity' => 'common',
                'earn_condition' => ['type' => 'streak', 'days' => 3],
                'xp_reward' => 30,
                'coin_reward' => 10,
                'order_number' => 3,
            ],

            // ═══════════════════════════════════════════════════════════════
            // UNCOMMON BADGELAR
            // ═══════════════════════════════════════════════════════════════
            [
                'slug' => 'perfectionist',
                'name' => 'Perfectionist',
                'name_uz' => 'Perfeksionist',
                'name_ru' => 'Перфекционист',
                'description_uz' => '3 ta tajribada 100% ball oling',
                'icon' => 'star',
                'color' => '#FBBF24',
                'rarity' => 'uncommon',
                'earn_condition' => ['type' => 'perfect_score', 'count' => 3],
                'xp_reward' => 75,
                'coin_reward' => 20,
                'order_number' => 4,
            ],
            [
                'slug' => 'mechanics-explorer',
                'name' => 'Mechanics Explorer',
                'name_uz' => 'Mexanika kashfiyotchisi',
                'name_ru' => 'Исследователь механики',
                'description_uz' => 'Mexanika bo\'limida 5 ta tajribani yakunlang',
                'icon' => 'cog',
                'color' => '#3B82F6',
                'rarity' => 'uncommon',
                'earn_condition' => ['type' => 'complete_experiments', 'count' => 5, 'category' => 'mechanics'],
                'xp_reward' => 100,
                'coin_reward' => 25,
                'order_number' => 5,
            ],
            [
                'slug' => 'electricity-explorer',
                'name' => 'Electricity Explorer',
                'name_uz' => 'Elektr kashfiyotchisi',
                'name_ru' => 'Исследователь электричества',
                'description_uz' => 'Elektr bo\'limida 5 ta tajribani yakunlang',
                'icon' => 'bolt',
                'color' => '#F59E0B',
                'rarity' => 'uncommon',
                'earn_condition' => ['type' => 'complete_experiments', 'count' => 5, 'category' => 'electricity'],
                'xp_reward' => 100,
                'coin_reward' => 25,
                'order_number' => 6,
            ],
            [
                'slug' => 'dedicated-scientist',
                'name' => 'Dedicated Scientist',
                'name_uz' => 'Sodiq olim',
                'name_ru' => 'Преданный учёный',
                'description_uz' => '7 kun ketma-ket lab bajaring',
                'icon' => 'fire',
                'color' => '#EF4444',
                'rarity' => 'uncommon',
                'earn_condition' => ['type' => 'streak', 'days' => 7],
                'xp_reward' => 100,
                'coin_reward' => 30,
                'order_number' => 7,
            ],

            // ═══════════════════════════════════════════════════════════════
            // RARE BADGELAR
            // ═══════════════════════════════════════════════════════════════
            [
                'slug' => 'speed-demon',
                'name' => 'Speed Demon',
                'name_uz' => 'Tezlik jinni',
                'name_ru' => 'Скоростной демон',
                'description_uz' => 'Qiyin tajribani 15 daqiqadan kam vaqtda yakunlang',
                'icon' => 'clock',
                'color' => '#8B5CF6',
                'rarity' => 'rare',
                'earn_condition' => ['type' => 'speed_complete', 'under_minutes' => 15, 'difficulty' => 'hard'],
                'xp_reward' => 150,
                'coin_reward' => 50,
                'order_number' => 8,
            ],
            [
                'slug' => 'xp-hunter',
                'name' => 'XP Hunter',
                'name_uz' => 'XP ovchisi',
                'name_ru' => 'Охотник за XP',
                'description_uz' => '1000 XP to\'plang',
                'icon' => 'trophy',
                'color' => '#10B981',
                'rarity' => 'rare',
                'earn_condition' => ['type' => 'total_xp', 'amount' => 1000],
                'xp_reward' => 150,
                'coin_reward' => 50,
                'order_number' => 9,
            ],
            [
                'slug' => 'multi-master',
                'name' => 'Multi Master',
                'name_uz' => 'Ko\'p sohali usta',
                'name_ru' => 'Мультимастер',
                'description_uz' => '20 ta tajribani yakunlang',
                'icon' => 'academic-cap',
                'color' => '#6366F1',
                'rarity' => 'rare',
                'earn_condition' => ['type' => 'complete_experiments', 'count' => 20],
                'xp_reward' => 200,
                'coin_reward' => 75,
                'order_number' => 10,
            ],
            [
                'slug' => 'streak-master',
                'name' => 'Streak Master',
                'name_uz' => 'Streak ustasi',
                'name_ru' => 'Мастер серии',
                'description_uz' => '14 kun ketma-ket lab bajaring',
                'icon' => 'fire',
                'color' => '#DC2626',
                'rarity' => 'rare',
                'earn_condition' => ['type' => 'streak', 'days' => 14],
                'xp_reward' => 200,
                'coin_reward' => 75,
                'order_number' => 11,
            ],

            // ═══════════════════════════════════════════════════════════════
            // EPIC BADGELAR
            // ═══════════════════════════════════════════════════════════════
            [
                'slug' => 'mechanics-master',
                'name' => 'Mechanics Master',
                'name_uz' => 'Mexanika ustasi',
                'name_ru' => 'Мастер механики',
                'description_uz' => 'Mexanika bo\'limini to\'liq yakunlang',
                'icon' => 'cog',
                'color' => '#1E40AF',
                'background_gradient' => 'from-blue-600 via-blue-500 to-cyan-400',
                'rarity' => 'epic',
                'earn_condition' => ['type' => 'complete_category', 'category' => 'mechanics'],
                'xp_reward' => 500,
                'coin_reward' => 150,
                'order_number' => 12,
            ],
            [
                'slug' => 'electricity-master',
                'name' => 'Electricity Master',
                'name_uz' => 'Elektr ustasi',
                'name_ru' => 'Мастер электричества',
                'description_uz' => 'Elektr bo\'limini to\'liq yakunlang',
                'icon' => 'bolt',
                'color' => '#B45309',
                'background_gradient' => 'from-yellow-600 via-amber-500 to-orange-400',
                'rarity' => 'epic',
                'earn_condition' => ['type' => 'complete_category', 'category' => 'electricity'],
                'xp_reward' => 500,
                'coin_reward' => 150,
                'order_number' => 13,
            ],
            [
                'slug' => 'perfect-ten',
                'name' => 'Perfect Ten',
                'name_uz' => 'Mukammal o\'nlik',
                'name_ru' => 'Идеальная десятка',
                'description_uz' => '10 ta tajribada 100% ball oling',
                'icon' => 'sparkles',
                'color' => '#7C3AED',
                'background_gradient' => 'from-purple-600 via-violet-500 to-fuchsia-400',
                'rarity' => 'epic',
                'earn_condition' => ['type' => 'perfect_score', 'count' => 10],
                'xp_reward' => 500,
                'coin_reward' => 200,
                'order_number' => 14,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LEGENDARY BADGELAR
            // ═══════════════════════════════════════════════════════════════
            [
                'slug' => 'physics-legend',
                'name' => 'Physics Legend',
                'name_uz' => 'Fizika afsonasi',
                'name_ru' => 'Легенда физики',
                'description_uz' => 'Barcha bo\'limlardagi 50 ta tajribani yakunlang',
                'icon' => 'star',
                'color' => '#CA8A04',
                'background_gradient' => 'from-yellow-500 via-amber-400 to-orange-300',
                'rarity' => 'legendary',
                'earn_condition' => ['type' => 'complete_experiments', 'count' => 50],
                'xp_reward' => 1000,
                'coin_reward' => 500,
                'order_number' => 15,
            ],
            [
                'slug' => 'ultimate-streak',
                'name' => 'Ultimate Streak',
                'name_uz' => 'Eng zo\'r streak',
                'name_ru' => 'Абсолютная серия',
                'description_uz' => '30 kun ketma-ket lab bajaring',
                'icon' => 'fire',
                'color' => '#B91C1C',
                'background_gradient' => 'from-red-600 via-orange-500 to-yellow-400',
                'rarity' => 'legendary',
                'earn_condition' => ['type' => 'streak', 'days' => 30],
                'xp_reward' => 1000,
                'coin_reward' => 500,
                'order_number' => 16,
            ],
            [
                'slug' => 'xp-master',
                'name' => 'XP Master',
                'name_uz' => 'XP ustasi',
                'name_ru' => 'Мастер XP',
                'description_uz' => '10000 XP to\'plang',
                'icon' => 'trophy',
                'color' => '#15803D',
                'background_gradient' => 'from-emerald-600 via-green-500 to-lime-400',
                'rarity' => 'legendary',
                'earn_condition' => ['type' => 'total_xp', 'amount' => 10000],
                'xp_reward' => 1000,
                'coin_reward' => 500,
                'order_number' => 17,
            ],
        ];

        foreach ($badges as $data) {
            LabBadge::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }

        $this->command->info('✅ 17 ta lab badge yaratildi!');
    }
}
