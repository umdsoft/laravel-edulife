<?php

namespace Database\Seeders;

use App\Models\LabCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LabCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'mechanics',
                'name' => 'Mechanics',
                'name_uz' => 'Mexanika',
                'name_ru' => 'Механика',
                'description_uz' => 'Jismlar harakati, kuchlar, energiya va ish. Nyuton qonunlari, erkin tushish, mayatnik, ishqalanish kuchi.',
                'grade_levels' => [6, 7, 8, 9, 10, 11],
                'min_grade' => 6,
                'max_grade' => 11,
                'icon' => 'cog',
                'color' => '#3B82F6',
                'gradient' => 'from-blue-500 to-blue-700',
                'order_number' => 1,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'thermodynamics',
                'name' => 'Thermodynamics',
                'name_uz' => 'Termodinamika',
                'name_ru' => 'Термодинамика',
                'description_uz' => 'Issiqlik hodisalari, harorat, ichki energiya, bug\'lanish va kondensatsiya.',
                'grade_levels' => [7, 8, 9, 10],
                'min_grade' => 7,
                'max_grade' => 10,
                'icon' => 'fire',
                'color' => '#EF4444',
                'gradient' => 'from-red-500 to-orange-500',
                'order_number' => 2,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'electricity',
                'name' => 'Electricity',
                'name_uz' => 'Elektr',
                'name_ru' => 'Электричество',
                'description_uz' => 'Elektr toki, kuchlanish, qarshilik, quvvat. Om qonuni, elektr zanjirlari.',
                'grade_levels' => [8, 9, 10, 11],
                'min_grade' => 8,
                'max_grade' => 11,
                'icon' => 'bolt',
                'color' => '#F59E0B',
                'gradient' => 'from-yellow-500 to-amber-600',
                'order_number' => 3,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'slug' => 'optics',
                'name' => 'Optics',
                'name_uz' => 'Optika',
                'name_ru' => 'Оптика',
                'description_uz' => 'Yorug\'lik hodisalari, qaytish va sinish qonunlari, linzalar, ko\'zgular, prizma.',
                'grade_levels' => [8, 9, 10, 11],
                'min_grade' => 8,
                'max_grade' => 11,
                'icon' => 'eye',
                'color' => '#8B5CF6',
                'gradient' => 'from-purple-500 to-violet-600',
                'order_number' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'waves',
                'name' => 'Waves',
                'name_uz' => 'To\'lqinlar',
                'name_ru' => 'Волны',
                'description_uz' => 'Mexanik to\'lqinlar, tovush to\'lqinlari, rezonans, Dopler effekti.',
                'grade_levels' => [9, 10, 11],
                'min_grade' => 9,
                'max_grade' => 11,
                'icon' => 'signal',
                'color' => '#06B6D4',
                'gradient' => 'from-cyan-500 to-teal-600',
                'order_number' => 5,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'magnetism',
                'name' => 'Magnetism',
                'name_uz' => 'Magnetizm',
                'name_ru' => 'Магнетизм',
                'description_uz' => 'Magnit maydon, elektromagnit induksiya, Lorents kuchi.',
                'grade_levels' => [9, 10, 11],
                'min_grade' => 9,
                'max_grade' => 11,
                'icon' => 'magnet',
                'color' => '#EC4899',
                'gradient' => 'from-pink-500 to-rose-600',
                'order_number' => 6,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'slug' => 'atomic',
                'name' => 'Atomic Physics',
                'name_uz' => 'Atom fizikasi',
                'name_ru' => 'Атомная физика',
                'description_uz' => 'Atom tuzilishi, radioaktivlik, yadro reaksiyalari.',
                'grade_levels' => [10, 11],
                'min_grade' => 10,
                'max_grade' => 11,
                'icon' => 'atom',
                'color' => '#10B981',
                'gradient' => 'from-emerald-500 to-green-600',
                'order_number' => 7,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($categories as $data) {
            LabCategory::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('✅ 7 ta lab kategoriya yaratildi!');
    }
}
