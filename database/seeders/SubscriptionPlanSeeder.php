<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Trial',
                'slug' => 'trial',
                'description' => '7 kunlik bepul sinov muddati',
                'interval' => 'month',
                'interval_count' => 1,
                'price' => 0,
                'original_price' => 0,
                'features' => [
                    '7 kunlik sinov',
                    'Cheklangan kurslar',
                    '3 battle/kun',
                    '10% COIN limit'
                ],
                'trial_days' => 7,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standart',
                'slug' => 'standart',
                'description' => 'Asosiy funksiyalar va kurslar',
                'interval' => 'month',
                'interval_count' => 1,
                'price' => 149000,
                'original_price' => 149000,
                'features' => [
                    'Barcha kurslar',
                    '10 battle/kun',
                    '15% COIN limit',
                    'Email support'
                ],
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Standart Yillik',
                'slug' => 'standart-yearly',
                'description' => 'Standart tarif yillik to\'lov bilan (20% chegirma)',
                'interval' => 'year',
                'interval_count' => 1,
                'price' => 1192000,
                'original_price' => 1490000,
                'features' => [
                    'Barcha kurslar',
                    '10 battle/kun',
                    '15% COIN limit',
                    'Email support',
                    '20% chegirma'
                ],
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Kengaytirilgan funksiyalar va bonuslar',
                'interval' => 'month',
                'interval_count' => 1,
                'price' => 249000,
                'original_price' => 249000,
                'features' => [
                    'Barcha + Premium kurslar',
                    '25 battle/kun',
                    '20% COIN limit',
                    '1.5x XP multiplikator',
                    'Priority support'
                ],
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Premium Yillik',
                'slug' => 'premium-yearly',
                'description' => 'Premium tarif yillik to\'lov bilan (22% chegirma)',
                'interval' => 'year',
                'interval_count' => 1,
                'price' => 1942200,
                'original_price' => 2490000,
                'features' => [
                    'Barcha + Premium kurslar',
                    '25 battle/kun',
                    '20% COIN limit',
                    '1.5x XP multiplikator',
                    'Priority support',
                    '22% chegirma'
                ],
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'VIP',
                'slug' => 'vip',
                'description' => 'Maksimal imkoniyatlar va ekskluziv xizmatlar',
                'interval' => 'month',
                'interval_count' => 1,
                'price' => 399000,
                'original_price' => 399000,
                'features' => [
                    'Hamma narsa',
                    'Unlimited battle',
                    '25% COIN limit',
                    '2x XP multiplikator',
                    '1-on-1 support',
                    'Early access'
                ],
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'VIP Yillik',
                'slug' => 'vip-yearly',
                'description' => 'VIP tarif yillik to\'lov bilan (25% chegirma)',
                'interval' => 'year',
                'interval_count' => 1,
                'price' => 2983140,
                'original_price' => 3975000,
                'features' => [
                    'Hamma narsa',
                    'Unlimited battle',
                    '25% COIN limit',
                    '2x XP multiplikator',
                    '1-on-1 support',
                    'Early access',
                    '25% chegirma'
                ],
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
