<?php

namespace Database\Seeders;

use App\Models\OlympiadType;
use App\Models\OlympiadStage;
use App\Models\Direction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OlympiadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedOlympiadStages();
        $this->seedOlympiadTypes();
    }

    /**
     * Seed olympiad stages
     */
    private function seedOlympiadStages(): void
    {
        $stages = [
            [
                'id' => Str::uuid()->toString(),
                'name' => OlympiadStage::STAGE_SCHOOL,
                'display_name' => 'School Stage',
                'display_name_uz' => 'Maktab bosqichi',
                'order_level' => OlympiadStage::ORDER_SCHOOL,
                'icon' => 'school',
                'color' => '#4CAF50',
                'advancement_config' => [
                    'top_n' => 10,
                    'min_score_percent' => 50,
                    'auto_register' => true,
                ],
                'reward_config' => [
                    '1' => ['discount_percent' => 60, 'bonus_coins' => 500, 'badge' => true],
                    '2' => ['discount_percent' => 50, 'bonus_coins' => 300, 'badge' => true],
                    '3' => ['discount_percent' => 40, 'bonus_coins' => 200, 'badge' => true],
                    '4-10' => ['discount_percent' => 20, 'bonus_coins' => 100],
                    '11+' => ['bonus_coins_percent' => 10],
                ],
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => OlympiadStage::STAGE_DISTRICT,
                'display_name' => 'District Stage',
                'display_name_uz' => 'Tuman bosqichi',
                'order_level' => OlympiadStage::ORDER_DISTRICT,
                'icon' => 'building-office',
                'color' => '#2196F3',
                'advancement_config' => [
                    'top_n' => 20,
                    'min_score_percent' => 55,
                    'auto_register' => true,
                ],
                'reward_config' => [
                    '1' => ['discount_percent' => 60, 'bonus_coins' => 1000, 'badge' => true],
                    '2' => ['discount_percent' => 50, 'bonus_coins' => 700, 'badge' => true],
                    '3' => ['discount_percent' => 40, 'bonus_coins' => 500, 'badge' => true],
                    '4-10' => ['discount_percent' => 20, 'bonus_coins' => 200],
                    '11+' => ['bonus_coins_percent' => 10],
                ],
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => OlympiadStage::STAGE_REGION,
                'display_name' => 'Region Stage',
                'display_name_uz' => 'Viloyat bosqichi',
                'order_level' => OlympiadStage::ORDER_REGION,
                'icon' => 'map',
                'color' => '#FF9800',
                'advancement_config' => [
                    'top_n' => 50,
                    'min_score_percent' => 60,
                    'auto_register' => true,
                ],
                'reward_config' => [
                    '1' => ['discount_percent' => 60, 'bonus_coins' => 1500, 'badge' => true],
                    '2' => ['discount_percent' => 50, 'bonus_coins' => 1000, 'badge' => true],
                    '3' => ['discount_percent' => 40, 'bonus_coins' => 700, 'badge' => true],
                    '4-10' => ['discount_percent' => 20, 'bonus_coins' => 300],
                    '11+' => ['bonus_coins_percent' => 10],
                ],
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => OlympiadStage::STAGE_NATIONAL,
                'display_name' => 'National Stage',
                'display_name_uz' => 'Respublika bosqichi',
                'order_level' => OlympiadStage::ORDER_NATIONAL,
                'icon' => 'flag',
                'color' => '#9C27B0',
                'advancement_config' => null, // Final stage
                'reward_config' => [
                    '1' => ['cash' => 5000000, 'bonus_coins' => 10000, 'badge' => true],
                    '2' => ['cash' => 3000000, 'bonus_coins' => 7000, 'badge' => true],
                    '3' => ['cash' => 2000000, 'bonus_coins' => 5000, 'badge' => true],
                    '4-10' => ['cash' => 500000, 'bonus_coins' => 2000],
                    '11+' => ['bonus_coins_percent' => 10],
                ],
            ],
        ];

        // Create stages
        foreach ($stages as $stageData) {
            OlympiadStage::updateOrCreate(
                ['name' => $stageData['name']],
                $stageData
            );
        }

        // Update next_stage_id references
        $school = OlympiadStage::where('name', OlympiadStage::STAGE_SCHOOL)->first();
        $district = OlympiadStage::where('name', OlympiadStage::STAGE_DISTRICT)->first();
        $region = OlympiadStage::where('name', OlympiadStage::STAGE_REGION)->first();
        $national = OlympiadStage::where('name', OlympiadStage::STAGE_NATIONAL)->first();

        $school->update(['next_stage_id' => $district->id]);
        $district->update(['next_stage_id' => $region->id]);
        $region->update(['next_stage_id' => $national->id]);
    }

    /**
     * Seed olympiad types
     */
    private function seedOlympiadTypes(): void
    {
        // Get or create directions for olympiad types
        $englishDirection = Direction::firstOrCreate(
            ['slug' => 'english-language'],
            [
                'name_uz' => 'Ingliz tili',
                'name_ru' => 'Английский язык',
                'name_en' => 'English Language',
                'slug' => 'english-language',
                'description' => 'English language olympiad direction',
                'icon' => 'language',
                'color' => '#3B82F6',
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $russianDirection = Direction::firstOrCreate(
            ['slug' => 'russian-language'],
            [
                'name_uz' => 'Rus tili',
                'name_ru' => 'Русский язык',
                'name_en' => 'Russian Language',
                'slug' => 'russian-language',
                'description' => 'Russian language olympiad direction',
                'icon' => 'language',
                'color' => '#EF4444',
                'sort_order' => 11,
                'is_active' => true,
            ]
        );

        $programmingDirection = Direction::firstOrCreate(
            ['slug' => 'programming'],
            [
                'name_uz' => 'Dasturlash',
                'name_ru' => 'Программирование',
                'name_en' => 'Programming',
                'slug' => 'programming',
                'description' => 'Programming olympiad direction',
                'icon' => 'code-bracket',
                'color' => '#10B981',
                'sort_order' => 12,
                'is_active' => true,
            ]
        );

        $mathDirection = Direction::firstOrCreate(
            ['slug' => 'mathematics'],
            [
                'name_uz' => 'Matematika',
                'name_ru' => 'Математика',
                'name_en' => 'Mathematics',
                'slug' => 'mathematics',
                'description' => 'Mathematics olympiad direction',
                'icon' => 'calculator',
                'color' => '#F59E0B',
                'sort_order' => 13,
                'is_active' => true,
            ]
        );

        $types = [
            [
                'direction_id' => $englishDirection->id,
                'name' => OlympiadType::TYPE_LANGUAGE_ENGLISH,
                'display_name' => 'English Language Olympiad',
                'display_name_uz' => 'Ingliz tili olimpiadasi',
                'description' => 'Comprehensive English language proficiency olympiad with test, listening, reading, writing, and speaking sections.',
                'sections' => ['test', 'listening', 'reading', 'writing', 'speaking'],
                'section_weights' => [
                    'test' => 20,
                    'listening' => 20,
                    'reading' => 20,
                    'writing' => 20,
                    'speaking' => 20,
                ],
                'icon' => 'language',
                'color' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'direction_id' => $russianDirection->id,
                'name' => OlympiadType::TYPE_LANGUAGE_RUSSIAN,
                'display_name' => 'Russian Language Olympiad',
                'display_name_uz' => 'Rus tili olimpiadasi',
                'description' => 'Comprehensive Russian language proficiency olympiad with test, listening, reading, writing, and speaking sections.',
                'sections' => ['test', 'listening', 'reading', 'writing', 'speaking'],
                'section_weights' => [
                    'test' => 20,
                    'listening' => 20,
                    'reading' => 20,
                    'writing' => 20,
                    'speaking' => 20,
                ],
                'icon' => 'language',
                'color' => '#EF4444',
                'is_active' => true,
            ],
            [
                'direction_id' => $programmingDirection->id,
                'name' => OlympiadType::TYPE_PROGRAMMING,
                'display_name' => 'Programming Olympiad',
                'display_name_uz' => 'Dasturlash olimpiadasi',
                'description' => 'Programming olympiad with algorithmic test and coding challenges.',
                'sections' => ['test', 'coding'],
                'section_weights' => [
                    'test' => 40,
                    'coding' => 60,
                ],
                'icon' => 'code-bracket',
                'color' => '#10B981',
                'is_active' => true,
            ],
            [
                'direction_id' => $mathDirection->id,
                'name' => OlympiadType::TYPE_MATH,
                'display_name' => 'Mathematics Olympiad',
                'display_name_uz' => 'Matematika olimpiadasi',
                'description' => 'Mathematics olympiad covering algebra, geometry, combinatorics, number theory, and logic.',
                'sections' => ['test'],
                'section_weights' => [
                    'test' => 100,
                ],
                'icon' => 'calculator',
                'color' => '#F59E0B',
                'is_active' => true,
            ],
        ];

        foreach ($types as $typeData) {
            OlympiadType::updateOrCreate(
                ['name' => $typeData['name']],
                $typeData
            );
        }
    }
}
