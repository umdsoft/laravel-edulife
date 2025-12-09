<?php

namespace Database\Seeders;

use App\Models\English\EnglishLevel;
use App\Models\English\EnglishUnit;
use App\Models\English\EnglishLesson;
use App\Models\English\EnglishLessonStep;
use App\Models\English\UserLessonProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishA1UnitSeeder extends Seeder
{
    /**
     * Balanced XP rewards by lesson type
     */
    private array $xpRewards = [
        'vocabulary' => 2,
        'grammar' => 3,
        'practice' => 2,
        'conversation' => 3,
        'test' => 5,
    ];

    /**
     * Balanced coin rewards by lesson type
     */
    private array $coinRewards = [
        'vocabulary' => 1,
        'grammar' => 1,
        'practice' => 1,
        'conversation' => 1,
        'test' => 2,
    ];

    public function run(): void
    {
        $level = EnglishLevel::where('code', 'A1')->first();

        if (!$level) {
            $this->command->error('A1 level not found. Please run EnglishLevelSeeder first.');
            return;
        }

        // --- Clean up existing A1 content (including orphans) ---
        $unitIds = EnglishUnit::where('level_id', $level->id)
            ->orWhere('slug', 'like', 'a1-%')
            ->pluck('id');

        $lessonIds = EnglishLesson::whereIn('unit_id', $unitIds)
            ->orWhere('slug', 'like', 'a1-%')
            ->pluck('id');

        if ($lessonIds->isNotEmpty()) {
            UserLessonProgress::whereIn('lesson_id', $lessonIds)->delete();
            EnglishLessonStep::whereIn('lesson_id', $lessonIds)->delete();
            EnglishLesson::whereIn('id', $lessonIds)->delete();
        }

        if ($unitIds->isNotEmpty()) {
            EnglishUnit::whereIn('id', $unitIds)->delete();
        }
        // ---------------------------------------------------------

        $units = $this->getUnitsData();

        foreach ($units as $unitData) {
            $lessons = $unitData['lessons'];
            unset($unitData['lessons']);

            $unit = EnglishUnit::create(
                array_merge($unitData, [
                    'title_uz' => $unitData['title_uz'] ?? $unitData['title'],
                    'description_uz' => $unitData['description'] ?? '',
                    'level_id' => $level->id,
                    'is_active' => true,
                ])
            );

            foreach ($lessons as $lessonData) {
                $lessonType = $lessonData['lesson_type'];
                $content = $lessonData['content'] ?? $this->getDefaultContent($lessonData['slug'], $lessonType);
                unset($lessonData['content']);

                EnglishLesson::create(
                    array_merge($lessonData, [
                        'unit_id' => $unit->id,
                        'title_uz' => $lessonData['title'] . ' (UZ)',
                        'xp_reward' => $this->xpRewards[$lessonType] ?? 2,
                        'coin_reward' => $this->coinRewards[$lessonType] ?? 1,
                        'pass_percentage' => 80,
                        'is_active' => true,
                        'is_free' => $lessonData['is_free'] ?? false,
                        'vocabulary_ids' => [],
                        'grammar_rule_ids' => [],
                        'content' => $content,
                    ])
                );
            }
        }

        $this->command->info('A1 units seeded with balanced XP/Coin economy and lesson content.');
    }

    private function getUnitsData(): array
    {
        return [
            [
                'unit_number' => 1,
                'slug' => 'a1-unit-1-greetings',
                'title' => 'Greetings & Introductions',
                'title_uz' => 'Salomlashish va Tanishuv',
                'description' => 'Learn basic greetings and introductions',
                'estimated_minutes' => 90,
                'xp_reward' => 50,
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a1-u1-l1-hello',
                        'title' => 'Hello & Goodbye',
                        'lesson_type' => 'vocabulary',
                        'is_free' => true,
                        'content' => $this->getHelloGoodbyeContent(),
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a1-u1-l2-name',
                        'title' => 'My Name Is...',
                        'lesson_type' => 'practice',
                        'is_free' => true,
                        'content' => $this->getMyNameContent(),
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a1-u1-l3-meet',
                        'title' => 'Nice to Meet You',
                        'lesson_type' => 'conversation',
                        'content' => $this->getNiceToMeetContent(),
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a1-u1-l4-origin',
                        'title' => 'Where Are You From?',
                        'lesson_type' => 'grammar',
                        'content' => $this->getWhereFromContent(),
                    ],
                    [
                        'lesson_number' => 5,
                        'slug' => 'a1-u1-l5-test',
                        'title' => 'Module Test',
                        'lesson_type' => 'test',
                        'content' => $this->getModuleTestContent(),
                    ],
                ],
            ],
            [
                'unit_number' => 2,
                'slug' => 'a1-unit-2-alphabet',
                'title' => 'Alphabet & Numbers',
                'title_uz' => 'Alifbo va Raqamlar',
                'description' => 'Learn the alphabet and counting',
                'estimated_minutes' => 100,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u2-l1-alphabet-1', 'title' => 'Alphabet A-M', 'lesson_type' => 'vocabulary', 'content' => $this->getAlphabetAMContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u2-l2-alphabet-2', 'title' => 'Alphabet N-Z', 'lesson_type' => 'vocabulary', 'content' => $this->getAlphabetNZContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u2-l3-numbers-1', 'title' => 'Numbers 1-20', 'lesson_type' => 'practice', 'content' => $this->getNumbers1To20Content()],
                    ['lesson_number' => 4, 'slug' => 'a1-u2-l4-numbers-2', 'title' => 'Numbers 21-100', 'lesson_type' => 'practice', 'content' => $this->getNumbers21To100Content()],
                    ['lesson_number' => 5, 'slug' => 'a1-u2-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule2TestContent()],
                ],
            ],
            [
                'unit_number' => 3,
                'slug' => 'a1-unit-3-colors',
                'title' => 'Colors & Shapes',
                'title_uz' => 'Ranglar va Shakllar',
                'description' => 'Learn colors and geometric shapes',
                'estimated_minutes' => 90,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u3-l1-basic-colors', 'title' => 'Basic Colors', 'lesson_type' => 'vocabulary', 'content' => $this->getBasicColorsContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u3-l2-more-colors', 'title' => 'More Colors', 'lesson_type' => 'vocabulary', 'content' => $this->getMoreColorsContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u3-l3-shapes', 'title' => 'Basic Shapes', 'lesson_type' => 'vocabulary', 'content' => $this->getBasicShapesContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u3-l4-descriptions', 'title' => 'Describing Objects', 'lesson_type' => 'grammar', 'content' => $this->getDescribingObjectsContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u3-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule3TestContent()],
                ],
            ],
            [
                'unit_number' => 4,
                'slug' => 'a1-unit-4-family',
                'title' => 'Family & Friends',
                'title_uz' => 'Oila va Do\'stlar',
                'description' => 'Talk about family members and people',
                'estimated_minutes' => 100,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u4-l1-family', 'title' => 'Family Members', 'lesson_type' => 'vocabulary', 'content' => $this->getFamilyMembersContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u4-l2-friends', 'title' => 'My Friends', 'lesson_type' => 'practice', 'content' => $this->getMyFriendsContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u4-l3-people', 'title' => 'Describing People', 'lesson_type' => 'grammar', 'content' => $this->getDescribingPeopleContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u4-l4-possessives', 'title' => 'Possessives', 'lesson_type' => 'grammar', 'content' => $this->getPossessivesContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u4-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule4TestContent()],
                ],
            ],
            [
                'unit_number' => 5,
                'slug' => 'a1-unit-5-time',
                'title' => 'Days & Months',
                'title_uz' => 'Kunlar va Oylar',
                'description' => 'Learn days of week and months',
                'estimated_minutes' => 90,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u5-l1-weekdays', 'title' => 'Days of the Week', 'lesson_type' => 'vocabulary', 'content' => $this->getDaysOfWeekContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u5-l2-months', 'title' => 'Months of the Year', 'lesson_type' => 'vocabulary', 'content' => $this->getMonthsContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u5-l3-seasons', 'title' => 'Seasons', 'lesson_type' => 'practice', 'content' => $this->getSeasonsContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u5-l4-intro-dates', 'title' => 'Dates', 'lesson_type' => 'grammar', 'content' => $this->getDatesContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u5-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule5TestContent()],
                ],
            ],
            [
                'unit_number' => 6,
                'slug' => 'a1-unit-6-routine',
                'title' => 'Time & Daily Routine',
                'title_uz' => 'Vaqt va Kun Tartibi',
                'description' => 'Learn to tell time and describe routine',
                'estimated_minutes' => 110,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u6-l1-time', 'title' => 'Telling Time', 'lesson_type' => 'vocabulary', 'content' => $this->getTellingTimeContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u6-l2-routine', 'title' => 'Daily Activities', 'lesson_type' => 'vocabulary', 'content' => $this->getDailyActivitiesContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u6-l3-present-simple', 'title' => 'Present Simple', 'lesson_type' => 'grammar', 'content' => $this->getPresentSimpleContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u6-l4-frequency', 'title' => 'How Often?', 'lesson_type' => 'grammar', 'content' => $this->getFrequencyContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u6-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule6TestContent()],
                ],
            ],
            [
                'unit_number' => 7,
                'slug' => 'a1-unit-7-food',
                'title' => 'Food & Drinks',
                'title_uz' => 'Ovqat va Ichimliklar',
                'description' => 'Learn food vocabulary and ordering',
                'estimated_minutes' => 100,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u7-l1-food', 'title' => 'Basic Food', 'lesson_type' => 'vocabulary', 'content' => $this->getBasicFoodContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u7-l2-drinks', 'title' => 'Drinks', 'lesson_type' => 'vocabulary', 'content' => $this->getDrinksContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u7-l3-ordering', 'title' => 'Ordering Food', 'lesson_type' => 'conversation', 'content' => $this->getOrderingFoodContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u7-l4-likes', 'title' => 'Likes & Dislikes', 'lesson_type' => 'grammar', 'content' => $this->getLikesDislikesContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u7-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule7TestContent()],
                ],
            ],
            [
                'unit_number' => 8,
                'slug' => 'a1-unit-8-home',
                'title' => 'Home & Furniture',
                'title_uz' => 'Uy va Mebel',
                'description' => 'Describe your home and rooms',
                'estimated_minutes' => 90,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u8-l1-rooms', 'title' => 'Rooms in the House', 'lesson_type' => 'vocabulary', 'content' => $this->getRoomsContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u8-l2-furniture', 'title' => 'Furniture', 'lesson_type' => 'vocabulary', 'content' => $this->getFurnitureContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u8-l3-prep', 'title' => 'Prepositions of Place', 'lesson_type' => 'grammar', 'content' => $this->getPrepositionsContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u8-l4-there-is', 'title' => 'There Is / There Are', 'lesson_type' => 'grammar', 'content' => $this->getThereIsAreContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u8-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule8TestContent()],
                ],
            ],
            [
                'unit_number' => 9,
                'slug' => 'a1-unit-9-shopping',
                'title' => 'Clothes & Shopping',
                'title_uz' => 'Kiyimlar va Savdo',
                'description' => 'Learn clothes vocabulary and shopping phrases',
                'estimated_minutes' => 100,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u9-l1-clothes', 'title' => 'Clothing', 'lesson_type' => 'vocabulary', 'content' => $this->getClothingContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u9-l2-colors-review', 'title' => 'Colors Review', 'lesson_type' => 'vocabulary', 'content' => $this->getColorsReviewContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u9-l3-shopping', 'title' => 'At the Shop', 'lesson_type' => 'conversation', 'content' => $this->getShoppingContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u9-l4-money', 'title' => 'Money & Prices', 'lesson_type' => 'vocabulary', 'content' => $this->getMoneyPricesContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u9-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule9TestContent()],
                ],
            ],
            [
                'unit_number' => 10,
                'slug' => 'a1-unit-10-nature',
                'title' => 'Animals & Nature',
                'title_uz' => 'Hayvonlar va Tabiat',
                'description' => 'Learn animals and nature words',
                'estimated_minutes' => 90,
                'xp_reward' => 50,
                'lessons' => [
                    ['lesson_number' => 1, 'slug' => 'a1-u10-l1-animals', 'title' => 'Common Animals', 'lesson_type' => 'vocabulary', 'content' => $this->getCommonAnimalsContent()],
                    ['lesson_number' => 2, 'slug' => 'a1-u10-l2-pets', 'title' => 'Pets', 'lesson_type' => 'vocabulary', 'content' => $this->getPetsContent()],
                    ['lesson_number' => 3, 'slug' => 'a1-u10-l3-weather', 'title' => 'Weather', 'lesson_type' => 'vocabulary', 'content' => $this->getWeatherContent()],
                    ['lesson_number' => 4, 'slug' => 'a1-u10-l4-nature', 'title' => 'In Nature', 'lesson_type' => 'practice', 'content' => $this->getInNatureContent()],
                    ['lesson_number' => 5, 'slug' => 'a1-u10-l5-test', 'title' => 'Module Test', 'lesson_type' => 'test', 'content' => $this->getModule10TestContent()],
                ],
            ],
        ];
    }

    // ==================== LESSON CONTENT ====================

    private function getHelloGoodbyeContent(): array
    {
        return [
            'totalSteps' => 16,
            'words' => [
                [
                    'english' => 'Hello',
                    'uzbek' => 'Salom',
                    'pronunciation' => '/həˈloʊ/',
                    'emoji' => '👋',
                    'example' => 'Hello! How are you?',
                    'exampleTranslation' => 'Salom! Qalaysiz?',
                ],
                [
                    'english' => 'Hi',
                    'uzbek' => 'Salom (norasmiy)',
                    'pronunciation' => '/haɪ/',
                    'emoji' => '🙋',
                    'example' => 'Hi there!',
                    'exampleTranslation' => 'Salom!',
                ],
                [
                    'english' => 'Good morning',
                    'uzbek' => 'Hayrli tong',
                    'pronunciation' => '/ɡʊd ˈmɔːrnɪŋ/',
                    'emoji' => '🌅',
                    'example' => 'Good morning, teacher!',
                    'exampleTranslation' => 'Hayrli tong, o\'qituvchi!',
                ],
                [
                    'english' => 'Good afternoon',
                    'uzbek' => 'Hayrli kun',
                    'pronunciation' => '/ɡʊd ˌæftərˈnuːn/',
                    'emoji' => '☀️',
                    'example' => 'Good afternoon, everyone!',
                    'exampleTranslation' => 'Hayrli kun, hamma!',
                ],
                [
                    'english' => 'Good evening',
                    'uzbek' => 'Hayrli kech',
                    'pronunciation' => '/ɡʊd ˈiːvnɪŋ/',
                    'emoji' => '🌆',
                    'example' => 'Good evening! Welcome!',
                    'exampleTranslation' => 'Hayrli kech! Xush kelibsiz!',
                ],
                [
                    'english' => 'Good night',
                    'uzbek' => 'Hayrli tun',
                    'pronunciation' => '/ɡʊd naɪt/',
                    'emoji' => '🌙',
                    'example' => 'Good night, sleep well!',
                    'exampleTranslation' => 'Hayrli tun, yaxshi uxlang!',
                ],
                [
                    'english' => 'Goodbye',
                    'uzbek' => 'Xayr',
                    'pronunciation' => '/ɡʊdˈbaɪ/',
                    'emoji' => '👋',
                    'example' => 'Goodbye! See you tomorrow!',
                    'exampleTranslation' => 'Xayr! Ertaga ko\'rishguncha!',
                ],
                [
                    'english' => 'See you',
                    'uzbek' => 'Ko\'rishguncha',
                    'pronunciation' => '/siː juː/',
                    'emoji' => '✌️',
                    'example' => 'See you later!',
                    'exampleTranslation' => 'Keyinroq ko\'rishguncha!',
                ],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - O'ZBEK TILIDA
            'explanation' => [
                [
                    'title' => '1. Salomlashish (Greetings)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida salomlashishning ikki turi mavjud: <strong>Rasmiy (Formal)</strong> va <strong>Norasmiy (Informal)</strong>. Vaziyatga qarab to\'g\'ri so\'zni tanlash muhim.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Salomlashish Turlari',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Qachon ishlatiladi?'],
                            'rows' => [
                                ['<b>Hello</b>', 'Salom', 'Har qanday vaziyatda (Rasmiy/Norasmiy)'],
                                ['<b>Hi</b>', 'Salom', 'Faqat do\'stlar va yaqinlar bilan (Norasmiy)'],
                                ['<b>Good morning</b>', 'Xayrli tong', 'Ertalabdan tushgacha (12:00 gacha)'],
                                ['<b>Good afternoon</b>', 'Xayrli kun', 'Tushdan keyin (12:00 dan 18:00 gacha)'],
                                ['<b>Good evening</b>', 'Xayrli kech', 'Kechqurun (18:00 dan keyin)'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 <strong>Good night</strong> (Xayrli tun) salomlashish uchun EMAS, balki xayrlashish yoki uxlashdan oldin aytiladi.'
                        ]
                    ]
                ],
                [
                    'title' => '2. Xayrlashish (Farewells)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Suhbatni tugatgandan so\'ng quyidagi iboralar bilan xayrlashishingiz mumkin:'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Xayrlashish Iboralari',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Izoh'],
                            'rows' => [
                                ['<b>Goodbye</b>', 'Xayr', 'Rasmiy va doimiy'],
                                ['<b>Bye</b>', 'Xayr', 'Qisqa va norasmiy'],
                                ['<b>See you</b>', 'Ko\'rishguncha', 'Keyinroq ko\'rishadigan bo\'lsangiz'],
                                ['<b>Have a nice day</b>', 'Kuningiz xayrli o\'tsin', 'Suhbat oxirida tilak sifatida'],
                            ]
                        ]
                    ]
                ]
            ],
            // MASHQLAR - O'ZBEK TILIDA
            'exercises' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Ertalab kimnidir ko\'rganingizda nima deysiz?',
                    'questionAudio' => 'How do you greet someone in the morning?',
                    'options' => ['Good night', 'Good evening', 'Good morning', 'Goodbye'],
                    'correctAnswer' => 2,
                    'explanation' => 'Ertalab salomlashish uchun "Good morning" (Hayrli tong) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Goodbye" so\'zining o\'zbekcha tarjimasi qanday?',
                    'options' => ['Salom', 'Xayr', 'Rahmat', 'Marhamat'],
                    'correctAnswer' => 1,
                    'explanation' => '"Goodbye" so\'zi o\'zbekchada "Xayr" degan ma\'noni anglatadi.',
                ],
            ],
            // YAKUNIY TEST - O'ZBEK TILIDA
            'quiz' => [
                [
                    'type' => 'multiple_choice',
                    'question' => '"Hayrli tong" iborasini ingliz tiliga tarjima qiling:',
                    'options' => ['Good night', 'Good morning', 'Good evening', 'Hello'],
                    'correctAnswer' => 1,
                    'explanation' => '"Hayrli tong" ingliz tilida "Good morning" deb aytiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kechqurun soat 8 da kimnidir ko\'rsangiz nima deysiz?',
                    'options' => ['Good morning', 'Good afternoon', 'Good evening', 'Good night'],
                    'correctAnswer' => 2,
                    'explanation' => 'Kechqurun (oqshom paytida) "Good evening" (Hayrli kech) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Salom" so\'zini ingliz tilida qanday aytasiz?',
                    'options' => ['Goodbye', 'See you', 'Hello', 'Good night'],
                    'correctAnswer' => 2,
                    'explanation' => '"Salom" ingliz tilida "Hello" yoki "Hi" deb aytiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Hello" so\'zini norasmiy shaklda qanday aytish mumkin?',
                    'options' => ['Good morning', 'Hi', 'Goodbye', 'See you'],
                    'correctAnswer' => 1,
                    'explanation' => '"Hi" - bu "Hello"ning norasmiy shakli.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kechasi uxlashdan oldin nima deysiz?',
                    'options' => ['Good morning', 'Good afternoon', 'Good evening', 'Good night'],
                    'correctAnswer' => 3,
                    'explanation' => 'Uxlashdan oldin "Good night" (Hayrli tun) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Quyidagi gapni to\'ldiring: "_____, see you tomorrow!"',
                    'questionTranslation' => 'Quyidagi gapni to\'ldiring: "_____, ertaga ko\'rishguncha!"',
                    'options' => ['Hello', 'Good morning', 'Goodbye', 'Good night'],
                    'correctAnswer' => 2,
                    'explanation' => 'Xayrlashishda "Goodbye" (Xayr) so\'zi ishlatiladi.',
                ],
            ],
        ];
    }

    private function getMyNameContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                [
                    'english' => 'My name is...',
                    'uzbek' => 'Mening ismim...',
                    'pronunciation' => '/maɪ neɪm ɪz/',
                    'emoji' => '🏷️',
                    'example' => 'My name is John.',
                    'exampleTranslation' => 'Mening ismim John.',
                ],
                [
                    'english' => 'What is your name?',
                    'uzbek' => 'Ismingiz nima?',
                    'pronunciation' => '/wɒt ɪz jɔːr neɪm/',
                    'emoji' => '❓',
                    'example' => 'Hello! What is your name?',
                    'exampleTranslation' => 'Salom! Ismingiz nima?',
                ],
                [
                    'english' => 'I am',
                    'uzbek' => 'Men',
                    'pronunciation' => '/aɪ æm/',
                    'emoji' => '👤',
                    'example' => 'I am a student.',
                    'exampleTranslation' => 'Men talabaman.',
                ],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - O'ZBEK TILIDA
            'explanation' => [
                [
                    'title' => '1. Ism Aytish (Introduction)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida o\'zingizni tanishtirishning asosan ikki usuli bor: <strong>"My name is..."</strong> va <strong>"I am..."</strong>.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Tanishtirish Usullari',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Izoh'],
                            'rows' => [
                                ['<b>My name is</b> John.', 'Mening ismim John.', 'Eng keng tarqalgan va rasmiy usul.'],
                                ['<b>I am</b> John.', 'Men Johnman.', 'Biroz norasmiy, oddiy so\'zlashuvda.'],
                                ['<b>I\'m</b> John.', 'Men Johnman.', 'Qisqartma shakl (I am -> I\'m).'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 <strong>My name is</strong> ko\'pincha rasmiy tanishuvlarda (ish, o\'qish) ishlatiladi. <strong>I\'m</strong> esa do\'stona va kundalik vaziyatlarda qulay.'
                        ]
                    ]
                ],
                [
                    'title' => '2. Ism So\'rash (Asking Name)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Boshqa odamning ismini so\'rash uchun quyidagi savol ishlatiladi:'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Savol va Javob',
                            'items' => [
                                ['en' => 'What is your name?', 'uz' => 'Ismingiz nima?'],
                                ['en' => 'My name is Anna.', 'uz' => 'Mening ismim Anna.'],
                            ]
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Tez-tez uchraydigan xatolar',
                            'items' => [
                                ['bad' => 'My name John.', 'good' => 'My name <b>is</b> John.'],
                                ['bad' => 'Your name what?', 'good' => '<b>What is</b> your name?'],
                            ]
                        ]
                    ]
                ]
            ],
            // MASHQLAR - O'ZBEK TILIDA
            'exercises' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "My _____ is John."',
                    'questionTranslation' => 'Mening _____ John.',
                    'options' => ['am', 'name', 'you', 'hello'],
                    'correctAnswer' => 1,
                    'explanation' => 'O\'zimizni tanishtirish uchun "My name is..." (Mening ismim...) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kimdir ismini so\'ramoqchi bo\'lsangiz nima deysiz?',
                    'options' => ['What is your name?', 'My name is?', 'Hello name?', 'You name?'],
                    'correctAnswer' => 0,
                    'explanation' => 'Ismni so\'rash uchun "What is your name?" (Ismingiz nima?) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "I _____ a student."',
                    'questionTranslation' => 'Men talaba_____.',
                    'options' => ['is', 'are', 'am', 'be'],
                    'correctAnswer' => 2,
                    'explanation' => '"I" bilan "am" ishlatiladi - "I am a student" (Men talabaman).',
                ],
            ],
            // YAKUNIY TEST - O'ZBEK TILIDA
            'quiz' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Qaysi gap to\'g\'ri?',
                    'options' => ['My name John is.', 'Name my is John.', 'My name is John.', 'Is my name John.'],
                    'correctAnswer' => 2,
                    'explanation' => 'To\'g\'ri tartib: "My name is John." (Mening ismim John.)',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Mening ismim..." iborasini ingliz tiliga tarjima qiling:',
                    'options' => ['My name is...', 'Your name is...', 'His name is...', 'Hello'],
                    'correctAnswer' => 0,
                    'explanation' => '"Mening ismim..." ingliz tilida "My name is..." deb aytiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "_____ is your name?"',
                    'questionTranslation' => 'Ismingiz _____?',
                    'options' => ['How', 'Where', 'What', 'Who'],
                    'correctAnswer' => 2,
                    'explanation' => '"Ismingiz nima?" - "What is your name?" deb so\'raladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kimdir ismingizni so\'raganda nima deb javob berasiz?',
                    'options' => ['What is your name?', 'Nice to meet you', 'My name is...', 'Goodbye'],
                    'correctAnswer' => 2,
                    'explanation' => 'Ismingizni aytish uchun "My name is..." (Mening ismim...) deyiladi.',
                ],
            ],
        ];
    }

    private function getNiceToMeetContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                [
                    'english' => 'Nice to meet you',
                    'uzbek' => 'Tanishganimdan xursandman',
                    'pronunciation' => '/naɪs tuː miːt juː/',
                    'emoji' => '🤝',
                    'example' => 'Hello! Nice to meet you!',
                    'exampleTranslation' => 'Salom! Tanishganimdan xursandman!',
                ],
                [
                    'english' => 'Nice to meet you too',
                    'uzbek' => 'Men ham tanishganimdan xursandman',
                    'pronunciation' => '/naɪs tuː miːt juː tuː/',
                    'emoji' => '🤝',
                    'example' => 'Nice to meet you too!',
                    'exampleTranslation' => 'Men ham tanishganimdan xursandman!',
                ],
                [
                    'english' => 'How are you?',
                    'uzbek' => 'Qalaysiz?',
                    'pronunciation' => '/haʊ ɑːr juː/',
                    'emoji' => '💬',
                    'example' => 'Hello! How are you?',
                    'exampleTranslation' => 'Salom! Qalaysiz?',
                ],
                [
                    'english' => 'I am fine',
                    'uzbek' => 'Men yaxshiman',
                    'pronunciation' => '/aɪ æm faɪn/',
                    'emoji' => '😊',
                    'example' => 'I am fine, thank you!',
                    'exampleTranslation' => 'Men yaxshiman, rahmat!',
                ],
                [
                    'english' => 'Thank you',
                    'uzbek' => 'Rahmat',
                    'pronunciation' => '/θæŋk juː/',
                    'emoji' => '🙏',
                    'example' => 'Thank you very much!',
                    'exampleTranslation' => 'Katta rahmat!',
                ],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - O'ZBEK TILIDA
            'explanation' => [
                [
                    'title' => '1. Hol-ahvol So\'rash (How are you?)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida birovning ahvolini so\'rash uchun eng ko\'p <strong>"How are you?"</strong> (Qalaysiz?) iborasi ishlatiladi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Javob Berish Usullari',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Ma\'nosi'],
                            'rows' => [
                                ['<b>I am fine.</b>', 'Men yaxshiman.', 'Eng keng tarqalgan ijobiy javob.'],
                                ['<b>I am good.</b>', 'Yaxshiman.', 'Kundalik so\'zlashuvda.'],
                                ['<b>I am great!</b>', 'Ajoyibman!', 'Ahvolingiz juda yaxshi bo\'lsa.'],
                                ['<b>I am OK.</b>', 'Yomon emas.', 'O\'rtacha ahvolda.'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Javob bergandan keyin <strong>"Thank you, and you?"</strong> (Rahmat, o\'zingizchi?) deb qaytarib so\'rash odobli hisoblanadi.'
                        ]
                    ]
                ],
                [
                    'title' => '2. Tanishuv (Nice to meet you)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>"Nice to meet you"</strong> (Tanishganimdan xursandman) iborasi faqat kimdir bilan <strong>birinchi marta</strong> ko\'rishganda aytiladi.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Suhbat Namunasi',
                            'items' => [
                                ['en' => 'A: Nice to meet you!', 'uz' => 'Tanishganimdan xursandman!'],
                                ['en' => 'B: Nice to meet you too!', 'uz' => 'Men ham tanishganimdan xursandman!'],
                            ]
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Ehtiyot bo\'ling!',
                            'items' => [
                                ['bad' => 'Nice to see you (birinchi marta).', 'good' => 'Nice to <b>meet</b> you (birinchi marta).'],
                                ['bad' => 'I am fine, and you? (thank you yo\'q)', 'good' => 'I am fine, <b>thank you</b>. And you?'],
                            ]
                        ]
                    ]
                ]
            ],
            'dialogue' => [
                ['speaker' => 'A', 'text' => 'Hello! My name is Anna.', 'translation' => 'Salom! Mening ismim Anna.'],
                ['speaker' => 'B', 'text' => 'Hi Anna! I\'m Bob. Nice to meet you!', 'translation' => 'Salom Anna! Men Bob. Tanishganimdan xursandman!'],
                ['speaker' => 'A', 'text' => 'Nice to meet you too! How are you?', 'translation' => 'Men ham tanishganimdan xursandman! Qalaysiz?'],
                ['speaker' => 'B', 'text' => 'I am fine, thank you! And you?', 'translation' => 'Men yaxshiman, rahmat! Siz-chi?'],
                ['speaker' => 'A', 'text' => 'I am fine too. Goodbye!', 'translation' => 'Men ham yaxshiman. Xayr!'],
            ],
            // ROL O'YNASH - O'ZBEK TILIDA
            'rolePlay' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Anna: "Hello! My name is Anna." Nima deb javob berasiz?',
                    'questionTranslation' => 'Anna: "Salom! Mening ismim Anna." deydi.',
                    'options' => ['Goodbye!', 'Hi Anna! I\'m Bob.', 'Good night!', 'See you later!'],
                    'correctAnswer' => 1,
                    'explanation' => 'Kimdir o\'zini tanishtirsa, siz ham o\'zingizni tanishtiring.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kimdir "Nice to meet you!" desa, nima deb javob berasiz?',
                    'options' => ['Goodbye!', 'Thank you!', 'Nice to meet you too!', 'Hello!'],
                    'correctAnswer' => 2,
                    'explanation' => '"Nice to meet you too!" (Men ham tanishganimdan xursandman!) deb javob beramiz.',
                ],
            ],
            // YAKUNIY TEST - O'ZBEK TILIDA
            'quiz' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Yangi odam bilan tanishganda nima deysiz?',
                    'options' => ['Goodbye!', 'Good night!', 'Nice to meet you!', 'I am fine'],
                    'correctAnswer' => 2,
                    'explanation' => 'Tanishganda "Nice to meet you!" (Tanishganimdan xursandman!) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Nice to meet you" iborasining o\'zbekcha tarjimasi qanday?',
                    'options' => ['Xayr', 'Tanishganimdan xursandman', 'Salom', 'Rahmat'],
                    'correctAnswer' => 1,
                    'explanation' => '"Nice to meet you" - "Tanishganimdan xursandman" degan ma\'noni anglatadi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Qalaysiz?" savolini ingliz tilida qanday aytasiz?',
                    'options' => ['What is your name?', 'How are you?', 'Where are you from?', 'Nice to meet you'],
                    'correctAnswer' => 1,
                    'explanation' => '"Qalaysiz?" ingliz tilida "How are you?" deb aytiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Rahmat" so\'zini ingliz tilida qanday aytasiz?',
                    'options' => ['Please', 'Sorry', 'Thank you', 'Goodbye'],
                    'correctAnswer' => 2,
                    'explanation' => '"Rahmat" ingliz tilida "Thank you" deb aytiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"I am fine" iborasining o\'zbekcha tarjimasi qanday?',
                    'options' => ['Men charchadim', 'Men yaxshiman', 'Men ketaman', 'Men kelaman'],
                    'correctAnswer' => 1,
                    'explanation' => '"I am fine" - "Men yaxshiman" degan ma\'noni anglatadi.',
                ],
            ],
        ];
    }

    private function getWhereFromContent(): array
    {
        return [
            'lessonId' => 'a1-m1-l4',
            'title' => 'Where Are You From?',
            'titleUz' => 'Siz qayerdansiz?',
            'type' => 'grammar',
            'level' => 'A1',
            'totalSteps' => 14,
            'words' => [
                [
                    'english' => 'Where are you from?',
                    'uzbek' => 'Siz qayerdansiz?',
                    'pronunciation' => '/weər ɑːr juː frɒm/',
                    'emoji' => '🌍',
                    'example' => 'Where are you from?',
                    'exampleTranslation' => 'Siz qayerdansiz?',
                ],
                [
                    'english' => 'I am from...',
                    'uzbek' => 'Men ...danman',
                    'pronunciation' => '/aɪ æm frɒm/',
                    'emoji' => '📍',
                    'example' => 'I am from Uzbekistan.',
                    'exampleTranslation' => 'Men O\'zbekistondanman.',
                ],
                [
                    'english' => 'country',
                    'uzbek' => 'mamlakat',
                    'pronunciation' => '/ˈkʌntri/',
                    'emoji' => '🏳️',
                    'example' => 'Uzbekistan is a beautiful country.',
                    'exampleTranslation' => 'O\'zbekiston chiroyli mamlakat.',
                ],
                [
                    'english' => 'city',
                    'uzbek' => 'shahar',
                    'pronunciation' => '/ˈsɪti/',
                    'emoji' => '🏙️',
                    'example' => 'Tashkent is a big city.',
                    'exampleTranslation' => 'Toshkent katta shahar.',
                ],
            ],
            'pages' => [
                [
                    'pageId' => 1,
                    'type' => 'grammar_intro',
                    'title' => 'TO BE fe\'li',
                    'titleUz' => 'Bo\'lmoq fe\'li',
                    'content' => [
                        'intro' => 'Ingliz tilida **bo\'lmoq** fe\'li 3 xil shaklda keladi: **AM**, **IS**, **ARE**. Bu fe\'l **eng muhim** va **eng ko\'p ishlatiladigan** fe\'l hisoblanadi!',
                        'importance' => '⭐⭐⭐⭐⭐',
                        'importanceText' => 'Bu qoidani yaxshi o\'rganing - **har bir gap**da kerak bo\'ladi!',
                        'table' => [
                            'title' => '📊 TO BE Fe\'li',
                            'headers' => ['Olmosh', 'Fe\'l', 'Qisqa shakl', 'O\'zbekcha'],
                            'rows' => [
                                ['I (Men)', '**AM**', "I'm", 'Men ...man'],
                                ['You (Sen/Siz)', '**ARE**', "You're", 'Sen/Siz ...san/siz'],
                                ['He (U - erkak)', '**IS**', "He's", 'U ...'],
                                ['She (U - ayol)', '**IS**', "She's", 'U ...'],
                                ['It (U - narsa)', '**IS**', "It's", 'Bu/U ...'],
                                ['We (Biz)', '**ARE**', "We're", 'Biz ...miz'],
                                ['They (Ular)', '**ARE**', "They're", 'Ular ...']
                            ],
                            'highlightColumn' => 1
                        ],
                        'memoryTip' => 'Eslab qolish uchun: **I** = **AM** (yolg\'iz), **HE/SHE/IT** = **IS** (bitta), **YOU/WE/THEY** = **ARE** (ko\'p)'
                    ]
                ],
                [
                    'pageId' => 2,
                    'type' => 'grammar_examples',
                    'title' => 'Misollar',
                    'titleUz' => 'Hayotiy misollar',
                    'content' => [
                        'intro' => 'Har bir olmosh bilan **to\'g\'ri fe\'l**ni ishlatishni mashq qilamiz:',
                        'exampleGroups' => [
                            [
                                'title' => 'I + AM',
                                'emoji' => '👤',
                                'examples' => [
                                    ['english' => 'I **am** a student.', 'uzbek' => 'Men talabaman.', 'context' => 'Kasbingizni aytganda'],
                                    ['english' => 'I **am** from Tashkent.', 'uzbek' => 'Men Toshkentdanman.', 'context' => 'Qayerdan ekaningizni aytganda'],
                                    ['english' => 'I **am** 20 years old.', 'uzbek' => 'Men 20 yoshdaman.', 'context' => 'Yoshingizni aytganda'],
                                    ['english' => 'I **am** happy.', 'uzbek' => 'Men xursandman.', 'context' => 'Kayfiyatingizni aytganda'],
                                    ['english' => 'I **am** at home.', 'uzbek' => 'Men uydaman.', 'context' => 'Qayerda ekaningizni aytganda']
                                ]
                            ],
                            [
                                'title' => 'HE/SHE/IT + IS',
                                'emoji' => '👥',
                                'examples' => [
                                    ['english' => '**He is** a doctor.', 'uzbek' => 'U shifokor.', 'context' => 'Erkak kishi haqida'],
                                    ['english' => '**She is** a teacher.', 'uzbek' => 'U o\'qituvchi.', 'context' => 'Ayol kishi haqida'],
                                    ['english' => '**It is** a book.', 'uzbek' => 'Bu kitob.', 'context' => 'Narsa haqida'],
                                    ['english' => '**He is** from Bukhara.', 'uzbek' => 'U Buxorodan.', 'context' => 'Erkak qayerdan'],
                                    ['english' => '**She is** my sister.', 'uzbek' => 'U mening singlim.', 'context' => 'Oila haqida']
                                ]
                            ],
                            [
                                'title' => 'YOU/WE/THEY + ARE',
                                'emoji' => '👨‍👩‍👧‍👦',
                                'examples' => [
                                    ['english' => '**You are** my friend.', 'uzbek' => 'Sen mening do\'stimsan.', 'context' => 'Bitta odamga'],
                                    ['english' => '**We are** students.', 'uzbek' => 'Biz talabamiz.', 'context' => 'O\'zimiz haqida'],
                                    ['english' => '**They are** from Samarkand.', 'uzbek' => 'Ular Samarqanddan.', 'context' => 'Boshqalar haqida'],
                                    ['english' => '**You are** very smart.', 'uzbek' => 'Siz juda aqllisiz.', 'context' => 'Hurmat bilan'],
                                    ['english' => '**They are** engineers.', 'uzbek' => 'Ular muhandislar.', 'context' => 'Ularning kasbi']
                                ]
                            ]
                        ],
                        'tip' => 'Ingliz tilida **fe\'lsiz gap bo\'lmaydi**! O\'zbek tilida "Men talaba" desak, ingliz tilida **"I am a student"** deyishimiz kerak.'
                    ]
                ],
                [
                    'pageId' => 3,
                    'type' => 'grammar_rule',
                    'title' => 'FROM - Qayerdan?',
                    'titleUz' => 'Joy haqida gapirish',
                    'explanation' => [
                        'intro' => '**FROM** so\'zi **...dan** degan ma\'noni anglatadi. Qayerdan ekaningizni aytish uchun ishlatiladi.',
                        'formula' => [
                            'pattern' => 'OLMOSH + AM/IS/ARE + FROM + JOY NOMI',
                            'patternUz' => 'Men/U/Ular + ...man/...dir + ...dan',
                            'visualFormula' => [
                                ['text' => 'I', 'color' => 'blue'],
                                ['text' => '+', 'color' => 'gray'],
                                ['text' => 'am', 'color' => 'green'],
                                ['text' => '+', 'color' => 'gray'],
                                ['text' => 'from', 'color' => 'purple'],
                                ['text' => '+', 'color' => 'gray'],
                                ['text' => 'Tashkent', 'color' => 'orange']
                            ],
                            'examples' => [
                                ['english' => 'I **am from** Uzbekistan.', 'uzbek' => 'Men O\'zbekistondanman.', 'flag' => '🇺🇿'],
                                ['english' => 'She **is from** Russia.', 'uzbek' => 'U Rossiyadan.', 'flag' => '🇷🇺'],
                                ['english' => 'They **are from** Korea.', 'uzbek' => 'Ular Koreyadanlik.', 'flag' => '🇰🇷'],
                                ['english' => 'We **are from** Fergana.', 'uzbek' => 'Biz Farg\'onadanmiz.', 'flag' => '📍'],
                                ['english' => 'He **is from** Namangan.', 'uzbek' => 'U Namangandan.', 'flag' => '📍']
                            ]
                        ],
                        'countries' => [
                            ['english' => 'Uzbekistan', 'uzbek' => 'O\'zbekiston', 'flag' => '🇺🇿'],
                            ['english' => 'Russia', 'uzbek' => 'Rossiya', 'flag' => '🇷🇺'],
                            ['english' => 'USA', 'uzbek' => 'AQSH', 'flag' => '🇺🇸'],
                            ['english' => 'Turkey', 'uzbek' => 'Turkiya', 'flag' => '🇹🇷'],
                            ['english' => 'Korea', 'uzbek' => 'Koreya', 'flag' => '🇰🇷'],
                            ['english' => 'China', 'uzbek' => 'Xitoy', 'flag' => '🇨🇳'],
                            ['english' => 'Germany', 'uzbek' => 'Germaniya', 'flag' => '🇩🇪'],
                            ['english' => 'England', 'uzbek' => 'Angliya', 'flag' => '🇬🇧'],
                            ['english' => 'France', 'uzbek' => 'Fransiya', 'flag' => '🇫🇷'],
                            ['english' => 'Japan', 'uzbek' => 'Yaponiya', 'flag' => '🇯🇵']
                        ],
                        'cities' => [
                            ['english' => 'Tashkent', 'uzbek' => 'Toshkent'],
                            ['english' => 'Samarkand', 'uzbek' => 'Samarqand'],
                            ['english' => 'Bukhara', 'uzbek' => 'Buxoro'],
                            ['english' => 'Fergana', 'uzbek' => 'Farg\'ona'],
                            ['english' => 'Namangan', 'uzbek' => 'Namangan'],
                            ['english' => 'Andijan', 'uzbek' => 'Andijon'],
                            ['english' => 'Khiva', 'uzbek' => 'Xiva'],
                            ['english' => 'Nukus', 'uzbek' => 'Nukus'],
                            ['english' => 'Karshi', 'uzbek' => 'Qarshi'],
                            ['english' => 'Urgench', 'uzbek' => 'Urganch']
                        ],
                        'warning' => 'Shahar va mamlakat nomlari oldida **THE** ishlatilmaydi! **He is from the Tashkent** ❌ NOTO\'G\'RI!'
                    ]
                ],
                [
                    'pageId' => 4,
                    'type' => 'grammar_intro', // Using simple intro for Questions page as it fits
                    'title' => 'Savol tuzish',
                    'titleUz' => 'Savol qanday tuziladi?',
                    'content' => [
                        'intro' => 'Savol tuzish uchun **fe\'lni oldinga olamiz**!\n\n**ARE** oldinga chiqadi → savol tayyor!\n\n**Darak gap**: You **are** from Tashkent.\n**Savol gap**: **Are** you from Tashkent?',
                        'table' => [
                            'title' => 'WHERE (Qayerdan?) savollari',
                            'headers' => ['Savol', 'Javob'],
                            'rows' => [
                                ['**Where are** you from?', 'I am from Uzbekistan.'],
                                ['**Where is** he from?', 'He is from Tashkent.'],
                                ['**Where is** she from?', 'She is from Samarkand.'],
                                ['**Where are** they from?', 'They are from Bukhara.']
                            ]
                        ],
                        'tip' => 'Qisqa javob berish uchun: **Yes, I am.** / **No, I am not.** (To\'liq javob berish shart emas!)'
                    ]
                ],
                [
                    'pageId' => 5,
                    'type' => 'grammar_intro', // Negative form
                    'title' => 'Inkor gap',
                    'titleUz' => 'Yo\'q degan ma\'no',
                    'content' => [
                        'intro' => 'Inkor qilish uchun fe\'ldan keyin **NOT** qo\'shamiz.\n\nFormala: OLMOSH + AM/IS/ARE + **NOT** + ...',
                        'table' => [
                            'title' => 'To\'liq va qisqa inkor shakllar',
                            'headers' => ['To\'liq shakl', 'Qisqa shakl 1', 'Qisqa shakl 2', 'O\'zbekcha'],
                            'rows' => [
                                ['I am not', "I'm not", '❌ (yo\'q)', 'Men ...emasman'],
                                ['He is not', "He isn't", "He's not", 'U ...emas'],
                                ['She is not', "She isn't", "She's not", 'U ...emas'],
                                ['It is not', "It isn't", "It's not", 'Bu ...emas'],
                                ['You are not', "You aren't", "You're not", '...emassan/emassiz'],
                                ['We are not', "We aren't", "We're not", 'Biz ...emasmiz'],
                                ['They are not', "They aren't", "They're not", 'Ular ...emas']
                            ]
                        ],
                        'memoryTip' => '**AMNT** degan shakl YO\'Q! ❌ **I amn\'t** - NOTO\'G\'RI! ✅ **I\'m not** - TO\'G\'RI!'
                    ]
                ],
                [
                    'pageId' => 6,
                    'type' => 'grammar_intro', // Articles
                    'title' => 'A va AN artikllari',
                    'titleUz' => 'A va AN qachon ishlatiladi?',
                    'content' => [
                        'intro' => '**A** va **AN** - bitta narsa yoki kasb aytganda ishlatiladi. O\'zbek tilida bunday so\'z yo\'q!\n\n**A** - **undosh tovush** oldida (B, C, D...)\n**AN** - **unli tovush** oldida (A, E, I, O, U)',
                        'table' => [
                            'title' => 'Misollar',
                            'headers' => ['Artikl', 'Misol', 'Izoh'],
                            'rows' => [
                                ['**a**', '**a** student', 'S - undosh'],
                                ['**a**', '**a** doctor', 'D - undosh'],
                                ['**an**', '**an** apple', 'A - unli'],
                                ['**an**', '**an** engineer', 'E - unli'],
                                ['**an**', '**an** hour', 'H eshitilmaydi (ou) -> unli!']
                            ]
                        ],
                        'tip' => 'Eslab qoling: **TOVUSH**ga qarang, **HARF**ga emas! **A-E-I-O-U** tovushlari = **AN**, qolganlari = **A**'
                    ]
                ],
                [
                    'pageId' => 7,
                    'type' => 'common_mistakes',
                    'title' => 'Ko\'p uchraydigan xatolar',
                    'titleUz' => 'Bu xatolarni qilmang!',
                    'content' => [
                        'mistakes' => [
                            [
                                'id' => 1,
                                'wrong' => 'I from Uzbekistan.',
                                'correct' => 'I **am** from Uzbekistan.',
                                'explanation' => '**AM** fe\'li tushib qolgan! Ingliz tilida **fe\'lsiz gap bo\'lmaydi**.',
                                'tip' => 'Har doim: I + **AM** + ...'
                            ],
                            [
                                'id' => 2,
                                'wrong' => 'She **are** a teacher.',
                                'correct' => 'She **is** a teacher.',
                                'explanation' => '**She** bilan faqat **IS** ishlatiladi!',
                                'tip' => 'He/She/It = **IS**'
                            ],
                            [
                                'id' => 3,
                                'wrong' => 'I am student.',
                                'correct' => 'I am **a** student.',
                                'explanation' => 'Kasb aytganda **A** yoki **AN** qo\'shish kerak!',
                                'tip' => 'a/an + kasb'
                            ],
                            [
                                'id' => 4,
                                'wrong' => 'He is from **the** Tashkent.',
                                'correct' => 'He is from Tashkent.',
                                'explanation' => 'Shahar va mamlakat nomlari oldida **THE** ishlatilmaydi!',
                                'tip' => 'from + city/country (THE yo\'q)'
                            ],
                            [
                                'id' => 5,
                                'wrong' => 'Where you from?',
                                'correct' => 'Where **are** you from?',
                                'explanation' => 'Savolda **fe\'l** bo\'lishi shart!',
                                'tip' => 'Where + **ARE** + you + from?'
                            ],
                            [
                                'id' => 6,
                                'wrong' => 'I **amn\'t** a doctor.',
                                'correct' => 'I **am not** a doctor. / I\'**m not** a doctor.',
                                'explanation' => '**AMNT** degan shakl yo\'q!',
                                'tip' => 'I am not = I\'m not (faqat shu 2 ta!)'
                            ],
                            [
                                'id' => 7,
                                'wrong' => 'He is **a** engineer.',
                                'correct' => 'He is **an** engineer.',
                                'explanation' => '**Engineer** so\'zi **E** (unli) bilan boshlanadi!',
                                'tip' => 'E = unli = AN'
                            ],
                            [
                                'id' => 8,
                                'wrong' => 'They **is** students.',
                                'correct' => 'They **are** students.',
                                'explanation' => '**They** bilan faqat **ARE** ishlatiladi!',
                                'tip' => 'They = ko\'plik = ARE'
                            ]
                        ]
                    ]
                ],
                [
                    'pageId' => 8,
                    'type' => 'dialogue',
                    'title' => 'Dialog',
                    'titleUz' => 'Suhbat',
                    'content' => [
                        'dialogue' => [
                            'title' => '🎭 Yangi tanishish',
                            'context' => 'Ali va Sara universitetda birinchi marta uchrashmoqda.',
                            'lines' => [
                                ['speaker' => 'Ali', 'english' => 'Hello! My name is Ali. What is your name?', 'uzbek' => 'Salom! Mening ismim Ali. Ismingiz nima?'],
                                ['speaker' => 'Sara', 'english' => 'Hi Ali! I am Sara. Nice to meet you!', 'uzbek' => 'Salom Ali! Men Sara. Tanishganimdan xursandman!'],
                                ['speaker' => 'Ali', 'english' => 'Nice to meet you too! Where are you from?', 'uzbek' => 'Men ham tanishganimdan xursandman! Siz qayerdansiz?'],
                                ['speaker' => 'Sara', 'english' => 'I am from Samarkand. And you? Where are you from?', 'uzbek' => 'Men Samarqanddanman. Siz-chi? Siz qayerdansiz?'],
                                ['speaker' => 'Ali', 'english' => 'I am from Tashkent. Are you a student?', 'uzbek' => 'Men Toshkentdanman. Siz talabamisiz?'],
                                ['speaker' => 'Sara', 'english' => 'Yes, I am. I am a student at this university. What about you?', 'uzbek' => 'Ha. Men shu universitetda talabaman. Siz-chi?'],
                                ['speaker' => 'Ali', 'english' => 'I am a student too! I am in my first year.', 'uzbek' => 'Men ham talabaman! Men birinchi kursda o\'qiyman.'],
                                ['speaker' => 'Sara', 'english' => 'Great! See you later, Ali!', 'uzbek' => 'Ajoyib! Keyinroq ko\'rishguncha, Ali!'],
                                ['speaker' => 'Ali', 'english' => 'Goodbye, Sara! Have a nice day!', 'uzbek' => 'Xayr, Sara! Kuningiz yaxshi o\'tsin!'],
                            ]
                        ],
                        'keyPhrases' => [
                            ['english' => 'What is your name?', 'uzbek' => 'Ismingiz nima?'],
                            ['english' => 'My name is...', 'uzbek' => 'Mening ismim...'],
                            ['english' => 'Where are you from?', 'uzbek' => 'Siz qayerdansiz?'],
                            ['english' => 'I am from...', 'uzbek' => 'Men ...danman.'],
                            ['english' => 'Nice to meet you!', 'uzbek' => 'Tanishganimdan xursandman!'],
                            ['english' => 'Are you a student?', 'uzbek' => 'Siz talabamisiz?'],
                            ['english' => 'Yes, I am. / No, I am not.', 'uzbek' => 'Ha. / Yo\'q.']
                        ]
                    ]
                ],
                [
                    'pageId' => 9,
                    'type' => 'summary',
                    'title' => 'Xulosa',
                    'titleUz' => 'Dars xulosasi',
                    'content' => [
                        'title' => '📝 DARS XULOSASI',
                        'keyPoints' => [
                            [
                                'topic' => 'TO BE fe\'li',
                                'rule' => 'I → **AM**, He/She/It → **IS**, You/We/They → **ARE**',
                                'example' => 'I **am** a student. She **is** from Tashkent.'
                            ],
                            [
                                'topic' => 'FROM - qayerdan',
                                'rule' => 'OLMOSH + am/is/are + **FROM** + joy',
                                'example' => 'I am **from** Uzbekistan.'
                            ],
                            [
                                'topic' => 'Savol tuzish',
                                'rule' => '**Fe\'l** oldinga chiqadi: Are you...? Is she...?',
                                'example' => '**Are** you from Tashkent?'
                            ],
                            [
                                'topic' => 'Inkor gap',
                                'rule' => 'Fe\'ldan keyin **NOT** qo\'shiladi',
                                'example' => 'I am **not** from Russia.'
                            ],
                            [
                                'topic' => 'A va AN',
                                'rule' => '**AN** = unli tovush, **A** = undosh tovush',
                                'example' => '**a** student, **an** engineer'
                            ]
                        ],
                        'practiceAdvice' => '🎯 Har kuni kamida **10 ta gap** yozib mashq qiling!',
                        'nextLesson' => 'Keyingi darsda: **Module Test** - barcha bilimlaringizni sinab ko\'ring!'
                    ]
                ]
            ],
            'exercises' => [
                [
                    'type' => 'fill_blank',
                    'instruction' => 'Bo\'sh joyga to\'g\'ri fe\'lni qo\'ying (am/is/are)',
                    'questions' => [
                        ['sentence' => 'I ___ from Tashkent.', 'answer' => 'am'],
                        ['sentence' => 'She ___ a doctor.', 'answer' => 'is'],
                        ['sentence' => 'They ___ students.', 'answer' => 'are'],
                        ['sentence' => 'He ___ from Bukhara.', 'answer' => 'is'],
                        ['sentence' => 'We ___ friends.', 'answer' => 'are'],
                        ['sentence' => 'It ___ a book.', 'answer' => 'is'],
                        ['sentence' => 'You ___ very smart.', 'answer' => 'are'],
                        ['sentence' => 'My name ___ Ali.', 'answer' => 'is'],
                    ]
                ],
                [
                    'type' => 'fill_blank_article',
                    'instruction' => 'Bo\'sh joyga to\'g\'ri artikl qo\'ying (a/an)',
                    'questions' => [
                        ['sentence' => 'I am ___ student.', 'answer' => 'a'],
                        ['sentence' => 'She is ___ engineer.', 'answer' => 'an'],
                        ['sentence' => 'He is ___ doctor.', 'answer' => 'a'],
                        ['sentence' => 'It is ___ apple.', 'answer' => 'an'],
                        ['sentence' => 'Tashkent is ___ city.', 'answer' => 'a'],
                        ['sentence' => 'He is ___ actor.', 'answer' => 'an'],
                    ]
                ],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"I ___ from Tashkent." - bo\'sh joyga qaysi so\'z keladi?', 'options' => ['is', 'are', 'am', 'be'], 'correctAnswer' => 2, 'explanation' => '**I** bilan faqat **AM** ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => '"She ___ a teacher." - to\'g\'ri javobni tanlang.', 'options' => ['am', 'are', 'is', 'be'], 'correctAnswer' => 2, 'explanation' => '**She** bilan faqat **IS** ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => '"They ___ from Bukhara." - qaysi fe\'l to\'g\'ri?', 'options' => ['am', 'is', 'are', 'be'], 'correctAnswer' => 2, 'explanation' => '**They** bilan **ARE** ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => '"He is ___ engineer." - bo\'sh joyga nima keladi?', 'options' => ['a', 'an', 'the', 'hech narsa'], 'correctAnswer' => 1, 'explanation' => '**Engineer** so\'zi **E** (unli) bilan boshlanadi, shuning uchun **AN** ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => '"_____ are you from?" - savol so\'zini tanlang.', 'options' => ['What', 'Who', 'Where', 'When'], 'correctAnswer' => 2, 'explanation' => 'Qayerdan ekanligini so\'rash uchun **WHERE** ishlatiladi.'],
            ]
        ];
    }

    private function getModuleTestContent(): array
    {
        return [
            'totalSteps' => 10,
            // MODUL TESTI - BARCHA SAVOLLAR O'ZBEK TILIDA
            'quiz' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Ertalab salomlashish uchun qaysi so\'zni ishlatasiz?',
                    'options' => ['Good night', 'Good morning', 'Goodbye', 'See you'],
                    'correctAnswer' => 1,
                    'explanation' => 'Ertalab "Good morning" (Hayrli tong) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Mening ismim Anna" gapini ingliz tiliga tarjima qiling:',
                    'options' => ['I am Anna.', 'My name is Anna.', 'Hello Anna.', 'You are Anna.'],
                    'correctAnswer' => 1,
                    'explanation' => '"Mening ismim" = "My name is" deb tarjima qilinadi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "Nice to _____ you!"',
                    'questionTranslation' => 'Tanishganimdan _____!',
                    'options' => ['see', 'meet', 'hello', 'bye'],
                    'correctAnswer' => 1,
                    'explanation' => '"Nice to meet you!" (Tanishganimdan xursandman!) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "I _____ from Tashkent."',
                    'questionTranslation' => 'Men Toshkentdan_____.',
                    'options' => ['is', 'are', 'am', 'be'],
                    'correctAnswer' => 2,
                    'explanation' => '"I" bilan "am" ishlatiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Xayr" so\'zini ingliz tilida qanday aytasiz?',
                    'options' => ['Hello', 'Thank you', 'Goodbye', 'Please'],
                    'correctAnswer' => 2,
                    'explanation' => '"Xayr" = "Goodbye" deb aytiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "She _____ a student."',
                    'questionTranslation' => 'U talaba_____.',
                    'options' => ['am', 'is', 'are', 'be'],
                    'correctAnswer' => 1,
                    'explanation' => '"She" bilan "is" ishlatiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kechqurun (soat 6 da) salomlashish uchun nima deysiz?',
                    'options' => ['Good morning', 'Good afternoon', 'Good evening', 'Good night'],
                    'correctAnswer' => 2,
                    'explanation' => 'Kechqurun "Good evening" (Hayrli kech) deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "Where _____ you from?"',
                    'questionTranslation' => 'Siz qayerdan_____?',
                    'options' => ['am', 'is', 'are', 'be'],
                    'correctAnswer' => 2,
                    'explanation' => '"You" bilan "are" ishlatiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Tanishganimdan xursandman" iborasini ingliz tiliga tarjima qiling:',
                    'options' => ['Hello', 'Goodbye', 'Nice to meet you', 'Thank you'],
                    'correctAnswer' => 2,
                    'explanation' => '"Tanishganimdan xursandman" = "Nice to meet you".',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "They _____ my friends."',
                    'questionTranslation' => 'Ular mening do\'stlarim_____.',
                    'options' => ['am', 'is', 'are', 'be'],
                    'correctAnswer' => 2,
                    'explanation' => '"They" bilan "are" ishlatiladi.',
                ],
            ],
        ];
    }

    private function getDefaultContent(string $slug, string $type): array
    {
        // Generate generic content based on lesson type
        $baseContent = [
            'totalSteps' => 8,
            'exercises' => [],
            'quiz' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'This is a sample question for ' . str_replace('-', ' ', $slug),
                    'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                    'correctAnswer' => 0,
                    'explanation' => 'This is a placeholder lesson. Real content will be added soon.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Practice question 2',
                    'options' => ['Answer 1', 'Answer 2', 'Answer 3', 'Answer 4'],
                    'correctAnswer' => 1,
                    'explanation' => 'Content coming soon!',
                ],
            ],
        ];

        if ($type === 'vocabulary') {
            $baseContent['words'] = [
                [
                    'english' => 'Example',
                    'uzbek' => 'Misol',
                    'pronunciation' => '/ɪɡˈzæmpəl/',
                    'emoji' => '📝',
                    'example' => 'This is an example.',
                    'exampleTranslation' => 'Bu misol.',
                ],
            ];
        }

        if ($type === 'grammar') {
            $baseContent['explanation'] = [
                'title' => 'Grammar Rule',
                'content' => 'This lesson covers an important grammar rule. Content will be expanded soon.',
                'tip' => 'Practice makes perfect!',
            ];
        }

        if ($type === 'conversation') {
            $baseContent['dialogue'] = [
                ['speaker' => 'A', 'text' => 'Hello!', 'translation' => 'Salom!'],
                ['speaker' => 'B', 'text' => 'Hi! How are you?', 'translation' => 'Salom! Qalaysiz?'],
            ];
            $baseContent['rolePlay'] = [];
        }

        return $baseContent;
    }

    // ==================== MODULE 2: ALPHABET & NUMBERS ====================

    private function getAlphabetAMContent(): array
    {
        return [
            'totalSteps' => 20,
            'words' => [
                ['english' => 'A', 'uzbek' => 'A harfi', 'pronunciation' => '/eɪ/', 'emoji' => '🅰️', 'example' => 'A is for Apple.', 'exampleTranslation' => 'A - Olma uchun.'],
                ['english' => 'B', 'uzbek' => 'B harfi', 'pronunciation' => '/biː/', 'emoji' => '🅱️', 'example' => 'B is for Ball.', 'exampleTranslation' => 'B - To\'p uchun.'],
                ['english' => 'C', 'uzbek' => 'C harfi', 'pronunciation' => '/siː/', 'emoji' => '©️', 'example' => 'C is for Cat.', 'exampleTranslation' => 'C - Mushuk uchun.'],
                ['english' => 'D', 'uzbek' => 'D harfi', 'pronunciation' => '/diː/', 'emoji' => '🇩', 'example' => 'D is for Dog.', 'exampleTranslation' => 'D - It uchun.'],
                ['english' => 'E', 'uzbek' => 'E harfi', 'pronunciation' => '/iː/', 'emoji' => '📧', 'example' => 'E is for Elephant.', 'exampleTranslation' => 'E - Fil uchun.'],
                ['english' => 'F', 'uzbek' => 'F harfi', 'pronunciation' => '/ef/', 'emoji' => '🎏', 'example' => 'F is for Fish.', 'exampleTranslation' => 'F - Baliq uchun.'],
                ['english' => 'G', 'uzbek' => 'G harfi', 'pronunciation' => '/dʒiː/', 'emoji' => '🎸', 'example' => 'G is for Guitar.', 'exampleTranslation' => 'G - Gitara uchun.'],
                ['english' => 'H', 'uzbek' => 'H harfi', 'pronunciation' => '/eɪtʃ/', 'emoji' => '🏠', 'example' => 'H is for House.', 'exampleTranslation' => 'H - Uy uchun.'],
                ['english' => 'I', 'uzbek' => 'I harfi', 'pronunciation' => '/aɪ/', 'emoji' => 'ℹ️', 'example' => 'I is for Ice cream.', 'exampleTranslation' => 'I - Muzqaymoq uchun.'],
                ['english' => 'J', 'uzbek' => 'J harfi', 'pronunciation' => '/dʒeɪ/', 'emoji' => '🃏', 'example' => 'J is for Juice.', 'exampleTranslation' => 'J - Sharbat uchun.'],
                ['english' => 'K', 'uzbek' => 'K harfi', 'pronunciation' => '/keɪ/', 'emoji' => '🔑', 'example' => 'K is for Key.', 'exampleTranslation' => 'K - Kalit uchun.'],
                ['english' => 'L', 'uzbek' => 'L harfi', 'pronunciation' => '/el/', 'emoji' => '🦁', 'example' => 'L is for Lion.', 'exampleTranslation' => 'L - Sher uchun.'],
                ['english' => 'M', 'uzbek' => 'M harfi', 'pronunciation' => '/em/', 'emoji' => '🌙', 'example' => 'M is for Moon.', 'exampleTranslation' => 'M - Oy uchun.'],
            ],
            'explanation' => [
                [
                    'title' => '1. Ingliz Alifbosi Haqida',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Ingliz alifbosida <strong>26 ta harf</strong> bor. Har bir harf <strong>katta (uppercase)</strong> va <strong>kichik (lowercase)</strong> shaklda yoziladi.<br><br>Bu darsda biz birinchi <strong>13 ta harfni</strong> o\'rganamiz: A dan M gacha.'],
                        [
                            'type' => 'table',
                            'title' => 'Harflar va Ularning Talaffuzi',
                            'headers' => ['Harf', 'Talaffuz (IPA)', 'O\'qilishi', 'Misol So\'z'],
                            'rows' => [
                                ['A a', '/eɪ/', 'ey', 'Apple (olma)'],
                                ['B b', '/biː/', 'bi', 'Ball (to\'p)'],
                                ['C c', '/siː/', 'si', 'Cat (mushuk)'],
                                ['D d', '/diː/', 'di', 'Dog (it)'],
                                ['E e', '/iː/', 'i', 'Elephant (fil)'],
                                ['F f', '/ef/', 'ef', 'Fish (baliq)'],
                                ['G g', '/dʒiː/', 'ji', 'Grapes (uzum)'],
                                ['H h', '/eɪtʃ/', 'eych', 'House (uy)'],
                                ['I i', '/aɪ/', 'ay', 'Ice (muz)'],
                                ['J j', '/dʒeɪ/', 'jey', 'Juice (sharbat)'],
                                ['K k', '/keɪ/', 'key', 'Key (kalit)'],
                                ['L l', '/el/', 'el', 'Lion (sher)'],
                                ['M m', '/em/', 'em', 'Moon (oy)'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '💡 <strong>Eslab qoling:</strong> Ingliz harflari o\'zbek harflaridan farq qiladi! Masalan, \"C\" harfi o\'zbekchada \"S\" kabi, \"G\" harfi esa \"J\" kabi o\'qiladi.'],
                    ]
                ],
                [
                    'title' => '2. Unli va Undosh Harflar',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Ingliz alifbosida <strong>5 ta unli harf</strong> (vowels) va <strong>21 ta undosh harf</strong> (consonants) bor.<br><br>Unli harflar: <strong>A, E, I, O, U</strong>'],
                        [
                            'type' => 'table',
                            'title' => 'A-M Orasidagi Unli va Undosh Harflar',
                            'headers' => ['Turi', 'Harflar', 'Soni'],
                            'rows' => [
                                ['Unlilar (Vowels)', 'A, E, I', '3 ta'],
                                ['Undoshlar (Consonants)', 'B, C, D, F, G, H, J, K, L, M', '10 ta'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '🎯 <strong>A va AN artikllari:</strong><br>• Undosh bilan boshlanadigan so\'zlar oldidan <strong>A</strong> ishlatiladi: a cat, a dog, a book<br>• Unli bilan boshlanadigan so\'zlar oldidan <strong>AN</strong> ishlatiladi: an apple, an egg, an ice cream'],
                    ]
                ],
                [
                    'title' => '3. Ko\'p Uchraydigan Xatolar',
                    'sections' => [
                        ['type' => 'text', 'content' => 'O\'zbek tilida so\'zlashuvchilar uchun ingliz harflarini talaffuz qilishda ba\'zi qiyinchiliklar mavjud:'],
                        [
                            'type' => 'mistakes',
                            'title' => 'Talaffuz Xatolari',
                            'items' => [
                                ['bad' => 'H harfini \"xa\" deb aytish', 'good' => 'H harfi \"eych\" deb aytiladi'],
                                ['bad' => 'G harfini \"ge\" deb aytish', 'good' => 'G harfi \"ji\" deb aytiladi'],
                                ['bad' => 'J harfini \"jo\" deb aytish', 'good' => 'J harfi \"jey\" deb aytiladi'],
                                ['bad' => 'I harfini \"i\" deb aytish', 'good' => 'I harfi \"ay\" deb aytiladi'],
                            ]
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'To\'g\'ri Talaffuz Namunalari',
                            'items' => [
                                ['en' => 'ABC = \"ey-bi-si\"', 'uz' => 'Alifboning birinchi uchta harfi'],
                                ['en' => 'DEF = \"di-i-ef\"', 'uz' => 'Keyingi uchta harf'],
                                ['en' => 'GHI = \"ji-eych-ay\"', 'uz' => 'Diqqat: G va H talaffuziga'],
                            ]
                        ],
                    ]
                ],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Apple" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['B', 'A', 'C', 'D'], 'correctAnswer' => 1, 'explanation' => '"Apple" (Olma) "A" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Cat" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['K', 'S', 'C', 'G'], 'correctAnswer' => 2, 'explanation' => '"Cat" (Mushuk) "C" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Quyidagilardan qaysi biri unli harf?', 'options' => ['B', 'C', 'D', 'E'], 'correctAnswer' => 3, 'explanation' => '"E" unli harf (vowel). Unlilar: A, E, I, O, U.'],
                ['type' => 'multiple_choice', 'question' => '"G" harfi qanday o\'qiladi?', 'options' => ['ge', 'gi', 'ji', 'go'], 'correctAnswer' => 2, 'explanation' => '"G" harfi "ji" (/dʒiː/) deb o\'qiladi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida birinchi harf qaysi?', 'options' => ['B', 'A', 'C', 'Z'], 'correctAnswer' => 1, 'explanation' => '"A" ingliz alifbosining birinchi harfi.'],
                ['type' => 'multiple_choice', 'question' => '"Dog" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['B', 'G', 'D', 'T'], 'correctAnswer' => 2, 'explanation' => '"Dog" (It) "D" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"House" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['G', 'J', 'H', 'K'], 'correctAnswer' => 2, 'explanation' => '"House" (Uy) "H" harfi bilan boshlanadi. "H" harfi "eych" deb o\'qiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Quyidagi harflardan qaysi biri alifboda birinchi keladi?', 'options' => ['M', 'G', 'C', 'K'], 'correctAnswer' => 2, 'explanation' => 'Alifbo tartibi: A, B, C, D, E, F, G, H, I, J, K, L, M. Demak C birinchi.'],
                ['type' => 'multiple_choice', 'question' => '"G" harfi qanday o\'qiladi?', 'options' => ['ge', 'gi', 'ji', 'ja'], 'correctAnswer' => 2, 'explanation' => '"G" harfi "ji" (/dʒiː/) deb o\'qiladi, "ge" emas!'],
                ['type' => 'multiple_choice', 'question' => 'Qaysi so\'z oldiga "AN" artikli qo\'yiladi?', 'options' => ['cat', 'dog', 'apple', 'ball'], 'correctAnswer' => 2, 'explanation' => '"Apple" unli harf (A) bilan boshlangani uchun "AN apple" deyiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Fish" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['P', 'V', 'F', 'E'], 'correctAnswer' => 2, 'explanation' => '"Fish" (Baliq) "F" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"I" harfi qanday o\'qiladi?', 'options' => ['i', 'ay', 'e', 'o'], 'correctAnswer' => 1, 'explanation' => '"I" harfi "ay" (/aɪ/) deb o\'qiladi. O\'zbek tilidagi "i" dan farq qiladi!'],
                ['type' => 'multiple_choice', 'question' => '"Juice" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['G', 'J', 'Y', 'I'], 'correctAnswer' => 1, 'explanation' => '"Juice" (Sharbat) "J" harfi bilan boshlanadi. "J" "jey" deb o\'qiladi.'],
                ['type' => 'multiple_choice', 'question' => 'A dan M gacha nechta harf bor?', 'options' => ['10', '11', '12', '13'], 'correctAnswer' => 3, 'explanation' => 'A, B, C, D, E, F, G, H, I, J, K, L, M = 13 ta harf.'],
            ],
        ];
    }

    private function getAlphabetNZContent(): array
    {
        return [
            'totalSteps' => 20,
            'words' => [
                ['english' => 'N', 'uzbek' => 'N harfi', 'pronunciation' => '/en/', 'emoji' => '📰', 'example' => 'N is for Newspaper.', 'exampleTranslation' => 'N - Gazeta uchun.'],
                ['english' => 'O', 'uzbek' => 'O harfi', 'pronunciation' => '/oʊ/', 'emoji' => '🍊', 'example' => 'O is for Orange.', 'exampleTranslation' => 'O - Apelsin uchun.'],
                ['english' => 'P', 'uzbek' => 'P harfi', 'pronunciation' => '/piː/', 'emoji' => '🖊️', 'example' => 'P is for Pen.', 'exampleTranslation' => 'P - Ruchka uchun.'],
                ['english' => 'Q', 'uzbek' => 'Q harfi', 'pronunciation' => '/kjuː/', 'emoji' => '👸', 'example' => 'Q is for Queen.', 'exampleTranslation' => 'Q - Malika uchun.'],
                ['english' => 'R', 'uzbek' => 'R harfi', 'pronunciation' => '/ɑːr/', 'emoji' => '🌈', 'example' => 'R is for Rainbow.', 'exampleTranslation' => 'R - Kamalak uchun.'],
                ['english' => 'S', 'uzbek' => 'S harfi', 'pronunciation' => '/es/', 'emoji' => '☀️', 'example' => 'S is for Sun.', 'exampleTranslation' => 'S - Quyosh uchun.'],
                ['english' => 'T', 'uzbek' => 'T harfi', 'pronunciation' => '/tiː/', 'emoji' => '🌳', 'example' => 'T is for Tree.', 'exampleTranslation' => 'T - Daraxt uchun.'],
                ['english' => 'U', 'uzbek' => 'U harfi', 'pronunciation' => '/juː/', 'emoji' => '☂️', 'example' => 'U is for Umbrella.', 'exampleTranslation' => 'U - Soyabon uchun.'],
                ['english' => 'V', 'uzbek' => 'V harfi', 'pronunciation' => '/viː/', 'emoji' => '🎻', 'example' => 'V is for Violin.', 'exampleTranslation' => 'V - Skripka uchun.'],
                ['english' => 'W', 'uzbek' => 'W harfi', 'pronunciation' => '/ˈdʌbljuː/', 'emoji' => '💧', 'example' => 'W is for Water.', 'exampleTranslation' => 'W - Suv uchun.'],
                ['english' => 'X', 'uzbek' => 'X harfi', 'pronunciation' => '/eks/', 'emoji' => '❌', 'example' => 'X is for X-ray.', 'exampleTranslation' => 'X - Rentgen uchun.'],
                ['english' => 'Y', 'uzbek' => 'Y harfi', 'pronunciation' => '/waɪ/', 'emoji' => '💛', 'example' => 'Y is for Yellow.', 'exampleTranslation' => 'Y - Sariq uchun.'],
                ['english' => 'Z', 'uzbek' => 'Z harfi', 'pronunciation' => '/ziː/', 'emoji' => '🦓', 'example' => 'Z is for Zebra.', 'exampleTranslation' => 'Z - Zebra uchun.'],
            ],
            'explanation' => [
                [
                    'title' => '1. N dan Z gacha Harflar',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Bu darsda biz alifboning oxirgi <strong>13 ta harfini</strong> o\'rganamiz: <strong>N dan Z gacha</strong>. Birinchi darsda A-M harflarini o\'rgandingiz, endi alifboni to\'liq bilib olasiz!'],
                        [
                            'type' => 'table',
                            'title' => 'N-Z Harflar va Talaffuz',
                            'headers' => ['Harf', 'Talaffuz (IPA)', 'O\'qilishi', 'Misol'],
                            'rows' => [
                                ['N n', '/en/', 'en', 'Newspaper'],
                                ['O o', '/oʊ/', 'ou', 'Orange'],
                                ['P p', '/piː/', 'pi', 'Pen'],
                                ['Q q', '/kjuː/', 'kyu', 'Queen'],
                                ['R r', '/ɑːr/', 'ar', 'Rainbow'],
                                ['S s', '/es/', 'es', 'Sun'],
                                ['T t', '/tiː/', 'ti', 'Tree'],
                                ['U u', '/juː/', 'yu', 'Umbrella'],
                                ['V v', '/viː/', 'vi', 'Violin'],
                                ['W w', '/ˈdʌbljuː/', 'dablyuu', 'Water'],
                                ['X x', '/eks/', 'eks', 'X-ray'],
                                ['Y y', '/waɪ/', 'way', 'Yellow'],
                                ['Z z', '/ziː/', 'zi', 'Zebra'],
                            ]
                        ],
                    ]
                ],
                [
                    'title' => '2. Qiyin Harflar: W, Q, R, Y',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Ba\'zi harflarning talaffuzi o\'zbek tili uchun qiyinroq. Keling, ularni batafsil o\'rganamiz:'],
                        [
                            'type' => 'mistakes',
                            'title' => 'Diqqat Bilan O\'rganing',
                            'items' => [
                                ['bad' => 'W harfini "ve" deb aytish', 'good' => 'W = "dablyuu" (double U - ikki U)'],
                                ['bad' => 'Q harfini "ku" deb aytish', 'good' => 'Q = "kyu" - doimo U bilan birga keladi: qu'],
                                ['bad' => 'R harfini "er" deb aytish', 'good' => 'R = "ar" - ingliz R o\'zbekchadan yumshoqroq'],
                                ['bad' => 'Y harfini "y" deb aytish', 'good' => 'Y = "way" - harf nomi "vay"'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '🎯 <strong>W</strong> - eng uzun nomli harf! "Double-U" ya\'ni "Ikki U" deyiladi. Sababli: "W" harfi qadimda "UU" kabi yozilgan.'],
                    ]
                ],
                [
                    'title' => '3. Unlilar: O va U',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Bu qismda yana ikkita unli harf bor: <strong>O</strong> va <strong>U</strong>. Ular ham A, E, I kabi muhim!'],
                        [
                            'type' => 'table',
                            'title' => 'O va U Unli Harflar',
                            'headers' => ['Harf', 'Nomi', 'Tovushi So\'zda', 'Misol'],
                            'rows' => [
                                ['O o', '/oʊ/ - ou', 'o, ɒ, ʌ', 'Orange, Hot, Love'],
                                ['U u', '/juː/ - yu', 'ʌ, uː, ju', 'Up, Blue, Use'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '📌 <strong>AN artikli:</strong> O va U bilan boshlangan so\'zlarda AN emas, A ishlatiladi, agar tovushi undosh bo\'lsa:<br>• an orange ✅ (O unli tovush)<br>• a university ✅ (U = "yu" undosh tovush bilan boshlanadi)'],
                    ]
                ],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Sun" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['Z', 'C', 'S', 'X'], 'correctAnswer' => 2, 'explanation' => '"Sun" (Quyosh) "S" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida oxirgi harf qaysi?', 'options' => ['X', 'Y', 'Z', 'W'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida oxirgi harf "Z" (zi).'],
                ['type' => 'multiple_choice', 'question' => '"W" harfi qanday o\'qiladi?', 'options' => ['ve', 'dablyuu', 'vu', 'way'], 'correctAnswer' => 1, 'explanation' => '"W" harfi "dablyuu" (double-U) deb o\'qiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Qaysi harflar unli?', 'options' => ['N va P', 'O va U', 'S va T', 'W va Y'], 'correctAnswer' => 1, 'explanation' => 'O va U unli harflar. Unlilar: A, E, I, O, U.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Water" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['V', 'W', 'U', 'Y'], 'correctAnswer' => 1, 'explanation' => '"Water" (Suv) "W" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Tree" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['R', 'S', 'T', 'P'], 'correctAnswer' => 2, 'explanation' => '"Tree" (Daraxt) "T" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida nechta harf bor?', 'options' => ['24', '25', '26', '27'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida 26 ta harf bor: A dan Z gacha.'],
                ['type' => 'multiple_choice', 'question' => '"Yellow" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['W', 'Y', 'U', 'I'], 'correctAnswer' => 1, 'explanation' => '"Yellow" (Sariq) "Y" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Q" harfi qanday o\'qiladi?', 'options' => ['ku', 'kyu', 'ko', 'ka'], 'correctAnswer' => 1, 'explanation' => '"Q" harfi "kyu" deb o\'qiladi. Q doimo U bilan keladi: "qu".'],
                ['type' => 'multiple_choice', 'question' => 'Qaysi so\'z oldiga "A" artikli qo\'yiladi?', 'options' => ['orange', 'apple', 'university', 'elephant'], 'correctAnswer' => 2, 'explanation' => '"University" "yu" tovushi bilan boshlanadi (undosh), shuning uchun "a university".'],
                ['type' => 'multiple_choice', 'question' => '"Rainbow" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['Q', 'P', 'R', 'S'], 'correctAnswer' => 2, 'explanation' => '"Rainbow" (Kamalak) "R" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida oxirgi harf qaysi?', 'options' => ['Y', 'X', 'W', 'Z'], 'correctAnswer' => 3, 'explanation' => 'Ingliz alifbosida oxirgi harf "Z".'],
                ['type' => 'multiple_choice', 'question' => 'N dan Z gacha nechta harf bor?', 'options' => ['11', '12', '13', '14'], 'correctAnswer' => 2, 'explanation' => 'N, O, P, Q, R, S, T, U, V, W, X, Y, Z = 13 ta harf.'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida nechta unli harf bor?', 'options' => ['4', '5', '6', '7'], 'correctAnswer' => 1, 'explanation' => 'Ingliz alifbosida 5 ta unli harf bor: A, E, I, O, U.'],
            ],
        ];
    }

    private function getNumbers1To20Content(): array
    {
        return [
            'totalSteps' => 14,
            'words' => [
                ['english' => 'One', 'uzbek' => 'Bir', 'pronunciation' => '/wʌn/', 'emoji' => '1️⃣', 'example' => 'I have one apple.', 'exampleTranslation' => 'Menda bitta olma bor.'],
                ['english' => 'Two', 'uzbek' => 'Ikki', 'pronunciation' => '/tuː/', 'emoji' => '2️⃣', 'example' => 'Two plus two is four.', 'exampleTranslation' => 'Ikki qo\'shish ikki to\'rtga teng.'],
                ['english' => 'Three', 'uzbek' => 'Uch', 'pronunciation' => '/θriː/', 'emoji' => '3️⃣', 'example' => 'I have three books.', 'exampleTranslation' => 'Menda uchta kitob bor.'],
                ['english' => 'Four', 'uzbek' => 'To\'rt', 'pronunciation' => '/fɔːr/', 'emoji' => '4️⃣', 'example' => 'There are four seasons.', 'exampleTranslation' => 'To\'rtta fasl bor.'],
                ['english' => 'Five', 'uzbek' => 'Besh', 'pronunciation' => '/faɪv/', 'emoji' => '5️⃣', 'example' => 'I have five fingers.', 'exampleTranslation' => 'Menda beshta barmoq bor.'],
                ['english' => 'Six', 'uzbek' => 'Olti', 'pronunciation' => '/sɪks/', 'emoji' => '6️⃣', 'example' => 'Six is after five.', 'exampleTranslation' => 'Olti beshdan keyin keladi.'],
                ['english' => 'Seven', 'uzbek' => 'Yetti', 'pronunciation' => '/ˈsevən/', 'emoji' => '7️⃣', 'example' => 'Seven days in a week.', 'exampleTranslation' => 'Haftada yetti kun.'],
                ['english' => 'Eight', 'uzbek' => 'Sakkiz', 'pronunciation' => '/eɪt/', 'emoji' => '8️⃣', 'example' => 'I am eight years old.', 'exampleTranslation' => 'Men sakkiz yoshdaman.'],
                ['english' => 'Nine', 'uzbek' => 'To\'qqiz', 'pronunciation' => '/naɪn/', 'emoji' => '9️⃣', 'example' => 'Nine is before ten.', 'exampleTranslation' => 'To\'qqiz o\'ndan oldin keladi.'],
                ['english' => 'Ten', 'uzbek' => 'O\'n', 'pronunciation' => '/ten/', 'emoji' => '🔟', 'example' => 'I have ten toes.', 'exampleTranslation' => 'Menda o\'nta oyoq barmog\'i bor.'],
            ],
            'explanation' => [
                [
                    'title' => '1. Raqamlar 1-10',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Ingliz tilida raqamlarni bilish juda muhim! Bu darsda biz <strong>1 dan 20 gacha</strong> raqamlarni o\'rganamiz. Dastlab 1-10 ni yodlang:'],
                        [
                            'type' => 'table',
                            'title' => '1-10 Raqamlar',
                            'headers' => ['Raqam', 'Inglizcha', 'O\'zbekcha', 'Talaffuz'],
                            'rows' => [
                                ['1', 'One', 'Bir', '/wʌn/ - van'],
                                ['2', 'Two', 'Ikki', '/tuː/ - tu'],
                                ['3', 'Three', 'Uch', '/θriː/ - sri'],
                                ['4', 'Four', 'To\'rt', '/fɔːr/ - for'],
                                ['5', 'Five', 'Besh', '/faɪv/ - fayv'],
                                ['6', 'Six', 'Olti', '/sɪks/ - siks'],
                                ['7', 'Seven', 'Yetti', '/ˈsevən/ - sevn'],
                                ['8', 'Eight', 'Sakkiz', '/eɪt/ - eyt'],
                                ['9', 'Nine', 'To\'qqiz', '/naɪn/ - nayn'],
                                ['10', 'Ten', 'O\'n', '/ten/ - ten'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '💡 <strong>Three</strong> so\'zidagi "th" tovushi o\'zbek tilida yo\'q! Tilni tishlar orasiga qo\'yib "th" deng.'],
                    ]
                ],
                [
                    'title' => '2. Teen Raqamlari (11-19)',
                    'sections' => [
                        ['type' => 'text', 'content' => '11-19 raqamlari maxsus qoidaga ega. Ular <strong>"-teen"</strong> qo\'shimchasi bilan tugaydi (13-19):'],
                        [
                            'type' => 'table',
                            'title' => '11-19 Raqamlar',
                            'headers' => ['Raqam', 'Inglizcha', 'O\'zbekcha', 'Izoh'],
                            'rows' => [
                                ['11', 'Eleven', 'O\'n bir', 'Maxsus so\'z'],
                                ['12', 'Twelve', 'O\'n ikki', 'Maxsus so\'z'],
                                ['13', 'Thirteen', 'O\'n uch', 'Three + teen'],
                                ['14', 'Fourteen', 'O\'n to\'rt', 'Four + teen'],
                                ['15', 'Fifteen', 'O\'n besh', 'Five + teen (e tushib qoladi)'],
                                ['16', 'Sixteen', 'O\'n olti', 'Six + teen'],
                                ['17', 'Seventeen', 'O\'n yetti', 'Seven + teen'],
                                ['18', 'Eighteen', 'O\'n sakkiz', 'Eight + teen (t bitta)'],
                                ['19', 'Nineteen', 'O\'n to\'qqiz', 'Nine + teen'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '⚠️ <strong>11 va 12</strong> "-teen" bilan tugamaydi! Ularni alohida yodlash kerak: Eleven, Twelve.'],
                    ]
                ],
                [
                    'title' => '3. Raqamlarni Ishlatish',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Raqamlarni turli holatlarda ishlatish mumkin: <strong>yoshni aytish</strong>, <strong>sanash</strong>, <strong>matematika</strong>.'],
                        [
                            'type' => 'examples',
                            'title' => 'Foydali Iboralar',
                            'items' => [
                                ['en' => 'I am ten years old.', 'uz' => 'Men o\'n yoshdaman.'],
                                ['en' => 'Two plus three is five.', 'uz' => 'Ikki qo\'shish uch beshga teng.'],
                                ['en' => 'I have five books.', 'uz' => 'Menda beshta kitob bor.'],
                                ['en' => 'There are seven days in a week.', 'uz' => 'Haftada yetti kun bor.'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '📌 <strong>Yosh aytish:</strong> "I am + raqam + years old" = Men ... yoshdaman'],
                    ]
                ],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Besh" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Four', 'Five', 'Six', 'Seven'], 'correctAnswer' => 1, 'explanation' => '"Besh" ingliz tilida "Five" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '3 + 4 = ?', 'options' => ['Six', 'Seven', 'Eight', 'Nine'], 'correctAnswer' => 1, 'explanation' => '3 + 4 = 7 (Seven - Yetti).'],
                ['type' => 'multiple_choice', 'question' => '"Twelve" qaysi raqam?', 'options' => ['10', '11', '12', '13'], 'correctAnswer' => 2, 'explanation' => '"Twelve" = 12 (O\'n ikki).'],
                ['type' => 'multiple_choice', 'question' => '"O\'n besh" ingliz tilida qanday?', 'options' => ['Fourteen', 'Fifteen', 'Sixteen', 'Seventeen'], 'correctAnswer' => 1, 'explanation' => '"O\'n besh" = "Fifteen".'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Seven" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['Olti', 'Yetti', 'Sakkiz', 'To\'qqiz'], 'correctAnswer' => 1, 'explanation' => '"Seven" = "Yetti".'],
                ['type' => 'multiple_choice', 'question' => '"O\'n" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Nine', 'Ten', 'Eleven', 'Eight'], 'correctAnswer' => 1, 'explanation' => '"O\'n" ingliz tilida "Ten" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '5 + 3 = ?', 'options' => ['Seven', 'Eight', 'Nine', 'Six'], 'correctAnswer' => 1, 'explanation' => '5 + 3 = 8 (Eight - Sakkiz).'],
                ['type' => 'multiple_choice', 'question' => 'Haftada necha kun bor?', 'options' => ['Five', 'Six', 'Seven', 'Eight'], 'correctAnswer' => 2, 'explanation' => 'Haftada 7 (Seven - Yetti) kun bor.'],
                ['type' => 'multiple_choice', 'question' => '"Eleven" qaysi raqam?', 'options' => ['10', '11', '12', '13'], 'correctAnswer' => 1, 'explanation' => '"Eleven" = 11 (O\'n bir).'],
                ['type' => 'multiple_choice', 'question' => '13-19 raqamlari qaysi qo\'shimcha bilan tugaydi?', 'options' => ['-ty', '-teen', '-ten', '-th'], 'correctAnswer' => 1, 'explanation' => '13-19 raqamlari "-teen" qo\'shimchasi bilan tugaydi: thirteen, fourteen...'],
                ['type' => 'multiple_choice', 'question' => '"Three" so\'zida qaysi qiyin tovush bor?', 'options' => ['r', 'th', 'ee', 'tr'], 'correctAnswer' => 1, 'explanation' => '"Three" da "th" tovushi bor. Bu tovush o\'zbek tilida yo\'q.'],
                ['type' => 'multiple_choice', 'question' => '"Eighteen" qaysi raqam?', 'options' => ['16', '17', '18', '19'], 'correctAnswer' => 2, 'explanation' => '"Eighteen" = 18 (O\'n sakkiz).'],
                ['type' => 'multiple_choice', 'question' => '7 + 6 = ?', 'options' => ['Eleven', 'Twelve', 'Thirteen', 'Fourteen'], 'correctAnswer' => 2, 'explanation' => '7 + 6 = 13 (Thirteen - O\'n uch).'],
                ['type' => 'multiple_choice', 'question' => '"I am ten years old" nimani anglatadi?', 'options' => ['Menda o\'nta kitob bor', 'Men o\'n yoshdaman', 'Soat o\'nda', 'O\'n kun'], 'correctAnswer' => 1, 'explanation' => '"I am ten years old" = "Men o\'n yoshdaman".'],
            ],
        ];
    }

    private function getNumbers21To100Content(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Twenty', 'uzbek' => 'Yigirma', 'pronunciation' => '/ˈtwenti/', 'emoji' => '2️⃣0️⃣', 'example' => 'I am twenty years old.', 'exampleTranslation' => 'Men yigirma yoshdaman.'],
                ['english' => 'Thirty', 'uzbek' => 'O\'ttiz', 'pronunciation' => '/ˈθɜːrti/', 'emoji' => '3️⃣0️⃣', 'example' => 'Thirty days in a month.', 'exampleTranslation' => 'Oyda o\'ttiz kun.'],
                ['english' => 'Forty', 'uzbek' => 'Qirq', 'pronunciation' => '/ˈfɔːrti/', 'emoji' => '4️⃣0️⃣', 'example' => 'He is forty years old.', 'exampleTranslation' => 'U qirq yoshda.'],
                ['english' => 'Fifty', 'uzbek' => 'Ellik', 'pronunciation' => '/ˈfɪfti/', 'emoji' => '5️⃣0️⃣', 'example' => 'Fifty percent is half.', 'exampleTranslation' => 'Ellik foiz yarmi.'],
                ['english' => 'Sixty', 'uzbek' => 'Oltmish', 'pronunciation' => '/ˈsɪksti/', 'emoji' => '6️⃣0️⃣', 'example' => 'Sixty minutes in an hour.', 'exampleTranslation' => 'Bir soatda oltmish daqiqa.'],
                ['english' => 'Seventy', 'uzbek' => 'Yetmish', 'pronunciation' => '/ˈsevənti/', 'emoji' => '7️⃣0️⃣', 'example' => 'My grandpa is seventy.', 'exampleTranslation' => 'Bobom yetmish yoshda.'],
                ['english' => 'Eighty', 'uzbek' => 'Sakson', 'pronunciation' => '/ˈeɪti/', 'emoji' => '8️⃣0️⃣', 'example' => 'Eighty is a big number.', 'exampleTranslation' => 'Sakson katta raqam.'],
                ['english' => 'Ninety', 'uzbek' => 'To\'qson', 'pronunciation' => '/ˈnaɪnti/', 'emoji' => '9️⃣0️⃣', 'example' => 'Ninety plus ten is one hundred.', 'exampleTranslation' => 'To\'qson qo\'shish o\'n yuzga teng.'],
                ['english' => 'One hundred', 'uzbek' => 'Yuz', 'pronunciation' => '/wʌn ˈhʌndrəd/', 'emoji' => '💯', 'example' => 'One hundred percent!', 'exampleTranslation' => 'Yuz foiz!'],
            ],
            'explanation' => [
                [
                    'title' => '1. O\'nliklar (-TY)',
                    'sections' => [
                        ['type' => 'text', 'content' => 'O\'nliklar (20, 30, 40...) <strong>"-ty"</strong> qo\'shimchasi bilan tugaydi. Bu "-teen" dan farq qiladi!'],
                        [
                            'type' => 'table',
                            'title' => 'O\'nliklar Jadvali',
                            'headers' => ['Raqam', 'Inglizcha', 'O\'zbekcha', 'Qoida'],
                            'rows' => [
                                ['20', 'Twenty', 'Yigirma', 'Twen + ty'],
                                ['30', 'Thirty', 'O\'ttiz', 'Thir + ty'],
                                ['40', 'Forty', 'Qirq', 'For + ty (u YO\'Q!)'],
                                ['50', 'Fifty', 'Ellik', 'Fif + ty'],
                                ['60', 'Sixty', 'Oltmish', 'Six + ty'],
                                ['70', 'Seventy', 'Yetmish', 'Seven + ty'],
                                ['80', 'Eighty', 'Sakson', 'Eigh + ty'],
                                ['90', 'Ninety', 'To\'qson', 'Nine + ty'],
                                ['100', 'One hundred', 'Yuz', 'Maxsus so\'z'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '⚠️ <strong>Forty</strong> = 40 (U harfi YO\'Q!). "Fourty" noto\'g\'ri!'],
                    ]
                ],
                [
                    'title' => '2. Qo\'shma Raqamlar (21-99)',
                    'sections' => [
                        ['type' => 'text', 'content' => '21-99 oralig\'idagi raqamlarni aytish uchun: <strong>O\'nlik + birlik</strong> formulasidan foydalanamiz:'],
                        [
                            'type' => 'examples',
                            'title' => 'Qo\'shma Raqamlar',
                            'items' => [
                                ['en' => '21 = Twenty-one', 'uz' => 'Yigirma bir'],
                                ['en' => '35 = Thirty-five', 'uz' => 'O\'ttiz besh'],
                                ['en' => '47 = Forty-seven', 'uz' => 'Qirq yetti'],
                                ['en' => '59 = Fifty-nine', 'uz' => 'Ellik to\'qqiz'],
                                ['en' => '82 = Eighty-two', 'uz' => 'Sakson ikki'],
                                ['en' => '99 = Ninety-nine', 'uz' => 'To\'qson to\'qqiz'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '📝 Qo\'shma raqamlarda o\'nlik va birlik orasiga <strong>defis (-)</strong> qo\'yiladi: twenty-one, thirty-five.'],
                    ]
                ],
                [
                    'title' => '3. Katta Raqamlarni Ishlatish',
                    'sections' => [
                        ['type' => 'text', 'content' => 'Katta raqamlar kundalik hayotda ko\'p ishlatiladi: <strong>yoshni</strong>, <strong>narxni</strong>, <strong>vaqtni</strong> aytishda:'],
                        [
                            'type' => 'examples',
                            'title' => 'Hayotda Ishlatilishi',
                            'items' => [
                                ['en' => 'I am twenty-five years old.', 'uz' => 'Men yigirma besh yoshdaman.'],
                                ['en' => 'It costs fifty dollars.', 'uz' => 'Bu ellik dollar turadi.'],
                                ['en' => 'There are sixty seconds in a minute.', 'uz' => 'Bir daqiqada oltmish soniya.'],
                                ['en' => 'My grandmother is eighty years old.', 'uz' => 'Buvim sakson yoshda.'],
                            ]
                        ],
                        ['type' => 'tip', 'content' => '💯 <strong>100 = One hundred</strong> yoki shunchaki <strong>A hundred</strong>. Keyingi darsda 100 dan katta raqamlarni o\'rganamiz!'],
                    ]
                ],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Ellik" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Forty', 'Fifty', 'Sixty', 'Seventy'], 'correctAnswer' => 1, 'explanation' => '"Ellik" ingliz tilida "Fifty" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Bir soatda necha daqiqa bor?', 'options' => ['Fifty', 'Sixty', 'Seventy', 'Eighty'], 'correctAnswer' => 1, 'explanation' => 'Bir soatda 60 (Sixty - Oltmish) daqiqa bor.'],
                ['type' => 'multiple_choice', 'question' => '"Forty" qaysi raqam?', 'options' => ['30', '40', '50', '60'], 'correctAnswer' => 1, 'explanation' => '"Forty" = 40 (Qirq).'],
                ['type' => 'multiple_choice', 'question' => '25 ni inglizcha qanday aytasiz?', 'options' => ['Twenty-four', 'Twenty-five', 'Twenty-six', 'Fifteen'], 'correctAnswer' => 1, 'explanation' => '25 = Twenty-five (Yigirma besh).'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"One hundred" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['To\'qson', 'Yuz', 'Sakson', 'Yetmish'], 'correctAnswer' => 1, 'explanation' => '"One hundred" = "Yuz".'],
                ['type' => 'multiple_choice', 'question' => '30 + 20 = ?', 'options' => ['Forty', 'Fifty', 'Sixty', 'Seventy'], 'correctAnswer' => 1, 'explanation' => '30 + 20 = 50 (Fifty - Ellik).'],
                ['type' => 'multiple_choice', 'question' => '"Qirq" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Thirty', 'Forty', 'Fifty', 'Sixty'], 'correctAnswer' => 1, 'explanation' => '"Qirq" = "Forty".'],
                ['type' => 'multiple_choice', 'question' => '"Ninety" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['Sakson', 'To\'qson', 'Yuz', 'Yetmish'], 'correctAnswer' => 1, 'explanation' => '"Ninety" = "To\'qson".'],
                ['type' => 'multiple_choice', 'question' => 'O\'nliklar qaysi qo\'shimcha bilan tugaydi?', 'options' => ['-teen', '-ty', '-ten', '-th'], 'correctAnswer' => 1, 'explanation' => 'O\'nliklar (20-90) "-ty" bilan tugaydi: twenty, thirty, forty...'],
                ['type' => 'multiple_choice', 'question' => '"Forty" so\'zida qaysi harf YO\'Q?', 'options' => ['f', 'o', 'u', 'r'], 'correctAnswer' => 2, 'explanation' => '"Forty" da "u" harfi yo\'q! "Fourty" noto\'g\'ri yozuv.'],
                ['type' => 'multiple_choice', 'question' => '73 ni inglizcha qanday aytasiz?', 'options' => ['Sixty-three', 'Seventy-three', 'Eighty-three', 'Thirty-seven'], 'correctAnswer' => 1, 'explanation' => '73 = Seventy-three (Yetmish uch).'],
                ['type' => 'multiple_choice', 'question' => '"Eighty" qaysi raqam?', 'options' => ['70', '80', '90', '100'], 'correctAnswer' => 1, 'explanation' => '"Eighty" = 80 (Sakson).'],
                ['type' => 'multiple_choice', 'question' => '50 + 50 = ?', 'options' => ['Ninety', 'One hundred', 'Eighty', 'Seventy'], 'correctAnswer' => 1, 'explanation' => '50 + 50 = 100 (One hundred - Yuz).'],
                ['type' => 'multiple_choice', 'question' => '"Sixty minutes in an hour" nimani anglatadi?', 'options' => ['Oltmish soniya', 'Bir soatda oltmish daqiqa', 'Oltmish soat', 'Oltmish kun'], 'correctAnswer' => 1, 'explanation' => '"Sixty minutes in an hour" = "Bir soatda oltmish daqiqa".'],
            ],
        ];
    }

    private function getModule2TestContent(): array
    {
        return [
            'totalSteps' => 15,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida nechta harf bor?', 'options' => ['24', '25', '26', '28'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida 26 ta harf bor: A dan Z gacha.'],
                ['type' => 'multiple_choice', 'question' => '"Apple" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['E', 'A', 'O', 'I'], 'correctAnswer' => 1, 'explanation' => '"Apple" (Olma) "A" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Yetti" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Six', 'Seven', 'Eight', 'Nine'], 'correctAnswer' => 1, 'explanation' => '"Yetti" ingliz tilida "Seven" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '10 + 10 = ?', 'options' => ['Fifteen', 'Twenty', 'Twenty-five', 'Thirty'], 'correctAnswer' => 1, 'explanation' => '10 + 10 = 20 (Twenty - Yigirma).'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida oxirgi harf qaysi?', 'options' => ['X', 'Y', 'Z', 'W'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida oxirgi harf "Z" (zi).'],
                ['type' => 'multiple_choice', 'question' => '"One hundred" qancha?', 'options' => ['90', '100', '110', '1000'], 'correctAnswer' => 1, 'explanation' => '"One hundred" = 100 (Yuz).'],
                ['type' => 'multiple_choice', 'question' => '"D" harfidan keyin qaysi harf keladi?', 'options' => ['C', 'E', 'F', 'G'], 'correctAnswer' => 1, 'explanation' => '"D" harfidan keyin "E" harfi keladi.'],
                ['type' => 'multiple_choice', 'question' => '"Fifty" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['Qirq', 'Ellik', 'Oltmish', 'Yetmish'], 'correctAnswer' => 1, 'explanation' => '"Fifty" = "Ellik".'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida nechta unli harf bor?', 'options' => ['4', '5', '6', '7'], 'correctAnswer' => 1, 'explanation' => 'Ingliz alifbosida 5 ta unli harf bor: A, E, I, O, U.'],
                ['type' => 'multiple_choice', 'question' => '"G" harfi qanday o\'qiladi?', 'options' => ['ge', 'gi', 'ji', 'go'], 'correctAnswer' => 2, 'explanation' => '"G" harfi "ji" (/dʒiː/) deb o\'qiladi.'],
                ['type' => 'multiple_choice', 'question' => '13-19 raqamlari qaysi qo\'shimcha bilan tugaydi?', 'options' => ['-ty', '-teen', '-ten', '-th'], 'correctAnswer' => 1, 'explanation' => '13-19 raqamlari "-teen" bilan tugaydi: thirteen, fourteen, fifteen...'],
                ['type' => 'multiple_choice', 'question' => '20-90 o\'nliklar qaysi qo\'shimcha bilan tugaydi?', 'options' => ['-teen', '-ty', '-ty', '-th'], 'correctAnswer' => 1, 'explanation' => 'O\'nliklar (20-90) "-ty" bilan tugaydi: twenty, thirty, forty...'],
                ['type' => 'multiple_choice', 'question' => 'Qaysi so\'z oldiga "AN" artikli qo\'yiladi?', 'options' => ['cat', 'apple', 'dog', 'book'], 'correctAnswer' => 1, 'explanation' => '"Apple" unli harf (A) bilan boshlangani uchun "AN apple".'],
                ['type' => 'multiple_choice', 'question' => '"W" harfi qanday o\'qiladi?', 'options' => ['ve', 'way', 'dablyuu', 'yu'], 'correctAnswer' => 2, 'explanation' => '"W" harfi "dablyuu" (double-U) deb o\'qiladi.'],
                ['type' => 'multiple_choice', 'question' => '7 + 6 = ?', 'options' => ['Eleven', 'Twelve', 'Thirteen', 'Fourteen'], 'correctAnswer' => 2, 'explanation' => '7 + 6 = 13 (Thirteen - O\'n uch).'],
            ],
        ];
    }

    // ==================== MODULE 3: COLORS & SHAPES ====================

    private function getBasicColorsContent(): array
    {
        return [
            'totalSteps' => 14,
            'words' => [
                ['english' => 'Red', 'uzbek' => 'Qizil', 'pronunciation' => '/red/', 'emoji' => '🔴', 'example' => 'The apple is red.', 'exampleTranslation' => 'Olma qizil.'],
                ['english' => 'Blue', 'uzbek' => 'Ko\'k', 'pronunciation' => '/bluː/', 'emoji' => '🔵', 'example' => 'The sky is blue.', 'exampleTranslation' => 'Osmon ko\'k.'],
                ['english' => 'Green', 'uzbek' => 'Yashil', 'pronunciation' => '/ɡriːn/', 'emoji' => '🟢', 'example' => 'The grass is green.', 'exampleTranslation' => 'O\'t yashil.'],
                ['english' => 'Yellow', 'uzbek' => 'Sariq', 'pronunciation' => '/ˈjeloʊ/', 'emoji' => '🟡', 'example' => 'The sun is yellow.', 'exampleTranslation' => 'Quyosh sariq.'],
                ['english' => 'Black', 'uzbek' => 'Qora', 'pronunciation' => '/blæk/', 'emoji' => '⚫', 'example' => 'The cat is black.', 'exampleTranslation' => 'Mushuk qora.'],
                ['english' => 'White', 'uzbek' => 'Oq', 'pronunciation' => '/waɪt/', 'emoji' => '⚪', 'example' => 'The snow is white.', 'exampleTranslation' => 'Qor oq.'],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'Quyosh qaysi rangda?', 'options' => ['Red', 'Blue', 'Yellow', 'Green'], 'correctAnswer' => 2, 'explanation' => 'Quyosh sariq (Yellow) rangda.'],
                ['type' => 'multiple_choice', 'question' => '"Qizil" rangni ingliz tilida qanday aytasiz?', 'options' => ['Blue', 'Red', 'Green', 'Yellow'], 'correctAnswer' => 1, 'explanation' => '"Qizil" ingliz tilida "Red" deb aytiladi.'],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - O'ZBEK TILIDA
            'explanation' => [
                [
                    'title' => '1. Odamlarni Tasvirlash',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Odamlarning tashqi ko\'rinishini tasvirlash uchun <strong>"To Be"</strong> fe\'li va <strong>sifatlar</strong>dan foydalanamiz.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Foydali Sifatlar',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Misol'],
                            'rows' => [
                                ['<b>Tall</b>', 'Baland bo\'yli', 'He is tall. (U baland bo\'yli.)'],
                                ['<b>Short</b>', 'Past bo\'yli', 'She is short. (U past bo\'yli.)'],
                                ['<b>Young</b>', 'Yosh', 'The boy is young. (Bola yosh.)'],
                                ['<b>Old</b>', 'Keksa/Qari', 'My grandfather is old. (Bobom keksa.)'],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => '2. Gap Tuzilishi',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Gap tuzish formulasi: <strong>Ega + To Be + Sifat</strong>.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Misollar',
                            'items' => [
                                ['en' => 'My father is tall.', 'uz' => 'Mening otam baland bo\'yli.'],
                                ['en' => 'Her sister is young.', 'uz' => 'Uning singlisi yosh.'],
                                ['en' => 'They are old.', 'uz' => 'Ular keksa.'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 O\'zbek tilida "bo\'yli" so\'zi qo\'shilsa ham, ingliz tilida shunchaki "He is tall" (U baland) deyish yetarli.'
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Blue" rangning o\'zbekcha tarjimasi qanday?', 'options' => ['Qizil', 'Yashil', 'Ko\'k', 'Sariq'], 'correctAnswer' => 2, 'explanation' => '"Blue" - "Ko\'k" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'O\'t qaysi rangda?', 'options' => ['Red', 'Yellow', 'Green', 'Blue'], 'correctAnswer' => 2, 'explanation' => 'O\'t yashil (Green) rangda.'],
                ['type' => 'multiple_choice', 'question' => '"Oq" rangni ingliz tilida qanday aytasiz?', 'options' => ['Black', 'White', 'Yellow', 'Red'], 'correctAnswer' => 1, 'explanation' => '"Oq" ingliz tilida "White" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Black" rangning o\'zbekcha tarjimasi qanday?', 'options' => ['Oq', 'Qora', 'Ko\'k', 'Yashil'], 'correctAnswer' => 1, 'explanation' => '"Black" - "Qora" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getMoreColorsContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Orange', 'uzbek' => 'To\'q sariq', 'pronunciation' => '/ˈɔːrɪndʒ/', 'emoji' => '🟠', 'example' => 'The orange is orange.', 'exampleTranslation' => 'Apelsin to\'q sariq.'],
                ['english' => 'Pink', 'uzbek' => 'Pushti', 'pronunciation' => '/pɪŋk/', 'emoji' => '🩷', 'example' => 'The flower is pink.', 'exampleTranslation' => 'Gul pushti.'],
                ['english' => 'Purple', 'uzbek' => 'Binafsha', 'pronunciation' => '/ˈpɜːrpl/', 'emoji' => '🟣', 'example' => 'The grape is purple.', 'exampleTranslation' => 'Uzum binafsha rangda.'],
                ['english' => 'Brown', 'uzbek' => 'Jigarrang', 'pronunciation' => '/braʊn/', 'emoji' => '🟤', 'example' => 'The tree is brown.', 'exampleTranslation' => 'Daraxt jigarrang.'],
                ['english' => 'Gray', 'uzbek' => 'Kulrang', 'pronunciation' => '/ɡreɪ/', 'emoji' => '🩶', 'example' => 'The elephant is gray.', 'exampleTranslation' => 'Fil kulrang.'],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Pushti" rangni ingliz tilida qanday aytasiz?', 'options' => ['Purple', 'Pink', 'Orange', 'Brown'], 'correctAnswer' => 1, 'explanation' => '"Pushti" ingliz tilida "Pink" deb aytiladi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Purple" rangning o\'zbekcha tarjimasi qanday?', 'options' => ['Pushti', 'Binafsha', 'To\'q sariq', 'Kulrang'], 'correctAnswer' => 1, 'explanation' => '"Purple" - "Binafsha" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Fil qaysi rangda?', 'options' => ['Brown', 'Gray', 'Black', 'White'], 'correctAnswer' => 1, 'explanation' => 'Fil kulrang (Gray).'],
                ['type' => 'multiple_choice', 'question' => '"Brown" rangning o\'zbekcha tarjimasi qanday?', 'options' => ['Kulrang', 'Jigarrang', 'Qora', 'Oq'], 'correctAnswer' => 1, 'explanation' => '"Brown" - "Jigarrang" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getBasicShapesContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Circle', 'uzbek' => 'Doira', 'pronunciation' => '/ˈsɜːrkl/', 'emoji' => '⭕', 'example' => 'The ball is a circle.', 'exampleTranslation' => 'To\'p doira shaklida.'],
                ['english' => 'Square', 'uzbek' => 'Kvadrat', 'pronunciation' => '/skweər/', 'emoji' => '⬜', 'example' => 'The box is a square.', 'exampleTranslation' => 'Quti kvadrat shaklida.'],
                ['english' => 'Triangle', 'uzbek' => 'Uchburchak', 'pronunciation' => '/ˈtraɪæŋɡl/', 'emoji' => '🔺', 'example' => 'The roof is a triangle.', 'exampleTranslation' => 'Tom uchburchak shaklida.'],
                ['english' => 'Rectangle', 'uzbek' => 'To\'g\'ri to\'rtburchak', 'pronunciation' => '/ˈrektæŋɡl/', 'emoji' => '▬', 'example' => 'The door is a rectangle.', 'exampleTranslation' => 'Eshik to\'g\'ri to\'rtburchak shaklida.'],
                ['english' => 'Star', 'uzbek' => 'Yulduz', 'pronunciation' => '/stɑːr/', 'emoji' => '⭐', 'example' => 'I see a star.', 'exampleTranslation' => 'Men yulduz ko\'ryapman.'],
                ['english' => 'Heart', 'uzbek' => 'Yurak', 'pronunciation' => '/hɑːrt/', 'emoji' => '❤️', 'example' => 'I draw a heart.', 'exampleTranslation' => 'Men yurak chizaman.'],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Doira" shaklini ingliz tilida qanday aytasiz?', 'options' => ['Square', 'Circle', 'Triangle', 'Star'], 'correctAnswer' => 1, 'explanation' => '"Doira" ingliz tilida "Circle" deb aytiladi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Triangle" shaklning o\'zbekcha tarjimasi qanday?', 'options' => ['Kvadrat', 'Doira', 'Uchburchak', 'Yulduz'], 'correctAnswer' => 2, 'explanation' => '"Triangle" - "Uchburchak" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Quti qaysi shaklda?', 'options' => ['Circle', 'Square', 'Triangle', 'Star'], 'correctAnswer' => 1, 'explanation' => 'Quti kvadrat (Square) shaklida.'],
                ['type' => 'multiple_choice', 'question' => '"Star" shaklning o\'zbekcha tarjimasi qanday?', 'options' => ['Yurak', 'Yulduz', 'Doira', 'Kvadrat'], 'correctAnswer' => 1, 'explanation' => '"Star" - "Yulduz" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getDescribingObjectsContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Big', 'uzbek' => 'Katta', 'pronunciation' => '/bɪɡ/', 'emoji' => '🐘', 'example' => 'The elephant is big.', 'exampleTranslation' => 'Fil katta.'],
                ['english' => 'Small', 'uzbek' => 'Kichik', 'pronunciation' => '/smɔːl/', 'emoji' => '🐜', 'example' => 'The ant is small.', 'exampleTranslation' => 'Chumoli kichik.'],
                ['english' => 'Long', 'uzbek' => 'Uzun', 'pronunciation' => '/lɔːŋ/', 'emoji' => '🐍', 'example' => 'The snake is long.', 'exampleTranslation' => 'Ilon uzun.'],
                ['english' => 'Short', 'uzbek' => 'Qisqa', 'pronunciation' => '/ʃɔːrt/', 'emoji' => '📏', 'example' => 'The pencil is short.', 'exampleTranslation' => 'Qalam qisqa.'],
            ],
            'explanation' => [
                [
                    'title' => '1. Sifatlar (Adjectives)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Sifatlar</strong> (Adjectives) narsa-buyumlarning qandayligini (rangi, shakli, hajmi) tasvirlash uchun ishlatiladi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Foydali Sifatlar',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Misol'],
                            'rows' => [
                                ['<b>Big</b>', 'Katta', 'A big house (Katta uy)'],
                                ['<b>Small</b>', 'Kichik', 'A small cat (Kichkina mushuk)'],
                                ['<b>Long</b>', 'Uzun', 'A long road (Uzun yo\'l)'],
                                ['<b>Short</b>', 'Qisqa', 'A short pencil (Qisqa qalam)'],
                                ['<b>Red</b>', 'Qizil', 'A red apple (Qizil olma)'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Ingliz tilida sifatlar <strong>o\'zgarmaydi</strong>. "Katta uylar" demoqchi bo\'lsangiz ham "Big houses" deysiz (Bigs emas!).'
                        ]
                    ]
                ],
                [
                    'title' => '2. Sifatlarning O\'rni',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Sifatlar gapda ikki xil o\'rinda kelishi mumkin:'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Gap Tuzilishi',
                            'headers' => ['Qoida', 'Misol (Inglizcha)', 'Misol (O\'zbekcha)'],
                            'rows' => [
                                ['Otdan oldin', 'It is a <b>red</b> car.', 'Bu qizil mashina.'],
                                ['To Be dan keyin', 'The car is <b>red</b>.', 'Mashina qizil.'],
                            ]
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Ehtiyot bo\'ling!',
                            'items' => [
                                ['bad' => 'A car red.', 'good' => 'A <b>red</b> car.'],
                                ['bad' => 'The apple is a red.', 'good' => 'The apple is <b>red</b>.'],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => '3. Sifatlar Tartibi',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Agar bitta otga bir nechta sifat ishlatsangiz, odatda <strong>Hajm + Rang</strong> tartibida keladi.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Hajm va Rang',
                            'items' => [
                                ['en' => 'A big red ball.', 'uz' => 'Katta qizil to\'p.'],
                                ['en' => 'A small green box.', 'uz' => 'Kichkina yashil quti.'],
                                ['en' => 'A long yellow snake.', 'uz' => 'Uzun sariq ilon.'],
                            ]
                        ]
                    ]
                ]
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Katta qizil to\'p" ni ingliz tilida qanday aytasiz?', 'options' => ['A red big ball', 'A big red ball', 'A ball big red', 'Big red a ball'], 'correctAnswer' => 1, 'explanation' => 'To\'g\'ri tartib: "A big red ball".'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Small" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Katta', 'Kichik', 'Uzun', 'Qisqa'], 'correctAnswer' => 1, 'explanation' => '"Small" - "Kichik" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Uzun" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Short', 'Long', 'Big', 'Small'], 'correctAnswer' => 1, 'explanation' => '"Uzun" ingliz tilida "Long" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Quyidagi gapda bo\'sh joyni to\'ldiring: "The cat is _____."', 'questionTranslation' => 'Mushuk _____.', 'options' => ['small', 'a small', 'the small', 'smalls'], 'correctAnswer' => 0, 'explanation' => 'Sifat yolg\'iz ishlatilganda artikl kerak emas: "The cat is small."'],
            ],
        ];
    }

    private function getModule3TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Red" rangning o\'zbekcha tarjimasi qanday?', 'options' => ['Ko\'k', 'Sariq', 'Qizil', 'Yashil'], 'correctAnswer' => 2, 'explanation' => '"Red" - "Qizil" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Doira" shaklini ingliz tilida qanday aytasiz?', 'options' => ['Square', 'Triangle', 'Circle', 'Rectangle'], 'correctAnswer' => 2, 'explanation' => '"Doira" ingliz tilida "Circle" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Osmon qaysi rangda?', 'options' => ['Green', 'Blue', 'Yellow', 'Red'], 'correctAnswer' => 1, 'explanation' => 'Osmon ko\'k (Blue) rangda.'],
                ['type' => 'multiple_choice', 'question' => '"Kichik" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Big', 'Long', 'Short', 'Small'], 'correctAnswer' => 3, 'explanation' => '"Kichik" ingliz tilida "Small" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Triangle" shaklning o\'zbekcha tarjimasi qanday?', 'options' => ['Kvadrat', 'Uchburchak', 'Doira', 'Yulduz'], 'correctAnswer' => 1, 'explanation' => '"Triangle" - "Uchburchak" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Binafsha" rangni ingliz tilida qanday aytasiz?', 'options' => ['Pink', 'Purple', 'Brown', 'Orange'], 'correctAnswer' => 1, 'explanation' => '"Binafsha" ingliz tilida "Purple" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Big" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Kichik', 'Katta', 'Uzun', 'Qisqa'], 'correctAnswer' => 1, 'explanation' => '"Big" - "Katta" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    // ==================== MODULE 4: FAMILY & FRIENDS ====================

    private function getFamilyMembersContent(): array
    {
        return [
            'totalSteps' => 14,
            'words' => [
                ['english' => 'Mother', 'uzbek' => 'Ona', 'pronunciation' => '/ˈmʌðər/', 'emoji' => '👩', 'example' => 'My mother is kind.', 'exampleTranslation' => 'Mening onam mehribon.'],
                ['english' => 'Father', 'uzbek' => 'Ota', 'pronunciation' => '/ˈfɑːðər/', 'emoji' => '👨', 'example' => 'My father is tall.', 'exampleTranslation' => 'Mening otam baland bo\'yli.'],
                ['english' => 'Sister', 'uzbek' => 'Opa/Singil', 'pronunciation' => '/ˈsɪstər/', 'emoji' => '👧', 'example' => 'I have a sister.', 'exampleTranslation' => 'Mening singlim bor.'],
                ['english' => 'Brother', 'uzbek' => 'Aka/Uka', 'pronunciation' => '/ˈbrʌðər/', 'emoji' => '👦', 'example' => 'He is my brother.', 'exampleTranslation' => 'U mening ukam.'],
                ['english' => 'Grandmother', 'uzbek' => 'Buvi', 'pronunciation' => '/ˈɡrænˌmʌðər/', 'emoji' => '👵', 'example' => 'My grandmother is 70.', 'exampleTranslation' => 'Buvim 70 yoshda.'],
                ['english' => 'Grandfather', 'uzbek' => 'Bobo', 'pronunciation' => '/ˈɡrændˌfɑːðər/', 'emoji' => '👴', 'example' => 'Grandfather tells stories.', 'exampleTranslation' => 'Bobom ertak aytib beradi.'],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Ona" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Father', 'Mother', 'Sister', 'Brother'], 'correctAnswer' => 1, 'explanation' => '"Ona" ingliz tilida "Mother" deb aytiladi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Father" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Ona', 'Ota', 'Aka', 'Bobo'], 'correctAnswer' => 1, 'explanation' => '"Father" - "Ota" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Buvi" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Grandfather', 'Grandmother', 'Mother', 'Sister'], 'correctAnswer' => 1, 'explanation' => '"Buvi" ingliz tilida "Grandmother" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Sister" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Aka', 'Uka', 'Opa/Singil', 'Ona'], 'correctAnswer' => 2, 'explanation' => '"Sister" - "Opa/Singil" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getMyFriendsContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Friend', 'uzbek' => 'Do\'st', 'pronunciation' => '/frend/', 'emoji' => '🧑‍🤝‍🧑', 'example' => 'He is my best friend.', 'exampleTranslation' => 'U mening eng yaqin do\'stim.'],
                ['english' => 'Classmate', 'uzbek' => 'Sinfdosh', 'pronunciation' => '/ˈklæsmeɪt/', 'emoji' => '👨‍🎓', 'example' => 'She is my classmate.', 'exampleTranslation' => 'U mening sinfdoshim.'],
                ['english' => 'Neighbor', 'uzbek' => 'Qo\'shni', 'pronunciation' => '/ˈneɪbər/', 'emoji' => '🏠', 'example' => 'Our neighbor is friendly.', 'exampleTranslation' => 'Bizning qo\'shnimiz do\'stona.'],
                ['english' => 'Teacher', 'uzbek' => 'O\'qituvchi', 'pronunciation' => '/ˈtiːtʃər/', 'emoji' => '👨‍🏫', 'example' => 'My teacher is smart.', 'exampleTranslation' => 'Mening o\'qituvchim aqlli.'],
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Do\'st" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Teacher', 'Friend', 'Neighbor', 'Classmate'], 'correctAnswer' => 1, 'explanation' => '"Do\'st" ingliz tilida "Friend" deb aytiladi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Classmate" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Do\'st', 'Sinfdosh', 'Qo\'shni', 'O\'qituvchi'], 'correctAnswer' => 1, 'explanation' => '"Classmate" - "Sinfdosh" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"O\'qituvchi" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Friend', 'Student', 'Teacher', 'Neighbor'], 'correctAnswer' => 2, 'explanation' => '"O\'qituvchi" ingliz tilida "Teacher" deb aytiladi.'],
            ],
        ];
    }

    private function getDescribingPeopleContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Tall', 'uzbek' => 'Baland', 'pronunciation' => '/tɔːl/', 'emoji' => '📏', 'example' => 'My father is tall.', 'exampleTranslation' => 'Otam baland bo\'yli.'],
                ['english' => 'Short', 'uzbek' => 'Past', 'pronunciation' => '/ʃɔːrt/', 'emoji' => '📐', 'example' => 'My sister is short.', 'exampleTranslation' => 'Singlim past bo\'yli.'],
                ['english' => 'Young', 'uzbek' => 'Yosh', 'pronunciation' => '/jʌŋ/', 'emoji' => '👶', 'example' => 'The baby is young.', 'exampleTranslation' => 'Chaqaloq yosh.'],
                ['english' => 'Old', 'uzbek' => 'Keksa', 'pronunciation' => '/oʊld/', 'emoji' => '👴', 'example' => 'Grandfather is old.', 'exampleTranslation' => 'Bobom keksa.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Tall" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Past', 'Baland', 'Yosh', 'Keksa'], 'correctAnswer' => 1, 'explanation' => '"Tall" - "Baland" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Yosh" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Old', 'Young', 'Tall', 'Short'], 'correctAnswer' => 1, 'explanation' => '"Yosh" ingliz tilida "Young" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Old" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Yosh', 'Keksa', 'Baland', 'Past'], 'correctAnswer' => 1, 'explanation' => '"Old" - "Keksa" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getPossessivesContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'My', 'uzbek' => 'Mening', 'pronunciation' => '/maɪ/', 'emoji' => '👆', 'example' => 'This is my book.', 'exampleTranslation' => 'Bu mening kitobim.'],
                ['english' => 'Your', 'uzbek' => 'Sening/Sizning', 'pronunciation' => '/jɔːr/', 'emoji' => '👉', 'example' => 'Is this your pen?', 'exampleTranslation' => 'Bu sening ruchkangmi?'],
                ['english' => 'His', 'uzbek' => 'Uning (erkak)', 'pronunciation' => '/hɪz/', 'emoji' => '👨', 'example' => 'His name is Tom.', 'exampleTranslation' => 'Uning ismi Tom.'],
                ['english' => 'Her', 'uzbek' => 'Uning (ayol)', 'pronunciation' => '/hɜːr/', 'emoji' => '👩', 'example' => 'Her bag is red.', 'exampleTranslation' => 'Uning sumkasi qizil.'],
            ],
            'explanation' => [
                [
                    'title' => '1. Egalik Olmoshlari (Possessive Adjectives)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Egalik olmoshlari</strong> ingliz tilida narsaning kimga tegishli ekanligini bildiradi (Mening, Sening, Uning...).'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Egalik Olmoshlari Jadvali',
                            'headers' => ['Olmosh (Kishilik)', 'Olmosh (Egalik)', 'O\'zbekcha', 'Misol'],
                            'rows' => [
                                ['I (Men)', '<b>My</b>', 'Mening', 'My book (Kitobim)'],
                                ['You (Sen)', '<b>Your</b>', 'Sening', 'Your pen (Ruchkang)'],
                                ['He (U - o\'g\'il)', '<b>His</b>', 'Uning', 'His car (Mashinasi)'],
                                ['She (U - qiz)', '<b>Her</b>', 'Uning', 'Her bag (Sumkasi)'],
                                ['It (U - narsa)', '<b>Its</b>', 'Uning', 'Its color (Rangi)'],
                                ['We (Biz)', '<b>Our</b>', 'Bizning', 'Our house (Uyimiz)'],
                                ['They (Ular)', '<b>Their</b>', 'Ularning', 'Their dog (Iti)'],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => '2. To\'g\'ri Ishlatish',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida <strong>His</strong> (erkaklar uchun) va <strong>Her</strong> (ayollar uchun) farqlanadi. Bunga e\'tiborli bo\'ling!'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Misollar',
                            'items' => [
                                ['en' => 'This is Tom. His name is Tom.', 'uz' => 'Bu Tom. Uning ismi Tom.'],
                                ['en' => 'This is Anna. Her name is Anna.', 'uz' => 'Bu Anna. Uning ismi Anna.'],
                            ]
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Tez-tez uchraydigan xatolar',
                            'items' => [
                                ['bad' => 'He name is Tom.', 'good' => '<b>His</b> name is Tom.'],
                                ['bad' => 'She name is Anna.', 'good' => '<b>Her</b> name is Anna.'],
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Mening" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Your', 'My', 'His', 'Her'], 'correctAnswer' => 1, 'explanation' => '"Mening" ingliz tilida "My" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "_____ name is Anna." (Anna - qiz)', 'options' => ['His', 'Her', 'My', 'Your'], 'correctAnswer' => 1, 'explanation' => 'Anna qiz, shuning uchun "Her" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => '"His" qachon ishlatiladi?', 'options' => ['Ayollar uchun', 'Erkaklar uchun', 'Hayvonlar uchun', 'Narsalar uchun'], 'correctAnswer' => 1, 'explanation' => '"His" erkaklar uchun ishlatiladi.'],
            ],
        ];
    }

    private function getModule4TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Mother" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Ota', 'Ona', 'Opa', 'Buvi'], 'correctAnswer' => 1, 'explanation' => '"Mother" - "Ona" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Do\'st" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Family', 'Friend', 'Teacher', 'Brother'], 'correctAnswer' => 1, 'explanation' => '"Do\'st" ingliz tilida "Friend" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Tall" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Past', 'Baland', 'Yosh', 'Keksa'], 'correctAnswer' => 1, 'explanation' => '"Tall" - "Baland" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "This is _____ book." (Mening)', 'options' => ['your', 'my', 'his', 'her'], 'correctAnswer' => 1, 'explanation' => '"Mening kitobim" = "my book".'],
                ['type' => 'multiple_choice', 'question' => '"Grandfather" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Buvi', 'Bobo', 'Ota', 'Aka'], 'correctAnswer' => 1, 'explanation' => '"Grandfather" - "Bobo" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Young" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Keksa', 'Yosh', 'Baland', 'Past'], 'correctAnswer' => 1, 'explanation' => '"Young" - "Yosh" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    // ==================== MODULE 5: DAYS & MONTHS ====================

    private function getDaysOfWeekContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Monday', 'uzbek' => 'Dushanba', 'pronunciation' => '/ˈmʌndeɪ/', 'emoji' => '1️⃣', 'example' => 'Monday is the first day.', 'exampleTranslation' => 'Dushanba birinchi kun.'],
                ['english' => 'Tuesday', 'uzbek' => 'Seshanba', 'pronunciation' => '/ˈtuːzdeɪ/', 'emoji' => '2️⃣', 'example' => 'I go to school on Tuesday.', 'exampleTranslation' => 'Men seshanba kuni maktabga boraman.'],
                ['english' => 'Wednesday', 'uzbek' => 'Chorshanba', 'pronunciation' => '/ˈwenzdeɪ/', 'emoji' => '3️⃣', 'example' => 'Wednesday is in the middle.', 'exampleTranslation' => 'Chorshanba o\'rtada.'],
                ['english' => 'Thursday', 'uzbek' => 'Payshanba', 'pronunciation' => '/ˈθɜːrzdeɪ/', 'emoji' => '4️⃣', 'example' => 'I have English on Thursday.', 'exampleTranslation' => 'Payshanba kuni ingliz tilim bor.'],
                ['english' => 'Friday', 'uzbek' => 'Juma', 'pronunciation' => '/ˈfraɪdeɪ/', 'emoji' => '5️⃣', 'example' => 'Friday is my favorite day.', 'exampleTranslation' => 'Juma mening sevimli kunim.'],
                ['english' => 'Saturday', 'uzbek' => 'Shanba', 'pronunciation' => '/ˈsætərdeɪ/', 'emoji' => '6️⃣', 'example' => 'Saturday is a weekend.', 'exampleTranslation' => 'Shanba dam olish kuni.'],
                ['english' => 'Sunday', 'uzbek' => 'Yakshanba', 'pronunciation' => '/ˈsʌndeɪ/', 'emoji' => '7️⃣', 'example' => 'Sunday is a rest day.', 'exampleTranslation' => 'Yakshanba dam olish kuni.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Dushanba" kunini ingliz tilida qanday aytasiz?', 'options' => ['Sunday', 'Monday', 'Tuesday', 'Friday'], 'correctAnswer' => 1, 'explanation' => '"Dushanba" ingliz tilida "Monday" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Friday" kunining o\'zbekcha tarjimasi qanday?', 'options' => ['Shanba', 'Juma', 'Payshanba', 'Chorshanba'], 'correctAnswer' => 1, 'explanation' => '"Friday" - "Juma" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Haftada necha kun?', 'options' => ['Five', 'Six', 'Seven', 'Eight'], 'correctAnswer' => 2, 'explanation' => 'Haftada 7 (Seven) kun bor.'],
            ],
        ];
    }

    private function getMonthsContent(): array
    {
        return [
            'totalSteps' => 14,
            'words' => [
                ['english' => 'January', 'uzbek' => 'Yanvar', 'pronunciation' => '/ˈdʒænjueri/', 'emoji' => '❄️', 'example' => 'January is the first month.', 'exampleTranslation' => 'Yanvar birinchi oy.'],
                ['english' => 'February', 'uzbek' => 'Fevral', 'pronunciation' => '/ˈfebrueri/', 'emoji' => '💕', 'example' => 'February is short.', 'exampleTranslation' => 'Fevral qisqa oy.'],
                ['english' => 'March', 'uzbek' => 'Mart', 'pronunciation' => '/mɑːrtʃ/', 'emoji' => '🌸', 'example' => 'March is in spring.', 'exampleTranslation' => 'Mart bahorda.'],
                ['english' => 'April', 'uzbek' => 'Aprel', 'pronunciation' => '/ˈeɪprəl/', 'emoji' => '🌷', 'example' => 'April has rain.', 'exampleTranslation' => 'Aprelda yomg\'ir yog\'adi.'],
                ['english' => 'May', 'uzbek' => 'May', 'pronunciation' => '/meɪ/', 'emoji' => '🌼', 'example' => 'May is warm.', 'exampleTranslation' => 'May iliq.'],
                ['english' => 'June', 'uzbek' => 'Iyun', 'pronunciation' => '/dʒuːn/', 'emoji' => '☀️', 'example' => 'June starts summer.', 'exampleTranslation' => 'Iyunda yoz boshlanadi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Yanvar" oyini ingliz tilida qanday aytasiz?', 'options' => ['June', 'January', 'July', 'March'], 'correctAnswer' => 1, 'explanation' => '"Yanvar" ingliz tilida "January" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Yilda necha oy?', 'options' => ['Ten', 'Eleven', 'Twelve', 'Thirteen'], 'correctAnswer' => 2, 'explanation' => 'Yilda 12 (Twelve) oy bor.'],
                ['type' => 'multiple_choice', 'question' => '"March" oyining o\'zbekcha tarjimasi qanday?', 'options' => ['May', 'Mart', 'Aprel', 'Fevral'], 'correctAnswer' => 1, 'explanation' => '"March" - "Mart" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getSeasonsContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'Spring', 'uzbek' => 'Bahor', 'pronunciation' => '/sprɪŋ/', 'emoji' => '🌸', 'example' => 'Spring is warm.', 'exampleTranslation' => 'Bahor iliq.'],
                ['english' => 'Summer', 'uzbek' => 'Yoz', 'pronunciation' => '/ˈsʌmər/', 'emoji' => '☀️', 'example' => 'Summer is hot.', 'exampleTranslation' => 'Yoz issiq.'],
                ['english' => 'Autumn', 'uzbek' => 'Kuz', 'pronunciation' => '/ˈɔːtəm/', 'emoji' => '🍂', 'example' => 'Autumn has colorful leaves.', 'exampleTranslation' => 'Kuzda barglar rangli.'],
                ['english' => 'Winter', 'uzbek' => 'Qish', 'pronunciation' => '/ˈwɪntər/', 'emoji' => '❄️', 'example' => 'Winter is cold.', 'exampleTranslation' => 'Qish sovuq.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Yoz" faslini ingliz tilida qanday aytasiz?', 'options' => ['Spring', 'Summer', 'Autumn', 'Winter'], 'correctAnswer' => 1, 'explanation' => '"Yoz" ingliz tilida "Summer" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Winter" faslining o\'zbekcha tarjimasi qanday?', 'options' => ['Bahor', 'Yoz', 'Kuz', 'Qish'], 'correctAnswer' => 3, 'explanation' => '"Winter" - "Qish" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Yilda nechta fasl bor?', 'options' => ['Two', 'Three', 'Four', 'Five'], 'correctAnswer' => 2, 'explanation' => 'Yilda 4 (Four) ta fasl bor.'],
            ],
        ];
    }

    private function getDatesContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'First', 'uzbek' => 'Birinchi', 'pronunciation' => '/fɜːrst/', 'emoji' => '1️⃣', 'example' => 'January 1st is New Year.', 'exampleTranslation' => 'Yanvarning 1-si Yangi yil.'],
                ['english' => 'Second', 'uzbek' => 'Ikkinchi', 'pronunciation' => '/ˈsekənd/', 'emoji' => '2️⃣', 'example' => 'March 2nd is my birthday.', 'exampleTranslation' => 'Martning 2-si mening tug\'ilgan kunim.'],
                ['english' => 'Third', 'uzbek' => 'Uchinchi', 'pronunciation' => '/θɜːrd/', 'emoji' => '3️⃣', 'example' => 'The third month is March.', 'exampleTranslation' => 'Uchinchi oy Mart.'],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - O'ZBEK TILIDA
            'explanation' => [
                [
                    'title' => '1. Vaqt Predloglari (Prepositions of Time)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Vaqtni to\'g\'ri ifodalash uchun <strong>IN, ON, AT</strong> predloglaridan foydalanamiz.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Qo\'llanish Qoidalari',
                            'headers' => ['Predlog', 'Qachon Ishlatiladi', 'Misol'],
                            'rows' => [
                                ['<b>IN</b>', 'Oylar, Yillar, Fasllar', 'In January (Yanvarda), In 2024, In Summer'],
                                ['<b>ON</b>', 'Hafta kunlari, Sanalar', 'On Monday (Dushanbada), On May 1st'],
                                ['<b>AT</b>', 'Aniq soatlar', 'At 5 o\'clock (Soat 5 da)'],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => '2. Sanalarni Aytish',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Sanalarda har doim <strong>tartib sonlardan</strong> (First, Second, Third...) foydalanamiz.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Yozilishi va Aytilishi',
                            'items' => [
                                ['en' => 'March 1st', 'uz' => 'The first of March.'],
                                ['en' => 'May 21st', 'uz' => 'The twenty-first of May.'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Sanadan oldin har doim <strong>"ON"</strong> ishlatiladi: "My birthday is <strong>on</strong> March 2nd."'
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Birinchi" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['One', 'First', 'Second', 'Third'], 'correctAnswer' => 1, 'explanation' => '"Birinchi" ingliz tilida "First" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Second" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Birinchi', 'Ikkinchi', 'Uchinchi', 'To\'rtinchi'], 'correctAnswer' => 1, 'explanation' => '"Second" - "Ikkinchi" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getModule5TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Monday" kunining o\'zbekcha tarjimasi qanday?', 'options' => ['Yakshanba', 'Dushanba', 'Seshanba', 'Juma'], 'correctAnswer' => 1, 'explanation' => '"Monday" - "Dushanba" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Bahor" faslini ingliz tilida qanday aytasiz?', 'options' => ['Winter', 'Summer', 'Spring', 'Autumn'], 'correctAnswer' => 2, 'explanation' => '"Bahor" ingliz tilida "Spring" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"February" oyining o\'zbekcha tarjimasi qanday?', 'options' => ['Yanvar', 'Fevral', 'Mart', 'Aprel'], 'correctAnswer' => 1, 'explanation' => '"February" - "Fevral" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Haftaning oxirgi kuni qaysi?', 'options' => ['Friday', 'Saturday', 'Sunday', 'Monday'], 'correctAnswer' => 2, 'explanation' => 'Haftaning oxirgi kuni Sunday (Yakshanba).'],
                ['type' => 'multiple_choice', 'question' => '"Summer" faslining o\'zbekcha tarjimasi qanday?', 'options' => ['Bahor', 'Yoz', 'Kuz', 'Qish'], 'correctAnswer' => 1, 'explanation' => '"Summer" - "Yoz" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    // ==================== MODULE 6: TIME & DAILY ROUTINE ====================

    private function getTellingTimeContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'O\'clock', 'uzbek' => 'Soat', 'pronunciation' => '/əˈklɒk/', 'emoji' => '🕐', 'example' => 'It\'s three o\'clock.', 'exampleTranslation' => 'Soat uchda.'],
                ['english' => 'Half past', 'uzbek' => 'Yarim', 'pronunciation' => '/hɑːf pɑːst/', 'emoji' => '🕧', 'example' => 'It\'s half past two.', 'exampleTranslation' => 'Soat ikki yarim.'],
                ['english' => 'Morning', 'uzbek' => 'Ertalab', 'pronunciation' => '/ˈmɔːrnɪŋ/', 'emoji' => '🌅', 'example' => 'I wake up in the morning.', 'exampleTranslation' => 'Men ertalab uyg\'onaman.'],
                ['english' => 'Afternoon', 'uzbek' => 'Tushdan keyin', 'pronunciation' => '/ˌɑːftərˈnuːn/', 'emoji' => '☀️', 'example' => 'I study in the afternoon.', 'exampleTranslation' => 'Men tushdan keyin o\'qiyman.'],
                ['english' => 'Evening', 'uzbek' => 'Kechqurun', 'pronunciation' => '/ˈiːvnɪŋ/', 'emoji' => '🌆', 'example' => 'I rest in the evening.', 'exampleTranslation' => 'Men kechqurun dam olaman.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Soat uchda" ni ingliz tilida qanday aytasiz?', 'options' => ['Three o\'clock', 'Half past three', 'Three thirty', 'Three morning'], 'correctAnswer' => 0, 'explanation' => '"Soat uchda" = "Three o\'clock".'],
                ['type' => 'multiple_choice', 'question' => '"Morning" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Kechqurun', 'Ertalab', 'Tushdan keyin', 'Tun'], 'correctAnswer' => 1, 'explanation' => '"Morning" - "Ertalab" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getDailyActivitiesContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Wake up', 'uzbek' => 'Uyg\'onish', 'pronunciation' => '/weɪk ʌp/', 'emoji' => '⏰', 'example' => 'I wake up at 7.', 'exampleTranslation' => 'Men soat 7 da uyg\'onaman.'],
                ['english' => 'Eat breakfast', 'uzbek' => 'Nonushta qilish', 'pronunciation' => '/iːt ˈbrekfəst/', 'emoji' => '🍳', 'example' => 'I eat breakfast at 8.', 'exampleTranslation' => 'Men soat 8 da nonushta qilaman.'],
                ['english' => 'Go to school', 'uzbek' => 'Maktabga borish', 'pronunciation' => '/ɡoʊ tuː skuːl/', 'emoji' => '🏫', 'example' => 'I go to school at 9.', 'exampleTranslation' => 'Men soat 9 da maktabga boraman.'],
                ['english' => 'Sleep', 'uzbek' => 'Uxlash', 'pronunciation' => '/sliːp/', 'emoji' => '😴', 'example' => 'I sleep at 10 PM.', 'exampleTranslation' => 'Men kechqurun soat 10 da uxlayman.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Uyg\'onish" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Sleep', 'Wake up', 'Eat', 'Go'], 'correctAnswer' => 1, 'explanation' => '"Uyg\'onish" ingliz tilida "Wake up" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Eat breakfast" iborasining o\'zbekcha tarjimasi qanday?', 'options' => ['Uxlash', 'Nonushta qilish', 'Maktabga borish', 'Uyg\'onish'], 'correctAnswer' => 1, 'explanation' => '"Eat breakfast" - "Nonushta qilish" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getPresentSimpleContent(): array
    {
        return [
            'totalSteps' => 20,
            // KENGAYTIRILGAN GRAMMATIKA TUSHUNTIRISHI - 3 TA SAHIFA
            'explanation' => [
                [
                    'title' => '1. Present Simple (Oddiy hozirgi zamon)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Present Simple - ingliz tilida eng ko\'p ishlatiladigan zamon. U har doim takrorlanadigan ishlar, odatlar va umumiy haqiqatlar uchun ishlatiladi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Ishlatilish Holatlari',
                            'headers' => ['Ishlatilish holati', 'Misol (English)', 'Tarjima (O\'zbek)'],
                            'rows' => [
                                ['Takrorlanadigan ish', 'I wake up at 7 AM every day.', 'Men har kuni soat 7 da turaman.'],
                                ['Odatlar (Habits)', 'She drinks coffee in the morning.', 'U ertalab kofe ichadi.'],
                                ['Umumiy haqiqat', 'The sun rises in the east.', 'Quyosh sharqdan chiqadi.'],
                                ['Jadvallar', 'The train leaves at 9 PM.', 'Poyezd soat 9 da jo\'naydi.'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Present Simple asosan "always, usually, often, sometimes, never" kabi so\'zlar bilan ishlatiladi.'
                        ]
                    ]
                ],
                [
                    'title' => '2. Fe\'l qo\'shimchasi: -s / -es',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'He, She, It (uchinchi shaxs birlik) uchun fe\'lga <strong>-s</strong> yoki <strong>-es</strong> qo\'shiladi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Qo\'shimcha Qo\'shish Qoidalari',
                            'headers' => ['Qoida', 'Fe\'l', 'Natija'],
                            'rows' => [
                                ['Ko\'p fe\'llarga -s qo\'shiladi', 'work, play', 'works, plays'],
                                ['-s, -sh, -ch, -x, -o bilan tugasa -> -es', 'go, watch', 'goes, watches'],
                                ['Undosh + y bilan tugasa -> ies', 'study, fly', 'studies, flies'],
                                ['Unli + y bilan tugasa -> -s', 'play, buy', 'plays, buys'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => 'E\'tibor bering: <strong>have</strong> fe\'li <strong>has</strong> bo\'ladi (He has a car).'
                        ]
                    ]
                ],
                [
                    'title' => '3. Inkor va Savol Shakllari',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Inkor gap uchun <strong>don\'t / doesn\'t</strong>, savol uchun <strong>Do / Does</strong> yordamchi fe\'llaridan foydalanamiz.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Gap Tuzilishi',
                            'headers' => ['Tur', 'Struktura', 'Misol'],
                            'rows' => [
                                ['Tasdiq (I/You/We)', 'Subyekt + Fe\'l', 'I work.'],
                                ['Tasdiq (He/She/It)', 'Subyekt + Fe\'l + s/es', 'She works.'],
                                ['Inkor (I/You/We)', 'Subyekt + don\'t + Fe\'l', 'I don\'t work.'],
                                ['Inkor (He/She/It)', 'Subyekt + doesn\'t + Fe\'l', 'He doesn\'t work.'],
                                ['Savol (I/You/We)', 'Do + Subyekt + Fe\'l?', 'Do you work?'],
                                ['Savol (He/She/It)', 'Does + Subyekt + Fe\'l?', 'Does she work?'],
                            ]
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Muhim Qoida!',
                            'items' => [
                                ['bad' => 'Does she works?', 'good' => 'Does she <b>work</b>? (s tushib qoladi)'],
                                ['bad' => 'She doesn\'t likes.', 'good' => 'She doesn\'t <b>like</b>. (s tushib qoladi)'],
                            ]
                        ]
                    ]
                ]
            ],

            // MASHQLAR - KO'PROQ MASHQLAR
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'To\'g\'ri javobni tanlang: "She _____ to school every day."', 'options' => ['go', 'goes', 'going', 'went'], 'correctAnswer' => 1, 'explanation' => '"She" bilan fe\'lga -s qo\'shiladi: "goes".'],
                ['type' => 'multiple_choice', 'question' => 'To\'g\'ri javobni tanlang: "They _____ football on Sundays."', 'options' => ['plays', 'play', 'playing', 'played'], 'correctAnswer' => 1, 'explanation' => '"They" bilan fe\'l o\'zgarmasdan ishlatiladi: "play".'],
                ['type' => 'multiple_choice', 'question' => 'To\'g\'ri javobni tanlang: "He _____ TV in the evening."', 'options' => ['watch', 'watchs', 'watches', 'watching'], 'correctAnswer' => 2, 'explanation' => '"watch" fe\'liga -es qo\'shiladi chunki u -ch bilan tugaydi: "watches".'],
                ['type' => 'multiple_choice', 'question' => 'To\'g\'ri javobni tanlang: "My father _____ a car."', 'options' => ['have', 'haves', 'has', 'having'], 'correctAnswer' => 2, 'explanation' => '"have" fe\'li "He/She/It" bilan "has" bo\'ladi.'],
                ['type' => 'multiple_choice', 'question' => 'Inkor gapni tanlang:', 'options' => ['She don\'t like tea.', 'She doesn\'t likes tea.', 'She doesn\'t like tea.', 'She not like tea.'], 'correctAnswer' => 2, 'explanation' => '"She" bilan "doesn\'t" ishlatiladi va fe\'l oddiy shaklda qoladi.'],
            ],
            // YAKUNIY TEST - KO'PROQ SAVOLLAR
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "I _____ breakfast at 8 AM."', 'options' => ['eats', 'eat', 'eating', 'ate'], 'correctAnswer' => 1, 'explanation' => '"I" bilan fe\'l o\'zgarmasdan ishlatiladi: "eat".'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "He _____ English very well."', 'options' => ['speak', 'speaks', 'speaking', 'spoke'], 'correctAnswer' => 1, 'explanation' => '"He" bilan fe\'lga -s qo\'shiladi: "speaks".'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "The train _____ at 6 PM."', 'options' => ['leave', 'leaves', 'leaving', 'left'], 'correctAnswer' => 1, 'explanation' => 'Jadvallar uchun Present Simple ishlatiladi: "leaves".'],
                ['type' => 'multiple_choice', 'question' => 'Qaysi gap TO\'G\'RI?', 'options' => ['Does he works here?', 'Does he work here?', 'Do he work here?', 'He does works here?'], 'correctAnswer' => 1, 'explanation' => '"Does" ishlatilganda fe\'l oddiy shaklda qoladi: "Does he work?"'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "My mother _____ delicious food."', 'options' => ['cook', 'cooks', 'cooking', 'cooked'], 'correctAnswer' => 1, 'explanation' => '"My mother" = "She", shuning uchun fe\'lga -s qo\'shiladi: "cooks".'],
                ['type' => 'multiple_choice', 'question' => '"The sun rises in the east" - bu gap nimani ifodalaydi?', 'options' => ['Odatiy ish', 'Umumiy haqiqat', 'Kelajak reja', 'O\'tgan voqea'], 'correctAnswer' => 1, 'explanation' => 'Umumiy haqiqatlar (facts) uchun Present Simple ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "Water _____ at 100 degrees."', 'options' => ['boil', 'boils', 'boiling', 'boiled'], 'correctAnswer' => 1, 'explanation' => '"Water" = "It", umumiy haqiqat uchun Present Simple: "boils".'],
                ['type' => 'multiple_choice', 'question' => 'Qaysi chastotlik ravishi "hech qachon" ma\'nosini beradi?', 'options' => ['always', 'usually', 'sometimes', 'never'], 'correctAnswer' => 3, 'explanation' => '"Never" = "hech qachon" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getFrequencyContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'Always', 'uzbek' => 'Doimo', 'pronunciation' => '/ˈɔːlweɪz/', 'emoji' => '✅', 'example' => 'I always wake up early.', 'exampleTranslation' => 'Men doimo erta turaman.'],
                ['english' => 'Sometimes', 'uzbek' => 'Ba\'zan', 'pronunciation' => '/ˈsʌmtaɪmz/', 'emoji' => '🔄', 'example' => 'I sometimes eat pizza.', 'exampleTranslation' => 'Men ba\'zan pizza yeyman.'],
                ['english' => 'Never', 'uzbek' => 'Hech qachon', 'pronunciation' => '/ˈnevər/', 'emoji' => '❌', 'example' => 'I never drink coffee.', 'exampleTranslation' => 'Men hech qachon kofe ichmayman.'],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - 2 TA SAHIFA
            'explanation' => [
                [
                    'title' => '1. Takroriylik Ravishlari (Adverbs of Frequency)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Biz biror ishni qanchalik tez-tez qilishimizni aytish uchun <strong>always, usually, sometimes, never</strong> so\'zlaridan foydalanamiz.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Asosiy So\'zlar',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Daraja'],
                            'rows' => [
                                ['<b>Always</b>', 'Doimo', '100%'],
                                ['<b>Usually</b>', 'Odatda', '80%'],
                                ['<b>Often</b>', 'Tez-tez', '60%'],
                                ['<b>Sometimes</b>', 'Ba\'zan', '40%'],
                                ['<b>Never</b>', 'Hech qachon', '0%'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Bu so\'zlar odatda <strong>egadan keyin</strong> va <strong>asosiy fe\'ldan oldin</strong> keladi: "I <b>always</b> drink tea."'
                        ]
                    ]
                ],
                [
                    'title' => '2. Gapda Joylashuvi',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Takroriylik ravishlarining gapdagi o\'rni fe\'l turiga qarab o\'zgaradi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Qoidalar',
                            'headers' => ['Holat', 'Formula', 'Misol'],
                            'rows' => [
                                ['Oddiy fe\'l bilan', 'Ega + <b>ravish</b> + fe\'l', 'I <b>usually</b> wake up at 7.'],
                                ['To Be (am/is/are) bilan', 'To Be + <b>ravish</b>', 'I am <b>never</b> late.'],
                            ]
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Xatolarga e\'tibor bering',
                            'items' => [
                                ['bad' => 'I go always to school.', 'good' => 'I <b>always go</b> to school.'],
                                ['bad' => 'He usually is happy.', 'good' => 'He is <b>usually</b> happy.'],
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Doimo" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Never', 'Sometimes', 'Always', 'Often'], 'correctAnswer' => 2, 'explanation' => '"Doimo" ingliz tilida "Always" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Never" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Doimo', 'Ba\'zan', 'Hech qachon', 'Ko\'pincha'], 'correctAnswer' => 2, 'explanation' => '"Never" - "Hech qachon" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getModule6TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Wake up" iborasining o\'zbekcha tarjimasi qanday?', 'options' => ['Uxlash', 'Uyg\'onish', 'Yurish', 'O\'tirish'], 'correctAnswer' => 1, 'explanation' => '"Wake up" - "Uyg\'onish" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "She _____ breakfast at 7."', 'options' => ['eat', 'eats', 'eating', 'ate'], 'correctAnswer' => 1, 'explanation' => '"She" bilan fe\'lga -s qo\'shiladi: "eats".'],
                ['type' => 'multiple_choice', 'question' => '"Always" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Hech qachon', 'Ba\'zan', 'Doimo', 'Kamdan-kam'], 'correctAnswer' => 2, 'explanation' => '"Always" - "Doimo" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Soat 9 da" ni ingliz tilida qanday aytasiz?', 'options' => ['Nine clock', 'At nine o\'clock', 'Nine time', 'Clock nine'], 'correctAnswer' => 1, 'explanation' => '"Soat 9 da" = "At nine o\'clock".'],
            ],
        ];
    }

    // ==================== MODULE 7: FOOD & DRINKS ====================

    private function getBasicFoodContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Bread', 'uzbek' => 'Non', 'pronunciation' => '/bred/', 'emoji' => '🍞', 'example' => 'I eat bread for breakfast.', 'exampleTranslation' => 'Men nonushtada non yeyman.'],
                ['english' => 'Rice', 'uzbek' => 'Guruch', 'pronunciation' => '/raɪs/', 'emoji' => '🍚', 'example' => 'Rice is popular in Uzbekistan.', 'exampleTranslation' => 'O\'zbekistonda guruch mashhur.'],
                ['english' => 'Meat', 'uzbek' => 'Go\'sht', 'pronunciation' => '/miːt/', 'emoji' => '🥩', 'example' => 'I like meat.', 'exampleTranslation' => 'Men go\'shtni yoqtiraman.'],
                ['english' => 'Fruit', 'uzbek' => 'Meva', 'pronunciation' => '/fruːt/', 'emoji' => '🍎', 'example' => 'Fruit is healthy.', 'exampleTranslation' => 'Meva foydali.'],
                ['english' => 'Vegetables', 'uzbek' => 'Sabzavotlar', 'pronunciation' => '/ˈvedʒtəblz/', 'emoji' => '🥕', 'example' => 'Vegetables are good for you.', 'exampleTranslation' => 'Sabzavotlar foydali.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Non" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Rice', 'Bread', 'Meat', 'Fruit'], 'correctAnswer' => 1, 'explanation' => '"Non" ingliz tilida "Bread" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Meat" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Non', 'Guruch', 'Go\'sht', 'Meva'], 'correctAnswer' => 2, 'explanation' => '"Meat" - "Go\'sht" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getDrinksContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'Water', 'uzbek' => 'Suv', 'pronunciation' => '/ˈwɔːtər/', 'emoji' => '💧', 'example' => 'I drink water every day.', 'exampleTranslation' => 'Men har kuni suv ichaman.'],
                ['english' => 'Tea', 'uzbek' => 'Choy', 'pronunciation' => '/tiː/', 'emoji' => '🍵', 'example' => 'Uzbek people love tea.', 'exampleTranslation' => 'O\'zbek xalqi choyni yaxshi ko\'radi.'],
                ['english' => 'Coffee', 'uzbek' => 'Kofe', 'pronunciation' => '/ˈkɒfi/', 'emoji' => '☕', 'example' => 'I drink coffee in the morning.', 'exampleTranslation' => 'Men ertalab kofe ichaman.'],
                ['english' => 'Juice', 'uzbek' => 'Sharbat', 'pronunciation' => '/dʒuːs/', 'emoji' => '🧃', 'example' => 'Orange juice is sweet.', 'exampleTranslation' => 'Apelsin sharbati shirin.'],
                ['english' => 'Milk', 'uzbek' => 'Sut', 'pronunciation' => '/mɪlk/', 'emoji' => '🥛', 'example' => 'Milk is white.', 'exampleTranslation' => 'Sut oq.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Choy" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Coffee', 'Tea', 'Water', 'Milk'], 'correctAnswer' => 1, 'explanation' => '"Choy" ingliz tilida "Tea" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Water" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Choy', 'Kofe', 'Suv', 'Sut'], 'correctAnswer' => 2, 'explanation' => '"Water" - "Suv" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getOrderingFoodContent(): array
    {
        return [
            'totalSteps' => 12,
            'dialogue' => [
                ['speaker' => 'Waiter', 'text' => 'What would you like?', 'translation' => 'Nima ish buyurasiz?'],
                ['speaker' => 'Customer', 'text' => 'I would like rice and meat, please.', 'translation' => 'Guruch va go\'sht bo\'lsa.'],
                ['speaker' => 'Waiter', 'text' => 'Anything to drink?', 'translation' => 'Ichimlik kerakmi?'],
                ['speaker' => 'Customer', 'text' => 'Tea, please.', 'translation' => 'Choy bo\'lsa.'],
            ],
            'words' => [
                ['english' => 'I would like...', 'uzbek' => '...bo\'lsa', 'pronunciation' => '/aɪ wʊd laɪk/', 'emoji' => '🙏', 'example' => 'I would like coffee.', 'exampleTranslation' => 'Kofe bo\'lsa.'],
                ['english' => 'Please', 'uzbek' => 'Iltimos', 'pronunciation' => '/pliːz/', 'emoji' => '🙏', 'example' => 'Water, please.', 'exampleTranslation' => 'Suv bo\'lsa, iltimos.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Restoranda biror narsa buyurtma qilmoqchi bo\'lsangiz nima deysiz?', 'options' => ['I want...', 'I would like...', 'Give me...', 'I need...'], 'correctAnswer' => 1, 'explanation' => 'Odobli so\'rash uchun "I would like..." ishlatiladi.'],
            ],
        ];
    }

    private function getLikesDislikesContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'I like', 'uzbek' => 'Men yoqtiraman', 'pronunciation' => '/aɪ laɪk/', 'emoji' => '👍', 'example' => 'I like pizza.', 'exampleTranslation' => 'Men pitsani yoqtiraman.'],
                ['english' => 'I don\'t like', 'uzbek' => 'Men yoqtirmayman', 'pronunciation' => '/aɪ doʊnt laɪk/', 'emoji' => '👎', 'example' => 'I don\'t like fish.', 'exampleTranslation' => 'Men baliqni yoqtirmayman.'],
                ['english' => 'I love', 'uzbek' => 'Men juda yoqtiraman', 'pronunciation' => '/aɪ lʌv/', 'emoji' => '❤️', 'example' => 'I love ice cream.', 'exampleTranslation' => 'Men muzqaymoqni juda yoqtiraman.'],
            ],
            // GRAMMATIKA TUSHUNTIRISHI - 2 TA SAHIFA
            'explanation' => [
                [
                    'title' => '1. Yoqtirish va Yoqtirmaslik',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Biror narsani yoqtirish yoki yoqtirmaslikni ifodalash uchun <strong>like, love</strong> va <strong>don\'t like</strong> fe\'llaridan foydalanamiz.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Ifodalash Usullari',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Daraja'],
                            'rows' => [
                                ['<b>I love</b> ...', 'Men ...ni juda yoqtiraman', '❤️ (Juda kuchli)'],
                                ['<b>I like</b> ...', 'Men ...ni yoqtiraman', '👍 (Oddiy)'],
                                ['<b>I don\'t like</b> ...', 'Men ...ni yoqtirmayman', '👎 (Inkor)'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Ega "He / She / It" bo\'lsa: <br>He <b>likes</b> (s qo\'shiladi)<br>She <b>doesn\'t like</b> (don\'t -> doesn\'t)'
                        ]
                    ]
                ],
                [
                    'title' => '2. Nima bilan ishlatiladi?',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Bu fe\'llardan keyin ot (noun) yoki fe\'l (verb+ing) kelishi mumkin.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Misollar',
                            'items' => [
                                ['en' => 'I like <b>pizza</b>. (Ot)', 'uz' => 'Men pitsani yoqtiraman.'],
                                ['en' => 'I like <b>eating</b> pizza. (Fe\'l+ing)', 'uz' => 'Men pitsa <b>yeyishni</b> yoqtiraman.'],
                                ['en' => 'She loves <b>music</b>. (Ot)', 'uz' => 'U musiqani sevadi.'],
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Men pitsani yoqtiraman" ni ingliz tilida qanday aytasiz?', 'options' => ['I like pizza', 'I don\'t like pizza', 'I love pizza', 'I eat pizza'], 'correctAnswer' => 0, 'explanation' => '"Men yoqtiraman" = "I like".'],
                ['type' => 'multiple_choice', 'question' => '"I don\'t like" iborasining o\'zbekcha tarjimasi qanday?', 'options' => ['Men yoqtiraman', 'Men yoqtirmayman', 'Men juda yoqtiraman', 'Men yeyman'], 'correctAnswer' => 1, 'explanation' => '"I don\'t like" - "Men yoqtirmayman" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getModule7TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Bread" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Guruch', 'Non', 'Go\'sht', 'Meva'], 'correctAnswer' => 1, 'explanation' => '"Bread" - "Non" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Choy" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Coffee', 'Water', 'Tea', 'Juice'], 'correctAnswer' => 2, 'explanation' => '"Choy" ingliz tilida "Tea" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"I like" iborasining o\'zbekcha tarjimasi qanday?', 'options' => ['Men yoqtirmayman', 'Men yeyman', 'Men yoqtiraman', 'Men ichaman'], 'correctAnswer' => 2, 'explanation' => '"I like" - "Men yoqtiraman" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Restoranda ovqat buyurtma qilishda qaysi ibora odobli?', 'options' => ['Give me...', 'I want...', 'I would like...', 'I need...'], 'correctAnswer' => 2, 'explanation' => '"I would like..." eng odobli buyurtma usuli.'],
            ],
        ];
    }

    // ==================== MODULE 8: HOME & FURNITURE ====================

    private function getRoomsContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Bedroom', 'uzbek' => 'Yotoqxona', 'pronunciation' => '/ˈbedruːm/', 'emoji' => '🛏️', 'example' => 'I sleep in the bedroom.', 'exampleTranslation' => 'Men yotoqxonada uxlayman.'],
                ['english' => 'Kitchen', 'uzbek' => 'Oshxona', 'pronunciation' => '/ˈkɪtʃɪn/', 'emoji' => '🍳', 'example' => 'Mom cooks in the kitchen.', 'exampleTranslation' => 'Onam oshxonada ovqat pishiradi.'],
                ['english' => 'Bathroom', 'uzbek' => 'Hammom', 'pronunciation' => '/ˈbɑːθruːm/', 'emoji' => '🚿', 'example' => 'I wash in the bathroom.', 'exampleTranslation' => 'Men hammomda yuvinaman.'],
                ['english' => 'Living room', 'uzbek' => 'Mehmonxona', 'pronunciation' => '/ˈlɪvɪŋ ruːm/', 'emoji' => '🛋️', 'example' => 'We watch TV in the living room.', 'exampleTranslation' => 'Biz mehmonxonada televizor ko\'ramiz.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Oshxona" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Bedroom', 'Kitchen', 'Bathroom', 'Living room'], 'correctAnswer' => 1, 'explanation' => '"Oshxona" ingliz tilida "Kitchen" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Bedroom" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Oshxona', 'Hammom', 'Yotoqxona', 'Mehmonxona'], 'correctAnswer' => 2, 'explanation' => '"Bedroom" - "Yotoqxona" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getFurnitureContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Bed', 'uzbek' => 'Karavot', 'pronunciation' => '/bed/', 'emoji' => '🛏️', 'example' => 'The bed is soft.', 'exampleTranslation' => 'Karavot yumshoq.'],
                ['english' => 'Table', 'uzbek' => 'Stol', 'pronunciation' => '/ˈteɪbl/', 'emoji' => '🪑', 'example' => 'The book is on the table.', 'exampleTranslation' => 'Kitob stol ustida.'],
                ['english' => 'Chair', 'uzbek' => 'Stul', 'pronunciation' => '/tʃeər/', 'emoji' => '🪑', 'example' => 'Sit on the chair.', 'exampleTranslation' => 'Stulga o\'tiring.'],
                ['english' => 'Sofa', 'uzbek' => 'Divan', 'pronunciation' => '/ˈsoʊfə/', 'emoji' => '🛋️', 'example' => 'The sofa is comfortable.', 'exampleTranslation' => 'Divan qulay.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Stol" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Chair', 'Table', 'Bed', 'Sofa'], 'correctAnswer' => 1, 'explanation' => '"Stol" ingliz tilida "Table" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Chair" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Stol', 'Stul', 'Karavot', 'Divan'], 'correctAnswer' => 1, 'explanation' => '"Chair" - "Stul" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getPrepositionsContent(): array
    {
        return [
            'lessonId' => 'a1-m9-l3',
            'title' => 'Prepositions of Place',
            'titleUz' => 'Joy predloglari',
            'type' => 'grammar',
            'level' => 'A1',
            'totalSteps' => 12,
            'pages' => [
                [
                    'pageId' => 1,
                    'type' => 'grammar_intro',
                    'title' => 'Asosiy Predloglar',
                    'titleUz' => 'Joy bildiruvchi so\'zlar',
                    'content' => [
                        'intro' => 'Predloglar narsaning **qayerda** joylashganligini bildiradi. Ular juda muhim!',
                        'table' => [
                            'title' => '📍 Asosiy Predloglar',
                            'headers' => ['Inglizcha', 'O\'zbekcha', 'Misol'],
                            'rows' => [
                                ['**in**', 'ichida', 'in the box (quti **ichida**)'],
                                ['**on**', 'ustida', 'on the table (stol **ustida**)'],
                                ['**under**', 'ostida', 'under the chair (stul **ostida**)'],
                                ['**next to**', 'yonida', 'next to me (mening **yonimda**)'],
                                ['**behind**', 'orqasida', 'behind the door (eshik **orqasida**)'],
                                ['**in front of**', 'oldida', 'in front of the house (uy **oldida**)'],
                                ['**between**', 'orasida', 'between two chairs (ikki stul **orasida**)'],
                                ['**near**', 'yaqinida', 'near the window (deraza **yaqinida**)']
                            ],
                            'highlightColumn' => 0
                        ],
                        'memoryTip' => 'Predloglar odatda **otdan oldin** keladi: "The book is **on** the table."'
                    ]
                ],
                [
                    'pageId' => 2,
                    'type' => 'grammar_visual',
                    'title' => 'Rasmlar bilan tushuntirish',
                    'titleUz' => 'Ko\'rgazmali misollar',
                    'content' => [
                        'visualExamples' => [
                            [
                                'preposition' => 'IN',
                                'uzbek' => 'ICHIDA',
                                'emoji' => '📦',
                                'description' => 'Narsa biror narsaning **ichida** joylashgan',
                                'examples' => [
                                    ['english' => 'The ball is **in** the box.', 'uzbek' => 'To\'p quti **ichida**.'],
                                    ['english' => 'She is **in** the room.', 'uzbek' => 'U xona **ichida**.'],
                                    ['english' => 'The keys are **in** my bag.', 'uzbek' => 'Kalitlar sumkam **ichida**.'],
                                    ['english' => 'I live **in** Tashkent.', 'uzbek' => 'Men Toshkent **da** yashayman.']
                                ]
                            ],
                            [
                                'preposition' => 'ON',
                                'uzbek' => 'USTIDA',
                                'emoji' => '🔝',
                                'description' => 'Narsa biror narsaning **ustida** (tegib) joylashgan',
                                'examples' => [
                                    ['english' => 'The book is **on** the table.', 'uzbek' => 'Kitob stol **ustida**.'],
                                    ['english' => 'The picture is **on** the wall.', 'uzbek' => 'Rasm devor **ustida**.'],
                                    ['english' => 'I am sitting **on** the chair.', 'uzbek' => 'Men stul **ustida** o\'tiraman.']
                                ]
                            ],
                            [
                                'preposition' => 'UNDER',
                                'uzbek' => 'OSTIDA',
                                'emoji' => '⬇️',
                                'description' => 'Narsa biror narsaning **ostida** joylashgan',
                                'examples' => [
                                    ['english' => 'The cat is **under** the table.', 'uzbek' => 'Mushuk stol **ostida**.'],
                                    ['english' => 'My shoes are **under** the bed.', 'uzbek' => 'Oyoq kiyimlarim krovat **ostida**.']
                                ]
                            ],
                            [
                                'preposition' => 'NEXT TO',
                                'uzbek' => 'YONIDA',
                                'emoji' => '↔️',
                                'description' => 'Narsa biror narsaning **yonida** joylashgan',
                                'examples' => [
                                    ['english' => 'The bank is **next to** the shop.', 'uzbek' => 'Bank do\'kon **yonida**.'],
                                    ['english' => 'Sit **next to** me.', 'uzbek' => 'Mening **yonimda** o\'tir.']
                                ]
                            ],
                            [
                                'preposition' => 'BEHIND',
                                'uzbek' => 'ORQASIDA',
                                'emoji' => '🔙',
                                'description' => 'Narsa biror narsaning **orqasida** joylashgan',
                                'examples' => [
                                    ['english' => 'The garden is **behind** the house.', 'uzbek' => 'Bog\' uy **orqasida**.'],
                                    ['english' => 'He is hiding **behind** the door.', 'uzbek' => 'U eshik **orqasida** yashirinmoqda.']
                                ]
                            ],
                            [
                                'preposition' => 'IN FRONT OF',
                                'uzbek' => 'OLDIDA',
                                'emoji' => '🔜',
                                'description' => 'Narsa biror narsaning **oldida** joylashgan',
                                'examples' => [
                                    ['english' => 'There is a tree **in front of** my house.', 'uzbek' => 'Uyim **oldida** daraxt bor.'],
                                    ['english' => 'He is standing **in front of** the mirror.', 'uzbek' => 'U oyna **oldida** turibdi.']
                                ]
                            ],
                            [
                                'preposition' => 'BETWEEN',
                                'uzbek' => 'ORASIDA',
                                'emoji' => '↹',
                                'description' => 'Narsa **ikki** narsaning **orasida** joylashgan',
                                'examples' => [
                                    ['english' => 'The bank is **between** the shop and the cafe.', 'uzbek' => 'Bank do\'kon va kafe **orasida**.'],
                                    ['english' => 'I am sitting **between** two friends.', 'uzbek' => 'Men ikki do\'st **orasida** o\'tiraman.']
                                ]
                            ],
                            [
                                'preposition' => 'NEAR',
                                'uzbek' => 'YAQINIDA',
                                'emoji' => '📍',
                                'description' => 'Narsa biror narsaning **yaqinida** (uzoq emas) joylashgan',
                                'examples' => [
                                    ['english' => 'I live **near** the park.', 'uzbek' => 'Men park **yaqinida** yashayman.'],
                                    ['english' => 'The shop is **near** my house.', 'uzbek' => 'Do\'kon uyim **yaqinida**.']
                                ]
                            ]
                        ],
                        'tip' => '**NEXT TO** va **NEAR** farqi: **Next to** = yonida (tegishgan), **Near** = yaqinida (uzoqroq)'
                    ]
                ],
                [
                    'pageId' => 3,
                    'type' => 'common_mistakes',
                    'title' => 'Ko\'p uchraydigan xatolar',
                    'content' => [
                        'mistakes' => [
                            [
                                'wrong' => 'The book is **in** the table.',
                                'correct' => 'The book is **on** the table.',
                                'explanation' => 'Kitob stolning **ichida** emas, **ustida** turadi!'
                            ],
                            [
                                'wrong' => 'I live **on** Tashkent.',
                                'correct' => 'I live **in** Tashkent.',
                                'explanation' => 'Shahar **ichida** yashaymiz, shuning uchun **IN** ishlatiladi.'
                            ],
                            [
                                'wrong' => 'The shop is **next** the bank.',
                                'correct' => 'The shop is **next to** the bank.',
                                'explanation' => 'To\'liq ibora: **NEXT TO** (ikki so\'z!)'
                            ],
                            [
                                'wrong' => 'He is standing **in front** the door.',
                                'correct' => 'He is standing **in front of** the door.',
                                'explanation' => 'To\'liq ibora: **IN FRONT OF** (uch so\'z!)'
                            ],
                            [
                                'wrong' => 'The cat is **under** the wall.',
                                'correct' => 'The cat is **near** the wall.',
                                'explanation' => 'Devorning **osti** yo\'q, **yonida** yoki **yaqinida** bo\'ladi.'
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Kitob stol ustida" - ingliz tilida qanday aytiladi?', 'options' => ['The book is in the table.', 'The book is on the table.', 'The book is under the table.', 'The book is next the table.'], 'correctAnswer' => 1, 'explanation' => 'Stol **ustida** = **ON** the table.'],
                ['type' => 'multiple_choice', 'question' => '"Men Toshkentda yashayman" - qaysi predlog to\'g\'ri?', 'options' => ['on', 'at', 'in', 'near'], 'correctAnswer' => 2, 'explanation' => 'Shahar **ichida** = **IN** Tashkent.'],
                ['type' => 'multiple_choice', 'question' => '"Mushuk stol ostida" - to\'g\'ri tarjimani tanlang.', 'options' => ['The cat is on the table.', 'The cat is in the table.', 'The cat is under the table.', 'The cat is next to the table.'], 'correctAnswer' => 2, 'explanation' => 'Stol **ostida** = **UNDER** the table.'],
                ['type' => 'multiple_choice', 'question' => '"The bank is ___ the shop and the cafe." - qaysi predlog?', 'options' => ['in', 'on', 'between', 'next to'], 'correctAnswer' => 2, 'explanation' => 'Ikki narsa **orasida** = **BETWEEN**.'],
                ['type' => 'multiple_choice', 'question' => '"Bog\' uy orqasida" - qaysi predlog ishlatiladi?', 'options' => ['in front of', 'behind', 'next to', 'near'], 'correctAnswer' => 1, 'explanation' => '**Orqasida** = **BEHIND**.'],
            ]
        ];
    }

    private function getThereIsAreContent(): array
    {
        return [
            'totalSteps' => 10,
            'explanation' => [
                [
                    'title' => '1. Bor Ekanligini Aytish',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida biror narsaning borligini aytish uchun <strong>There is</strong> (birlik) va <strong>There are</strong> (ko\'plik) iboralari ishlatiladi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Qo\'llanishi',
                            'headers' => ['Ibora', 'Qachon?', 'Misol'],
                            'rows' => [
                                ['<b>There is</b>', 'Birlik (1 ta narsa)', 'There is a book. (Kitob bor)'],
                                ['<b>There are</b>', 'Ko\'plik (2+ narsa)', 'There are two books. (Ikki kitob bor)'],
                            ]
                        ],
                        [
                            'type' => 'tip',
                            'content' => '💡 Qisqartma shakl: There is -> <strong>There\'s</strong>. (There are qisqarmaydi)'
                        ]
                    ]
                ],
                [
                    'title' => '2. Inkor va Savol',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Inkor uchun <strong>not</strong> qo\'shamiz. Savol uchun <strong>Is / Are</strong> oldinga chiqadi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Tuzilishi',
                            'headers' => ['Tur', 'Formula', 'Misol'],
                            'rows' => [
                                ['Inkor', 'There is/are + <strong>not</strong>', 'There is not (isn\'t) a dog.'],
                                ['Savol', '<strong>Is/Are</strong> + there...?', 'Is there a cat?'],
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "There _____ a cat on the sofa."', 'options' => ['is', 'are', 'be', 'am'], 'correctAnswer' => 0, 'explanation' => '"A cat" birlik, shuning uchun "is" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "There _____ two chairs."', 'options' => ['is', 'are', 'be', 'am'], 'correctAnswer' => 1, 'explanation' => '"Two chairs" ko\'plik, shuning uchun "are" ishlatiladi.'],
            ],
        ];
    }

    private function getModule8TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Kitchen" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Yotoqxona', 'Oshxona', 'Hammom', 'Mehmonxona'], 'correctAnswer' => 1, 'explanation' => '"Kitchen" - "Oshxona" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Stul" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Table', 'Bed', 'Chair', 'Sofa'], 'correctAnswer' => 2, 'explanation' => '"Stul" ingliz tilida "Chair" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"On" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Ichida', 'Ustida', 'Ostida', 'Yonida'], 'correctAnswer' => 1, 'explanation' => '"On" - "Ustida" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "There _____ three books on the table."', 'options' => ['is', 'are', 'be', 'am'], 'correctAnswer' => 1, 'explanation' => '"Three books" ko\'plik, shuning uchun "are" ishlatiladi.'],
            ],
        ];
    }

    // ==================== MODULE 9: CLOTHES & SHOPPING ====================

    private function getClothingContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Shirt', 'uzbek' => 'Ko\'ylak', 'pronunciation' => '/ʃɜːrt/', 'emoji' => '👔', 'example' => 'I wear a white shirt.', 'exampleTranslation' => 'Men oq ko\'ylak kiyaman.'],
                ['english' => 'Pants', 'uzbek' => 'Shim', 'pronunciation' => '/pænts/', 'emoji' => '👖', 'example' => 'Blue pants are popular.', 'exampleTranslation' => 'Ko\'k shimlar mashhur.'],
                ['english' => 'Dress', 'uzbek' => 'Ko\'ylak (ayollar)', 'pronunciation' => '/dres/', 'emoji' => '👗', 'example' => 'She wears a red dress.', 'exampleTranslation' => 'U qizil ko\'ylak kiyadi.'],
                ['english' => 'Shoes', 'uzbek' => 'Oyoq kiyim', 'pronunciation' => '/ʃuːz/', 'emoji' => '👟', 'example' => 'New shoes are comfortable.', 'exampleTranslation' => 'Yangi oyoq kiyim qulay.'],
                ['english' => 'Hat', 'uzbek' => 'Shapka', 'pronunciation' => '/hæt/', 'emoji' => '🎩', 'example' => 'I wear a hat in winter.', 'exampleTranslation' => 'Men qishda shapka kiyaman.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Ko\'ylak" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Pants', 'Shirt', 'Dress', 'Shoes'], 'correctAnswer' => 1, 'explanation' => '"Ko\'ylak" ingliz tilida "Shirt" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Shoes" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Shim', 'Shapka', 'Oyoq kiyim', 'Ko\'ylak'], 'correctAnswer' => 2, 'explanation' => '"Shoes" - "Oyoq kiyim" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getColorsReviewContent(): array
    {
        return [
            'totalSteps' => 10,
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Qizil ko\'ylak" ni ingliz tilida qanday aytasiz?', 'options' => ['Red shirt', 'Shirt red', 'A shirt is red', 'The red'], 'correctAnswer' => 0, 'explanation' => 'Rang sifat sifatida oldin keladi: "Red shirt".'],
                ['type' => 'multiple_choice', 'question' => '"Blue pants" iborasining o\'zbekcha tarjimasi qanday?', 'options' => ['Qizil shim', 'Ko\'k shim', 'Sariq shim', 'Oq shim'], 'correctAnswer' => 1, 'explanation' => '"Blue pants" - "Ko\'k shim" degan ma\'noni anglatadi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Oq ko\'ylak" ni ingliz tilida qanday aytasiz?', 'options' => ['Shirt white', 'White shirt', 'A white', 'White a shirt'], 'correctAnswer' => 1, 'explanation' => 'Sifat otdan oldin keladi: "White shirt".'],
            ],
        ];
    }

    private function getShoppingContent(): array
    {
        return [
            'totalSteps' => 12,
            'dialogue' => [
                ['speaker' => 'Customer', 'text' => 'How much is this shirt?', 'translation' => 'Bu ko\'ylak qancha?'],
                ['speaker' => 'Seller', 'text' => 'It\'s 50,000 sum.', 'translation' => 'Ellik ming so\'m.'],
                ['speaker' => 'Customer', 'text' => 'I\'ll take it.', 'translation' => 'Olaman.'],
            ],
            'words' => [
                ['english' => 'How much?', 'uzbek' => 'Qancha?', 'pronunciation' => '/haʊ mʌtʃ/', 'emoji' => '💰', 'example' => 'How much is this?', 'exampleTranslation' => 'Bu qancha?'],
                ['english' => 'Expensive', 'uzbek' => 'Qimmat', 'pronunciation' => '/ɪkˈspensɪv/', 'emoji' => '💎', 'example' => 'This is expensive.', 'exampleTranslation' => 'Bu qimmat.'],
                ['english' => 'Cheap', 'uzbek' => 'Arzon', 'pronunciation' => '/tʃiːp/', 'emoji' => '💵', 'example' => 'This is cheap.', 'exampleTranslation' => 'Bu arzon.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Qancha?" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['How many?', 'How much?', 'What price?', 'Cost?'], 'correctAnswer' => 1, 'explanation' => '"Qancha?" ingliz tilida "How much?" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Expensive" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Arzon', 'Qimmat', 'Yaxshi', 'Yomon'], 'correctAnswer' => 1, 'explanation' => '"Expensive" - "Qimmat" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getMoneyPricesContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'Money', 'uzbek' => 'Pul', 'pronunciation' => '/ˈmʌni/', 'emoji' => '💵', 'example' => 'I have money.', 'exampleTranslation' => 'Menda pul bor.'],
                ['english' => 'Price', 'uzbek' => 'Narx', 'pronunciation' => '/praɪs/', 'emoji' => '🏷️', 'example' => 'What is the price?', 'exampleTranslation' => 'Narxi qancha?'],
                ['english' => 'Pay', 'uzbek' => 'To\'lamoq', 'pronunciation' => '/peɪ/', 'emoji' => '💳', 'example' => 'I pay with cash.', 'exampleTranslation' => 'Men naqd pul bilan to\'layman.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Pul" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Price', 'Money', 'Pay', 'Cost'], 'correctAnswer' => 1, 'explanation' => '"Pul" ingliz tilida "Money" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Pay" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Olmoq', 'To\'lamoq', 'Sotmoq', 'Ko\'rmoq'], 'correctAnswer' => 1, 'explanation' => '"Pay" - "To\'lamoq" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getModule9TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Shirt" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Shim', 'Ko\'ylak', 'Shapka', 'Oyoq kiyim'], 'correctAnswer' => 1, 'explanation' => '"Shirt" - "Ko\'ylak" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"How much?" iborasining o\'zbekcha tarjimasi qanday?', 'options' => ['Nechta?', 'Qancha?', 'Qayerda?', 'Kim?'], 'correctAnswer' => 1, 'explanation' => '"How much?" - "Qancha?" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Qimmat" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Cheap', 'Expensive', 'Good', 'Bad'], 'correctAnswer' => 1, 'explanation' => '"Qimmat" ingliz tilida "Expensive" deb aytiladi.'],
            ],
        ];
    }

    // ==================== MODULE 10: ANIMALS & NATURE ====================

    private function getCommonAnimalsContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Dog', 'uzbek' => 'It', 'pronunciation' => '/dɒɡ/', 'emoji' => '🐕', 'example' => 'The dog is friendly.', 'exampleTranslation' => 'It do\'stona.'],
                ['english' => 'Cat', 'uzbek' => 'Mushuk', 'pronunciation' => '/kæt/', 'emoji' => '🐈', 'example' => 'The cat sleeps.', 'exampleTranslation' => 'Mushuk uxlaydi.'],
                ['english' => 'Bird', 'uzbek' => 'Qush', 'pronunciation' => '/bɜːrd/', 'emoji' => '🐦', 'example' => 'The bird sings.', 'exampleTranslation' => 'Qush sayradi.'],
                ['english' => 'Fish', 'uzbek' => 'Baliq', 'pronunciation' => '/fɪʃ/', 'emoji' => '🐟', 'example' => 'Fish swim in water.', 'exampleTranslation' => 'Baliqlar suvda suzadi.'],
                ['english' => 'Cow', 'uzbek' => 'Sigir', 'pronunciation' => '/kaʊ/', 'emoji' => '🐄', 'example' => 'Cows give milk.', 'exampleTranslation' => 'Sigirlar sut beradi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"It" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Cat', 'Dog', 'Bird', 'Fish'], 'correctAnswer' => 1, 'explanation' => '"It" ingliz tilida "Dog" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Cat" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['It', 'Mushuk', 'Qush', 'Baliq'], 'correctAnswer' => 1, 'explanation' => '"Cat" - "Mushuk" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getPetsContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'Pet', 'uzbek' => 'Uy hayvoni', 'pronunciation' => '/pet/', 'emoji' => '🐾', 'example' => 'I have a pet.', 'exampleTranslation' => 'Mening uy hayvonim bor.'],
                ['english' => 'Hamster', 'uzbek' => 'Xomyak', 'pronunciation' => '/ˈhæmstər/', 'emoji' => '🐹', 'example' => 'The hamster is small.', 'exampleTranslation' => 'Xomyak kichik.'],
                ['english' => 'Rabbit', 'uzbek' => 'Quyon', 'pronunciation' => '/ˈræbɪt/', 'emoji' => '🐰', 'example' => 'Rabbits eat carrots.', 'exampleTranslation' => 'Quyonlar sabzi yeydi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Uy hayvoni" iborasini ingliz tilida qanday aytasiz?', 'options' => ['Animal', 'Pet', 'Bird', 'Fish'], 'correctAnswer' => 1, 'explanation' => '"Uy hayvoni" ingliz tilida "Pet" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Rabbit" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Mushuk', 'It', 'Quyon', 'Xomyak'], 'correctAnswer' => 2, 'explanation' => '"Rabbit" - "Quyon" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getWeatherContent(): array
    {
        return [
            'totalSteps' => 12,
            'words' => [
                ['english' => 'Sunny', 'uzbek' => 'Quyoshli', 'pronunciation' => '/ˈsʌni/', 'emoji' => '☀️', 'example' => 'It\'s sunny today.', 'exampleTranslation' => 'Bugun quyoshli.'],
                ['english' => 'Rainy', 'uzbek' => 'Yomg\'irli', 'pronunciation' => '/ˈreɪni/', 'emoji' => '🌧️', 'example' => 'It\'s rainy today.', 'exampleTranslation' => 'Bugun yomg\'irli.'],
                ['english' => 'Cloudy', 'uzbek' => 'Bulutli', 'pronunciation' => '/ˈklaʊdi/', 'emoji' => '☁️', 'example' => 'It\'s very cloudy.', 'exampleTranslation' => 'Juda bulutli.'],
                ['english' => 'Snowy', 'uzbek' => 'Qorli', 'pronunciation' => '/ˈsnoʊi/', 'emoji' => '❄️', 'example' => 'It\'s snowy in winter.', 'exampleTranslation' => 'Qishda qorli bo\'ladi.'],
                ['english' => 'Hot', 'uzbek' => 'Issiq', 'pronunciation' => '/hɒt/', 'emoji' => '🔥', 'example' => 'It\'s hot in summer.', 'exampleTranslation' => 'Yozda issiq.'],
                ['english' => 'Cold', 'uzbek' => 'Sovuq', 'pronunciation' => '/koʊld/', 'emoji' => '🥶', 'example' => 'It\'s cold in winter.', 'exampleTranslation' => 'Qishda sovuq.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Quyoshli" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Rainy', 'Sunny', 'Cloudy', 'Snowy'], 'correctAnswer' => 1, 'explanation' => '"Quyoshli" ingliz tilida "Sunny" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Cold" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Issiq', 'Sovuq', 'Iliq', 'Bulutli'], 'correctAnswer' => 1, 'explanation' => '"Cold" - "Sovuq" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getInNatureContent(): array
    {
        return [
            'totalSteps' => 10,
            'words' => [
                ['english' => 'Tree', 'uzbek' => 'Daraxt', 'pronunciation' => '/triː/', 'emoji' => '🌳', 'example' => 'Trees give oxygen.', 'exampleTranslation' => 'Daraxtlar kislorod beradi.'],
                ['english' => 'Flower', 'uzbek' => 'Gul', 'pronunciation' => '/ˈflaʊər/', 'emoji' => '🌸', 'example' => 'Flowers are beautiful.', 'exampleTranslation' => 'Gullar chiroyli.'],
                ['english' => 'River', 'uzbek' => 'Daryo', 'pronunciation' => '/ˈrɪvər/', 'emoji' => '🏞️', 'example' => 'The river is long.', 'exampleTranslation' => 'Daryo uzun.'],
                ['english' => 'Mountain', 'uzbek' => 'Tog\'', 'pronunciation' => '/ˈmaʊntən/', 'emoji' => '⛰️', 'example' => 'Mountains are high.', 'exampleTranslation' => 'Tog\'lar baland.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Daraxt" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Flower', 'Tree', 'River', 'Mountain'], 'correctAnswer' => 1, 'explanation' => '"Daraxt" ingliz tilida "Tree" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"River" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Tog\'', 'Gul', 'Daryo', 'Daraxt'], 'correctAnswer' => 2, 'explanation' => '"River" - "Daryo" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getModule10TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Dog" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Mushuk', 'It', 'Qush', 'Baliq'], 'correctAnswer' => 1, 'explanation' => '"Dog" - "It" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Quyoshli" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Rainy', 'Sunny', 'Cloudy', 'Snowy'], 'correctAnswer' => 1, 'explanation' => '"Quyoshli" ingliz tilida "Sunny" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Tree" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Gul', 'Daraxt', 'Daryo', 'Tog\''], 'correctAnswer' => 1, 'explanation' => '"Tree" - "Daraxt" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Pet" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Yovvoyi hayvon', 'Uy hayvoni', 'Qush', 'Baliq'], 'correctAnswer' => 1, 'explanation' => '"Pet" - "Uy hayvoni" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"Sovuq" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['Hot', 'Cold', 'Warm', 'Cool'], 'correctAnswer' => 1, 'explanation' => '"Sovuq" ingliz tilida "Cold" deb aytiladi.'],
            ],
        ];
    }
}
