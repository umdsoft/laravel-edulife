<?php

namespace Database\Seeders;

use App\Models\English\EnglishGrammarCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishGrammarCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'tenses',
                'name' => 'Tenses',
                'name_uz' => 'Zamonlar',
                'description' => 'All English tenses: Present, Past, Future',
                'description_uz' => 'Barcha ingliz zamonlari: Hozirgi, O\'tgan, Kelasi',
                'icon' => 'clock',
                'color' => '#3B82F6',
                'children' => [
                    ['slug' => 'present-tenses', 'name' => 'Present Tenses', 'name_uz' => 'Hozirgi Zamonlar'],
                    ['slug' => 'past-tenses', 'name' => 'Past Tenses', 'name_uz' => 'O\'tgan Zamonlar'],
                    ['slug' => 'future-tenses', 'name' => 'Future Tenses', 'name_uz' => 'Kelasi Zamonlar'],
                    ['slug' => 'perfect-tenses', 'name' => 'Perfect Tenses', 'name_uz' => 'Tugallangan Zamonlar'],
                ]
            ],
            [
                'slug' => 'verbs',
                'name' => 'Verbs',
                'name_uz' => 'Fe\'llar',
                'description' => 'All about verbs: modal, auxiliary, phrasal',
                'description_uz' => 'Fe\'llar haqida: modal, yordamchi, frazel',
                'icon' => 'lightning-bolt',
                'color' => '#EF4444',
                'children' => [
                    ['slug' => 'modal-verbs', 'name' => 'Modal Verbs', 'name_uz' => 'Modal Fe\'llar'],
                    ['slug' => 'auxiliary-verbs', 'name' => 'Auxiliary Verbs', 'name_uz' => 'Yordamchi Fe\'llar'],
                    ['slug' => 'phrasal-verbs', 'name' => 'Phrasal Verbs', 'name_uz' => 'Frazel Fe\'llar'],
                    ['slug' => 'irregular-verbs', 'name' => 'Irregular Verbs', 'name_uz' => 'Noto\'g\'ri Fe\'llar'],
                ]
            ],
            [
                'slug' => 'nouns',
                'name' => 'Nouns',
                'name_uz' => 'Otlar',
                'description' => 'Nouns: countable, uncountable, plurals',
                'description_uz' => 'Otlar: sanaladigan, sanalmaydigan, ko\'plik',
                'icon' => 'cube',
                'color' => '#22C55E',
            ],
            [
                'slug' => 'articles',
                'name' => 'Articles',
                'name_uz' => 'Artikllar',
                'description' => 'Definite and indefinite articles: a, an, the',
                'description_uz' => 'Aniq va noaniq artikllar: a, an, the',
                'icon' => 'document-text',
                'color' => '#F59E0B',
            ],
            [
                'slug' => 'adjectives',
                'name' => 'Adjectives',
                'name_uz' => 'Sifatlar',
                'description' => 'Adjectives: comparative, superlative',
                'description_uz' => 'Sifatlar: qiyosiy, orttirma daraja',
                'icon' => 'sparkles',
                'color' => '#EC4899',
            ],
            [
                'slug' => 'adverbs',
                'name' => 'Adverbs',
                'name_uz' => 'Ravishlar',
                'description' => 'Adverbs: frequency, manner, place, time',
                'description_uz' => 'Ravishlar: tezlik, usul, joy, vaqt',
                'icon' => 'arrow-right',
                'color' => '#8B5CF6',
            ],
            [
                'slug' => 'pronouns',
                'name' => 'Pronouns',
                'name_uz' => 'Olmoshlar',
                'description' => 'All types of pronouns',
                'description_uz' => 'Barcha olmosh turlari',
                'icon' => 'user-circle',
                'color' => '#06B6D4',
            ],
            [
                'slug' => 'prepositions',
                'name' => 'Prepositions',
                'name_uz' => 'Predloglar',
                'description' => 'Prepositions: time, place, movement',
                'description_uz' => 'Predloglar: vaqt, joy, harakat',
                'icon' => 'location-marker',
                'color' => '#14B8A6',
            ],
            [
                'slug' => 'conjunctions',
                'name' => 'Conjunctions',
                'name_uz' => 'Bog\'lovchilar',
                'description' => 'Connecting words and phrases',
                'description_uz' => 'Bog\'lovchi so\'z va iboralar',
                'icon' => 'link',
                'color' => '#64748B',
            ],
            [
                'slug' => 'conditionals',
                'name' => 'Conditionals',
                'name_uz' => 'Shart Gaplar',
                'description' => 'Zero, First, Second, Third conditional',
                'description_uz' => 'Nolinchi, Birinchi, Ikkinchi, Uchinchi shart',
                'icon' => 'switch-horizontal',
                'color' => '#A855F7',
            ],
            [
                'slug' => 'passive-voice',
                'name' => 'Passive Voice',
                'name_uz' => 'Majhul Nisbat',
                'description' => 'Passive voice in all tenses',
                'description_uz' => 'Barcha zamonlarda majhul nisbat',
                'icon' => 'refresh',
                'color' => '#0EA5E9',
            ],
            [
                'slug' => 'reported-speech',
                'name' => 'Reported Speech',
                'name_uz' => 'Ko\'chirma Gap',
                'description' => 'Direct and indirect speech',
                'description_uz' => 'To\'g\'ri va ko\'chirma gap',
                'icon' => 'chat-alt-2',
                'color' => '#F43F5E',
            ],
            [
                'slug' => 'questions',
                'name' => 'Questions',
                'name_uz' => 'Savollar',
                'description' => 'Question formation and types',
                'description_uz' => 'Savol tuzish va turlari',
                'icon' => 'question-mark-circle',
                'color' => '#FBBF24',
            ],
            [
                'slug' => 'sentence-structure',
                'name' => 'Sentence Structure',
                'name_uz' => 'Gap Tuzilishi',
                'description' => 'Word order and sentence patterns',
                'description_uz' => 'So\'z tartibi va gap namunalari',
                'icon' => 'view-list',
                'color' => '#78716C',
            ],
        ];

        foreach ($categories as $index => $category) {
            $children = $category['children'] ?? [];
            unset($category['children']);

            $parent = EnglishGrammarCategory::create(array_merge($category, [
                'id' => Str::uuid(),
                'order_number' => $index + 1,
                'is_active' => true,
            ]));

            foreach ($children as $childIndex => $child) {
                EnglishGrammarCategory::create(array_merge($child, [
                    'id' => Str::uuid(),
                    'parent_id' => $parent->id,
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'order_number' => $childIndex + 1,
                    'is_active' => true,
                ]));
            }
        }
    }
}
