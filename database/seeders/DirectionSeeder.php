<?php

namespace Database\Seeders;

use App\Models\Direction;
use Illuminate\Database\Seeder;

/**
 * The platform teaches only two subjects: Mathematics and Physics.
 * Other subject directions were removed on purpose (owner decision 2026-10-19).
 */
class DirectionSeeder extends Seeder
{
    public const MATH = 'math';
    public const PHYSICS = 'physics';

    public static function directions(): array
    {
        return [
            [
                'name_uz' => 'Matematika',
                'name_ru' => 'Математика',
                'name_en' => 'Mathematics',
                'slug' => self::MATH,
                'description' => 'Algebra, geometriya, trigonometriya va matematik analiz',
                'icon' => '📐',
                'color' => '#10B981',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name_uz' => 'Fizika',
                'name_ru' => 'Физика',
                'name_en' => 'Physics',
                'slug' => self::PHYSICS,
                'description' => 'Mexanika, termodinamika, elektr va magnetizm, optika, to\'lqinlar va atom fizikasi',
                'icon' => '⚛️',
                'color' => '#3B82F6',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::directions() as $direction) {
            Direction::updateOrCreate(['slug' => $direction['slug']], $direction);
        }
    }
}
