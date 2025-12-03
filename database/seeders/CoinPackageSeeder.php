<?php

namespace Database\Seeders;

use App\Models\CoinPackage;
use Illuminate\Database\Seeder;

class CoinPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Boshlang\'ich',
                'coins' => 5000,
                'bonus_coins' => 0,
                'total_coins' => 5000,
                'price' => 50000,
                'original_price' => 50000,
                'badge' => null,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Asosiy',
                'coins' => 10000,
                'bonus_coins' => 500,
                'total_coins' => 10500,
                'price' => 100000,
                'original_price' => 100000,
                'badge' => 'Ommabop',
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Mashhur',
                'coins' => 25000,
                'bonus_coins' => 2500,
                'total_coins' => 27500,
                'price' => 250000,
                'original_price' => 250000,
                'badge' => 'Eng foydali',
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Premium',
                'coins' => 50000,
                'bonus_coins' => 7500,
                'total_coins' => 57500,
                'price' => 500000,
                'original_price' => 500000,
                'badge' => null,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Ultimate',
                'coins' => 100000,
                'bonus_coins' => 20000,
                'total_coins' => 120000,
                'price' => 1000000,
                'original_price' => 1000000,
                'badge' => 'Maksimal',
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($packages as $package) {
            CoinPackage::create($package);
        }
    }
}
