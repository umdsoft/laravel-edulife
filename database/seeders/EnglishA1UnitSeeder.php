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
            // GRAMMATIKA TUSHUNTIRISHI - O'ZBEK TILIDA
            'explanation' => [
                'title' => '"TO BE" fe\'li',
                'titleEn' => 'The verb "TO BE"',
                'content' => '"To be" fe\'li subyektga qarab o\'zgaradi. "I" bilan "am", "you/we/they" bilan "are", "he/she/it" bilan "is" ishlatiladi.',
                'table' => [
                    'headers' => ['Subyekt', 'To Be', 'Misol'],
                    'rows' => [
                        ['I (Men)', 'am', 'I am from Uzbekistan. (Men O\'zbekistondanman.)'],
                        ['You (Siz)', 'are', 'You are a student. (Siz talaba.)'],
                        ['He/She/It (U)', 'is', 'She is from Tashkent. (U Toshkentdan.)'],
                        ['We (Biz)', 'are', 'We are friends. (Biz do\'stlarmiz.)'],
                        ['They (Ular)', 'are', 'They are teachers. (Ular o\'qituvchilar.)'],
                    ],
                ],
                'tip' => 'Eslab qoling: "am" faqat "I" bilan ishlatiladi!',
            ],
            // MASHQLAR - O'ZBEK TILIDA
            'exercises' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "I _____ a student."',
                    'questionTranslation' => 'Men talaba_____.',
                    'options' => ['is', 'are', 'am', 'be'],
                    'correctAnswer' => 2,
                    'explanation' => '"I" bilan doim "am" ishlatiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "She _____ from Samarkand."',
                    'questionTranslation' => 'U Samarqand_____.',
                    'options' => ['am', 'is', 'are', 'be'],
                    'correctAnswer' => 1,
                    'explanation' => '"She" bilan "is" ishlatiladi.',
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
            // YAKUNIY TEST - O'ZBEK TILIDA
            'quiz' => [
                [
                    'type' => 'multiple_choice',
                    'question' => 'Qaysi gap to\'g\'ri?',
                    'options' => ['I is happy.', 'I are happy.', 'I am happy.', 'I be happy.'],
                    'correctAnswer' => 2,
                    'explanation' => '"I am" - to\'g\'ri shakl.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "We _____ from Uzbekistan."',
                    'questionTranslation' => 'Biz O\'zbekistondanmiz.',
                    'options' => ['am', 'is', 'are', 'be'],
                    'correctAnswer' => 2,
                    'explanation' => '"We" bilan "are" ishlatiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Bo\'sh joyni to\'ldiring: "He _____ a teacher."',
                    'questionTranslation' => 'U o\'qituvchi.',
                    'options' => ['am', 'is', 'are', 'be'],
                    'correctAnswer' => 1,
                    'explanation' => '"He" bilan "is" ishlatiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'Kimdir qayerdan ekanligini so\'ramoqchi bo\'lsangiz nima deysiz?',
                    'options' => ['What is your name?', 'Where are you from?', 'How are you?', 'How old are you?'],
                    'correctAnswer' => 1,
                    'explanation' => 'Qayerdan ekanligini so\'rash uchun "Where are you from?" deyiladi.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '"Country" so\'zining o\'zbekcha tarjimasi qanday?',
                    'options' => ['shahar', 'qishloq', 'mamlakat', 'ko\'cha'],
                    'correctAnswer' => 2,
                    'explanation' => '"Country" - "mamlakat" degan ma\'noni anglatadi.',
                ],
            ],
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
            'totalSteps' => 15,
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
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Apple" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['B', 'A', 'C', 'D'], 'correctAnswer' => 1, 'explanation' => '"Apple" (Olma) "A" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Cat" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['K', 'S', 'C', 'G'], 'correctAnswer' => 2, 'explanation' => '"Cat" (Mushuk) "C" harfi bilan boshlanadi.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida "A" harfidan keyin qaysi harf keladi?', 'options' => ['C', 'B', 'D', 'E'], 'correctAnswer' => 1, 'explanation' => '"A" harfidan keyin "B" harfi keladi.'],
                ['type' => 'multiple_choice', 'question' => '"Dog" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['B', 'G', 'D', 'T'], 'correctAnswer' => 2, 'explanation' => '"Dog" (It) "D" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"House" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['G', 'J', 'H', 'K'], 'correctAnswer' => 2, 'explanation' => '"House" (Uy) "H" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Quyidagi harflardan qaysi biri alifboda birinchi keladi?', 'options' => ['M', 'G', 'C', 'K'], 'correctAnswer' => 2, 'explanation' => 'Alifbo tartibida: C, G, K, M.'],
            ],
        ];
    }

    private function getAlphabetNZContent(): array
    {
        return [
            'totalSteps' => 15,
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
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Sun" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['Z', 'C', 'S', 'X'], 'correctAnswer' => 2, 'explanation' => '"Sun" (Quyosh) "S" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida oxirgi harf qaysi?', 'options' => ['X', 'Y', 'Z', 'W'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida oxirgi harf "Z".'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Water" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['V', 'W', 'U', 'Y'], 'correctAnswer' => 1, 'explanation' => '"Water" (Suv) "W" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Tree" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['R', 'S', 'T', 'P'], 'correctAnswer' => 2, 'explanation' => '"Tree" (Daraxt) "T" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida nechta harf bor?', 'options' => ['24', '25', '26', '27'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida 26 ta harf bor.'],
                ['type' => 'multiple_choice', 'question' => '"Yellow" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['W', 'Y', 'U', 'I'], 'correctAnswer' => 1, 'explanation' => '"Yellow" (Sariq) "Y" harfi bilan boshlanadi.'],
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
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Besh" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Four', 'Five', 'Six', 'Seven'], 'correctAnswer' => 1, 'explanation' => '"Besh" ingliz tilida "Five" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '3 + 4 = ?', 'options' => ['Six', 'Seven', 'Eight', 'Nine'], 'correctAnswer' => 1, 'explanation' => '3 + 4 = 7 (Seven - Yetti).'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Seven" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['Olti', 'Yetti', 'Sakkiz', 'To\'qqiz'], 'correctAnswer' => 1, 'explanation' => '"Seven" - "Yetti" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '"O\'n" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Nine', 'Ten', 'Eleven', 'Eight'], 'correctAnswer' => 1, 'explanation' => '"O\'n" ingliz tilida "Ten" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '5 + 3 = ?', 'options' => ['Seven', 'Eight', 'Nine', 'Six'], 'correctAnswer' => 1, 'explanation' => '5 + 3 = 8 (Eight - Sakkiz).'],
                ['type' => 'multiple_choice', 'question' => 'Haftada necha kun bor?', 'options' => ['Five', 'Six', 'Seven', 'Eight'], 'correctAnswer' => 2, 'explanation' => 'Haftada 7 (Seven - Yetti) kun bor.'],
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
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => '"Ellik" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Forty', 'Fifty', 'Sixty', 'Seventy'], 'correctAnswer' => 1, 'explanation' => '"Ellik" ingliz tilida "Fifty" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Bir soatda necha daqiqa bor?', 'options' => ['Fifty', 'Sixty', 'Seventy', 'Eighty'], 'correctAnswer' => 1, 'explanation' => 'Bir soatda 60 (Sixty - Oltmish) daqiqa bor.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"One hundred" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['To\'qson', 'Yuz', 'Sakson', 'Yetmish'], 'correctAnswer' => 1, 'explanation' => '"One hundred" - "Yuz" degan ma\'noni anglatadi.'],
                ['type' => 'multiple_choice', 'question' => '30 + 20 = ?', 'options' => ['Forty', 'Fifty', 'Sixty', 'Seventy'], 'correctAnswer' => 1, 'explanation' => '30 + 20 = 50 (Fifty - Ellik).'],
                ['type' => 'multiple_choice', 'question' => '"Qirq" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Thirty', 'Forty', 'Fifty', 'Sixty'], 'correctAnswer' => 1, 'explanation' => '"Qirq" ingliz tilida "Forty" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Ninety" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['Sakson', 'To\'qson', 'Yuz', 'Yetmish'], 'correctAnswer' => 1, 'explanation' => '"Ninety" - "To\'qson" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getModule2TestContent(): array
    {
        return [
            'totalSteps' => 10,
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida nechta harf bor?', 'options' => ['24', '25', '26', '28'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida 26 ta harf bor.'],
                ['type' => 'multiple_choice', 'question' => '"Apple" so\'zi qaysi harf bilan boshlanadi?', 'options' => ['E', 'A', 'O', 'I'], 'correctAnswer' => 1, 'explanation' => '"Apple" (Olma) "A" harfi bilan boshlanadi.'],
                ['type' => 'multiple_choice', 'question' => '"Yetti" raqamini ingliz tilida qanday aytasiz?', 'options' => ['Six', 'Seven', 'Eight', 'Nine'], 'correctAnswer' => 1, 'explanation' => '"Yetti" ingliz tilida "Seven" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '10 + 10 = ?', 'options' => ['Fifteen', 'Twenty', 'Twenty-five', 'Thirty'], 'correctAnswer' => 1, 'explanation' => '10 + 10 = 20 (Twenty - Yigirma).'],
                ['type' => 'multiple_choice', 'question' => 'Ingliz alifbosida oxirgi harf qaysi?', 'options' => ['X', 'Y', 'Z', 'W'], 'correctAnswer' => 2, 'explanation' => 'Ingliz alifbosida oxirgi harf "Z".'],
                ['type' => 'multiple_choice', 'question' => '"One hundred" qancha?', 'options' => ['90', '100', '110', '1000'], 'correctAnswer' => 1, 'explanation' => '"One hundred" = 100 (Yuz).'],
                ['type' => 'multiple_choice', 'question' => '"D" harfidan keyin qaysi harf keladi?', 'options' => ['C', 'E', 'F', 'G'], 'correctAnswer' => 1, 'explanation' => '"D" harfidan keyin "E" harfi keladi.'],
                ['type' => 'multiple_choice', 'question' => '"Fifty" raqamining o\'zbekcha tarjimasi qanday?', 'options' => ['Qirq', 'Ellik', 'Oltmish', 'Yetmish'], 'correctAnswer' => 1, 'explanation' => '"Fifty" - "Ellik" degan ma\'noni anglatadi.'],
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
                'title' => 'Sifatlar (Adjectives)',
                'content' => 'Ingliz tilida sifatlar otdan oldin keladi. Masalan: "big elephant" (katta fil), "red apple" (qizil olma).',
                'tip' => 'Rang + hajm + ot tartibida yozing: "a big red ball" (katta qizil to\'p).',
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
                'title' => 'Egalik olmoshlari (Possessive Pronouns)',
                'content' => 'Egalik olmoshlari kimga tegishli ekanini ko\'rsatadi: my (mening), your (sening), his (uning-erkak), her (uning-ayol).',
                'tip' => 'His = erkaklar uchun, Her = ayollar uchun ishlatiladi.',
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
            'totalSteps' => 12,
            'explanation' => [
                'title' => 'Present Simple (Oddiy hozirgi zamon)',
                'content' => 'Present Simple har doim takrorlanadigan ishlarni ifodalaydi. "I/You/We/They" bilan fe\'l o\'zgarmasdan ishlatiladi, "He/She/It" bilan fe\'lga -s/-es qo\'shiladi.',
                'tip' => 'Esda tuting: He works, She plays, It runs (fe\'lga -s qo\'shiladi).',
            ],
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'To\'g\'ri javobni tanlang: "She _____ to school every day."', 'options' => ['go', 'goes', 'going', 'went'], 'correctAnswer' => 1, 'explanation' => '"She" bilan fe\'lga -s qo\'shiladi: "goes".'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "I _____ breakfast at 8 AM."', 'options' => ['eats', 'eat', 'eating', 'ate'], 'correctAnswer' => 1, 'explanation' => '"I" bilan fe\'l o\'zgarmasdan ishlatiladi: "eat".'],
                ['type' => 'multiple_choice', 'question' => 'Bo\'sh joyni to\'ldiring: "He _____ English."', 'options' => ['speak', 'speaks', 'speaking', 'spoke'], 'correctAnswer' => 1, 'explanation' => '"He" bilan fe\'lga -s qo\'shiladi: "speaks".'],
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
            'totalSteps' => 12,
            'words' => [
                ['english' => 'In', 'uzbek' => 'Ichida', 'pronunciation' => '/ɪn/', 'emoji' => '📦', 'example' => 'The ball is in the box.', 'exampleTranslation' => 'To\'p quti ichida.'],
                ['english' => 'On', 'uzbek' => 'Ustida', 'pronunciation' => '/ɒn/', 'emoji' => '📱', 'example' => 'The phone is on the table.', 'exampleTranslation' => 'Telefon stol ustida.'],
                ['english' => 'Under', 'uzbek' => 'Ostida', 'pronunciation' => '/ˈʌndər/', 'emoji' => '⬇️', 'example' => 'The cat is under the bed.', 'exampleTranslation' => 'Mushuk karavot ostida.'],
                ['english' => 'Next to', 'uzbek' => 'Yonida', 'pronunciation' => '/nekst tuː/', 'emoji' => '➡️', 'example' => 'The chair is next to the table.', 'exampleTranslation' => 'Stul stol yonida.'],
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => '"Ustida" so\'zini ingliz tilida qanday aytasiz?', 'options' => ['In', 'On', 'Under', 'Next to'], 'correctAnswer' => 1, 'explanation' => '"Ustida" ingliz tilida "On" deb aytiladi.'],
                ['type' => 'multiple_choice', 'question' => '"Under" so\'zining o\'zbekcha tarjimasi qanday?', 'options' => ['Ichida', 'Ustida', 'Ostida', 'Yonida'], 'correctAnswer' => 2, 'explanation' => '"Under" - "Ostida" degan ma\'noni anglatadi.'],
            ],
        ];
    }

    private function getThereIsAreContent(): array
    {
        return [
            'totalSteps' => 10,
            'explanation' => [
                'title' => 'There is / There are',
                'content' => 'Bir narsa bor ekanligini aytish uchun ishlatiladi. Birlik uchun "There is", ko\'plik uchun "There are".',
                'tip' => 'There is a book. (Bir kitob bor.) There are two books. (Ikki kitob bor.)',
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
