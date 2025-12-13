<?php

namespace Database\Seeders;

use App\Models\English\EnglishLevel;
use App\Models\English\EnglishUnit;
use App\Models\English\EnglishLesson;
use App\Models\English\EnglishLessonStep;
use App\Models\English\UserLessonProgress;
use Illuminate\Database\Seeder;

class EnglishA2UnitSeeder extends Seeder
{
    private array $xpRewards = [
        'vocabulary' => 3,
        'grammar' => 4,
        'practice' => 3,
        'conversation' => 4,
        'test' => 7,
    ];

    private array $coinRewards = [
        'vocabulary' => 1,
        'grammar' => 2,
        'practice' => 1,
        'conversation' => 2,
        'test' => 3,
    ];

    public function run(): void
    {
        $level = EnglishLevel::where('code', 'A2')->first();

        if (!$level) {
            $this->command->error('A2 level not found. Please run EnglishLevelSeeder first.');
            return;
        }

        // Cleanup
        $unitIds = EnglishUnit::where('level_id', $level->id)->pluck('id');
        if ($unitIds->isNotEmpty()) {
            EnglishLesson::whereIn('unit_id', $unitIds)->delete();
            EnglishUnit::whereIn('id', $unitIds)->delete();
        }

        $units = $this->getUnitsData();

        foreach ($units as $unitData) {
            $lessons = $unitData['lessons'];
            unset($unitData['lessons']);

            $unit = EnglishUnit::create(array_merge($unitData, [
                'level_id' => $level->id,
                'is_active' => true,
            ]));

            foreach ($lessons as $lessonData) {
                $content = $lessonData['content'] ?? [];
                unset($lessonData['content']);

                EnglishLesson::create(array_merge($lessonData, [
                    'unit_id' => $unit->id,
                    'title_uz' => $lessonData['title_uz'] ?? $lessonData['title'],
                    'xp_reward' => $this->xpRewards[$lessonData['lesson_type']] ?? 3,
                    'coin_reward' => $this->coinRewards[$lessonData['lesson_type']] ?? 1,
                    'pass_percentage' => 80,
                    'is_active' => true,
                    'content' => $content,
                ]));
            }
        }
    }

