<?php

namespace Database\Seeders;

use App\Models\English\EnglishLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'code' => 'A1',
                'name' => 'Beginner',
                'name_uz' => 'Boshlang\'ich',
                'description' => 'Can understand and use familiar everyday expressions and very basic phrases.',
                'description_uz' => 'Oddiy kundalik iboralar va juda sodda jumlalarni tushunadi va ishlata oladi.',
                'can_do_statements' => [
                    'listening' => ['Can understand familiar words and basic phrases', 'Can understand slow and clear speech'],
                    'speaking' => ['Can introduce themselves and others', 'Can ask and answer simple questions'],
                    'reading' => ['Can understand familiar names and simple words', 'Can understand simple sentences on signs'],
                    'writing' => ['Can write simple phrases', 'Can fill in forms with personal details']
                ],
                'vocabulary_target' => 500,
                'grammar_structures' => 20,
                'estimated_hours' => 100,
                'xp_required' => 0,
                'xp_to_complete' => 5000,
                'ielts_band_min' => null,
                'ielts_band_max' => null,
                'color' => 'emerald',
                'icon' => '🌱',
                'order_number' => 1,
            ],
            [
                'code' => 'A2',
                'name' => 'Elementary',
                'name_uz' => 'Boshlang\'ich+',
                'description' => 'Can understand sentences and frequently used expressions related to areas of most immediate relevance.',
                'description_uz' => 'Kundalik sohalarga oid gaplar va tez-tez ishlatiladigan iboralarni tushunadi.',
                'can_do_statements' => [
                    'listening' => ['Can understand phrases and common vocabulary', 'Can understand the main point in short, clear messages'],
                    'speaking' => ['Can communicate in simple routine tasks', 'Can describe their background and immediate environment'],
                    'reading' => ['Can read short, simple texts', 'Can find specific information in everyday material'],
                    'writing' => ['Can write short, simple notes and messages', 'Can write a simple personal letter']
                ],
                'vocabulary_target' => 1000,
                'grammar_structures' => 35,
                'estimated_hours' => 180,
                'xp_required' => 5000,
                'xp_to_complete' => 10000,
                'ielts_band_min' => 3.0,
                'ielts_band_max' => 3.5,
                'color' => 'blue',
                'icon' => '📘',
                'order_number' => 2,
            ],
            [
                'code' => 'B1',
                'name' => 'Intermediate',
                'name_uz' => 'O\'rta',
                'description' => 'Can understand the main points of clear standard input on familiar matters.',
                'description_uz' => 'Tanish mavzulardagi aniq standart matnlarning asosiy fikrlarini tushunadi.',
                'can_do_statements' => [
                    'listening' => ['Can understand main points of clear standard speech', 'Can understand TV programmes on current affairs'],
                    'speaking' => ['Can deal with most situations while travelling', 'Can describe experiences, events, dreams and ambitions'],
                    'reading' => ['Can understand texts with everyday language', 'Can understand description of events and feelings'],
                    'writing' => ['Can write simple connected text on familiar topics', 'Can write personal letters describing experiences']
                ],
                'vocabulary_target' => 2500,
                'grammar_structures' => 50,
                'estimated_hours' => 300,
                'xp_required' => 15000,
                'xp_to_complete' => 20000,
                'ielts_band_min' => 4.0,
                'ielts_band_max' => 5.0,
                'color' => 'purple',
                'icon' => '💜',
                'order_number' => 3,
            ],
            [
                'code' => 'B2',
                'name' => 'Upper-Intermediate',
                'name_uz' => 'O\'rta+',
                'description' => 'Can understand the main ideas of complex text on both concrete and abstract topics.',
                'description_uz' => 'Aniq va mavhum mavzulardagi murakkab matnlarning asosiy g\'oyalarini tushunadi.',
                'can_do_statements' => [
                    'listening' => ['Can understand extended speech and lectures', 'Can understand most TV news and current affairs'],
                    'speaking' => ['Can interact with a degree of fluency and spontaneity', 'Can explain a viewpoint on a topical issue'],
                    'reading' => ['Can read articles on contemporary problems', 'Can understand contemporary literary prose'],
                    'writing' => ['Can write clear, detailed text on a wide range of subjects', 'Can write essays or reports presenting arguments']
                ],
                'vocabulary_target' => 5000,
                'grammar_structures' => 70,
                'estimated_hours' => 400,
                'xp_required' => 35000,
                'xp_to_complete' => 30000,
                'ielts_band_min' => 5.5,
                'ielts_band_max' => 6.5,
                'color' => 'amber',
                'icon' => '🌟',
                'order_number' => 4,
            ],
            [
                'code' => 'C1',
                'name' => 'Advanced',
                'name_uz' => 'Yuqori',
                'description' => 'Can understand a wide range of demanding, longer texts, and recognise implicit meaning.',
                'description_uz' => 'Keng doiradagi talab qiluvchi uzun matnlarni tushunadi va yashirin ma\'nolarni anglaydi.',
                'can_do_statements' => [
                    'listening' => ['Can understand extended speech even when not clearly structured', 'Can understand TV programmes and films without much effort'],
                    'speaking' => ['Can express ideas fluently and spontaneously', 'Can use language flexibly for social and professional purposes'],
                    'reading' => ['Can understand long and complex texts', 'Can understand specialised articles and technical instructions'],
                    'writing' => ['Can write clear, well-structured text on complex subjects', 'Can write in a style appropriate to the reader in mind']
                ],
                'vocabulary_target' => 8000,
                'grammar_structures' => 85,
                'estimated_hours' => 600,
                'xp_required' => 65000,
                'xp_to_complete' => 40000,
                'ielts_band_min' => 7.0,
                'ielts_band_max' => 8.0,
                'color' => 'rose',
                'icon' => '🏆',
                'order_number' => 5,
            ],
            [
                'code' => 'C2',
                'name' => 'Proficiency',
                'name_uz' => 'Mukammal',
                'description' => 'Can understand with ease virtually everything heard or read.',
                'description_uz' => 'Eshitgan yoki o\'qigan deyarli hamma narsani osonlik bilan tushunadi.',
                'can_do_statements' => [
                    'listening' => ['Can understand any kind of spoken language with ease', 'Can understand accents and colloquialisms'],
                    'speaking' => ['Can express themselves spontaneously, very fluently and precisely', 'Can convey finer shades of meaning precisely'],
                    'reading' => ['Can read with ease virtually all forms of written language', 'Can understand complex texts including abstract and literary'],
                    'writing' => ['Can write clear, smoothly flowing text in appropriate style', 'Can write complex letters, reports or articles']
                ],
                'vocabulary_target' => 10000,
                'grammar_structures' => 100,
                'estimated_hours' => 800,
                'xp_required' => 105000,
                'xp_to_complete' => 50000,
                'ielts_band_min' => 8.5,
                'ielts_band_max' => 9.0,
                'color' => 'indigo',
                'icon' => '👑',
                'order_number' => 6,
            ],
        ];

        foreach ($levels as $level) {
            EnglishLevel::updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }
    }
}
