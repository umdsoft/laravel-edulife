<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QA3-S-04: the seeded avatars and badges pointed at storage files that were
 * never shipped (shop/avatars/ninja.png ...), so an equipped avatar had no
 * picture to show. They now use the pictures bundled in public/images/shop.
 * An item whose image an admin replaced is left alone.
 */
return new class extends Migration
{
    private const IMAGES = [
        'shop/avatars/ninja.png' => '/images/shop/avatars/ninja.svg',
        'shop/avatars/robot.png' => '/images/shop/avatars/robot.svg',
        'shop/avatars/dragon.png' => '/images/shop/avatars/dragon.svg',
        'shop/badges/vip.png' => '/images/shop/badges/vip.svg',
        'shop/badges/champion.png' => '/images/shop/badges/champion.svg',
    ];

    public function up(): void
    {
        foreach (self::IMAGES as $old => $new) {
            DB::table('shop_items')->where('image', $old)->update(['image' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::IMAGES as $old => $new) {
            DB::table('shop_items')->where('image', $new)->update(['image' => $old]);
        }
    }
};