    private function getUnitsData(): array
    {
        return [
            [
                'title' => 'Unit 1: Past Events',
                'title_uz' => '1-Bo\'lim: O\'tgan Zamon',
                'slug' => 'a2-u1-past-events',
                'unit_number' => 1,
                'description' => 'Talking about past experiences and history',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u1-l1-vocab',
                        'title' => 'Vocabulary: Past Events',
                        'title_uz' => 'Lug\'at: O\'tgan Zamon',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getPastSimpleVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u1-l2-grammar',
                        'title' => 'Past Simple: Regular Verbs',
                        'title_uz' => 'Grammatika: O\'tgan Zamon',
                        'lesson_type' => 'grammar',
                        'content' => $this->getPastSimpleContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u1-l3-practice',
                        'title' => 'Practice: Past Simple',
                        'title_uz' => 'Mashq: O\'tgan Zamon',
                        'lesson_type' => 'practice',
                        'content' => $this->getPastSimplePractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u1-l4-test',
                        'title' => 'Unit Test: Past Simple',
                        'title_uz' => 'Bo\'lim Testi: O\'tgan Zamon',
                        'lesson_type' => 'test',
                        'content' => $this->getPastSimpleTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 2: Stories',
                'title_uz' => '2-Bo\'lim: Hikoyalar',
                'slug' => 'a2-u2-stories',
                'unit_number' => 2,
                'description' => 'Telling stories and past actions',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u2-l1-vocab',
                        'title' => 'Vocabulary: Stories',
                        'title_uz' => 'Lug\'at: Hikoyalar',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getPastContinuousVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u2-l2-grammar',
                        'title' => 'Past Continuous',
                        'title_uz' => 'Grammatika: O\'tgan Davomiy Zamon',
                        'lesson_type' => 'grammar',
                        'content' => $this->getPastContinuousContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u2-l3-practice',
                        'title' => 'Practice: Past Continuous',
                        'title_uz' => 'Mashq: O\'tgan Davomiy Zamon',
                        'lesson_type' => 'practice',
                        'content' => $this->getPastContinuousPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u2-l4-test',
                        'title' => 'Unit Test: Past Continuous',
                        'title_uz' => 'Bo\'lim Testi: O\'tgan Davomiy Zamon',
                        'lesson_type' => 'test',
                        'content' => $this->getPastContinuousTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 3: Comparisons',
                'title_uz' => '3-Bo\'lim: Taqqoslash',
                'slug' => 'a2-u3-comparisons',
                'unit_number' => 3,
                'description' => 'Comparing people and things',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u3-l1-vocab',
                        'title' => 'Vocabulary: Comparisons',
                        'title_uz' => 'Lug\'at: Taqqoslash',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getComparativesVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u3-l2-grammar',
                        'title' => 'Comparatives & Superlatives',
                        'title_uz' => 'Grammatika: Sifat Darajalari',
                        'lesson_type' => 'grammar',
                        'content' => $this->getComparativesContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u3-l3-practice',
                        'title' => 'Practice: Comparisons',
                        'title_uz' => 'Mashq: Taqqoslash',
                        'lesson_type' => 'practice',
                        'content' => $this->getComparativesPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u3-l4-test',
                        'title' => 'Unit Test: Comparatives',
                        'title_uz' => 'Bo\'lim Testi: Taqqoslash',
                        'lesson_type' => 'test',
                        'content' => $this->getComparativesTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 4: Future Plans',
                'title_uz' => '4-Bo\'lim: Kelajak Rejalari',
                'slug' => 'a2-u4-future-plans',
                'unit_number' => 4,
                'description' => 'Talking about future plans and predictions',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u4-l1-vocab',
                        'title' => 'Vocabulary: Future Plans',
                        'title_uz' => 'Lug\'at: Kelajak Rejalari',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getFutureVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u4-l2-grammar',
                        'title' => 'Future: Will vs Going to',
                        'title_uz' => 'Grammatika: Kelajak Zamon',
                        'lesson_type' => 'grammar',
                        'content' => $this->getFutureFormsContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u4-l3-practice',
                        'title' => 'Practice: Future Forms',
                        'title_uz' => 'Mashq: Kelajak Zamon',
                        'lesson_type' => 'practice',
                        'content' => $this->getFuturePractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u4-l4-test',
                        'title' => 'Unit Test: Future Forms',
                        'title_uz' => 'Bo\'lim Testi: Kelajak Zamon',
                        'lesson_type' => 'test',
                        'content' => $this->getFutureFormsTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 5: Life Experiences',
                'title_uz' => '5-Bo\'lim: Hayotiy Tajribalar',
                'slug' => 'a2-u5-life-experiences',
                'unit_number' => 5,
                'description' => 'Talking about experiences',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u5-l1-vocab',
                        'title' => 'Vocabulary: Experiences',
                        'title_uz' => 'Lug\'at: Hayotiy Tajribalar',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getPresentPerfectVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u5-l2-grammar',
                        'title' => 'Present Perfect',
                        'title_uz' => 'Grammatika: Hozirgi Tugallangan Zamon',
                        'lesson_type' => 'grammar',
                        'content' => $this->getPresentPerfectContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u5-l3-practice',
                        'title' => 'Practice: Present Perfect',
                        'title_uz' => 'Mashq: Hozirgi Tugallangan Zamon',
                        'lesson_type' => 'practice',
                        'content' => $this->getPresentPerfectPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u5-l4-test',
                        'title' => 'Unit Test: Present Perfect',
                        'title_uz' => 'Bo\'lim Testi: Hozirgi Tugallangan Zamon',
                        'lesson_type' => 'test',
                        'content' => $this->getPresentPerfectTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 6: Health & Advice',
                'title_uz' => '6-Bo\'lim: Salomatlik va Maslahat',
                'slug' => 'a2-u6-health',
                'unit_number' => 6,
                'description' => 'Talking about health and giving advice',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u6-l1-vocab',
                        'title' => 'Vocabulary: Health',
                        'title_uz' => 'Lug\'at: Salomatlik',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getHealthVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u6-l2-grammar',
                        'title' => 'Health: Should vs Must',
                        'title_uz' => 'Grammatika: Should va Must',
                        'lesson_type' => 'grammar',
                        'content' => $this->getHealthContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u6-l3-practice',
                        'title' => 'Practice: Health',
                        'title_uz' => 'Mashq: Salomatlik',
                        'lesson_type' => 'practice',
                        'content' => $this->getHealthPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u6-l4-test',
                        'title' => 'Unit Test: Health',
                        'title_uz' => 'Bo\'lim Testi: Salomatlik',
                        'lesson_type' => 'test',
                        'content' => $this->getHealthTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 7: Food & Quantity',
                'title_uz' => '7-Bo\'lim: Oziq-ovqat va Miqdor',
                'slug' => 'a2-u7-food',
                'unit_number' => 7,
                'description' => 'Talking about food and quantities',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u7-l1-vocab',
                        'title' => 'Vocabulary: Food',
                        'title_uz' => 'Lug\'at: Oziq-ovqat',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getFoodVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u7-l2-grammar',
                        'title' => 'Countable & Uncountable',
                        'title_uz' => 'Grammatika: Sanaladigan Otlar',
                        'lesson_type' => 'grammar',
                        'content' => $this->getFoodContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u7-l3-practice',
                        'title' => 'Practice: Food',
                        'title_uz' => 'Mashq: Oziq-ovqat',
                        'lesson_type' => 'practice',
                        'content' => $this->getFoodPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u7-l4-test',
                        'title' => 'Unit Test: Food',
                        'title_uz' => 'Bo\'lim Testi: Oziq-ovqat',
                        'lesson_type' => 'test',
                        'content' => $this->getFoodTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 8: City & Directions',
                'title_uz' => '8-Bo\'lim: Shahar va Yo\'nalishlar',
                'slug' => 'a2-u8-city',
                'unit_number' => 8,
                'description' => 'Getting around the city',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u8-l1-vocab',
                        'title' => 'Vocabulary: City',
                        'title_uz' => 'Lug\'at: Shahar',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getCityVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u8-l2-grammar',
                        'title' => 'Prepositions of Movement',
                        'title_uz' => 'Grammatika: Harakat Predloglari',
                        'lesson_type' => 'grammar',
                        'content' => $this->getCityContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u8-l3-practice',
                        'title' => 'Practice: City',
                        'title_uz' => 'Mashq: Shahar',
                        'lesson_type' => 'practice',
                        'content' => $this->getCityPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u8-l4-test',
                        'title' => 'Unit Test: City',
                        'title_uz' => 'Bo\'lim Testi: Shahar',
                        'lesson_type' => 'test',
                        'content' => $this->getCityTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 9: Clothes & Fashion',
                'title_uz' => '9-Bo\'lim: Kiyim va Moda',
                'slug' => 'a2-u9-clothes',
                'unit_number' => 9,
                'description' => 'Shopping for clothes and describing style',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u9-l1-vocab',
                        'title' => 'Vocabulary: Clothes',
                        'title_uz' => 'Lug\'at: Kiyimlar',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getClothesVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u9-l2-grammar',
                        'title' => 'Too & Enough',
                        'title_uz' => 'Grammatika: Too va Enough',
                        'lesson_type' => 'grammar',
                        'content' => $this->getClothesContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u9-l3-practice',
                        'title' => 'Practice: Clothes',
                        'title_uz' => 'Mashq: Kiyimlar',
                        'lesson_type' => 'practice',
                        'content' => $this->getClothesPractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u9-l4-test',
                        'title' => 'Unit Test: Clothes',
                        'title_uz' => 'Bo\'lim Testi: Kiyimlar',
                        'lesson_type' => 'test',
                        'content' => $this->getClothesTest()
                    ],
                ]
            ],
            [
                'title' => 'Unit 10: Nature & World',
                'title_uz' => '10-Bo\'lim: Tabiat va Dunyo',
                'slug' => 'a2-u10-nature',
                'unit_number' => 10,
                'description' => 'Talking about nature and cause & effect',
                'lessons' => [
                    [
                        'lesson_number' => 1,
                        'slug' => 'a2-u10-l1-vocab',
                        'title' => 'Vocabulary: Nature',
                        'title_uz' => 'Lug\'at: Tabiat',
                        'lesson_type' => 'vocabulary',
                        'content' => $this->getNatureVocabulary()
                    ],
                    [
                        'lesson_number' => 2,
                        'slug' => 'a2-u10-l2-grammar',
                        'title' => 'First Conditional',
                        'title_uz' => 'Grammatika: Shart Ergash Gaplar',
                        'lesson_type' => 'grammar',
                        'content' => $this->getNatureContent()
                    ],
                    [
                        'lesson_number' => 3,
                        'slug' => 'a2-u10-l3-practice',
                        'title' => 'Practice: Nature',
                        'title_uz' => 'Mashq: Tabiat',
                        'lesson_type' => 'practice',
                        'content' => $this->getNaturePractice()
                    ],
                    [
                        'lesson_number' => 4,
                        'slug' => 'a2-u10-l4-test',
                        'title' => 'Unit Test: Nature',
                        'title_uz' => 'Bo\'lim Testi: Tabiat',
                        'lesson_type' => 'test',
                        'content' => $this->getNatureTest()
                    ],
                ]
            ],
        ];
    }

    private function getPastSimpleContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'explanation' => [
                [
                    'title' => 'Past Simple (O\'tgan Zamon)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Biz **Past Simple** zamonini o\'tmishda tugagan ish-harakatlar uchun ishlatamiz. Masalan: "Men kecha ishladim" yoki "Ular o\'tgan yili Angliyaga borishdi".<br><br>Bu zamon o\'tmishdagi voqealarni, hikoyalarni va tarixingizni aytib berish uchun eng asosiy zamondir.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Formula',
                            'headers' => ['Type', 'Formula', 'Example'],
                            'rows' => [
                                ['Positive', 'Subject + V2/ed', 'I **played** football.'],
                                ['Negative', 'Subject + did not + V1', 'I **didn\'t play** football.'],
                                ['Question', 'Did + Subject + V1?', '**Did** you **play** football?']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Regular vs Irregular',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida fe\'llar ikki turga bo\'linadi: **To\'g\'ri (Regular)** va **Noto\'g\'ri (Irregular)**.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Regular Verbs (+ed)',
                            'items' => [
                                ['en' => 'Work -> Worked', 'uz' => 'Ishlamoq -> Ishladi'],
                                ['en' => 'Play -> Played', 'uz' => 'O\'ynamoq -> O\'ynadi'],
                                ['en' => 'Watch -> Watched', 'uz' => 'Ko\'rmoq -> Ko\'rdi']
                            ]
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Irregular Verbs (2-shakl)',
                            'items' => [
                                ['en' => 'Go -> Went', 'uz' => 'Bormoq -> Bordi'],
                                ['en' => 'See -> Saw', 'uz' => 'Ko\'rmoq -> Ko\'rdi'],
                                ['en' => 'Buy -> Bought', 'uz' => 'Sotib olmoq -> Sotib oldi']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Dialogue in Context',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Ali:</strong> Hiiko, what did you do yesterday?<br><strong>Niko:</strong> I <strong>went</strong> to the park with my friends. We <strong>played</strong> football.<br><strong>Ali:</strong> Did you <strong>win</strong>?<br><strong>Niko:</strong> No, we <strong>lost</strong>. But we <strong>had</strong> fun!'
                        ]
                    ]
                ],
                [
                    'title' => 'Pro Tips & Mistakes',
                    'sections' => [
                        [
                            'type' => 'tip',
                            'content' => 'Eng muhim qoida: "Did" ishlatilgan joyda (inkor va savol gaplarda), asosiy fe\'l har doim o\'zining 1-shakliga (oddiy holatiga) qaytadi. Hech qachon "Did went" demang!'
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Common Mistakes',
                            'items' => [
                                ['bad' => 'I didn\'t went to school.', 'good' => 'I didn\'t go to school.'],
                                ['bad' => 'Did she played?', 'good' => 'Did she play?'],
                                ['bad' => 'He buyed a car.', 'good' => 'He bought a car.']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ to school yesterday.', 'options' => ['go', 'went', 'gone'], 'correctAnswer' => 1, 'explanation' => 'Go -> Went keywords: yesterday'],
                ['type' => 'multiple_choice', 'question' => 'They ___ play football last week.', 'options' => ['didn\'t', 'don\'t', 'doesn\'t'], 'correctAnswer' => 0, 'explanation' => 'Past Simple inkor shakli: did not (didn\'t)'],
                ['type' => 'multiple_choice', 'question' => '___ you watch the movie?', 'options' => ['Do', 'Did', 'Were'], 'correctAnswer' => 1, 'explanation' => 'Savol berishda Did ishlatiladi.'],
                ['type' => 'true_false', 'question' => 'The past of "Buy" is "Buyed".', 'correctAnswer' => false, 'explanation' => 'Xato. Buy -> Bought (Irregular).'],
                ['type' => 'multiple_choice', 'question' => 'He ___ come to the party.', 'options' => ['didn\'t', 'wasn\'t', 'weren\'t'], 'correctAnswer' => 0, 'explanation' => 'He didn\'t come (Fe\'l borligi uchun did).']
            ]
        ];
    }

    private function getPastContinuousContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'explanation' => [
                [
                    'title' => 'Past Continuous (O\'tgan Davomiy Zamon)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Biz **Past Continuous** zamonini o\'tmishda ma\'lum bir vaqtda davom etayotgan (jarayondagi) ish-harakatlar uchun ishlatamiz. Masalan: "Men soat 5 da uxlayotgan edim".'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Formula',
                            'headers' => ['Type', 'Formula', 'Example'],
                            'rows' => [
                                ['Positive', 'S + was/were + V-ing', 'I **was reading**.'],
                                ['Negative', 'S + wasn\'t/weren\'t + V-ing', 'We **weren\'t sleeping**.'],
                                ['Question', 'Was/Were + S + V-ing?', '**Were** you **working**?']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'While vs When',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Odatda ikkita ish-harakatni bog\'lash uchun **While** (davomida) va **When** (qachonki) so\'zlari ishlatiladi.'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Examples',
                            'items' => [
                                ['en' => 'While I was cooking, he was reading.', 'uz' => 'Men ovqat pishirayotganimda, u o\'qiyotgan edi.'],
                                ['en' => 'I was sleeping when the phone rang.', 'uz' => 'Telefon jiringlaganda men uxlayotgan edim.']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Dialogue',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Police:</strong> What were you doing at 8 PM?<br><strong>Suspect:</strong> I <strong>was watching</strong> TV at home.<br><strong>Police:</strong> Were you alone?<br><strong>Suspect:</strong> No, my wife <strong>was cooking</strong> dinner.'
                        ]
                    ]
                ],
                [
                    'title' => 'Pro Tips & Mistakes',
                    'sections' => [
                        [
                            'type' => 'tip',
                            'content' => 'Eslab qoling: Qisqa va tez sodir bo\'lgan ishlar uchun Past Simple (rang, arrived), uzoq davom etgan jarayonlar uchun Past Continuous (was raining, was sleeping) ishlatiladi.'
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Common Mistakes',
                            'items' => [
                                ['bad' => 'I was go to school.', 'good' => 'I was going to school.'],
                                ['bad' => 'We was playing.', 'good' => 'We were playing.']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ watching TV.', 'options' => ['was', 'were', 'did'], 'correctAnswer' => 0, 'explanation' => 'I was watching.'],
                ['type' => 'multiple_choice', 'question' => 'They ___ playing football.', 'options' => ['was', 'were', 'are'], 'correctAnswer' => 1, 'explanation' => 'They were playing.'],
                ['type' => 'multiple_choice', 'question' => 'What ___ you doing?', 'options' => ['did', 'was', 'were'], 'correctAnswer' => 2, 'explanation' => 'What were you doing?'],
                ['type' => 'multiple_choice', 'question' => 'She was cooking ___ I was reading.', 'options' => ['if', 'while', 'so'], 'correctAnswer' => 1, 'explanation' => 'While (davomida) ikkita jarayonni bog\'laydi.'],
                ['type' => 'multiple_choice', 'question' => 'We ___ sleeping.', 'options' => ['wasn\'t', 'weren\'t', 'didn\'t'], 'correctAnswer' => 1, 'explanation' => 'We weren\'t sleeping.']
            ]
        ];
    }

    private function getComparativesContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'explanation' => [
                [
                    'title' => 'Comparatives (Sifat Darajalari)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Biz narsalarni solishtirish uchun **Comparative** (Qiyosiy) va eng zo\'rini aytish uchun **Superlative** (Orttirma) sifatlardan foydalanamiz.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Qoidalar',
                            'headers' => ['Sifat Turi', 'Comparative (-roq)', 'Superlative (eng)'],
                            'rows' => [
                                ['Short (Big)', 'Bigger', 'The Biggest'],
                                ['Long (Beautiful)', 'More beautiful', 'The Most beautiful'],
                                ['Ends in Y (Happy)', 'Happier', 'The Happiest']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Irregular Adjectives',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ba\'zi sifatlar qoidaga bo\'ysunmaydi (Noto\'g\'ri sifatlar).'
                        ],
                        [
                            'type' => 'examples',
                            'title' => 'Irregulars',
                            'items' => [
                                ['en' => 'Good -> Better -> The Best', 'uz' => 'Yaxshi -> Yaxshiroq -> Eng yaxshi'],
                                ['en' => 'Bad -> Worse -> The Worst', 'uz' => 'Yomon -> Yomonroq -> Eng yomon'],
                                ['en' => 'Far -> Further -> The Furthest', 'uz' => 'Uzoq -> Uzoqroq -> Eng uzoq']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Dialogue',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Shop Assistant:</strong> This red dress is nice.<br><strong>Customer:</strong> Yes, but the blue one is <strong>cheaper</strong>.<br><strong>Shop Assistant:</strong> True, but the red one is <strong>more beautiful</strong>.<br><strong>Customer:</strong> You are right. It is the <strong>best</strong> dress in the shop!'
                        ]
                    ]
                ],
                [
                    'title' => 'Pro Tips & Mistakes',
                    'sections' => [
                        [
                            'type' => 'tip',
                            'content' => 'Solishtirma darajada (Er/More) har doim "THAN" (qaraganda/ko\'ra) so\'zini ishlating. Orttirma darajada (Est/Most) har doim sifatdan oldin "THE" qo\'ying.'
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Common Mistakes',
                            'items' => [
                                ['bad' => 'He is more tall.', 'good' => 'He is taller.'],
                                ['bad' => 'The goodest book.', 'good' => 'The best book.'],
                                ['bad' => 'She is taller that me.', 'good' => 'She is taller than me.']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'This car is ___ than that one.', 'options' => ['fast', 'faster', 'fastest'], 'correctAnswer' => 1, 'explanation' => 'Than bor, demak Comparative (Faster).'],
                ['type' => 'multiple_choice', 'question' => 'She is the ___ girl in class.', 'options' => ['beautiful', 'more beautiful', 'most beautiful'], 'correctAnswer' => 2, 'explanation' => 'The bor, demak Superlative (Most beautiful).'],
                ['type' => 'multiple_choice', 'question' => 'Good -> ___', 'options' => ['Gooder', 'Better', 'Best'], 'correctAnswer' => 1, 'explanation' => 'Good -> Better.'],
                ['type' => 'multiple_choice', 'question' => 'Summer is ___ than winter.', 'options' => ['hot', 'hotter', 'hottest'], 'correctAnswer' => 1, 'explanation' => 'Hot -> Hotter.'],
                ['type' => 'multiple_choice', 'question' => 'This is the ___ movie ever.', 'options' => ['bad', 'worse', 'worst'], 'correctAnswer' => 2, 'explanation' => 'Eng yomon - The Worst.']
            ]
        ];
    }

    private function getFutureFormsContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'pages' => [
                [
                    'pageId' => 1,
                    'type' => 'grammar_intro',
                    'title' => 'Future Forms',
                    'titleUz' => 'Kelajakni ifodalash',
                    'content' => [
                        'intro' => 'Kelajak haqida гапирганда asosan ikki shakl ishlatiladi: **Will** va **Going to**. Ular orasida kichik farq bor.',
                        'table' => [
                            'headers' => ['Shakl', 'Ma\'no', 'Misol'],
                            'rows' => [
                                ['**Will**', 'O\'sha paytda qilingan qaror (Spontaneous)', 'I **will** help you! (Senga yordam beraman - hozir qaror qildim)'],
                                ['**Going to**', 'Rejalashtirilgan ish (Plan)', 'I am **going to** buy a car. (Mashina sotib olmoqchiman - rejam bor)']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Look at those clouds! It ___ rain.', 'options' => ['is going to', 'will', 'go to'], 'correctAnswer' => 0, 'explanation' => 'Osmonda bulutlar bor - bu fakt/belgi. Bashorat qilish uchun "going to" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'I ___ have the salad, please.', 'options' => ['am going to', 'will', 'want'], 'correctAnswer' => 1, 'explanation' => 'Restoranda buyurtma berish - spontan qaror. Shuning uchun "will" ishlatiladi.']
            ]
        ];
    }

    private function getPresentPerfectContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'explanation' => [
                [
                    'title' => 'Present Perfect (Hozirgi Tugallangan)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '**Present Perfect** o\'tgan ishning hozirgi paytga aloqasi borligini yoki natijasi muhimligini ko\'rsatadi. Vaqti aniq emas yoki muhim emas.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Formula',
                            'headers' => ['Type', 'Formula', 'Example'],
                            'rows' => [
                                ['Positive', 'S + have/has + V3', 'I **have eaten**.'],
                                ['Negative', 'S + haven\'t/hasn\'t + V3', 'She **hasn\'t arrived**.'],
                                ['Question', 'Have/Has + S + V3?', '**Have** you **seen** it?']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Keywords',
                    'sections' => [
                        [
                            'type' => 'examples',
                            'title' => 'Signal Words',
                            'items' => [
                                ['en' => 'Just (Hozirgina)', 'uz' => 'I have just finished.'],
                                ['en' => 'Already (Allaqachon)', 'uz' => 'I have already done it.'],
                                ['en' => 'Yet (Hali - inkorda)', 'uz' => 'I haven\'t eaten yet.']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Dialogue',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Interviewer:</strong> <strong>Have</strong> you <strong>ever</strong> been to London?<br><strong>Candidate:</strong> Yes, I <strong>have been</strong> there twice.<br><strong>Interviewer:</strong> When did you go?<br><strong>Candidate:</strong> I <strong>went</strong> in 2019. (Notice: Past Simple for specific time!)'
                        ]
                    ]
                ],
                [
                    'title' => 'Pro Tips & Mistakes',
                    'sections' => [
                        [
                            'type' => 'tip',
                            'content' => 'Agar gapda aniq vaqt (yesterday, in 2010, last week) bo\'lsa, Present Perfect ISHLATILMAYDI. Uning o\'rniga Past Simple ishlating.'
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Common Mistakes',
                            'items' => [
                                ['bad' => 'I have seen him yesterday.', 'good' => 'I saw him yesterday.'],
                                ['bad' => 'Did you have eaten?', 'good' => 'Have you eaten?'],
                                ['bad' => 'She has go home.', 'good' => 'She has gone home.']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I have ___ my homework.', 'options' => ['finish', 'finished', 'finishing'], 'correctAnswer' => 1, 'explanation' => 'Have + V3 (Finished).'],
                ['type' => 'multiple_choice', 'question' => 'She ___ not arrived yet.', 'options' => ['have', 'has', 'did'], 'correctAnswer' => 1, 'explanation' => 'She + Has.'],
                ['type' => 'multiple_choice', 'question' => 'Have you ___ been to USA?', 'options' => ['ever', 'never', 'always'], 'correctAnswer' => 0, 'explanation' => 'Savolda tajriba so\'rash -> Ever.'],
                ['type' => 'true_false', 'question' => 'I have seen him yesterday.', 'correctAnswer' => false, 'explanation' => 'Xato. Yesterday borligi uchun "I saw him" bo\'lishi kerak.'],
                ['type' => 'multiple_choice', 'question' => 'We have lived here ___ 20 years.', 'options' => ['since', 'for', 'in'], 'correctAnswer' => 1, 'explanation' => 'Vaqt davomiyligi -> For.']
            ]
        ];
    }
    private function getHealthContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'pages' => [
                [
                    'pageId' => 1,
                    'type' => 'grammar_intro',
                    'title' => 'Should & Must',
                    'titleUz' => 'Maslahat va Majburiyat',
                    'content' => [
                        'intro' => 'Ingliz tilida maslahat berish yoki majburiyatni ifodalash uchun **Should** va **Must** fe\'llari ishlatiladi.',
                        'table' => [
                            'title' => 'Farqi nimada?',
                            'headers' => ['Modal Fe\'l', 'Ma\'nosi', 'Qachon ishlatamiz?'],
                            'rows' => [
                                ['**Should**', 'Kerak (Maslahat)', 'Bi,irovga maslahat berganda (Yumshoq)'],
                                ['**Must**', 'Shart (Majburiyat)', 'Qoida yoki kuchli tavsiya bo\'lganda (Qattiq)']
                            ]
                        ]
                    ]
                ],
                [
                    'pageId' => 2,
                    'type' => 'grammar_rule',
                    'title' => 'Formula',
                    'explanation' => [
                        'formula' => [
                            'pattern' => 'Subject + should/must + Verb',
                            'patternUz' => 'Ega + should/must + Fe\'l',
                            'examples' => [
                                ['english' => 'You **should** drink water.', 'uzbek' => 'Siz suv ichishingiz kerak (Maslahat).'],
                                ['english' => 'You **must** stop at red light.', 'uzbek' => 'Qizil chiroqda to\'xtashingiz shart (Qoida).']
                            ]
                        ],
                        'visualTable' => [
                            'memoryTrick' => 'Should = Good idea (Yaxshi fikr) 👍\nMust = Rule (Qoida) 👮‍♂️'
                        ]
                    ]
                ],
                [
                    'pageId' => 3,
                    'type' => 'grammar_visual',
                    'title' => 'Health Problems',
                    'titleUz' => 'Muammolar va Maslahatlar',
                    'content' => [
                        'visualExamples' => [
                            [
                                'preposition' => 'Headache',
                                'uzbek' => 'Bosh og\'rig\'i',
                                'emoji' => '🤕',
                                'description' => 'Agar boshingiz og\'risa:',
                                'examples' => [
                                    ['english' => 'You **should** take medicine.', 'uzbek' => 'Dori ichishingiz kerak.'],
                                    ['english' => 'You **shouldn\'t** watch TV.', 'uzbek' => 'Televizor ko\'rmasligingiz kerak.']
                                ]
                            ],
                            [
                                'preposition' => 'Flu',
                                'uzbek' => 'Gripp',
                                'emoji' => '🤒',
                                'description' => 'Agar gripp bo\'lsangiz:',
                                'examples' => [
                                    ['english' => 'You **must** stay at home.', 'uzbek' => 'Uyda qolishingiz shart (Boshqalarga yuqmasligi uchun).']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'It is raining. You ___ take an umbrella.', 'options' => ['should', 'must', 'can'], 'correctAnswer' => 0, 'explanation' => 'Yomg\'irda soyabon olish - maslahat (should).'],
                ['type' => 'multiple_choice', 'question' => 'Drivers ___ stop at the red light.', 'options' => ['should', 'must', 'can'], 'correctAnswer' => 1, 'explanation' => 'Qizil chiroqda to\'xtash - qoida/majburiyat (must).']
            ]
        ];
    }

    private function getFoodContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'explanation' => [
                [
                    'title' => 'Countable & Uncountable (Sanaladigan va Sanalmaydigan)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Ingliz tilida otlar sanaladigan (bitta, ikkita deb sanasa bo\'ladigan) va sanalmaydigan (suyuqliklar, kukunlar) turlarga bo\'linadi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Farqlari',
                            'headers' => ['Type', 'Keywords', 'Example'],
                            'rows' => [
                                ['Countable', 'a/an, many, a few', 'A book, Many cars'],
                                ['Uncountable', 'some, much, a little', 'Some water, Much sugar'],
                                ['Both', 'some, any, a lot of', 'Some books, Some water']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Quantifiers',
                    'sections' => [
                        [
                            'type' => 'examples',
                            'title' => 'Key Rules',
                            'items' => [
                                ['en' => 'Use "Much" for Uncountable (negative/question).', 'uz' => 'Much - sanalmaydigan otlar uchun (ko\'p).'],
                                ['en' => 'Use "Many" for Countable.', 'uz' => 'Many - sanaladigan otlar uchun (ko\'p).'],
                                ['en' => 'Use "Any" for Questions/Negatives.', 'uz' => 'Any - inkor va so\'roq gaplarda.']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Dialogue',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>A:</strong> Do we have <strong>any</strong> milk?<br><strong>B:</strong> No, we don\'t. But we have <strong>some</strong> apple juice.<br><strong>A:</strong> How <strong>much</strong> juice is there?<br><strong>B:</strong> Just <strong>a little</strong>. We need to buy <strong>some</strong> more.'
                        ]
                    ]
                ],
                [
                    'title' => 'Pro Tips & Mistakes',
                    'sections' => [
                        [
                            'type' => 'tip',
                            'content' => 'Non (Bread), Pishloq (Cheese), Pul (Money) va Soch (Hair) ingliz tilida odatda SANALMAYDI. Ularga "a" yoki "one" qo\'shmang.'
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Common Mistakes',
                            'items' => [
                                ['bad' => 'I have a money.', 'good' => 'I have money / some money.'],
                                ['bad' => 'How much friends do you have?', 'good' => 'How many friends do you have?'],
                                ['bad' => 'Pass me a bread.', 'good' => 'Pass me some bread / a piece of bread.']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'How ___ water do you drink?', 'options' => ['many', 'much', 'any'], 'correctAnswer' => 1, 'explanation' => 'Water sanalmaydi -> Much.'],
                ['type' => 'multiple_choice', 'question' => 'I have ___ friends.', 'options' => ['a little', 'a few', 'much'], 'correctAnswer' => 1, 'explanation' => 'Friends sanaladi -> A few.'],
                ['type' => 'multiple_choice', 'question' => 'Do you have ___ money?', 'options' => ['some', 'any', 'many'], 'correctAnswer' => 1, 'explanation' => 'Savol gap -> Any.'],
                ['type' => 'true_false', 'question' => 'I have a bread.', 'correctAnswer' => false, 'explanation' => 'Xato. Bread sanalmaydi.'],
                ['type' => 'multiple_choice', 'question' => 'There are ___ apples.', 'options' => ['much', 'a little', 'a lot of'], 'correctAnswer' => 2, 'explanation' => 'A lot of hamma otlar bilan ishlatiladi.']
            ]
        ];
    }

    private function getCityContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'explanation' => [
                [
                    'title' => 'Prepositions of Movement (Harakat Predloglari)',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => 'Harakatni ifodalash uchun maxsus predloglardan foydalanamiz. Ular "Qayerga?" yoki "Qayerdan?" degan savollarga javob beradi.'
                        ],
                        [
                            'type' => 'table',
                            'title' => 'Common Prepositions',
                            'headers' => ['Preposition', 'Meaning', 'Example'],
                            'rows' => [
                                ['To / Into', 'Ga / Ichiga', 'Go **into** the room.'],
                                ['Out of', 'Ichidan tashqariga', 'Get **out of** the car.'],
                                ['Across', 'Kesib o\'tish (ko\'ndalang)', 'Walk **across** the road.'],
                                ['Along', 'Bo\'ylab', 'Run **along** the street.']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'More Prepositions',
                    'sections' => [
                        [
                            'type' => 'examples',
                            'title' => 'Visual Examples',
                            'items' => [
                                ['en' => 'Through: Tunnel or Forest', 'uz' => 'Ichidan (teshib o\'tish): Tunnel yoki o\'rmon.'],
                                ['en' => 'Over: Bridge or Wall', 'uz' => 'Ustidan oshib o\'tish: Ko\'prik yoki devor.'],
                                ['en' => 'Past: Passing something', 'uz' => 'Yonidan o\'tib ketish.']
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Dialogue',
                    'sections' => [
                        [
                            'type' => 'text',
                            'content' => '<strong>Tourist:</strong> Excuse me, how do I get to the bank?<br><strong>Local:</strong> Go <strong>along</strong> this street, then walk <strong>across</strong> the bridge. Go <strong>through</strong> the park. The bank is <strong>past</strong> the cinema.'
                        ]
                    ]
                ],
                [
                    'title' => 'Pro Tips & Mistakes',
                    'sections' => [
                        [
                            'type' => 'tip',
                            'content' => '"Get on/off" jamoat transporti (bus, train, plane) uchun ishlatiladi. "Get in/out of" esa shaxsiy transport (car, taxi) uchun ishlatiladi.'
                        ],
                        [
                            'type' => 'mistakes',
                            'title' => 'Common Mistakes',
                            'items' => [
                                ['bad' => 'Get out the bus.', 'good' => 'Get OFF the bus.'],
                                ['bad' => 'Walk in the street.', 'good' => 'Walk ACROSS / ALONG the street.'],
                                ['bad' => 'Go to home.', 'good' => 'Go home.'],
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Go ___ the bridge.', 'options' => ['across', 'through', 'into'], 'correctAnswer' => 0, 'explanation' => 'Ko\'prik ustidan -> Across.'],
                ['type' => 'multiple_choice', 'question' => 'The train went ___ the tunnel.', 'options' => ['over', 'through', 'along'], 'correctAnswer' => 1, 'explanation' => 'Tunnel ichidan -> Through.'],
                ['type' => 'multiple_choice', 'question' => 'Get ___ the bus at the next stop.', 'options' => ['out', 'off', 'from'], 'correctAnswer' => 1, 'explanation' => 'Avtobusdan tushish -> Get off.'],
                ['type' => 'multiple_choice', 'question' => 'He jumped ___ the water.', 'options' => ['into', 'in', 'to'], 'correctAnswer' => 0, 'explanation' => 'Harakat bilan ichiga -> Into.'],
                ['type' => 'multiple_choice', 'question' => 'Walk ___ the street to the end.', 'options' => ['across', 'along', 'through'], 'correctAnswer' => 1, 'explanation' => 'Ko\'cha bo\'ylab -> Along.']
            ]
        ];
    }

    private function getClothesContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'pages' => [
                [
                    'pageId' => 1,
                    'type' => 'grammar_intro',
                    'title' => 'Too & Enough',
                    'titleUz' => 'Juda va Yetarlicha',
                    'content' => [
                        'intro' => 'Kiyim sotib olayotganda o\'lcham to\'g\'ri kelmasa, **Too** (o\'ta/juda) yoki **Not Enough** (yetarlicha emas) so\'zlarini ishlatamiz.',
                        'table' => [
                            'headers' => ['So\'z', 'Ma\'no', 'Misol'],
                            'rows' => [
                                ['**Too**', 'Juda (Normadan ortiq)', 'It is **too** big. (Bu juda katta - menga to\'g\'ri kelmaydi).'],
                                ['**Enough**', 'Yetarlicha', 'It is big **enough**. (Bu yetarlicha katta - menga to\'g\'ri keladi).']
                            ]
                        ]
                    ]
                ],
                [
                    'pageId' => 2,
                    'type' => 'grammar_rule',
                    'title' => 'Qoida',
                    'explanation' => [
                        'visualTable' => [
                            'title' => 'Joylashuvi',
                            'memoryTrick' => 'Too + Sifat (Too big)\nSifat + Enough (Big enough)'
                        ],
                        'formula' => [
                            'pattern' => 'Too + Adjective | Adjective + Enough',
                            'patternUz' => 'Too + Sifat | Sifat + Enough',
                            'examples' => [
                                ['english' => 'This shirt is **too small**.', 'uzbek' => 'Bu ko\'ylak juda kichkina.'],
                                ['english' => 'This shirt is not **big enough**.', 'uzbek' => 'Bu ko\'ylak yetarlicha katta emas.']
                            ]
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I can\'t buy it. It is ___ expensive.', 'options' => ['too', 'enough', 'many'], 'correctAnswer' => 0, 'explanation' => 'Juda qimmat -> Too expensive.'],
                ['type' => 'multiple_choice', 'question' => 'He is not old ___ to drive a car.', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 1, 'explanation' => 'Old enough -> Sifatdan keyin enough keladi.']
            ]
        ];
    }

    private function getNatureContent(): array
    {
        return [
            'level' => 'A2',
            'type' => 'grammar',
            'pages' => [
                [
                    'pageId' => 1,
                    'type' => 'grammar_intro',
                    'title' => 'First Conditional',
                    'titleUz' => 'Shart Ergash Gaplar (1-tur)',
                    'content' => [
                        'intro' => '**First Conditional** kelajakdagi aniq bo\'lishi mumkin bo\'lgan voqealarni ifodalash uchun ishlatiladi. "Agar X qilsang, Y bo\'ladi".',
                    ]
                ],
                [
                    'pageId' => 2,
                    'type' => 'grammar_rule',
                    'title' => 'Formula',
                    'explanation' => [
                        'formula' => [
                            'pattern' => 'If + Present Simple, + Will + Verb',
                            'patternUz' => 'Agar + Hozirgi Zamon, + Kelajak (Will)',
                            'examples' => [
                                ['english' => 'If it **rains**, I **will stay** at home.', 'uzbek' => 'Agar yomg\'ir yog\'sa, men uyda qolaman.'],
                                ['english' => 'If you **study**, you **will pass** the exam.', 'uzbek' => 'Agar o\'qisangiz, imtihondan o\'tasiz.']
                            ]
                        ],
                        'visualTable' => [
                            'title' => 'Eslab qoling',
                            'memoryTrick' => '"If" qatnashgan qismda WILL ishlatmang! (If I will go ❌ -> If I go ✅)'
                        ]
                    ]
                ]
            ],
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'If she ___, she will prove it.', 'options' => ['come', 'comes', 'will come'], 'correctAnswer' => 1, 'explanation' => 'If qismida Present Simple (she comes) ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'If I have time, I ___ call you.', 'options' => ['will', 'am', 'do'], 'correctAnswer' => 0, 'explanation' => 'Ikkinchi qismda (natija) Will ishlatiladi.']
            ]
        ];
    }
    // --- Practice Content Methods ---

    private function getPastSimplePractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'Choose the correct sentence:', 'options' => ['I did went to school.', 'I went to school.', 'I go to school yesterday.'], 'correctAnswer' => 1, 'explanation' => 'Past Simpleda fe\'lning 2-shakli ishlatiladi (went). Yordamchi fe\'l (did) kerak emas.'],
                ['type' => 'multiple_choice', 'question' => 'Select the past form of "Buy":', 'options' => ['Buyed', 'Bought', 'Bring'], 'correctAnswer' => 1, 'explanation' => 'Buy - noto\'g\'ri fe\'l, o\'tgan zamoni Bought.'],
                ['type' => 'multiple_choice', 'question' => 'Complete: She ___ not ___ pizza.', 'options' => ['did / eat', 'did / ate', 'do / ate'], 'correctAnswer' => 0, 'explanation' => 'Inkor gapda "did not" va fe\'lning 1-shakli (eat) ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Translate: "Biz futbol o\'ynadik"', 'options' => ['We play football.', 'We played football.', 'We playing football.'], 'correctAnswer' => 1, 'explanation' => 'Play - to\'g\'ri fe\'l, unga -ed qo\'shiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Find the mistake: "He didn\'t saw me."', 'options' => ['didn\'t', 'saw', 'me'], 'correctAnswer' => 1, 'explanation' => '"Did" bor joyda fe\'l 1-shaklga qaytishi kerak (see). "Saw" xato.'],
                ['type' => 'multiple_choice', 'question' => 'When ___ you born?', 'options' => ['did', 'were', 'was'], 'correctAnswer' => 1, 'explanation' => 'Tug\'ilmoq (be born) passiv ma\'noda. You bilan "were" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'I ___ a book yesterday.', 'options' => ['read', 'readed', 'rode'], 'correctAnswer' => 0, 'explanation' => 'Read o\'tgan zamonda ham "read" (red deb o\'qiladi) yoziladi.'],
                ['type' => 'multiple_choice', 'question' => 'Did they ___ late?', 'options' => ['came', 'come', 'coming'], 'correctAnswer' => 1, 'explanation' => 'Savolda "Did" bo\'lgani uchun asosiy fe\'l (come) o\'zgarmaydi.'],
                ['type' => 'multiple_choice', 'question' => 'Choose the negative sentence:', 'options' => ['I not went.', 'I didn\'t went.', 'I didn\'t go.'], 'correctAnswer' => 2, 'explanation' => 'To\'g\'ri inkor shakli: didn\'t + V1.'],
                ['type' => 'multiple_choice', 'question' => 'My friend ___ me a gift.', 'options' => ['give', 'gived', 'gave'], 'correctAnswer' => 2, 'explanation' => 'Give - noto\'g\'ri fe\'l, o\'tgan zamoni Gave.']
            ]
        ];
    }

    private function getPastContinuousPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ watching TV at 5 PM.', 'options' => ['am', 'was', 'were'], 'correctAnswer' => 1, 'explanation' => 'I bilan "was" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'They ___ playing football.', 'options' => ['was', 'were', 'did'], 'correctAnswer' => 1, 'explanation' => 'They bilan "were" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'What were you ___?', 'options' => ['do', 'doing', 'did'], 'correctAnswer' => 1, 'explanation' => 'Past Continuousda fe\'lga -ing qo\'shiladi.'],
                ['type' => 'multiple_choice', 'question' => 'She was cooking ___ I was reading.', 'options' => ['when', 'while', 'where'], 'correctAnswer' => 1, 'explanation' => 'Ikki ish bir vaqtda bo\'lsa "while" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'We ___ sleeping when you called.', 'options' => ['wasn\'t', 'weren\'t', 'didn\'t'], 'correctAnswer' => 1, 'explanation' => 'We uchun "were not" (weren\'t) to\'g\'ri.'],
                ['type' => 'multiple_choice', 'question' => 'Find the correct order:', 'options' => ['Was she working?', 'She was working?', 'Working was she?'], 'correctAnswer' => 0, 'explanation' => 'Savol gapda yordamchi fe\'l (Was) egadan oldinga o\'tadi.'],
                ['type' => 'multiple_choice', 'question' => 'It ___ raining all day.', 'options' => ['was', 'were', 'is'], 'correctAnswer' => 0, 'explanation' => 'It bilan "was" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Translate: "Ular kulishayotgan edi"', 'options' => ['They laughed.', 'They were laughing.', 'They are laughing.'], 'correctAnswer' => 1, 'explanation' => 'O\'tgan zamonda davom etayotgan ish - Were laughing.'],
                ['type' => 'multiple_choice', 'question' => 'I saw him while I ___ to work.', 'options' => ['walked', 'was walking', 'walking'], 'correctAnswer' => 1, 'explanation' => 'While dan keyin odatda Continuous keladi.'],
                ['type' => 'multiple_choice', 'question' => '___ you listening to me?', 'options' => ['Did', 'Was', 'Were'], 'correctAnswer' => 2, 'explanation' => 'You bilan "Were" ishlatiladi.']
            ]
        ];
    }

    private function getComparativesPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'This car is ___ than that one.', 'options' => ['fast', 'faster', 'fastest'], 'correctAnswer' => 1, 'explanation' => 'Solishtirma darajada -er qo\'shiladi.'],
                ['type' => 'multiple_choice', 'question' => 'She is ___ than her sister.', 'options' => ['beautiful', 'more beautiful', 'beautifuler'], 'correctAnswer' => 1, 'explanation' => 'Uzun sifatlar oldiga "more" qo\'yiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Correct form of "Good":', 'options' => ['Gooder', 'More good', 'Better'], 'correctAnswer' => 2, 'explanation' => 'Good - noto\'g\'ri sifat, solishtirma shakli Better.'],
                ['type' => 'multiple_choice', 'question' => 'Russia is ___ than France.', 'options' => ['big', 'bigger', 'more big'], 'correctAnswer' => 1, 'explanation' => 'Big -> Bigger (undosh ikkilanadi).'],
                ['type' => 'multiple_choice', 'question' => 'I am ___ than you.', 'options' => ['old', 'older', 'more older'], 'correctAnswer' => 1, 'explanation' => 'Old -> Older.'],
                ['type' => 'multiple_choice', 'question' => 'Math is ___ difficult than Art.', 'options' => ['most', 'more', 'much'], 'correctAnswer' => 1, 'explanation' => 'Solishtirish uchun "more" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Which sentence is correct?', 'options' => ['He is tall than me.', 'He is taller than me.', 'He is taller that me.'], 'correctAnswer' => 1, 'explanation' => 'Solishtirish bog\'lovchisi "than".'],
                ['type' => 'multiple_choice', 'question' => 'Bad -> ___', 'options' => ['Badder', 'Worse', 'More bad'], 'correctAnswer' => 1, 'explanation' => 'Bad - noto\'g\'ri sifat, solishtirma shakli Worse.'],
                ['type' => 'multiple_choice', 'question' => 'Today is ___ than yesterday.', 'options' => ['hot', 'hotter', 'more hot'], 'correctAnswer' => 1, 'explanation' => 'Hot -> Hotter.'],
                ['type' => 'multiple_choice', 'question' => 'Is English ___ than German?', 'options' => ['easier', 'easyer', 'more easy'], 'correctAnswer' => 0, 'explanation' => 'Easy -> Easier (y harfi i ga aylanadi).']
            ]
        ];
    }

    private function getFuturePractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ visit my grandma tomorrow.', 'options' => ['will', 'am', 'did'], 'correctAnswer' => 0, 'explanation' => 'Kelajak uchun "will" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Look at the clouds! It ___ rain.', 'options' => ['will', 'is going to', 'shall'], 'correctAnswer' => 1, 'explanation' => 'Aniq belgi (bulutlar) bo\'lsa "going to" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'In 2050, people ___ fly cars.', 'options' => ['will', 'are going to', 'flying'], 'correctAnswer' => 0, 'explanation' => 'Bashorat (Prediction) uchun "will" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => '___ you help me?', 'options' => ['Going to', 'Will', 'Are'], 'correctAnswer' => 1, 'explanation' => 'Taklif yoki iltimos uchun "will" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'She ___ to Paris next week.', 'options' => ['is flying', 'will flying', 'fly'], 'correctAnswer' => 0, 'explanation' => 'Aniq rejalashtirilgan ishlar uchun Continuous zamoni kelajak ma\'nosida keladi.'],
                ['type' => 'multiple_choice', 'question' => 'I promise I ___ tell anyone.', 'options' => ['don\'t', 'won\'t', 'not'], 'correctAnswer' => 1, 'explanation' => 'Va\'da berishda "will" yoki "won\'t" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'We are ___ to buy a house.', 'options' => ['go', 'going', 'will'], 'correctAnswer' => 1, 'explanation' => 'Going to strukturasi.'],
                ['type' => 'multiple_choice', 'question' => 'Short form of "I will":', 'options' => ['I\'ll', 'Ill', 'I will'], 'correctAnswer' => 0, 'explanation' => 'I will -> I\'ll'],
                ['type' => 'multiple_choice', 'question' => 'Translate: "Men doktor bo\'lmoqchiman"', 'options' => ['I be a doctor.', 'I am going to be a doctor.', 'I doctor.'], 'correctAnswer' => 1, 'explanation' => 'Niyat (Intention) uchun "going to" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'When ___ they arrive?', 'options' => ['will', 'do', 'did'], 'correctAnswer' => 0, 'explanation' => 'Kelajak vaqti so\'ralmoqda.']
            ]
        ];
    }

    private function getPresentPerfectPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'I have ___ my keys.', 'options' => ['lose', 'lost', 'losed'], 'correctAnswer' => 1, 'explanation' => 'Lose fe\'lining V3 shakli - Lost.'],
                ['type' => 'multiple_choice', 'question' => 'She ___ finished her homework.', 'options' => ['have', 'has', 'did'], 'correctAnswer' => 1, 'explanation' => 'She bilan "has" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Have you ___ eaten sushi?', 'options' => ['ever', 'never', 'just'], 'correctAnswer' => 0, 'explanation' => 'Savolda tajriba haqida so\'rash uchun "ever" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'We have lived here ___ 10 years.', 'options' => ['since', 'for', 'in'], 'correctAnswer' => 1, 'explanation' => 'Vaqt oralig\'i (10 yil) uchun "for" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'He has ___ London 2015.', 'options' => ['since', 'for', 'at'], 'correctAnswer' => 0, 'explanation' => 'Boshlanish vaqti (2015) uchun "since" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'I have never ___ to USA.', 'options' => ['be', 'been', 'was'], 'correctAnswer' => 1, 'explanation' => 'Borib kelganlik ma\'nosida "been" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Complete: She has just ___ out.', 'options' => ['go', 'went', 'gone'], 'correctAnswer' => 2, 'explanation' => 'Go fe\'lining V3 shakli - Gone.'],
                ['type' => 'multiple_choice', 'question' => '___ you seen the news?', 'options' => ['Has', 'Have', 'Did'], 'correctAnswer' => 1, 'explanation' => 'You bilan "Have" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'The train hasn\'t arrived ___.', 'options' => ['already', 'just', 'yet'], 'correctAnswer' => 2, 'explanation' => 'Inkorda "yet" (hali) ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Choose correct sentence:', 'options' => ['I have saw him.', 'I have seen him.', 'I has seen him.'], 'correctAnswer' => 1, 'explanation' => 'I have + V3 (seen).']
            ]
        ];
    }

    private function getHealthPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'I have a headache. You ___ take an aspirin.', 'options' => ['should', 'shouldn\'t', 'mustn\'t'], 'correctAnswer' => 0, 'explanation' => 'Maslahat berishda "should" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'You ___ smoke in the hospital.', 'options' => ['must', 'mustn\'t', 'should'], 'correctAnswer' => 1, 'explanation' => 'Taqiq (qonun-qoida) uchun "mustn\'t" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'He isn\'t feeling well. He ___ go to work.', 'options' => ['should', 'shouldn\'t', 'must'], 'correctAnswer' => 1, 'explanation' => 'Salbiy maslahat - shouldn\'t.'],
                ['type' => 'multiple_choice', 'question' => 'If you have a toothache, go to the ___.', 'options' => ['doctor', 'dentist', 'nurse'], 'correctAnswer' => 1, 'explanation' => 'Tish og\'rig\'i bilan tish shifokoriga (dentist) boriladi.'],
                ['type' => 'multiple_choice', 'question' => 'Drivers ___ stop at red light.', 'options' => ['should', 'must', 'can'], 'correctAnswer' => 1, 'explanation' => 'Qonuniy majburiyat - Must.'],
                ['type' => 'multiple_choice', 'question' => 'It\'s raining. You ___ take an umbrella.', 'options' => ['must', 'should', 'have'], 'correctAnswer' => 1, 'explanation' => 'Kuchli maslahat - Should.'],
                ['type' => 'multiple_choice', 'question' => 'Translate: "Sen ko\'proq suv ichishing kerak"', 'options' => ['You must drink water.', 'You should drink more water.', 'You drink water.'], 'correctAnswer' => 1, 'explanation' => 'Maslahat - Should.'],
                ['type' => 'multiple_choice', 'question' => '___ I call the doctor?', 'options' => ['Should', 'Must', 'Do'], 'correctAnswer' => 0, 'explanation' => 'Maslahat so\'rashda "Should I...?" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'She has a fever. She ___ stay in bed.', 'options' => ['should', 'mustn\'t', 'can\'t'], 'correctAnswer' => 0, 'explanation' => 'Kasallikda dam olish kerak.'],
                ['type' => 'multiple_choice', 'question' => 'Choose the advice:', 'options' => ['You must pay taxes.', 'You should watch this movie.', 'You have to work.'], 'correctAnswer' => 1, 'explanation' => 'Kino ko\'rish bu majburiyat emas, maslahat.']
            ]
        ];
    }

    private function getFoodPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'There is ___ water in the bottle.', 'options' => ['some', 'any', 'a few'], 'correctAnswer' => 0, 'explanation' => 'Darak gapda sanalmaydigan ot (water) bilan "some" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'Do we have ___ eggs?', 'options' => ['some', 'any', 'much'], 'correctAnswer' => 1, 'explanation' => 'Savol gapda "any" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'How ___ money do you have?', 'options' => ['many', 'much', 'any'], 'correctAnswer' => 1, 'explanation' => 'Pul (money) sanalmaydi, shuning uchun "Much".'],
                ['type' => 'multiple_choice', 'question' => 'How ___ apples are there?', 'options' => ['many', 'much', 'any'], 'correctAnswer' => 0, 'explanation' => 'Olmalar (apples) sanaladi, shuning uchun "Many".'],
                ['type' => 'multiple_choice', 'question' => 'I have ___ friends.', 'options' => ['a little', 'a few', 'much'], 'correctAnswer' => 1, 'explanation' => 'Do\'stlar sanaladi -> a few (bir nechta).'],
                ['type' => 'multiple_choice', 'question' => 'There is ___ sugar left.', 'options' => ['a little', 'a few', 'many'], 'correctAnswer' => 0, 'explanation' => 'Shakar sanalmaydi -> a little (ozgina).'],
                ['type' => 'multiple_choice', 'question' => 'Countable noun:', 'options' => ['Milk', 'Banana', 'Rice'], 'correctAnswer' => 1, 'explanation' => 'Banana (banan) sanaladi.'],
                ['type' => 'multiple_choice', 'question' => 'Uncountable noun:', 'options' => ['Car', 'Bread', 'Book'], 'correctAnswer' => 1, 'explanation' => 'Bread (non) ingliz tilida sanalmaydi deb hisoblanadi.'],
                ['type' => 'multiple_choice', 'question' => 'Would you like ___ coffee?', 'options' => ['some', 'any', 'a'], 'correctAnswer' => 0, 'explanation' => 'Taklif (Offer) qilishda "some" ishlatiladi, savol bo\'lsa ham.'],
                ['type' => 'multiple_choice', 'question' => 'I don\'t eat ___ meat.', 'options' => ['many', 'much', 'some'], 'correctAnswer' => 1, 'explanation' => 'Meat sanalmaydi, inkorda "much" keladi.']
            ]
        ];
    }

    private function getCityPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'Walk ___ the bridge.', 'options' => ['across', 'into', 'under'], 'correctAnswer' => 0, 'explanation' => 'Ko\'prik ustidan o\'tish - Across.'],
                ['type' => 'multiple_choice', 'question' => 'Go ___ the street.', 'options' => ['along', 'into', 'through'], 'correctAnswer' => 0, 'explanation' => 'Ko\'cha bo\'ylab yurish - Along.'],
                ['type' => 'multiple_choice', 'question' => 'The cat jumped ___ the box.', 'options' => ['out of', 'into', 'through'], 'correctAnswer' => 1, 'explanation' => 'Ichiga sakradi - Into.'],
                ['type' => 'multiple_choice', 'question' => 'Go ___ the tunnel.', 'options' => ['across', 'through', 'over'], 'correctAnswer' => 1, 'explanation' => 'Tunnel ichidan o\'tish - Through.'],
                ['type' => 'multiple_choice', 'question' => 'He walked ___ the hill.', 'options' => ['up', 'into', 'through'], 'correctAnswer' => 0, 'explanation' => 'Tepaga chiqish - Up.'],
                ['type' => 'multiple_choice', 'question' => 'Come ___ the stairs.', 'options' => ['down', 'under', 'through'], 'correctAnswer' => 0, 'explanation' => 'Zinadan tushish - Down.'],
                ['type' => 'multiple_choice', 'question' => 'Go ___ the bank and turn left.', 'options' => ['past', 'through', 'into'], 'correctAnswer' => 0, 'explanation' => 'Bank yonidan o\'tib ketish - Past.'],
                ['type' => 'multiple_choice', 'question' => 'The bird flew ___ the house.', 'options' => ['over', 'through', 'in'], 'correctAnswer' => 0, 'explanation' => 'Uy ustidan uchib o\'tish - Over.'],
                ['type' => 'multiple_choice', 'question' => 'Get ___ the bus.', 'options' => ['out', 'off', 'from'], 'correctAnswer' => 1, 'explanation' => 'Avtobusdan tushish - Get off.'],
                ['type' => 'multiple_choice', 'question' => 'Get ___ the car.', 'options' => ['into', 'onto', 'up'], 'correctAnswer' => 0, 'explanation' => 'Mashinaga minish - Get into.']
            ]
        ];
    }

    private function getClothesPractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'This shirt is ___ big for me.', 'options' => ['too', 'enough', 'much'], 'correctAnswer' => 0, 'explanation' => 'Juda katta (salbiy ma\'noda) - Too big.'],
                ['type' => 'multiple_choice', 'question' => 'It isn\'t cheap ___ to buy.', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 1, 'explanation' => 'Sifatdan keyin "enough" keladi (Cheap enough).'],
                ['type' => 'multiple_choice', 'question' => 'I don\'t have ___ money.', 'options' => ['enough', 'too', 'many'], 'correctAnswer' => 0, 'explanation' => 'Otdan oldin "enough" keladi (Enough money).'],
                ['type' => 'multiple_choice', 'question' => 'He is old ___ to drive.', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 1, 'explanation' => 'Yetarlicha yoshda - Old enough.'],
                ['type' => 'multiple_choice', 'question' => 'The tea is ___ hot to drink.', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 0, 'explanation' => 'Ichish uchun o\'ta issiq - Too hot.'],
                ['type' => 'multiple_choice', 'question' => 'Correct sentence:', 'options' => ['He is enough tall.', 'He is tall enough.', 'He is too tall enough.'], 'correctAnswer' => 1, 'explanation' => 'Sifat + Enough.'],
                ['type' => 'multiple_choice', 'question' => 'Correct sentence:', 'options' => ['I have money enough.', 'I have enough money.', 'I have too money.'], 'correctAnswer' => 1, 'explanation' => 'Enough + Ot.'],
                ['type' => 'multiple_choice', 'question' => 'These shoes are ___ small.', 'options' => ['enough', 'too', 'much'], 'correctAnswer' => 1, 'explanation' => 'Juda kichik - Too small.'],
                ['type' => 'multiple_choice', 'question' => 'Is it warm ___ for you?', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 1, 'explanation' => 'Warm enough?'],
                ['type' => 'multiple_choice', 'question' => 'This bag is too expensive. It costs ___ much.', 'options' => ['too', 'enough', 'to'], 'correctAnswer' => 0, 'explanation' => 'Too much - juda ko\'p.']
            ]
        ];
    }

    private function getNaturePractice(): array
    {
        return [
            'level' => 'A2',
            'type' => 'practice',
            'exercises' => [
                ['type' => 'multiple_choice', 'question' => 'If it rains, I ___ stay home.', 'options' => ['will', 'would', 'did'], 'correctAnswer' => 0, 'explanation' => 'First Conditional: If + Present, Will + Verb.'],
                ['type' => 'multiple_choice', 'question' => 'If you ___ hard, you will pass.', 'options' => ['study', 'will study', 'studied'], 'correctAnswer' => 0, 'explanation' => 'If qismida Present Simple (study) bo\'lishi kerak.'],
                ['type' => 'multiple_choice', 'question' => 'She will be late if she ___ hurry.', 'options' => ['doesn\'t', 'won\'t', 'don\'t'], 'correctAnswer' => 0, 'explanation' => 'If she doesn\'t...'],
                ['type' => 'multiple_choice', 'question' => 'What ___ you do if he comes?', 'options' => ['will', 'do', 'did'], 'correctAnswer' => 0, 'explanation' => 'Natija qismida "will" ishlatiladi.'],
                ['type' => 'multiple_choice', 'question' => 'If I see him, I ___ tell him.', 'options' => ['tell', 'will tell', 'told'], 'correctAnswer' => 1, 'explanation' => 'I will tell.'],
                ['type' => 'multiple_choice', 'question' => 'Unless means:', 'options' => ['If', 'If not', 'When'], 'correctAnswer' => 1, 'explanation' => 'Unless = If not (Agar ...masa).'],
                ['type' => 'multiple_choice', 'question' => 'We will go to the park if the weather ___ good.', 'options' => ['is', 'will be', 'was'], 'correctAnswer' => 0, 'explanation' => 'If the weather IS good.'],
                ['type' => 'multiple_choice', 'question' => 'If you mix red and white, you ___ pink.', 'options' => ['get', 'will get', 'got'], 'correctAnswer' => 0, 'explanation' => 'Zero Conditional (fakt) bo\'lsa ikkala tomon ham Present bo\'ladi.'],
                ['type' => 'multiple_choice', 'question' => 'She ___ buy a car if she saves money.', 'options' => ['will', 'is', 'did'], 'correctAnswer' => 0, 'explanation' => 'She will buy.'],
                ['type' => 'multiple_choice', 'question' => 'If you don\'t water plants, they ___.', 'options' => ['die', 'will die', 'died'], 'correctAnswer' => 0, 'explanation' => 'Fakt (Zero Conditional) - Die.']
            ]
        ];
    }


    // --- Vocabulary Content Methods ---

    private function getPastSimpleVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Yesterday', 'translation' => 'Kecha', 'ipa' => '/ˈjɛstədeɪ/', 'emoji' => '📅', 'definition' => 'Bugundan oldingi kun.', 'example' => 'I saw him **yesterday**.'],
                ['word' => 'Ago', 'translation' => 'Oldin', 'ipa' => '/əˈɡəʊ/', 'emoji' => '🔙', 'definition' => 'Hozirgi vaqtdan oldin.', 'example' => 'We met two years **ago**.'],
                ['word' => 'Last', 'translation' => 'O\'tgan', 'ipa' => '/lɑːst/', 'emoji' => '⏮️', 'definition' => 'Eng oxirgi yoki o\'tgan vaqt.', 'example' => 'I went there **last** week.'],
                ['word' => 'Did', 'translation' => 'Bajardi (yordamchi fe\'l)', 'ipa' => '/dɪd/', 'emoji' => '✅', 'definition' => 'Do fe\'lining o\'tgan zamon shakli.', 'example' => '**Did** you go to school?'],
                ['word' => 'Went', 'translation' => 'Bordi', 'ipa' => '/wɛnt/', 'emoji' => '🚶', 'definition' => 'Go (bormoq) fe\'lining o\'tgan zamon shakli.', 'example' => 'He **went** to the park.'],
                ['word' => 'Saw', 'translation' => 'Ko\'rdi', 'ipa' => '/sɔː/', 'emoji' => '👀', 'definition' => 'See (ko\'rmoq) fe\'lining o\'tgan zamon shakli.', 'example' => 'I **saw** a movie.'],
                ['word' => 'Ate', 'translation' => 'Yedi', 'ipa' => '/ɛt/', 'emoji' => '🍔', 'definition' => 'Eat (yemoq) fe\'lining o\'tgan zamon shakli.', 'example' => 'She **ate** pizza.'],
                ['word' => 'Bought', 'translation' => 'Sotib oldi', 'ipa' => '/bɔːt/', 'emoji' => '🛍️', 'definition' => 'Buy (sotib olmoq) fe\'lining o\'tgan zamon shakli.', 'example' => 'We **bought** a new car.'],
                ['word' => 'Had', 'translation' => 'Bor edi / Ega edi', 'ipa' => '/hæd/', 'emoji' => '🤲', 'definition' => 'Have (bor bo\'lmoq) fe\'lining o\'tgan zamon shakli.', 'example' => 'They **had** a good time.'],
                ['word' => 'Was', 'translation' => 'Eda (birlik)', 'ipa' => '/wɒz/', 'emoji' => '👤', 'definition' => 'To be fe\'lining birlikdagi o\'tgan zamon shakli.', 'example' => 'I **was** tired.'],
                ['word' => 'Were', 'translation' => 'Eda (ko\'plik)', 'ipa' => '/wɜː/', 'emoji' => '👥', 'definition' => 'To be fe\'lining ko\'plikdagi o\'tgan zamon shakli.', 'example' => 'They **were** happy.'],
                ['word' => 'Visited', 'translation' => 'Tashrif buyurdi', 'ipa' => '/ˈvɪzɪtɪd/', 'emoji' => '🏛️', 'definition' => 'Visit (tashrif buyurmoq) to\'g\'ri fe\'lining o\'tgan zamon shakli.', 'example' => 'We **visited** the museum.']
            ]
        ];
    }

    private function getPastContinuousVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'While', 'translation' => 'Davomida (Pabitda)', 'ipa' => '/waɪl/', 'emoji' => '⏳', 'definition' => 'Bir vaqtning o\'zida sodir bo\'layotgan ishlar uchun.', 'example' => 'I was reading **while** he was cooking.'],
                ['word' => 'When', 'translation' => 'Qachonki (Vaqtida)', 'ipa' => '/wɛn/', 'emoji' => '⌚', 'definition' => 'Biror voqea sodir bo\'lgan payt.', 'example' => 'I was sleeping **when** you called.'],
                ['word' => 'Reading', 'translation' => 'O\'qiyotgan', 'ipa' => '/ˈriːdɪŋ/', 'emoji' => '📖', 'definition' => 'Kitob yoki matn o\'qish jarayoni.', 'example' => 'She was **reading** all day.'],
                ['word' => 'Cooking', 'translation' => 'Pishirayotgan', 'ipa' => '/ˈkʊkɪŋ/', 'emoji' => '🍳', 'definition' => 'Ovqat tayyorlash jarayoni.', 'example' => 'Mom was **cooking** dinner.'],
                ['word' => 'Working', 'translation' => 'Ishlayotgan', 'ipa' => '/ˈwɜːkɪŋ/', 'emoji' => '💼', 'definition' => 'Mehnat qilish jarayoni.', 'example' => 'He was **working** at 5 PM.'],
                ['word' => 'Playing', 'translation' => 'O\'ynayotgan', 'ipa' => '/ˈpleɪɪŋ/', 'emoji' => '⚽', 'definition' => 'O\'yin yoki sport bilan shug\'ullanish.', 'example' => 'They were **playing** football.'],
                ['word' => 'Sleeping', 'translation' => 'Uxlayotgan', 'ipa' => '/ˈsliːpɪŋ/', 'emoji' => '😴', 'definition' => 'Uyqu jarayoni.', 'example' => 'The baby was **sleeping**.'],
                ['word' => 'Happening', 'translation' => 'Sodir bo\'layotgan', 'ipa' => '/ˈhæpənɪŋ/', 'emoji' => '⚡', 'definition' => 'Voqea yuz berish jarayoni.', 'example' => 'What was **happening** there?'],
                ['word' => 'Whole', 'translation' => 'Butun (hamma)', 'ipa' => '/həʊl/', 'emoji' => '🌕', 'definition' => 'To\'liq, bo\'linmagan.', 'example' => 'It rained the **whole** day.'],
                ['word' => 'Interrupted', 'translation' => 'Bo\'lingan', 'ipa' => '/ˌɪntəˈrʌptɪd/', 'emoji' => '🚧', 'definition' => 'Sodir bo\'layotgan ishning to\'xtatib qo\'yilishi.', 'example' => 'My sleep was **interrupted**.'],
                ['word' => 'Background', 'translation' => 'Orqa fon', 'ipa' => '/ˈbækɡraʊnd/', 'emoji' => '🖼️', 'definition' => 'Asosiy voqeadan oldin yoki atrofida bo\'lgan holat.', 'example' => 'The music was playing in the **background**.'],
                ['word' => 'During', 'translation' => 'Davomida', 'ipa' => '/ˈdjʊərɪŋ/', 'emoji' => '🔛', 'definition' => 'Ma\'lum bir vaqt oralig\'ida.', 'example' => 'He slept **during** the movie.']
            ]
        ];
    }

    private function getComparativesVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Than', 'translation' => '-dan ko\'ra', 'ipa' => '/ðæn/', 'emoji' => '⚖️', 'definition' => 'Solishtirish uchun ishlatiladigan bog\'lovchi.', 'example' => 'She is taller **than** me.'],
                ['word' => 'Better', 'translation' => 'Yaxshiroq', 'ipa' => '/ˈbɛtə/', 'emoji' => '👍', 'definition' => 'Good (yaxshi) so\'zining qiyosiy shakli.', 'example' => 'This book is **better**.'],
                ['word' => 'Worse', 'translation' => 'Yomonroq', 'ipa' => '/wɜːs/', 'emoji' => '👎', 'definition' => 'Bad (yomon) so\'zining qiyosiy shakli.', 'example' => 'The weather is **worse** today.'],
                ['word' => 'More', 'translation' => 'Ko\'proq', 'ipa' => '/mɔː/', 'emoji' => '➕', 'definition' => 'Uzun sifatlar oldidan qo\'yiladi.', 'example' => 'It is **more** expensive.'],
                ['word' => 'Less', 'translation' => 'Kamroq', 'ipa' => '/lɛs/', 'emoji' => '➖', 'definition' => 'More so\'zining teskarisi.', 'example' => 'It is **less** interesting.'],
                ['word' => 'Tall', 'translation' => 'Baland (bo\'y)', 'ipa' => '/tɔːl/', 'emoji' => '🦒', 'definition' => 'Bo\'yi uzun.', 'example' => 'He is a **tall** man.'],
                ['word' => 'Cheap', 'translation' => 'Arzon', 'ipa' => '/tʃiːp/', 'emoji' => '🏷️', 'definition' => 'Narxi past.', 'example' => 'This shirt is **cheap**.'],
                ['word' => 'Expensive', 'translation' => 'Qimmat', 'ipa' => '/ɪkˈspɛnsɪv/', 'emoji' => '💎', 'definition' => 'Narxi yuqori.', 'example' => 'A Ferrari is **expensive**.'],
                ['word' => 'Fast', 'translation' => 'Tez', 'ipa' => '/fɑːst/', 'emoji' => '🐆', 'definition' => 'Yuqori tezlikda.', 'example' => 'The car is very **fast**.'],
                ['word' => 'Slow', 'translation' => 'Sekin', 'ipa' => '/sləʊ/', 'emoji' => '🐢', 'definition' => 'Past tezlikda.', 'example' => 'Turtles are **slow**.'],
                ['word' => 'Heavy', 'translation' => 'Og\'ir', 'ipa' => '/ˈhɛvi/', 'emoji' => '🏋️', 'definition' => 'Vazni katta.', 'example' => 'This box is **heavy**.'],
                ['word' => 'Light', 'translation' => 'Yengil', 'ipa' => '/laɪt/', 'emoji' => '🪶', 'definition' => 'Vazni kichik.', 'example' => 'The feather is **light**.']
            ]
        ];
    }

    private function getFutureVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Tomorrow', 'translation' => 'Ertaga', 'ipa' => '/təˈmɒrəʊ/', 'emoji' => '🌅', 'definition' => 'Bugundan keyingi kun.', 'example' => 'See you **tomorrow**.'],
                ['word' => 'Next', 'translation' => 'Keyingi', 'ipa' => '/nɛkst/', 'emoji' => '⏭️', 'definition' => 'Navbatdagi vaqt.', 'example' => 'See you **next** week.'],
                ['word' => 'Soon', 'translation' => 'Tez orada', 'ipa' => '/suːn/', 'emoji' => '🔜', 'definition' => 'Yaqin vaqt ichida.', 'example' => 'I will be there **soon**.'],
                ['word' => 'Plan', 'translation' => 'Reja', 'ipa' => '/plæn/', 'emoji' => '📝', 'definition' => 'Oldindan o\'ylangan ish.', 'example' => 'What is your **plan**?'],
                ['word' => 'Prediction', 'translation' => 'Bashorat', 'ipa' => '/prɪˈdɪkʃən/', 'emoji' => '🔮', 'definition' => 'Kelajakda nima bo\'lishini aytish.', 'example' => 'It is a weather **prediction**.'],
                ['word' => 'Promise', 'translation' => 'Va\'da', 'ipa' => '/ˈprɒmɪs/', 'emoji' => '🤝', 'definition' => 'So\'z berish.', 'example' => 'I **promise** to help.'],
                ['word' => 'Hope', 'translation' => 'Umid qilmoq', 'ipa' => '/həʊp/', 'emoji' => '🤞', 'definition' => 'Yaxshi narsa kutish.', 'example' => 'I **hope** you come.'],
                ['word' => 'Think', 'translation' => 'O\'ylamoq (fikr)', 'ipa' => '/θɪŋk/', 'emoji' => '💭', 'definition' => 'Fikr bildirish.', 'example' => 'I **think** it will rain.'],
                ['word' => 'Probably', 'translation' => 'Ehtimol', 'ipa' => '/ˈprɒbəbli/', 'emoji' => '🤔', 'definition' => 'Bo\'lishi mumkin.', 'example' => 'I will **probably** go.'],
                ['word' => 'Maybe', 'translation' => 'Balki', 'ipa' => '/ˈmeɪbi/', 'emoji' => '🤷', 'definition' => 'Aniq emas.', 'example' => '**Maybe** he is late.'],
                ['word' => 'Intention', 'translation' => 'Niyat', 'ipa' => '/ɪnˈtɛnʃən/', 'emoji' => '🎯', 'definition' => 'Qilmoqchi bo\'lgan ish.', 'example' => 'It is my **intention** to win.'],
                ['word' => 'Flight', 'translation' => 'Parvoz (Reys)', 'ipa' => '/flaɪt/', 'emoji' => '✈️', 'definition' => 'Samolyot safari.', 'example' => 'My **flight** is at 9 PM.']
            ]
        ];
    }

    private function getPresentPerfectVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Just', 'translation' => 'Hozirgina', 'ipa' => '/dʒʌst/', 'emoji' => '⏱️', 'definition' => 'Juda qisqa vaqt oldin.', 'example' => 'I have **just** finished.'],
                ['word' => 'Already', 'translation' => 'Allaqachon', 'ipa' => '/ɔːlˈrɛdi/', 'emoji' => '✅', 'definition' => 'Kutilgandan oldin bajarilgan.', 'example' => 'I have **already** eaten.'],
                ['word' => 'Yet', 'translation' => 'Hali (inkorda)', 'ipa' => '/jɛt/', 'emoji' => '❌', 'definition' => 'Hozirgacha bajarilmagan.', 'example' => 'I haven\'t started **yet**.'],
                ['word' => 'Ever', 'translation' => 'Qachondir (hech)', 'ipa' => '/ˈɛvə/', 'emoji' => '🌏', 'definition' => 'Hayotingiz davomida.', 'example' => 'Have you **ever** been to Paris?'],
                ['word' => 'Never', 'translation' => 'Hech qachon', 'ipa' => '/ˈnɛvə/', 'emoji' => '🚫', 'definition' => 'Umuman sodir bo\'lmagan.', 'example' => 'I have **never** seen it.'],
                ['word' => 'Since', 'translation' => '-dan beri (vaqt)', 'ipa' => '/sɪns/', 'emoji' => '📅', 'definition' => 'Boshlanish vaqtini ko\'rsatadi.', 'example' => 'I have lived here **since** 2010.'],
                ['word' => 'For', 'translation' => 'Davomida (vaqt)', 'ipa' => '/fɔː/', 'emoji' => '⏳', 'definition' => 'Vaqt oralig\'ini ko\'rsatadi.', 'example' => 'I have waited **for** 2 hours.'],
                ['word' => 'Experience', 'translation' => 'Tajriba', 'ipa' => '/ɪkˈspɪərɪəns/', 'emoji' => '🧠', 'definition' => 'Hayotda ko\'rgan-kechirgan narsalar.', 'example' => 'It was a great **experience**.'],
                ['word' => 'Recently', 'translation' => 'Yaqinda', 'ipa' => '/ˈriːsntli/', 'emoji' => '🆕', 'definition' => 'Yaqin kunlarda.', 'example' => 'Have you seen him **recently**?'],
                ['word' => 'Done', 'translation' => 'Bajarilgan', 'ipa' => '/dʌn/', 'emoji' => '🏁', 'definition' => 'Do fe\'lining V3 shakli.', 'example' => 'I have **done** it.'],
                ['word' => 'Been', 'translation' => 'Bo\'lgan', 'ipa' => '/biːn/', 'emoji' => '📍', 'definition' => 'To be fe\'lining V3 shakli (borib-kelgan).', 'example' => 'I have **been** there.'],
                ['word' => 'Gone', 'translation' => 'Ketgan', 'ipa' => '/ɡɒn/', 'emoji' => '👋', 'definition' => 'Go fe\'lining V3 shakli (hali qaytmagan).', 'example' => 'She has **gone** home.']
            ]
        ];
    }

    private function getHealthVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Headache', 'translation' => 'Bosh og\'rig\'i', 'ipa' => '/ˈhɛdeɪk/', 'emoji' => '🤕', 'definition' => 'Boshdagi og\'riq.', 'example' => 'I have a terrible **headache**.'],
                ['word' => 'Stomach ache', 'translation' => 'Qorin og\'rig\'i', 'ipa' => '/ˈstʌmək eɪk/', 'emoji' => '🤢', 'definition' => 'Oshqozondagi og\'riq.', 'example' => 'He has a **stomach ache**.'],
                ['word' => 'Fever', 'translation' => 'Isitma', 'ipa' => '/ˈfiːvə/', 'emoji' => '🤒', 'definition' => 'Tana haroratining ko\'tarilishi.', 'example' => 'She has a high **fever**.'],
                ['word' => 'Cold', 'translation' => 'Shamollash', 'ipa' => '/kəʊld/', 'emoji' => '🤧', 'definition' => 'Yengil kasallik, tumov.', 'example' => 'I caught a **cold**.'],
                ['word' => 'Cough', 'translation' => 'Yo\'tal', 'ipa' => '/kɒf/', 'emoji' => '😮‍💨', 'definition' => 'Tom qirilishi va tovush.', 'example' => 'Take syrup for your **cough**.'],
                ['word' => 'Medicine', 'translation' => 'Dori', 'ipa' => '/ˈmɛdsɪn/', 'emoji' => '💊', 'definition' => 'Davolanish vositasi.', 'example' => 'Take this **medicine**.'],
                ['word' => 'Dentist', 'translation' => 'Tish shifokori', 'ipa' => '/ˈdɛntɪst/', 'emoji' => '🦷', 'definition' => 'Tishlarni davolovchi shifokor.', 'example' => 'Go to the **dentist**.'],
                ['word' => 'Rest', 'translation' => 'Dam olish', 'ipa' => '/rɛst/', 'emoji' => '🛌', 'definition' => 'Hordiq chiqarish.', 'example' => 'You need to **rest**.'],
                ['word' => 'Should', 'translation' => 'Kerak (maslahat)', 'ipa' => '/ʃʊd/', 'emoji' => '☝️', 'definition' => 'Maslahat berish uchun modal so\'z.', 'example' => 'You **should** sleep.'],
                ['word' => 'Must', 'translation' => 'Shart', 'ipa' => '/mʌst/', 'emoji' => '👮', 'definition' => 'Majburiyat uchun modal so\'z.', 'example' => 'You **must** stop.'],
                ['word' => 'Pill', 'translation' => 'Tabletka', 'ipa' => '/pɪl/', 'emoji' => '💊', 'definition' => 'Kichik dori.', 'example' => 'Take one **pill** a day.'],
                ['word' => 'Healthy', 'translation' => 'Sog\'lom', 'ipa' => '/ˈhɛlθi/', 'emoji' => '🥗', 'definition' => 'Sog\'lig\'i yaxshi.', 'example' => 'Eat **healthy** food.']
            ]
        ];
    }

    private function getFoodVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Meal', 'translation' => 'Ovqatlanish', 'ipa' => '/miːl/', 'emoji' => '🍽️', 'definition' => 'Nonushta, tushlik yoki kechki ovqat.', 'example' => 'Enjoy your **meal**.'],
                ['word' => 'Breakfast', 'translation' => 'Nonushta', 'ipa' => '/ˈbrɛkfəst/', 'emoji' => '🍳', 'definition' => 'Ertalabki ovqat.', 'example' => 'I eat eggs for **breakfast**.'],
                ['word' => 'Lunch', 'translation' => 'Tushlik', 'ipa' => '/lʌntʃ/', 'emoji' => '🥪', 'definition' => 'Kunduzgi ovqat.', 'example' => 'We have **lunch** at 1 PM.'],
                ['word' => 'Dinner', 'translation' => 'Kechki ovqat', 'ipa' => '/ˈdɪnə/', 'emoji' => '🍲', 'definition' => 'Kechqurungi ovqat.', 'example' => 'Make **dinner** for us.'],
                ['word' => 'Vegetable', 'translation' => 'Sabzavot', 'ipa' => '/ˈvɛdʒtəbəl/', 'emoji' => '🥕', 'definition' => 'O\'simlik mahsuloti.', 'example' => 'Eat your **vegetables**.'],
                ['word' => 'Fruit', 'translation' => 'Meva', 'ipa' => '/fruːt/', 'emoji' => '🍎', 'definition' => 'Shirin o\'simlik mahsuloti.', 'example' => 'Fresh **fruit** is good.'],
                ['word' => 'Delicious', 'translation' => 'Mazzali', 'ipa' => '/dɪˈlɪʃəs/', 'emoji' => '😋', 'definition' => 'Ta\'mi juda yaxshi.', 'example' => 'This cake is **delicious**.'],
                ['word' => 'Thirsty', 'translation' => 'Chanqagan', 'ipa' => '/ˈθɜːsti/', 'emoji' => '🥤', 'definition' => 'Suv ichgisi kelgan.', 'example' => 'I am very **thirsty**.'],
                ['word' => 'Hungry', 'translation' => 'Och qolgan', 'ipa' => '/ˈhʌŋɡri/', 'emoji' => '😫', 'definition' => 'Ovqat yegisi kelgan.', 'example' => 'Are you **hungry**?'],
                ['word' => 'Bottle', 'translation' => 'Shisha (idish)', 'ipa' => '/ˈbɒtl/', 'emoji' => '🍾', 'definition' => 'Suyuqlik idishi.', 'example' => 'A **bottle** of water.'],
                ['word' => 'Cup', 'translation' => 'Fincan', 'ipa' => '/kʌp/', 'emoji' => '☕', 'definition' => 'Choy yoki kofe idishi.', 'example' => 'A **cup** of tea.'],
                ['word' => 'Salt', 'translation' => 'Tuz', 'ipa' => '/sɔːlt/', 'emoji' => '🧂', 'definition' => 'Ovqat ta\'mini kuchaytiruvchi.', 'example' => 'Pass the **salt**, please.']
            ]
        ];
    }

    private function getCityVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Bridge', 'translation' => 'Ko\'prik', 'ipa' => '/brɪdʒ/', 'emoji' => '🌉', 'definition' => 'Daryo yoki yo\'l ustidan o\'tish joyi.', 'example' => 'Cross the **bridge**.'],
                ['word' => 'Roundabout', 'translation' => 'Aylanma yo\'l', 'ipa' => '/ˈraʊndəbaʊt/', 'emoji' => '🔄', 'definition' => 'Yo\'llar kesishmasidagi aylana harakat.', 'example' => 'Turn left at the **roundabout**.'],
                ['word' => 'Traffic light', 'translation' => 'Svetofor', 'ipa' => '/ˈtræfɪk laɪt/', 'emoji' => '🚦', 'definition' => 'Yo\'l harakatini boshqaruvchi chiroq.', 'example' => 'Stop at the **traffic light**.'],
                ['word' => 'Corner', 'translation' => 'Burchak', 'ipa' => '/ˈkɔːnə/', 'emoji' => '↙️', 'definition' => 'Ikki ko\'cha kesishgan joy.', 'example' => 'The shop is on the **corner**.'],
                ['word' => 'Crossing', 'translation' => 'Piyodalar o\'tish joyi', 'ipa' => '/ˈkrɒsɪŋ/', 'emoji' => '🦓', 'definition' => 'Yo\'ldan o\'tish chizig\'i.', 'example' => 'Use the zebra **crossing**.'],
                ['word' => 'Straight', 'translation' => 'To\'g\'ri', 'ipa' => '/streɪt/', 'emoji' => '⬆️', 'definition' => 'Burilmasdan.', 'example' => 'Go **straight** on.'],
                ['word' => 'Turn', 'translation' => 'Burilmoq', 'ipa' => '/tɜːn/', 'emoji' => '↩️', 'definition' => 'Yo\'nalishni o\'zgartirish.', 'example' => '**Turn** right here.'],
                ['word' => 'Map', 'translation' => 'Xarita', 'ipa' => '/mæp/', 'emoji' => '🗺️', 'definition' => 'Joylashuv chizmasi.', 'example' => 'Look at the **map**.'],
                ['word' => 'Lost', 'translation' => 'Adashgan', 'ipa' => '/lɒst/', 'emoji' => '❓', 'definition' => 'Yo\'lni bilmay qolish.', 'example' => 'I am **lost**.'],
                ['word' => 'Far', 'translation' => 'Uzoq', 'ipa' => '/fɑː/', 'emoji' => '🔭', 'definition' => 'Katta masofada.', 'example' => 'Is it **far**?'],
                ['word' => 'Near', 'translation' => 'Yaqin', 'ipa' => '/nɪə/', 'emoji' => '📍', 'definition' => 'Qisqa masofada.', 'example' => 'It is **near** the bank.'],
                ['word' => 'Building', 'translation' => 'Bino', 'ipa' => '/ˈbɪldɪŋ/', 'emoji' => '🏢', 'definition' => 'Imorat (uy, ofis).', 'example' => 'That is a tall **building**.']
            ]
        ];
    }

    private function getClothesVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Wear', 'translation' => 'Kiyib yurmoq', 'ipa' => '/weə/', 'emoji' => '👕', 'definition' => 'Kiyimni eginda tutish.', 'example' => 'I **wear** jeans every day.'],
                ['word' => 'Try on', 'translation' => 'Kiyib ko\'rmoq', 'ipa' => '/traɪ ɒn/', 'emoji' => '👗', 'definition' => 'O\'lchami to\'g\'ri kelishini tekshirish.', 'example' => 'Can I **try on** this shirt?'],
                ['word' => 'Fit', 'translation' => 'Mos kelmoq (o\'lcham)', 'ipa' => '/fɪt/', 'emoji' => '👌', 'definition' => 'O\'lchami to\'g\'ri bo\'lmoq.', 'example' => 'It doesn\'t **fit** me.'],
                ['word' => 'Suit', 'translation' => 'Yarashmoq', 'ipa' => '/suːt/', 'emoji' => '✨', 'definition' => 'Ko\'rinishi chiroyli bo\'lmoq.', 'example' => 'Blue **suits** you.'],
                ['word' => 'Size', 'translation' => 'O\'lcham', 'ipa' => '/saɪz/', 'emoji' => '📏', 'definition' => 'Kiyim kattaligi (S, M, L).', 'example' => 'What is your **size**?'],
                ['word' => 'Medium', 'translation' => 'O\'rta', 'ipa' => '/ˈmiːdiəm/', 'emoji' => 'M', 'definition' => 'O\'rta o\'lcham.', 'example' => 'I need a **medium**.'],
                ['word' => 'Large', 'translation' => 'Katta', 'ipa' => '/lɑːdʒ/', 'emoji' => 'L', 'definition' => 'Katta o\'lcham.', 'example' => 'Do you have a **large**?'],
                ['word' => 'Coat', 'translation' => 'Palto', 'ipa' => '/kəʊt/', 'emoji' => '🧥', 'definition' => 'Qishki ustki kiyim.', 'example' => 'Put on your **coat**.'],
                ['word' => 'Shoes', 'translation' => 'Oyoq kiyim', 'ipa' => '/ʃuːz/', 'emoji' => 'a👞', 'definition' => 'Oyoqqa kiyiladigan narsa.', 'example' => 'New leather **shoes**.'],
                ['word' => 'Dress', 'translation' => 'Ko\'ylak (ayollar)', 'ipa' => '/drɛs/', 'emoji' => '👗', 'definition' => 'Ayollar kiyimi.', 'example' => 'A beautiful red **dress**.'],
                ['word' => 'Sales', 'translation' => 'Chegirmalar', 'ipa' => '/seɪlz/', 'emoji' => '🏷️%', 'definition' => 'Arzonlashtirilgan narxlar.', 'example' => 'Buy it at the **sales**.'],
                ['word' => 'Cash', 'translation' => 'Naqd pul', 'ipa' => '/kæʃ/', 'emoji' => '💵', 'definition' => 'Qog\'oz pul.', 'example' => 'Pay by **cash** or card?']
            ]
        ];
    }

    private function getNatureVocabulary(): array
    {
        return [
            'level' => 'A2',
            'type' => 'vocabulary',
            'words' => [
                ['word' => 'Weather', 'translation' => 'Ob-havo', 'ipa' => '/ˈwɛðə/', 'emoji' => '⛅', 'definition' => 'Tabiat holati.', 'example' => 'The **weather** is nice.'],
                ['word' => 'Rain', 'translation' => 'Yomg\'ir', 'ipa' => '/reɪn/', 'emoji' => '🌧️', 'definition' => 'Osmondan tushadigan suv.', 'example' => 'I like the **rain**.'],
                ['word' => 'Snow', 'translation' => 'Qor', 'ipa' => '/snəʊ/', 'emoji' => '❄️', 'definition' => 'Muzlagan suv parchalari.', 'example' => 'Look at the **snow**.'],
                ['word' => 'Wind', 'translation' => 'Shamol', 'ipa' => '/wɪnd/', 'emoji' => '💨', 'definition' => 'Havo harakati.', 'example' => 'The **wind** is strong.'],
                ['word' => 'Sun', 'translation' => 'Quyosh', 'ipa' => '/sʌn/', 'emoji' => '☀️', 'definition' => 'Yorug\'lik manbai.', 'example' => 'The **sun** is shining.'],
                ['word' => 'Forest', 'translation' => 'O\'rmon', 'ipa' => '/ˈfɒrɪst/', 'emoji' => '🌲', 'definition' => 'Ko\'p daraxtli joy.', 'example' => 'Walk in the **forest**.'],
                ['word' => 'Mountain', 'translation' => 'Tog\'', 'ipa' => '/ˈmaʊntɪn/', 'emoji' => '🏔️', 'definition' => 'Baland yer.', 'example' => 'Climb a **mountain**.'],
                ['word' => 'River', 'translation' => 'Daryo', 'ipa' => '/ˈrɪvə/', 'emoji' => '🏞️', 'definition' => 'Oqadigan katta suv.', 'example' => 'Swim in the **river**.'],
                ['word' => 'Beach', 'translation' => 'Sohil', 'ipa' => '/biːtʃ/', 'emoji' => '🏖️', 'definition' => 'Dengiz bo\'yi.', 'example' => 'Go to the **beach**.'],
                ['word' => 'Flower', 'translation' => 'Gul', 'ipa' => '/ˈflaʊə/', 'emoji' => '🌸', 'definition' => 'Chiroyli o\'simlik.', 'example' => 'A beautiful **flower**.'],
                ['word' => 'Tree', 'translation' => 'Daraxt', 'ipa' => '/triː/', 'emoji' => '🌳', 'definition' => 'Katta o\'simlik.', 'example' => 'Sit under the **tree**.'],
                ['word' => 'World', 'translation' => 'Dunyo', 'ipa' => '/wɜːld/', 'emoji' => '🌍', 'definition' => 'Yer yuzi.', 'example' => 'Travel the **world**.'],
            ]
        ];
    }


    private function getPastSimpleTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ to school yesterday.', 'options' => ['go', 'went', 'gone'], 'correctAnswer' => 1, 'explanation' => 'Go -> Went keywords: yesterday'],
                ['type' => 'multiple_choice', 'question' => 'They ___ play football last week.', 'options' => ['didn\'t', 'don\'t', 'doesn\'t'], 'correctAnswer' => 0, 'explanation' => 'Past Simple inkor shakli: did not (didn\'t)'],
                ['type' => 'multiple_choice', 'question' => '___ you watch the movie?', 'options' => ['Do', 'Did', 'Were'], 'correctAnswer' => 1, 'explanation' => 'Savol berishda Did ishlatiladi.'],
                ['type' => 'true_false', 'question' => 'The past of "Buy" is "Buyed".', 'correctAnswer' => false, 'explanation' => 'Xato. Buy -> Bought (Irregular).'],
                ['type' => 'multiple_choice', 'question' => 'She ___ English in 2010.', 'options' => ['studies', 'studied', 'study'], 'correctAnswer' => 1, 'explanation' => '2010 yil - o\'tgan zamon.'],
                ['type' => 'multiple_choice', 'question' => 'We ___ happy to see you.', 'options' => ['was', 'were', 'did'], 'correctAnswer' => 1, 'explanation' => 'We were happy.'],
                ['type' => 'multiple_choice', 'question' => 'He ___ come to the party.', 'options' => ['didn\'t', 'wasn\'t', 'weren\'t'], 'correctAnswer' => 0, 'explanation' => 'He didn\'t come (Fe\'l borligi uchun did).'],
                ['type' => 'true_false', 'question' => 'Did she went to school?', 'correctAnswer' => false, 'explanation' => 'Xato. Did she GO to school? (Did bor joyda fe\'l 1-shaklga qaytadi).'],
                ['type' => 'multiple_choice', 'question' => 'When ___ you born?', 'options' => ['did', 'were', 'was'], 'correctAnswer' => 1, 'explanation' => 'When were you born?'],
                ['type' => 'multiple_choice', 'question' => 'I ___ a new car last month.', 'options' => ['buy', 'bought', 'buying'], 'correctAnswer' => 1, 'explanation' => 'Last month -> Bought']
            ]
        ];
    }

    private function getPastContinuousTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ reading a book at 5 PM.', 'options' => ['was', 'were', 'am'], 'correctAnswer' => 0, 'explanation' => 'I was.'],
                ['type' => 'multiple_choice', 'question' => 'They ___ playing tennis when it rained.', 'options' => ['was', 'were', 'are'], 'correctAnswer' => 1, 'explanation' => 'They were.'],
                ['type' => 'multiple_choice', 'question' => 'What ___ you doing?', 'options' => ['was', 'were', 'did'], 'correctAnswer' => 1, 'explanation' => 'What were you doing?'],
                ['type' => 'true_false', 'question' => 'She was cook dinner.', 'correctAnswer' => false, 'explanation' => 'Xato. She was cookING dinner.'],
                ['type' => 'multiple_choice', 'question' => 'He wasn\'t ___ attention.', 'options' => ['pay', 'paying', 'paid'], 'correctAnswer' => 1, 'explanation' => 'Was/Were + Verb-ing.'],
                ['type' => 'multiple_choice', 'question' => '___ it raining?', 'options' => ['Was', 'Were', 'Did'], 'correctAnswer' => 0, 'explanation' => 'Was it raining?'],
                ['type' => 'multiple_choice', 'question' => 'We ___ sleeping when you called.', 'options' => ['was', 'were', 'are'], 'correctAnswer' => 1, 'explanation' => 'We were sleeping.'],
                ['type' => 'multiple_choice', 'question' => 'The kids ___ watching TV.', 'options' => ['was', 'were', 'is'], 'correctAnswer' => 1, 'explanation' => 'Kids (plural) -> were.'],
                ['type' => 'true_false', 'question' => 'I were running.', 'correctAnswer' => false, 'explanation' => 'Xato. I WAS running.'],
                ['type' => 'multiple_choice', 'question' => 'Why was she ___?', 'options' => ['cry', 'crying', 'cries'], 'correctAnswer' => 1, 'explanation' => 'Why was she crying?']
            ]
        ];
    }

    private function getComparativesTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'This bag is ___ than that one.', 'options' => ['big', 'biger', 'bigger'], 'correctAnswer' => 2, 'explanation' => 'Big -> Bigger (2 g).'],
                ['type' => 'multiple_choice', 'question' => 'She is ___ than me.', 'options' => ['tall', 'taller', 'more tall'], 'correctAnswer' => 1, 'explanation' => 'Tall -> Taller.'],
                ['type' => 'multiple_choice', 'question' => 'Ferraris are ___ than Fords.', 'options' => ['expensive', 'more expensive', 'expensiver'], 'correctAnswer' => 1, 'explanation' => 'More expensive (long adjective).'],
                ['type' => 'multiple_choice', 'question' => 'This is the ___ movie ever.', 'options' => ['good', 'better', 'best'], 'correctAnswer' => 2, 'explanation' => 'The best (Superlative).'],
                ['type' => 'true_false', 'question' => 'More happyer.', 'correctAnswer' => false, 'explanation' => 'Xato. Happier.'],
                ['type' => 'multiple_choice', 'question' => 'Summer is ___ than winter.', 'options' => ['hot', 'hotter', 'more hot'], 'correctAnswer' => 1, 'explanation' => 'Hot -> Hotter.'],
                ['type' => 'multiple_choice', 'question' => 'English is ___ than Chinese.', 'options' => ['easy', 'easier', 'more easy'], 'correctAnswer' => 1, 'explanation' => 'Easy -> Easier.'],
                ['type' => 'multiple_choice', 'question' => 'He is the ___ student in class.', 'options' => ['smart', 'smarter', 'smartest'], 'correctAnswer' => 2, 'explanation' => 'The smartest (Superlative).'],
                ['type' => 'true_false', 'question' => 'Bad -> Badder.', 'correctAnswer' => false, 'explanation' => 'Xato. Bad -> Worse.'],
                ['type' => 'multiple_choice', 'question' => 'This test was ___ than the last one.', 'options' => ['difficult', 'more difficult', 'difficulter'], 'correctAnswer' => 1, 'explanation' => 'More difficult.']
            ]
        ];
    }

    private function getFutureFormsTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'Look at the clouds! It ___.', 'options' => ['will rain', 'is going to rain', 'rains'], 'correctAnswer' => 1, 'explanation' => 'Evidence (bulutlar) -> Going to.'],
                ['type' => 'multiple_choice', 'question' => 'I think she ___ pass the exam.', 'options' => ['will', 'is going to', 'does'], 'correctAnswer' => 0, 'explanation' => 'Think/Believe (fikr) -> Will.'],
                ['type' => 'multiple_choice', 'question' => 'We ___ fly to Paris next week (Planned).', 'options' => ['will', 'are going to', 'go'], 'correctAnswer' => 1, 'explanation' => 'Plan -> Going to.'],
                ['type' => 'multiple_choice', 'question' => 'The phone is ringing. I ___ answer it.', 'options' => ['will', 'am going to', 'am answering'], 'correctAnswer' => 0, 'explanation' => 'Spontaneous decision -> Will.'],
                ['type' => 'true_false', 'question' => 'I going to sleep.', 'correctAnswer' => false, 'explanation' => 'Xato. I AM going to sleep.'],
                ['type' => 'multiple_choice', 'question' => 'In 2050, people ___ live on Mars.', 'options' => ['will', 'are going to', 'live'], 'correctAnswer' => 0, 'explanation' => 'Prediction -> Will.'],
                ['type' => 'multiple_choice', 'question' => 'Are you ___ watch TV?', 'options' => ['go to', 'going to', 'will'], 'correctAnswer' => 1, 'explanation' => 'Going to.'],
                ['type' => 'multiple_choice', 'question' => 'I promise I ___ help you.', 'options' => ['will', 'am going to', 'help'], 'correctAnswer' => 0, 'explanation' => 'Promise -> Will.'],
                ['type' => 'multiple_choice', 'question' => 'She ___ have a baby.', 'options' => ['will', 'is going to', 'shall'], 'correctAnswer' => 1, 'explanation' => 'Evidence (Homilador) -> Going to.'],
                ['type' => 'multiple_choice', 'question' => 'I ___ have the steak, please.', 'options' => ['will', 'am going to', 'want'], 'correctAnswer' => 0, 'explanation' => 'Order -> Will.']
            ]
        ];
    }

    private function getPresentPerfectTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I ___ finished my work.', 'options' => ['has', 'have', 'did'], 'correctAnswer' => 1, 'explanation' => 'I have finished.'],
                ['type' => 'multiple_choice', 'question' => 'She ___ been to London.', 'options' => ['have', 'has', 'was'], 'correctAnswer' => 1, 'explanation' => 'She has been.'],
                ['type' => 'multiple_choice', 'question' => 'Have you ever ___ sushi?', 'options' => ['eat', 'ate', 'eaten'], 'correctAnswer' => 2, 'explanation' => 'Have + V3 (Eaten).'],
                ['type' => 'true_false', 'question' => 'I have see that movie.', 'correctAnswer' => false, 'explanation' => 'Xato. I have SEEN that movie.'],
                ['type' => 'multiple_choice', 'question' => 'We ___ just arrived.', 'options' => ['have', 'has', 'did'], 'correctAnswer' => 0, 'explanation' => 'We have just arrived.'],
                ['type' => 'multiple_choice', 'question' => 'He ___ lost his keys.', 'options' => ['have', 'has', 'is'], 'correctAnswer' => 1, 'explanation' => 'He has lost.'],
                ['type' => 'multiple_choice', 'question' => '___ you done your homework?', 'options' => ['Has', 'Have', 'Did'], 'correctAnswer' => 1, 'explanation' => 'Have you done?'],
                ['type' => 'multiple_choice', 'question' => 'I haven\'t seen him ___ 2 years.', 'options' => ['since', 'for', 'in'], 'correctAnswer' => 1, 'explanation' => 'For 2 years (duration).'],
                ['type' => 'multiple_choice', 'question' => 'She has lived here ___ 2010.', 'options' => ['since', 'for', 'ago'], 'correctAnswer' => 0, 'explanation' => 'Since 2010 (point in time).'],
                ['type' => 'true_false', 'question' => 'They has gone.', 'correctAnswer' => false, 'explanation' => 'Xato. They HAVE gone.']
            ]
        ];
    }

    private function getHealthTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'You have a toothache. You ___ go to the dentist.', 'options' => ['should', 'must', 'can'], 'correctAnswer' => 0, 'explanation' => 'Maslahat (Advice) -> Should.'],
                ['type' => 'multiple_choice', 'question' => 'Drivers ___ stop at red lights.', 'options' => ['should', 'must', 'could'], 'correctAnswer' => 1, 'explanation' => 'Qoida (Rule) -> Must.'],
                ['type' => 'multiple_choice', 'question' => 'You ___ smoke in the hospital.', 'options' => ['shouldn\'t', 'mustn\'t', 'don\'t'], 'correctAnswer' => 1, 'explanation' => 'Taqiq (Prohibition) -> Mustn\'t.'],
                ['type' => 'multiple_choice', 'question' => 'It\'s cold. You ___ wear a jacket.', 'options' => ['should', 'must', 'have'], 'correctAnswer' => 0, 'explanation' => 'Maslahat -> Should.'],
                ['type' => 'true_false', 'question' => 'You should to sleep.', 'correctAnswer' => false, 'explanation' => 'Xato. You should sleep (TO bo\'lmaydi).'],
                ['type' => 'multiple_choice', 'question' => 'I ___ finish this work today. (Deadline)', 'options' => ['should', 'must', 'can'], 'correctAnswer' => 1, 'explanation' => 'Majburiyat -> Must.'],
                ['type' => 'multiple_choice', 'question' => 'She has a cold. She ___ drink water.', 'options' => ['should', 'mustn\'t', 'cant'], 'correctAnswer' => 0, 'explanation' => 'Maslahat -> Should.'],
                ['type' => 'multiple_choice', 'question' => 'You ___ speak loudly in the library.', 'options' => ['mustn\'t', 'should', 'can'], 'correctAnswer' => 0, 'explanation' => 'Taqiq -> Mustn\'t.'],
                ['type' => 'true_false', 'question' => 'He musts go.', 'correctAnswer' => false, 'explanation' => 'Xato. He must go (S qo\'shilmaydi).'],
                ['type' => 'multiple_choice', 'question' => '___ I call the doctor?', 'options' => ['Should', 'Must', 'Do'], 'correctAnswer' => 0, 'explanation' => 'Should I call? (Maslahat so\'rash).']
            ]
        ];
    }

    private function getFoodTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'I have ___ friends.', 'options' => ['much', 'many', 'a little'], 'correctAnswer' => 1, 'explanation' => 'Friends (Countable) -> Many.'],
                ['type' => 'multiple_choice', 'question' => 'How ___ water do you need?', 'options' => ['many', 'much', 'any'], 'correctAnswer' => 1, 'explanation' => 'Water (Uncountable) -> Much.'],
                ['type' => 'multiple_choice', 'question' => 'There are ___ apples.', 'options' => ['some', 'any', 'much'], 'correctAnswer' => 0, 'explanation' => 'Positive sentence -> Some.'],
                ['type' => 'multiple_choice', 'question' => 'Is there ___ milk?', 'options' => ['some', 'any', 'many'], 'correctAnswer' => 1, 'explanation' => 'Question -> Any.'],
                ['type' => 'true_false', 'question' => 'I don\'t have some money.', 'correctAnswer' => false, 'explanation' => 'Xato. Negative -> I don\'t have ANY money.'],
                ['type' => 'multiple_choice', 'question' => 'Can I have ___ water?', 'options' => ['some', 'any', 'many'], 'correctAnswer' => 0, 'explanation' => 'Request (Taklif/So\'rov) -> Some.'],
                ['type' => 'multiple_choice', 'question' => 'We need ___ information.', 'options' => ['many', 'some', 'an'], 'correctAnswer' => 1, 'explanation' => 'Information (Uncountable) -> Some.'],
                ['type' => 'multiple_choice', 'question' => 'How ___ brothers do you have?', 'options' => ['much', 'many', 'any'], 'correctAnswer' => 1, 'explanation' => 'Brothers -> Many.'],
                ['type' => 'true_false', 'question' => 'A bread.', 'correctAnswer' => false, 'explanation' => 'Xato. Bread sanalmaydi -> Some bread.'],
                ['type' => 'multiple_choice', 'question' => 'There isn\'t ___ sugar.', 'options' => ['some', 'much', 'many'], 'correctAnswer' => 1, 'explanation' => 'Sugar (Sanalmaydi, inkor) -> Much/Any.']
            ]
        ];
    }

    private function getCityTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'The cat walked ___ the room.', 'options' => ['into', 'at', 'on'], 'correctAnswer' => 0, 'explanation' => 'Ichkariga -> Into.'],
                ['type' => 'multiple_choice', 'question' => 'Walk ___ the street at the lights.', 'options' => ['across', 'through', 'in'], 'correctAnswer' => 0, 'explanation' => 'Kesib o\'tish -> Across.'],
                ['type' => 'multiple_choice', 'question' => 'The train went ___ the tunnel.', 'options' => ['across', 'through', 'on'], 'correctAnswer' => 1, 'explanation' => 'Ichidan o\'tish (tunnel) -> Through.'],
                ['type' => 'multiple_choice', 'question' => 'He jumped ___ the wall.', 'options' => ['over', 'under', 'in'], 'correctAnswer' => 0, 'explanation' => 'Ustidan sakrash -> Over.'],
                ['type' => 'multiple_choice', 'question' => 'Go ___ the stairs.', 'options' => ['up', 'in', 'at'], 'correctAnswer' => 0, 'explanation' => 'Tepaga -> Up.'],
                ['type' => 'multiple_choice', 'question' => 'Take the book ___ of the bag.', 'options' => ['out', 'off', 'from'], 'correctAnswer' => 0, 'explanation' => 'Ichidan tashqariga -> Out of.'],
                ['type' => 'multiple_choice', 'question' => 'Walk ___ the bank.', 'options' => ['past', 'through', 'into'], 'correctAnswer' => 0, 'explanation' => 'Yonidan o\'tish -> Past.'],
                ['type' => 'true_false', 'question' => 'Go to home.', 'correctAnswer' => false, 'explanation' => 'Xato. Go home ("to" shart emas).'],
                ['type' => 'multiple_choice', 'question' => 'The bird flew ___ the house.', 'options' => ['over', 'on', 'in'], 'correctAnswer' => 0, 'explanation' => 'Tepasidan -> Over.'],
                ['type' => 'multiple_choice', 'question' => 'Come ___ me.', 'options' => ['towards', 'at', 'on'], 'correctAnswer' => 0, 'explanation' => 'Tomonga -> Towards.']
            ]
        ];
    }

    private function getClothesTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'This shirt is ___ small.', 'options' => ['too', 'enough', 'many'], 'correctAnswer' => 0, 'explanation' => 'Juda kichik -> Too small.'],
                ['type' => 'multiple_choice', 'question' => 'I don\'t have ___ money.', 'options' => ['too', 'enough', 'many'], 'correctAnswer' => 1, 'explanation' => 'Ismdan oldin -> Enough money.'],
                ['type' => 'multiple_choice', 'question' => 'He is old ___ to drive.', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 1, 'explanation' => 'Sifatdan keyin -> Old enough.'],
                ['type' => 'true_false', 'question' => 'It is enough good.', 'correctAnswer' => false, 'explanation' => 'Xato. Good enough.'],
                ['type' => 'multiple_choice', 'question' => 'The soup is ___ hot to eat.', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 0, 'explanation' => 'Juda issiq (yeb bo\'lmaydi) -> Too.'],
                ['type' => 'multiple_choice', 'question' => 'Is the room big ___?', 'options' => ['too', 'enough', 'very'], 'correctAnswer' => 1, 'explanation' => 'Big enough.'],
                ['type' => 'multiple_choice', 'question' => 'I am ___ tired to walk.', 'options' => ['too', 'enough', 'so'], 'correctAnswer' => 0, 'explanation' => 'Too tired.'],
                ['type' => 'true_false', 'question' => 'She has money enough.', 'correctAnswer' => false, 'explanation' => 'Xato. Enough money.'],
                ['type' => 'multiple_choice', 'question' => 'He isn\'t tall ___ to reach.', 'options' => ['enough', 'too', 'very'], 'correctAnswer' => 0, 'explanation' => 'Tall enough.'],
                ['type' => 'multiple_choice', 'question' => 'These shoes are ___ expensive.', 'options' => ['too', 'enough', 'much'], 'correctAnswer' => 0, 'explanation' => 'Too expensive.']
            ]
        ];
    }

    private function getNatureTest(): array
    {
        return [
            'level' => 'A2',
            'type' => 'test',
            'quiz' => [
                ['type' => 'multiple_choice', 'question' => 'If it rains, I ___ stay home.', 'options' => ['will', 'am', 'do'], 'correctAnswer' => 0, 'explanation' => 'First Conditional: Will.'],
                ['type' => 'multiple_choice', 'question' => 'If she ___ late, we will leave.', 'options' => ['is', 'will be', 'be'], 'correctAnswer' => 0, 'explanation' => 'If qismida Present Simple.'],
                ['type' => 'multiple_choice', 'question' => 'I will buy a car if I ___ money.', 'options' => ['have', 'will have', 'had'], 'correctAnswer' => 0, 'explanation' => 'If qismida Present Simple (have).'],
                ['type' => 'true_false', 'question' => 'If I will go, I will see.', 'correctAnswer' => false, 'explanation' => 'Xato. If I GO.'],
                ['type' => 'multiple_choice', 'question' => 'What ___ you do if he comes?', 'options' => ['will', 'do', 'are'], 'correctAnswer' => 0, 'explanation' => 'What WILL you do?'],
                ['type' => 'multiple_choice', 'question' => 'If you study, you ___ pass.', 'options' => ['will', 'are', 'did'], 'correctAnswer' => 0, 'explanation' => 'Natija -> Will.'],
                ['type' => 'multiple_choice', 'question' => 'If we ___, we will be late.', 'options' => ['don\'t hurry', 'won\'t hurry', 'not hurry'], 'correctAnswer' => 0, 'explanation' => 'Present Simple Negative -> Don\'t hurry.'],
                ['type' => 'multiple_choice', 'question' => 'She will be happy if you ___.', 'options' => ['call', 'will call', 'called'], 'correctAnswer' => 0, 'explanation' => 'If you call.'],
                ['type' => 'true_false', 'question' => 'If it snows, we make a snowman.', 'correctAnswer' => false, 'explanation' => 'Aniq emas, Will make bo\'lishi kerak (Specific future).'],
                ['type' => 'multiple_choice', 'question' => 'Where ___ you go if it rains?', 'options' => ['will', 'do', 'did'], 'correctAnswer' => 0, 'explanation' => 'Where WILL you go?']
            ]
        ];
    }
}
