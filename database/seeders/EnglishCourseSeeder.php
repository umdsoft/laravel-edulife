<?php

namespace Database\Seeders;

use App\Models\English\EnglishLevel;
use App\Models\English\EnglishUnit;
use App\Models\English\EnglishLesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishCourseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Levels
        $levels = [
            [
                'code' => 'A1',
                'name' => 'Beginner',
                'description' => 'Start your English journey',
                'color' => 'emerald',
                'icon' => '🌱',
                'order_number' => 1,
                'total_lessons' => 50,
            ],
            [
                'code' => 'A2',
                'name' => 'Elementary',
                'description' => 'Build basic communication skills',
                'color' => 'blue',
                'icon' => '📘',
                'order_number' => 2,
                'total_lessons' => 60,
            ],
            [
                'code' => 'B1',
                'name' => 'Intermediate',
                'description' => 'Expand your vocabulary and grammar',
                'color' => 'purple',
                'icon' => '💜',
                'order_number' => 3,
                'total_lessons' => 70,
            ],
            [
                'code' => 'B2',
                'name' => 'Upper-Intermediate',
                'description' => 'Reach fluency in most situations',
                'color' => 'amber',
                'icon' => '🌟',
                'order_number' => 4,
                'total_lessons' => 80,
            ],
            [
                'code' => 'C1',
                'name' => 'Advanced',
                'description' => 'Master complex language structures',
                'color' => 'rose',
                'icon' => '🏆',
                'order_number' => 5,
                'total_lessons' => 90,
            ],
            [
                'code' => 'C2',
                'name' => 'Proficient',
                'description' => 'Near-native proficiency',
                'color' => 'indigo',
                'icon' => '👑',
                'order_number' => 6,
                'total_lessons' => 100,
            ],
        ];

        foreach ($levels as $levelData) {
            $level = EnglishLevel::where('code', $levelData['code'])->first();

            if (!$level) {
                $level = EnglishLevel::create([
                    'id' => Str::uuid(),
                    'code' => $levelData['code'],
                    'name' => $levelData['name'],
                    'name_uz' => $levelData['name'], // Default to English name
                    'description' => $levelData['description'],
                    'description_uz' => $levelData['description'], // Default to English description
                    'color' => $levelData['color'],
                    'icon' => $levelData['icon'],
                    'order_number' => $levelData['order_number'],
                    'is_active' => true,
                    'xp_required' => ($levelData['order_number'] - 1) * 1000,
                ]);
            }

            // Only populate A1 fully for now
            if ($levelData['code'] === 'A1') {
                $this->createA1Modules($level);
            }
        }
    }

    private function createA1Modules(EnglishLevel $level)
    {
        $modules = [
            [
                'title' => 'Greetings & Introductions',
                'description' => 'Learn basic greetings',
                'lessons' => [
                    ['title' => 'Hello & Goodbye', 'type' => 'vocabulary'],
                    ['title' => 'My Name Is...', 'type' => 'conversation'], // mapped from speaking
                    ['title' => 'Nice to Meet You', 'type' => 'conversation'], // mapped from listening
                    ['title' => 'Where Are You From?', 'type' => 'grammar'],
                    ['title' => 'Module Test', 'type' => 'test'],
                ]
            ],
            [
                'title' => 'Numbers & Alphabet',
                'description' => 'A-Z, 1-100, ordinal numbers',
                'lessons' => [
                    ['title' => 'Alphabet A-M', 'type' => 'vocabulary'],
                    ['title' => 'Alphabet N-Z', 'type' => 'vocabulary'],
                    ['title' => 'Numbers 1-20', 'type' => 'vocabulary'],
                    ['title' => 'Numbers 21-100', 'type' => 'vocabulary'],
                    ['title' => 'Module Test', 'type' => 'test'],
                ]
            ],
            [
                'title' => 'Colors & Shapes',
                'description' => 'Basic colors, geometric shapes',
                'lessons' => [
                    ['title' => 'Basic Colors', 'type' => 'vocabulary'],
                    ['title' => 'Shapes', 'type' => 'vocabulary'],
                    ['title' => 'Describing Objects', 'type' => 'grammar'],
                    ['title' => 'Module Test', 'type' => 'test'],
                ]
            ],
            // Add more modules as placeholders
            ['title' => 'Family & Friends', 'description' => 'Family members', 'lessons' => []],
            ['title' => 'Days & Months', 'description' => 'Time expressions', 'lessons' => []],
        ];

        foreach ($modules as $index => $moduleData) {
            $unit = EnglishUnit::where('level_id', $level->id)
                ->where('title', $moduleData['title'])
                ->first();

            if (!$unit) {
                $unit = EnglishUnit::create([
                    'id' => Str::uuid(),
                    'level_id' => $level->id,
                    'title' => $moduleData['title'],
                    'title_uz' => $moduleData['title'], // Default to English title
                    'description' => $moduleData['description'],
                    'description_uz' => $moduleData['description'], // Default to English description
                    'unit_number' => $index + 1,
                    'order_number' => $index + 1,
                    'estimated_minutes' => 60,
                    'is_active' => true,
                    'slug' => Str::slug($moduleData['title']),
                ]);
            }

            if (!empty($moduleData['lessons'])) {
                foreach ($moduleData['lessons'] as $lIndex => $lessonData) {
                    $lesson = EnglishLesson::where('unit_id', $unit->id)
                        ->where('title', $lessonData['title'])
                        ->first();

                    if (!$lesson) {
                        EnglishLesson::create([
                            'id' => Str::uuid(),
                            'unit_id' => $unit->id,
                            'title' => $lessonData['title'],
                            'title_uz' => $lessonData['title'], // Default to English title
                            'lesson_type' => $lessonData['type'],
                            'order_number' => $lIndex + 1,
                            'lesson_number' => $lIndex + 1,
                            'slug' => Str::slug($moduleData['title'] . '-' . $lessonData['title']),
                            'xp_reward' => 20,
                            'coin_reward' => 5,
                            'estimated_minutes' => 15,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }
}
