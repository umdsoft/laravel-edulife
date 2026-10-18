<?php

namespace Database\Seeders;

use App\Models\Direction;
use Illuminate\Database\Seeder;

class DirectionSeeder extends Seeder
{
    public function run(): void
    {
        $directions = [
            [
                'name_uz' => 'Dasturlash',
                'name_ru' => 'Программирование',
                'name_en' => 'Programming',
                'slug' => 'programming',
                'description' => 'Web, Mobile va Desktop dasturlash tillari va texnologiyalari',
                'icon' => '💻',
                'color' => '#7C3AED',
                'sort_order' => 1,
                'is_active' => true,
                'courses_count' => 0,
            ],
            [
                'name_uz' => 'Rus tili',
                'name_ru' => 'Русский язык',
                'name_en' => 'Russian Language',
                'slug' => 'russian',
                'description' => 'Rus tili grammatika va nutq madaniyati',
                'icon' => '🇷🇺',
                'color' => '#EF4444',
                'sort_order' => 3,
                'is_active' => true,
                'courses_count' => 0,
            ],
            [
                'name_uz' => 'Matematika',
                'name_ru' => 'Математика',
                'name_en' => 'Mathematics',
                'slug' => 'math',
                'description' => 'Algebra, Geometriya va analitik matematika',
                'icon' => '📐',
                'color' => '#10B981',
                'sort_order' => 4,
                'is_active' => true,
                'courses_count' => 0,
            ],
            [
                'name_uz' => 'Marketing',
                'name_ru' => 'Маркетинг',
                'name_en' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Digital marketing, SMM va brendlash',
                'icon' => '📈',
                'color' => '#F59E0B',
                'sort_order' => 5,
                'is_active' => true,
                'courses_count' => 0,
            ],
            [
                'name_uz' => 'Dizayn',
                'name_ru' => 'Дизайн',
                'name_en' => 'Design',
                'slug' => 'design',
                'description' => 'Grafik dizayn, UI/UX va motion dizayn',
                'icon' => '🎨',
                'color' => '#EC4899',
                'sort_order' => 6,
                'is_active' => true,
                'courses_count' => 0,
            ],
            [
                'name_uz' => 'Biznes',
                'name_ru' => 'Бизнес',
                'name_en' => 'Business',
                'slug' => 'business',
                'description' => 'Tadbirkorlik, menejment va moliya',
                'icon' => '💼',
                'color' => '#6366F1',
                'sort_order' => 7,
                'is_active' => true,
                'courses_count' => 0,
            ],
        ];

        foreach ($directions as $direction) {
            Direction::create($direction);
        }
    }
}
